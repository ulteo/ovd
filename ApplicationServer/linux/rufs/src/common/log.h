/**
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
 **/

#ifndef LOG_H
#define LOG_H

#include "types.h"
#include "fs.h"
#include <stdarg.h>


#define LOG_BUFFER_SIZE  2048
#define logDebug(msg, ...) log_message(DEBUG, __func__, __LINE__, msg, ##__VA_ARGS__)
#define logInfo(msg, ...) log_message(INFO, __func__, __LINE__, msg, ##__VA_ARGS__)
#define logWarn(msg, ...) log_message(WARNING, __func__, __LINE__, msg, ##__VA_ARGS__)
#define logError(msg, ...) log_message(ERROR, __func__, __LINE__, msg, ##__VA_ARGS__)

#define logHex(level, data, size) log_hexdump(level, __func__, __LINE__, data, size);


typedef enum {
	DEBUG,
	INFO,
	WARNING,
	ERROR
} LogLevel;


typedef struct _logger {
	char program[PATH_MAX];
	int logFileFd;
	LogLevel level;
	bool develOutput;
	bool stdOutput;
} Logger;


void log_hexdump(LogLevel level, const char* function, int line, byte* data, size_t size);
void log_message(LogLevel level, const char* function, int line, const char* msg, ...);

void log_init();
void log_release();

void log_setProgram(const char* program);
void log_setLevel(LogLevel level);
LogLevel log_str2Level(char* buf);
void log_setOutputFile(const char* filename);
void log_enableStdOutput(bool enable);
void log_enableDevelOutput(bool enable);


LogLevel getLogLevel(char* s);

#endif
