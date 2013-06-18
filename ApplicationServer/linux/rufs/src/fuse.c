/*
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2012
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

#define FUSE_USE_VERSION 26

#define _GNU_SOURCE

#include <fuse.h>
#include <ulockmgr.h>
#include <stdio.h>
#include <string.h>
#include <unistd.h>
#include <fcntl.h>
#include <dirent.h>
#include <errno.h>
#include <sys/time.h>
#include "status.h"
#include "common/log.h"
#include "configuration.h"
#include "common/regexp.h"
#include "common/sys.h"
#include "common/str.h"
#include "common/user.h"
#include "common/rsync.h"
#include "common/signal.h"
#include "shares.h"
#include <linux/limits.h>
#ifdef HAVE_SETXATTR
#include <sys/xattr.h>
#endif


Configuration* config;

enum {
     KEY_HELP,
     KEY_VERSION,
     KEY_DEBUG,
};

#define RUFS_OPT(t, p, v) { t, offsetof(Configuration, p), v }

static struct fuse_opt rufs_opts[] = {
     RUFS_OPT("user=%s", user, 0),
     RUFS_OPT("fsconfig=%s", configFile, 0),

     FUSE_OPT_KEY("-V",          KEY_VERSION),
     FUSE_OPT_KEY("--version",   KEY_VERSION),
     FUSE_OPT_KEY("-h",          KEY_HELP),
     FUSE_OPT_KEY("--help",      KEY_HELP),
     FUSE_OPT_KEY("-d",          KEY_DEBUG),
     FUSE_OPT_KEY("--debug",     KEY_DEBUG),
     FUSE_OPT_END
};


static bool authorized(const char* path) {
	bool res;

	if (path == NULL)
		return true;

	char* root = fs_getRoot(path);
	if (root == NULL || str_len(root) == 0)
		res = true;
	else
		res = shares_activated(root);

	memory_free(root);

	return res;
}


static bool quotaExceed(const char* path) {
	bool res;

	if (path == NULL)
		return false;

	char* root = fs_getRoot(path);
	if (root == NULL || str_len(root) == 0)
		res = false;
	else
		res = shares_quotaExceed(root);

	memory_free(root);

	return res;
}


static void updateShareSpace(const char* path, long size) {
	if (path == NULL)
		return;

	char* root = fs_getRoot(path);
	if (root != NULL && str_len(root) > 0)
		shares_updateSpace(root, size);

	memory_free(root);
}

// Transform path returned by ls
static void transformPathOut(const char* path, char* to) {

	List* transList = config->translations;
	Translation* trans;
	int i;

	for(i = 0 ; i < transList->size ; i++) {
		trans = (Translation*) list_get(transList, i);
		if (str_cmp(path, trans->in) == 0) {
			str_cpy(to, trans->out);
			return;
		}
	}

	str_cpy(to, path);
}


// transform path used to access the filesystem
static void transformPathIn(const char* path, char* to) {
	int i;
	char* p;
	List *pathComponent = (List*)str_split(path, '/');
	List* transList = config->translations;
	Translation* trans;
	to[0] = 0;

	if (pathComponent == NULL || pathComponent->size == 0) {
		str_cpy(to, path);
		return;
	}

	p = (char*)list_get(pathComponent, 0);

	for(i = 0 ; i < transList->size ; i++) {
		trans = (Translation*) list_get(transList, i);
		if (str_cmp(p, trans->out) == 0) {
			sprintf(to, "/%s", trans->in);
			break;
		}
	}

	if (str_len(to) == 0) {
		p = (char*)list_get(pathComponent, 0);
		str_cat(to, "/");
		str_cat(to, p);
	}

	for(i = 1 ; i < pathComponent->size ; i++) {
		p = (char*)list_get(pathComponent, i);
		str_cat(to, "/");
		str_cat(to, p);
	}

	// Cleaning
	list_delete(pathComponent);
}

static bool transformPath(const char* path, char* to, bool isSymlink) {
	int lastIndex = config->unions->size - 1;
	char trpath[PATH_MAX];
	Regexp* reg;
	Union* u;
	int i = 0;
	int a = 0;
	int r = 0;

	transformPathIn(path, trpath);

	// Firstly, check if it already exist somewhere
	for(i = 0 ; i < config->unions->size ; i++) {
		u = (Union*)list_get(config->unions, i);
		List* reject;

		if (!u) {
			continue;
		}

		// if the file exist in a union, we return it
		str_sprintf(to, "%s%s", u->path, trpath);

		// do not resolv symlink => this generate deadlock
		if (faccessat(0, to, F_OK, AT_EACCESS | AT_SYMLINK_NOFOLLOW) == 0) {
			logDebug("path '%s' already exist in repository %s", path, u->name);
			return true;
		}
	}

	// The file do not exist
	for(i = 0 ; i < config->rules->size ; i++) {
		Rule* rule = (Rule*)list_get(config->rules, i);
		List* accept;
		List* reject;

		if (!rule || !u || !reg) {
			continue;
		}

		u = rule->u;
		reg = rule->reg;

		if (isSymlink && !u->acceptSymlink)
			continue;

		// if the file exist in a union, we return it
		str_sprintf(to, "%s%s", u->path, trpath);

		reject = u->reject;

		if (regexp_match(reg, trpath)) {
			for(r = 0 ; r < reject->size ; r++) {
				reg = (Regexp*)list_get(reject, r);
				if (regexp_match(reg, trpath)) {
					continue;
				}
			}

			// We found a valid union
			logDebug("Union %s is valid for the path %s", u->name, trpath);

			str_sprintf(to, "%s%s", u->path, trpath);

			if (u->createParentDirectory)
				fs_mkdirs(to);

			return true;
		}
	}

	// If we found noting, we return the last union
	u = (Union*)list_get(config->unions, lastIndex);
	logDebug("Failed back %s\n", u->name);

	str_sprintf(to, "%s%s", u->path, trpath);

	if (u->createParentDirectory)
		fs_mkdirs(to);

	return true;
}

static int rufs_getattr(const char *path, struct stat *stbuf)
{
	int res;
	char trpath[PATH_MAX];
	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}

	res = lstat(trpath, stbuf);
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_fgetattr(const char *path, struct stat *stbuf,
			struct fuse_file_info *fi)
{
	int res;

	(void) path;

	res = fstat(fi->fh, stbuf);
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_access(const char *path, int mask)
{
	int res;
	char trpath[PATH_MAX];
	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}

	res = access(trpath, mask);
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_readlink(const char *path, char *buf, size_t size)
{
	int res;
	char trpath[PATH_MAX];

	if (! authorized(path))
		return -EPERM;

	if (!transformPath(path, trpath, true))
	{
		return -ENOENT;
	}

	res = readlink(trpath, buf, size - 1);
	if (res == -1)
		return -errno;

	buf[res] = '\0';
	return 0;
}

static int rufs_opendir(const char *path, struct fuse_file_info *fi)
{
	int res;
	char trpath[PATH_MAX];

	if (! authorized(path))
		return -EPERM;

	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}

	DIR *dp = opendir(trpath);
	if (dp == NULL)
		return -errno;

	fi->fh = (unsigned long) dp;
	return 0;
}

static inline DIR *get_dirp(struct fuse_file_info *fi)
{
	return (DIR *) (uintptr_t) fi->fh;
}

static int rufs_readdir(const char *path, void *buf, fuse_fill_dir_t filler,
		       off_t offset, struct fuse_file_info *fi)
{
	DIR *dp = get_dirp(fi);
	struct dirent *de;

	if (! authorized(path))
		return -EPERM;

	// We need to list all the file present in all union
	if (str_cmp(path, "/") == 0) {
		int i = 0;
		for (i = 0 ; i < config->unions->size ; i++) {
			Union* u = (Union*)list_get(config->unions, i);
			if (!u) {
				continue;
			}

			dp = opendir(u->path);
			while ((de = readdir(dp)) != NULL) {
				char trpath[PATH_MAX];
				struct stat st;
				memset(&st, 0, sizeof(st));
				st.st_ino = de->d_ino;
				st.st_mode = de->d_type << 12;
				transformPathOut(de->d_name, trpath);
				if (filler(buf, trpath, &st, 0))
					break;
			}
			closedir(dp);
		}
		return 0;
	}

	seekdir(dp, offset);
	while ((de = readdir(dp)) != NULL) {
		struct stat st;
		memset(&st, 0, sizeof(st));
		st.st_ino = de->d_ino;
		st.st_mode = de->d_type << 12;
		if (filler(buf, de->d_name, &st, telldir(dp)))
			break;
	}

	return 0;
}

static int rufs_releasedir(const char *path, struct fuse_file_info *fi)
{
	DIR *dp = get_dirp(fi);
	(void) path;
	closedir(dp);
	return 0;
}

static int rufs_mknod(const char *path, mode_t mode, dev_t rdev)
{
	int res;
	char trpath[PATH_MAX];

	if (! authorized(path))
		return -EPERM;

	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}

	if (S_ISFIFO(mode))
		res = mkfifo(trpath, mode);
	else
		res = mknod(trpath, mode, rdev);
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_mkdir(const char *path, mode_t mode)
{
	int res;
	char trpath[PATH_MAX];

	if (! authorized(path))
		return -EPERM;

	if (quotaExceed(path))
		return -EDQUOT;

	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}

	res = mkdir(trpath, mode);
	if (res == -1)
		return -errno;

	// TODO find better way
	updateShareSpace(path, 4096);

	return 0;
}

static int rufs_unlink(const char *path)
{
	int res;
	long fsize;
	char trpath[PATH_MAX];

	if (! authorized(path))
		return -EPERM;

	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}

	fsize = file_size(trpath);

	res = unlink(trpath);
	if (res == -1)
		return -errno;

	updateShareSpace(path, (-1 * fsize));

	return 0;
}

static int rufs_rmdir(const char *path)
{
	int res;
	char trpath[PATH_MAX];

	if (! authorized(path))
		return -EPERM;

	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}

	res = rmdir(trpath);
	if (res == -1)
		return -errno;

	// TODO find better way
	updateShareSpace(path, -4096);

	return 0;
}

static int rufs_symlink(const char *from, const char *to)
{
	int res;
	char trto[PATH_MAX];

	if (! authorized(to))
		return -EPERM;

	if (!transformPath(to, trto, true))
	{
		return -ENOENT;
	}

	fs_mkdirs(trto);
	res = symlink(from, trto);
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_rename(const char *from, const char *to)
{
	int res;
	char trto[PATH_MAX];
	char trfrom[PATH_MAX];

	if (! authorized(to))
		return -EPERM;

	if (!transformPath(to, trto, false))
	{
		return -ENOENT;
	}

	if (!transformPath(from, trfrom, false))
	{
		return -ENOENT;
	}

	res = rename(trfrom, trto);
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_link(const char *from, const char *to)
{
	int res;
	char trto[PATH_MAX];
	char trfrom[PATH_MAX];

	if (! authorized(to))
		return -EPERM;

	if (!transformPath(to, trto, true))
	{
		return -ENOENT;
	}

	if (!transformPath(from, trfrom, true))
	{
		return -ENOENT;
	}

	res = link(trfrom, trto);
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_chmod(const char *path, mode_t mode)
{
	int res;
	char trpath[PATH_MAX];

	if (! authorized(path))
		return -EPERM;

	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}

	res = chmod(trpath, mode);
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_chown(const char *path, uid_t uid, gid_t gid)
{
	int res;
	char trpath[PATH_MAX];

	if (! authorized(path))
		return -EPERM;

	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}

	res = lchown(trpath, uid, gid);
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_truncate(const char *path, off_t size)
{
	int res;
	int fsize;
	char trpath[PATH_MAX];

	if (quotaExceed(path))
		return -EDQUOT;

	if (! authorized(path))
		return -EPERM;

	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}

	fsize = file_size(trpath);

	res = truncate(trpath, size);
	if (res == -1)
		return -errno;

	updateShareSpace(path, (size - fsize));

	return 0;
}

static int rufs_ftruncate(const char *path, off_t size,
			 struct fuse_file_info *fi)
{
	int res;
	int fsize;
	char trpath[PATH_MAX];

	if (quotaExceed(path))
		return -EDQUOT;

	if (! authorized(path))
		return -EPERM;

	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}

	fsize = file_size(trpath);

	res = ftruncate(fi->fh, size);
	if (res == -1)
		return -errno;

	updateShareSpace(path, (size - fsize));

	return 0;
}

static int rufs_utimens(const char *path, const struct timespec ts[2])
{
	int res;
	char trpath[PATH_MAX];

	if (! authorized(path))
		return -EPERM;

	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}
	struct timeval tv[2];

	tv[0].tv_sec = ts[0].tv_sec;
	tv[0].tv_usec = ts[0].tv_nsec / 1000;
	tv[1].tv_sec = ts[1].tv_sec;
	tv[1].tv_usec = ts[1].tv_nsec / 1000;

	res = utimes(trpath, tv);
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_create(const char *path, mode_t mode, struct fuse_file_info *fi)
{
	int fd;
	char trpath[PATH_MAX];

	if (! authorized(path))
		return -EPERM;

	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}


	fd = open(trpath, fi->flags, mode);
	if (fd == -1)
		return -errno;

	fi->fh = fd;
	return 0;
}

static int rufs_open(const char *path, struct fuse_file_info *fi)
{
	int fd;
	char trpath[PATH_MAX];

	if (! authorized(path))
		return -EPERM;

	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}

	fd = open(trpath, fi->flags);
	if (fd == -1)
		return -errno;

	fi->fh = fd;
	return 0;
}

static int rufs_read(const char *path, char *buf, size_t size, off_t offset,
		    struct fuse_file_info *fi)
{
	int res;
	char trpath[PATH_MAX];

	if (! authorized(path))
		return -EPERM;

	res = pread(fi->fh, buf, size, offset);
	if (res == -1)
		res = -errno;

	return res;
}

static int rufs_write(const char *path, const char *buf, size_t size,
		     off_t offset, struct fuse_file_info *fi)
{
	int res;
	long fsize;
	char trpath[PATH_MAX];

	if (! authorized(path))
		return -EPERM;

	if (quotaExceed(path))
		return -EDQUOT;

	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}

	fsize = file_size(trpath);

	res = pwrite(fi->fh, buf, size, offset);
	if (res == -1)
		res = -errno;

	if (offset + size > fsize)
		updateShareSpace(path, (offset + size - fsize));

	return res;
}

static int rufs_statfs(const char *path, struct statvfs *stbuf)
{
	int res;
	char trpath[PATH_MAX];

	if (! authorized(path))
		return -EPERM;

	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}

	char* root = fs_getRoot(path);
	long long quota = shares_getQuota(root);
	long long spaceUsed = shares_getSpaceUsed(root);
	memory_free(root);

	res = statvfs(trpath, stbuf);

	if (quota != -1) {
		long block = quota/stbuf->f_frsize;
		long blockUsed = spaceUsed/stbuf->f_frsize;
		long blockAvailable = block - blockUsed;

		if (blockAvailable < 0)
			blockAvailable = 0;

		stbuf->f_blocks = block;
		stbuf->f_bfree = blockAvailable;
		stbuf->f_bavail = blockAvailable;
	}

	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_flush(const char *path, struct fuse_file_info *fi)
{
	int res;

	if (! authorized(path))
		return -EPERM;

	/* This is called from every close on an open file, so call the
	   close on the underlying filesystem.	But since flush may be
	   called multiple times for an open file, this must not really
	   close the file.  This is important if used on a network
	   filesystem like NFS which flush the data/metadata on close() */
	res = close(dup(fi->fh));
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_release(const char *path, struct fuse_file_info *fi)
{
	if (! authorized(path))
		return -EPERM;

	close(fi->fh);

	return 0;
}


static void* rufs_init()
{
	char pidData[255];

	logInfo("rufs inited");

	if (config->pidFile != NULL) {
		int fd = file_open(config->pidFile);
		str_sprintf(pidData, "%i", sys_getPID());

		file_write(fd, pidData, strlen(pidData));
	}

	return NULL;
}


static int rufs_fsync(const char *path, int isdatasync,
		     struct fuse_file_info *fi)
{
	int res;

	if (! authorized(path))
		return -EPERM;

#ifndef HAVE_FDATASYNC
	(void) isdatasync;
#else
	if (isdatasync)
		res = fdatasync(fi->fh);
	else
#endif
		res = fsync(fi->fh);
	if (res == -1)
		return -errno;

	return 0;
}

#ifdef HAVE_SETXATTR
/* xattr operations are optional and can safely be left unimplemented */
static int rufs_setxattr(const char *path, const char *name, const char *value,
			size_t size, int flags)
{
	char trpath[PATH_MAX];

	if (! authorized(path))
		return -EPERM;

	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}

	int res = lsetxattr(trpath, name, value, size, flags);
	if (res == -1)
		return -errno;
	return 0;
}

static int rufs_getxattr(const char *path, const char *name, char *value,
			size_t size)
{
	int res;
	char trpath[PATH_MAX];

	if (! authorized(path))
		return -EPERM;

	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}

	res = lgetxattr(trpath, name, value, size);
	if (res == -1)
		return -errno;
	return res;
}

static int rufs_listxattr(const char *path, char *list, size_t size)
{
	int res;
	char trpath[PATH_MAX];

	if (! authorized(path))
		return -EPERM;

	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}

	res = llistxattr(trpath, list, size);
	if (res == -1)
		return -errno;
	return res;
}

static int rufs_removexattr(const char *path, const char *name)
{
	int res;
	char trpath[PATH_MAX];

	if (! authorized(path))
		return -EPERM;

	if (!transformPath(path, trpath, false))
	{
		return -ENOENT;
	}

	res = lremovexattr(trpath, name);
	if (res == -1)
		return -errno;
	return 0;
}
#endif /* HAVE_SETXATTR */

static int rufs_lock(const char *path, struct fuse_file_info *fi, int cmd,
		    struct flock *lock)
{
	if (! authorized(path))
		return -EPERM;

	return ulockmgr_op(fi->fh, cmd, lock, &fi->lock_owner,
			   sizeof(fi->lock_owner));
}


static struct fuse_operations rufs_oper = {
	.getattr	= rufs_getattr,
	.fgetattr	= rufs_fgetattr,
	.access		= rufs_access,
	.readlink	= rufs_readlink,
	.opendir	= rufs_opendir,
	.readdir	= rufs_readdir,
	.releasedir	= rufs_releasedir,
	.mknod		= rufs_mknod,
	.mkdir		= rufs_mkdir,
	.symlink	= rufs_symlink,
	.unlink		= rufs_unlink,
	.rmdir		= rufs_rmdir,
	.rename		= rufs_rename,
	.link		= rufs_link,
	.chmod		= rufs_chmod,
	.chown		= rufs_chown,
	.truncate	= rufs_truncate,
	.ftruncate	= rufs_ftruncate,
	.utimens	= rufs_utimens,
	.create		= rufs_create,
	.open		= rufs_open,
	.read		= rufs_read,
	.write		= rufs_write,
	.statfs		= rufs_statfs,
	.flush		= rufs_flush,
	.release	= rufs_release,
	.init		= rufs_init,
	.fsync		= rufs_fsync,
#ifdef HAVE_SETXATTR
	.setxattr	= rufs_setxattr,
	.getxattr	= rufs_getxattr,
	.listxattr	= rufs_listxattr,
	.removexattr	= rufs_removexattr,
#endif
	.lock		= rufs_lock,
};


static int rufs_opt_proc(void *data, const char *arg, int key, struct fuse_args *outargs)
{
	Configuration* conf = data;

	switch (key) {
	case FUSE_OPT_KEY_NONOPT:
		if (conf->source_path == 0) {
			conf->source_path = str_dup(arg);
			return 0;
		}
		return 1;

	case KEY_HELP:
		fprintf(stderr,
				"usage: %s source mountpoint [options]\n"
				"\n"
				"general options:\n"
				"    -o opt,[opt...]  mount options\n"
				"    -h   --help      print help\n"
				"    -V   --version   print version\n"
				"\n"
				"rufs options:\n"
				"    -o user=STRING\n"
				"    -o config=STRING\n"
				, outargs->argv[0]);
		fuse_opt_add_arg(outargs, "-ho");
		fuse_main(outargs->argc, outargs->argv, &rufs_oper, NULL);
		sys_exit(1);
		break;

	case KEY_VERSION:
		fprintf(stderr, "rufs version %s\n", PACKAGE_VERSION);
		fuse_opt_add_arg(outargs, "--version");
		fuse_main(outargs->argc, outargs->argv, &rufs_oper, NULL);
		sys_exit(0);
		break;

	case KEY_DEBUG:
		log_setLevel(DEBUG);
		fuse_opt_add_arg(outargs, "-d");
		return 0;
	}
	return 1;
}


bool processRsync(Configuration* conf, bool start) {
	int i = 0;
	RSync* rsync;

	if (conf == NULL) {
		logWarn("Configuration is NULL");
		return false;
	}

	for (i = 0 ; i < conf->unions->size ; i++) {
		Union* u = (Union*) list_get(conf->unions, i);

		if (u->rsync_src[0]) {
			logInfo("process rsync of '%s'", u->name);

			if (start)
				rsync = RSync_new(u->rsync_src, u->path, u->rsync_filter_filename);
			else
				rsync = RSync_new(u->path, u->rsync_src, u->rsync_filter_filename);

			if (rsync == NULL) {
				logWarn("Failed to create rsync command for union '%s'", u->name);
				continue;
			}

			if (! Rsync_sync(rsync)) {
				logWarn("Failed to rsync '%s'", u->name);
				Rsync_free(rsync);
				continue;
			}

			if (rsync->status != 0)
				Rsync_dumpStatus(rsync);

			Rsync_free(rsync);
		}
	}

	return true;
}

bool processDeleteOnEnd(Configuration* conf) {
	int i = 0;
	RSync* rsync;

	if (conf == NULL) {
		logWarn("Configuration is NULL");
		return false;
	}

	for (i = 0 ; i < conf->unions->size ; i++) {
		Union* u = (Union*) list_get(conf->unions, i);

		if (u->deleteOnEnd) {
			logInfo("removing union '%s'", u->name);

			if (! fs_rmdirs(u->path)) {
				logWarn("Failed to remove directory %s: %s", u->path, str_geterror());
			}
		}
	}

	return true;
}



int fuse_start(int argc, char** argv) {
    struct fuse_args args = FUSE_ARGS_INIT(argc, argv);
	int ret;

	config = configuration_new();

	logDebug("Fuse configuration");

	if (fuse_opt_parse(&args, config, rufs_opts, rufs_opt_proc) == -1) {
		logError("Failed to parse option");
		sys_exit(1);
	}

	fuse_parse_cmdline(&args, &config->destination_path, NULL, NULL);
	if (config->destination_path == NULL) {
		logError("no mount point, exiting");
		sys_exit(CONF_ERROR);
	}

	if (config->source_path == NULL) {
		logError("no source path, exiting");
		sys_exit(CONF_ERROR);
	}

	logDebug("mount point is %s", config->destination_path);
	logDebug("source path is %s", config->source_path);

	fuse_opt_add_arg(&args, "-o");
	fuse_opt_add_arg(&args, "allow_other");
	fuse_opt_add_arg(&args, "-o");
	fuse_opt_add_arg(&args, "nonempty");
	fuse_opt_add_arg(&args, config->destination_path);

	if (config->user != NULL) {
		logDebug("Switching to user %s", config->user);
		if (! user_switch(config->user, NULL)) {
			logWarn("Failed to switch to user %s", config->user);

			fuse_opt_free_args(&args);
			configuration_free(config);
			sys_exit(PERMISSION_ERROR);
		}
	}


	if (! configuration_parse(config)) {
		logError("Failed to parse configuration file");
		sys_exit(CONF_ERROR);
	}

	if (config == NULL) {
		logError("There is no valid configuration, exiting");
		return CONF_ERROR;
	}

	configuration_dump(config);

	// cleanup pid file
	if (config->pidFile != NULL && fs_exist(config->pidFile)) {
		if (! file_delete(config->pidFile)) {
			logWarn("Failed to delete pid file %s: %s", config->pidFile, str_geterror());
			return CONF_ERROR;
		}
	}

	// load share right
	if (config->shareFile != NULL) {
		shares_init(config);
		shares_reload();
		signal_installSIGHUPHandler(shares_signalReload);
		shares_dump();
	}

	fs_mkdir(config->destination_path);
	if (! fs_exist(config->destination_path))
	{
		logError("Unable to initialize the mount point : %s", config->destination_path);
	}

	// make the mountbind
	if (config->bind && config->bind_path[0] != 0) {
		if (! fs_mountbind(config->destination_path, config->bind_path)) {
			logError("Failed to bind %s to %s: %s", config->destination_path, config->bind_path, str_geterror());
			return MOUNT_ERROR;
		}
	}

	// do rsync operations
	logDebug("rsync operation...");
	processRsync(config, true);


	ret = fuse_main(args.argc, args.argv, &rufs_oper, NULL);
	fuse_opt_free_args(&args);

	if (config->bind && config->bind_path[0] != 0) {
		fs_umount(config->bind_path);
	}

	processRsync(config, false);
	processDeleteOnEnd(config);

	configuration_free(config);
	shares_delete();

	return ret;
}

