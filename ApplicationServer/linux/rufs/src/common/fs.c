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

#include "fs.h"
#include <stdio.h>
#include <fcntl.h>
#include <sys/stat.h>
#include "log.h"
#include "list.h"
#include "str.h"
#include "error.h"
#include "sys.h"


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
	stat(filename, &fileStat);
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
	int i;
	char* p = destination;
	List *pathComponent = str_split(source, '/');

	if (pathComponent == NULL)
		return true;

	if (destination == NULL) {
		logWarn("Invalid destination");
		list_delete(pathComponent);
		return false;
	}

	for(i = 0 ; i < pathComponent->size ; i++) {
		char* p = (char*)list_get(pathComponent, i);
		char* r = NULL;
		if (str_len(p) == 0)
			continue;

		if (source[0] == '/')
			str_cat(destination, "/");

		switch (p[0]) {
		case '$': // Environment variable
			r = sys_getEnv(p+1);
			if (r == NULL) {
				logWarn("%s is not a valid environment variable", p);
				return false;
			}

			str_cat(destination, r);
			break;
		case '%': // XDG variable
			r = xdg_user_dir_lookup(p+1);
			if (r == NULL) {
				printf("%s is not a valid xdg variable\n", p);
				return false;
			}

			str_cat(destination, r);
			memory_free(r);
			break;

		default:
			str_cat(destination, "/");
			str_cat(destination, p);
			break;
		}
	}

	// Cleaning
	list_delete(pathComponent);
	str_replaceAll(destination, "//", "/");
	return true;
}
