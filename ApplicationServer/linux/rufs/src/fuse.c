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
#include <linux/limits.h>
#ifdef HAVE_SETXATTR
#include <sys/xattr.h>
#endif


extern Configuration* config;


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

static bool transformPath(const char* path, char* to) {
	logDebug("======> test %s", path);
	int lastIndex = config->unions->size - 1;
	char trpath[PATH_MAX];
	Regexp* reg;
	Union* u;
	int i = 0;
	int a = 0;
	int r = 0;

	transformPathIn(path, trpath);

	for(i = 0 ; i < config->unions->size ; i++) {
		u = (Union*)list_get(config->unions, i);
		List* accept;
		List* reject;
		bool match = false;

		if (!u) {
			continue;
		}

		// if the file exist in a union, we return it
		str_sprintf(to, "%s%s", u->path, trpath);
		if (fs_exist(to)) {
			return true;
		}

		accept = u->accept;
		reject = u->reject;

		for(a = 0 ; a < accept->size ; a++) {
			reg = (Regexp*)list_get(accept, a);
			if (regexp_match(reg, trpath)) {
				match = true;
			}
		}

		// if no accept rules matched, we test the following union
		if (!match && accept->size > 0) {
			continue;
		}


		for(r = 0 ; r < reject->size ; r++) {
			reg = (Regexp*)list_get(reject, r);
			if (regexp_match(reg, trpath)) {
				continue;
			}
		}

		// We found a valid union
		logDebug("Union %s is valid for the path %s", u->name, trpath);

		str_sprintf(to, "%s%s", u->path, trpath);
		return true;
	}

	// If we found noting, we return the last union
	u = (Union*)list_get(config->unions, lastIndex);
	logDebug("Failed back %s\n", u->name);

	str_sprintf(to, "%s%s", u->path, trpath);
	return true;
}

static int rufs_getattr(const char *path, struct stat *stbuf)
{
	int res;
	char trpath[PATH_MAX];
	if (!transformPath(path, trpath))
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
	if (!transformPath(path, trpath))
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
	if (!transformPath(path, trpath))
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
	if (!transformPath(path, trpath))
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
	(void) path;

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
	if (!transformPath(path, trpath))
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
	if (!transformPath(path, trpath))
	{
		return -ENOENT;
	}

	res = mkdir(trpath, mode);
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_unlink(const char *path)
{
	int res;
	char trpath[PATH_MAX];
	if (!transformPath(path, trpath))
	{
		return -ENOENT;
	}

	res = unlink(trpath);
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_rmdir(const char *path)
{
	int res;
	char trpath[PATH_MAX];
	if (!transformPath(path, trpath))
	{
		return -ENOENT;
	}

	res = rmdir(trpath);
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_symlink(const char *from, const char *to)
{
	int res;
	char trto[PATH_MAX];
	if (!transformPath(to, trto))
	{
		return -ENOENT;
	}

	res = symlink(from, trto);
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_rename(const char *from, const char *to)
{
	int res;
	char trto[PATH_MAX];
	if (!transformPath(to, trto))
	{
		return -ENOENT;
	}

	res = rename(from, trto);
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_link(const char *from, const char *to)
{
	int res;
	char trto[PATH_MAX];
	char trfrom[PATH_MAX];
	if (!transformPath(to, trto))
	{
		return -ENOENT;
	}

	if (!transformPath(from, trfrom))
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
	if (!transformPath(path, trpath))
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
	if (!transformPath(path, trpath))
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
	char trpath[PATH_MAX];
	if (!transformPath(path, trpath))
	{
		return -ENOENT;
	}

	res = truncate(trpath, size);
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_ftruncate(const char *path, off_t size,
			 struct fuse_file_info *fi)
{
	int res;

	(void) path;

	res = ftruncate(fi->fh, size);
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_utimens(const char *path, const struct timespec ts[2])
{
	int res;
	char trpath[PATH_MAX];
	if (!transformPath(path, trpath))
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
	if (!transformPath(path, trpath))
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
	if (!transformPath(path, trpath))
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

	(void) path;
	res = pread(fi->fh, buf, size, offset);
	if (res == -1)
		res = -errno;

	return res;
}

static int rufs_write(const char *path, const char *buf, size_t size,
		     off_t offset, struct fuse_file_info *fi)
{
	int res;

	(void) path;
	res = pwrite(fi->fh, buf, size, offset);
	if (res == -1)
		res = -errno;

	return res;
}

static int rufs_statfs(const char *path, struct statvfs *stbuf)
{
	int res;
	char trpath[PATH_MAX];
	if (!transformPath(path, trpath))
	{
		return -ENOENT;
	}

	res = statvfs(trpath, stbuf);
	if (res == -1)
		return -errno;

	return 0;
}

static int rufs_flush(const char *path, struct fuse_file_info *fi)
{
	int res;

	(void) path;
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
	(void) path;
	close(fi->fh);

	return 0;
}

static int rufs_fsync(const char *path, int isdatasync,
		     struct fuse_file_info *fi)
{
	int res;
	(void) path;

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
	if (!transformPath(path, trpath))
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
	if (!transformPath(path, trpath))
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
	if (!transformPath(path, trpath))
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
	if (!transformPath(path, trpath))
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
	(void) path;

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
	.fsync		= rufs_fsync,
#ifdef HAVE_SETXATTR
	.setxattr	= rufs_setxattr,
	.getxattr	= rufs_getxattr,
	.listxattr	= rufs_listxattr,
	.removexattr	= rufs_removexattr,
#endif
	.lock		= rufs_lock,
};


int fuse_start(int argc, char** argv) {
	struct fuse_args args = FUSE_ARGS_INIT(0, NULL);
	int ret;
	int i;

	if (config == NULL) {
		logError("There is no valid configuration, exiting");
		return CONF_ERROR;
	}

	fs_mkdir(config->destination_path);
	if (! fs_exist(config->destination_path))
	{
		logError("Unable to initialize the mount point : %s", config->destination_path);
	}

	logDebug("Fuse configuration");
	fuse_opt_add_arg(&args, "");
	fuse_opt_add_arg(&args, "-f");
	fuse_opt_add_arg(&args, "-o");
	fuse_opt_add_arg(&args, "allow_other");
	fuse_opt_add_arg(&args, "-o");
	fuse_opt_add_arg(&args, "nonempty");

	for(i = 1 ; i < argc; i++) {
		fuse_opt_add_arg(&args, argv[i]);
	}

	fuse_opt_add_arg(&args, config->destination_path);

	// make the mountbind
	if (config->bind && config->bind_path[0] != 0) {
		if (! fs_mountbind(config->destination_path, config->bind_path)) {
			logError("Failed to bind %s to %s: %s", config->destination_path, config->bind_path, str_geterror());
			return MOUNT_ERROR;
		}
	}

	ret = fuse_main(args.argc, args.argv, &rufs_oper, NULL);
	fuse_opt_free_args(&args);

	if (config->bind && config->bind_path[0] != 0) {
		fs_umount(config->bind_path);
	}


	return ret;
}

