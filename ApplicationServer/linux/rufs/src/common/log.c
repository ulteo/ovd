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

#include "types.h"
#include "time.h"
#include "memory.h"
#include "log.h"


const char* logStr[] = {"[DEBUG ]", "[INFO  ]", "[WARN  ]", "[ERROR ]"};

static Logger logger;


static const char* log_getLevelStr(LogLevel logLevel) {
	return logStr[logLevel];
}

void log_init() {
	logger.stdOutput = true;
	logger.logFileFd = 0;
	logger.level = INFO;
	str_cat(logger.program, "");
}

void log_release() {
	if (logger.logFileFd > 0)
		file_close(logger.logFileFd);
}

void log_setProgram(const char* program) {
	if (program == NULL)
		return;

	str_ncpy(logger.program, program, str_len(program));
}

void log_setLevel(LogLevel level) {
	logger.level = level;
}

void log_setOutputFile(const char* filename) {
	logger.logFileFd = file_open(filename);
}

void log_enableStdOutput(bool enable) {
	logger.stdOutput = enable;
}

void log_enableDevelOutput(bool enable) {
	logger.develOutput = enable;
}


LogLevel log_str2Level(char* buf) {
	if (str_casecmp(buf, "error") == 0)
		return ERROR;

	if ((str_casecmp(buf, "warn") == 0) || (str_casecmp(buf, "warning") == 0))
		return WARNING;

	if (str_casecmp(buf, "info") == 0)
		return INFO;

	if (str_casecmp(buf, "debug") == 0)
		return DEBUG;

	return DEBUG;
}

void log_message(LogLevel level, const char* function, int line, const char* msg, ...) {
	if (level < logger.level)
		return;

	char buffer[LOG_BUFFER_SIZE];

	va_list va;
	int preffixSize;

	time_format(buffer, "[%Y:%m:%d-%H:%M:%S]");
	str_sprintf(buffer, "%s %s %s ", buffer, log_getLevelStr(level), logger.program, function, line);

	if (logger.develOutput)
		str_sprintf(buffer, "%s[%s:%i] ", buffer, function, line);

	preffixSize = str_len(buffer);

	va_start(va, msg);
	vsnprintf(buffer + preffixSize, LOG_BUFFER_SIZE - preffixSize, msg, va);
	va_end(va);

	if (logger.stdOutput)
		puts(buffer);

	if (logger.logFileFd)
		file_write(logger.logFileFd, buffer, str_len(buffer));
}

void log_hexdump(LogLevel level, const char* function, int line, byte* data, size_t size) {
	if (level < logger.level)
		return;

	byte* p = data;
	char* dump = (char*)memory_alloc(128, true);
	char* dump_offset = dump;
	int i, thisline, offset, len = 0;

	while (offset < len) {
		size = str_sprintf(dump_offset, "%04x ", offset);
		dump_offset += size;

		thisline = len - offset;
		if (thisline > 16)
			thisline = 16;

		for (i = 0; i < thisline; i++)
			dump_offset += str_sprintf(dump_offset, "%02x ", p[i]);

		for (; i < 16; i++)
			dump_offset += str_sprintf(dump_offset, "   ");

		for (i = 0; i < thisline; i++)
			dump_offset += str_sprintf(dump_offset, "%c", (p[i] >= 0x20 && p[i] < 0x7f) ? p[i] : '.');
			dump_offset += size;

		dump_offset = 0;
		log_message(level, function, line, dump);
		dump_offset = dump;
		offset += thisline;
		p += thisline;
	}

	memory_free(dump);
}

