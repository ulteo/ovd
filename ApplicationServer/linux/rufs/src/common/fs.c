/*
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2012, 2013
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

#include "fs.h"
#include <dirent.h>
#include <stdio.h>
#include <fcntl.h>
#include <unistd.h>
#include <sys/stat.h>
#include <string.h>
#include "log.h"
#include "list.h"
#include "str.h"
#include "error.h"
#include "sys.h"
#include "xdg_user_dir.h"
#include <sys/mount.h>


int file_open(const char* filename) {
	int fd;

	if (filename == NULL) {
		logError("Could not open a NULL file");
		return -1;
	}

	fd = open(filename, O_RDWR | O_CREAT, S_IRUSR | S_IWUSR);
	if (fd < 0)
		fd = open(filename, O_RDONLY);

	if (fd < 0)
		logError("Failed to open %s: [%s]", filename, error_str());

	return fd;
}

void file_close(int fd) {
	close(fd);
}

bool file_delete(const char* filename) {
	return unlink(filename) == 0;
}

size_t file_read(int fd, char* buffer, size_t size) {
	size_t nbRead = read(fd, buffer, size);
	if (nbRead == -1)
		logError("Failed to read file: %s", error_str());

	return nbRead;
}

int file_write(int fd, char* buffer, size_t size) {
	return write(fd, buffer, size);
}

off_t file_seek(int fd, off_t offset) {
	return (off_t)lseek(fd, offset, SEEK_SET);
}

off_t file_getOffset(int fd) {
	return (off_t)lseek(fd, 0, SEEK_CUR);
}


size_t file_size(char* filename) {
	struct stat fileStat;
	lstat(filename, &fileStat);
	return fileStat.st_size;
}

bool file_readLine(int fd, char* line) {
	byte buffer[MAX_LINE_SIZE];
	off_t offset = 0;
	off_t initialOffset = file_getOffset(fd);
	size_t sizeRead = file_read(fd, buffer, MAX_LINE_SIZE);

	if (sizeRead == -1 || sizeRead == 0)
		return false;

	while (offset < sizeRead) {
		byte b = buffer[offset];
		if (b == '\r' || b == '\n') {
			break;
		}

		offset++;
	}

	memory_copy(line, buffer, offset);
	line[offset] = 0;
	file_seek(fd, (initialOffset + offset + 1));

	return true;
}

char* file_getShortName(const char* filename) {
	char* p = (char*)str_lastOf(filename, "/");

	if (p != NULL)
		return p + 1;

	return NULL;
}



bool fs_expandPath(const char* source, char* destination) {
	char keyString[256];
	char key[256];
	char* res;
	char* pos;
	char* end;
	size_t len = strlen(source);
	strncpy(destination, source, len);
	destination[len] = '\0';

	// We are searching for XDG constant
	if (strncmp(destination, "%{", 2) == 0) {
		size_t len;

		pos = strchr(destination, '}');
		if (pos != NULL) {
			len = pos - destination + 1;
			strncpy(keyString, destination, len);
			keyString[len] = '\0';

			strncpy(key, keyString + 2, len - 2);
			key[len - 3] = '\0';

			res = xdg_user_dir_lookup(key);
			if (res == NULL) {
				printf("%s is not a valid xdg variable\n", key);
				return false;
			}

			str_replaceFirst(destination, keyString, res);
			memory_free(res);
		}
	}

	pos = destination;
	while((pos = strstr(pos, "${")) != NULL) {
		end = strchr(pos, '}');
		if (end == NULL)
			break;

		len = end - pos + 1;

		strncpy(keyString, pos, len);
		keyString[len] = 0;
		strncpy(key, keyString + 2, len - 2);
		key[len - 3] = '\0';

		res = sys_getEnv(key);
		if (res != NULL)
			str_replaceFirst(destination, keyString, res);

		pos +=2;
	}

	str_replaceAll(destination, "//", "/");
	return true;
}

bool fs_mkdir(const char* path) {
	mkdir(path, S_IRWXU);
	return true;
}


bool fs_exist(const char* path) {
	return access(path, F_OK) == 0;
}


bool fs_isdir(const char* path) {
	struct stat st;

	if (lstat(path, &st) == 0)
	{
		return S_ISDIR(st.st_mode);
	}

	return false;
}


bool fs_mountbind(const char* src, const char* dst) {
	fs_mkdir(dst);

	if (! fs_exist(dst))
	{
		return false;
	}

	return (mount(src, dst, NULL, MS_BIND, NULL) == 0);
}


bool fs_umount(const char* dst) {
	return (umount(dst) == 0);
}


bool fs_setCurrentDir(const char* dir) {
	if (chdir(dir) < 0) {
		logError("Failed to set the current directory to %s: %s", dir, str_geterror());
		return false;
	}

	return true;
}


char* fs_getRoot(const char* path) {
	char* res;
	char* p;

	if (path == NULL)
		return NULL;

	if (path[0] == '/')
		path++;

	res = str_dup(path);
	p = strchr(res, '/');

	if (p != NULL)
		*p = '\0';

	return res;
}


char* fs_join(const char* p1, const char* p2) {
	char path[PATH_MAX];

	if (p1 == NULL || p2 == NULL)
		return NULL;

	if (p1[str_len(p1) - 1] == '/')
		str_sprintf(path, "%s%s", p1, p2);
	else
		str_sprintf(path, "%s/%s", p1, p2);

	return str_dup(path);
}


long long fs_getDirectorySize(const char* path) {
	struct dirent* dirnt = NULL;
	struct stat buf;
	long long size = 0;
	char* p;

	if (path == NULL)
		return 0;

	DIR *dir = opendir (path);

	if (dir == NULL) {
		logWarn("Failed to open directory '%s': %s", path, str_geterror());
		return 0;
	}

	while ((dirnt = readdir (dir)) != NULL) {
		if ((strcmp (dirnt->d_name, ".") == 0) || (strcmp (dirnt->d_name, "..") == 0))
			continue;

		p = fs_join(path, dirnt->d_name);

		if (lstat (p, &buf) != 0) {
			logWarn("failed to stat %s : %s", p, str_geterror());
			return -1;
		}

		size += buf.st_size;
		if (S_ISDIR (buf.st_mode))
			size += fs_getDirectorySize(p);

		memory_free(p);
	}

	if (closedir (dir) != 0)
		logWarn("Failed to close directory '%s': %s", path, str_geterror());

	return size;
}


long long fs_getSpace(const char* path) {
	struct stat buf;
	if (lstat (path, &buf) != 0)
		return -1;

	if (S_ISDIR (buf.st_mode))
		return buf.st_size + fs_getDirectorySize(path);
	else
		return buf.st_size;
}


bool fs_mkdirs(const char* file) {
	char *pos;
	char* cpy = str_dup(file);
	struct stat st;

	pos = strchr(cpy,'/');
	if (pos == NULL) {
		memory_free(cpy);
		return false;
	}

	pos = strchr(pos+1,'/');
	while (pos != NULL) {
		*pos = 0;
		mkdir(cpy, S_IRWXU);
		if ( lstat(cpy, &st) == -1) {
			memory_free(cpy);
			return false;
		}

		*pos = '/';
		pos = strchr(pos+1,'/');
	}

	memory_free(cpy);
	return true;
}



bool fs_rmdirs(const char* path) {
	char subpath[PATH_MAX];
	struct dirent *dir_entry;
	DIR *dir;
	dir = opendir(path);

	if( dir == NULL)
		return false;

	while((dir_entry = readdir(dir)) != NULL) {
		if( str_cmp(dir_entry->d_name, ".") == 0 || str_cmp(dir_entry->d_name, "..") == 0)
			continue;

		str_sprintf(subpath, "%s/%s", path, dir_entry->d_name);

		if(dir_entry->d_type & DT_DIR)
			fs_rmdirs(subpath);
		else
			unlink(subpath);
	}

	closedir(dir);
	return (rmdir(path) == 0);
}

