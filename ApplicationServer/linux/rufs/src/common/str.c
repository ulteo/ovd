/**
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2012
 * Author Thomas MOUTON <thomas@ulteo.com> 2012
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

#define _GNU_SOURCE
#include <string.h>
#include <stdio.h>
#include <stdarg.h>
#include <errno.h>
#include "memory.h"
#include "types.h"
#include "list.h"
#include "log.h"
#include "str.h"
#include <math.h>
#include <ctype.h>


size_t str_len(const char* txt) {
	if (txt == 0)
		return 0;

	return strlen(txt);
}

char* str_cpy(char* dst, const char* src) {
	if (dst != NULL)
		dst[0] = '\0';

	if (src == NULL || dst == NULL)
		return 0;

	return strcpy(dst, src);
}

char* str_ncpy(char* dst, const char* src, size_t len) {
	return strncpy(dst, src, len);
}

char* str_cat(char* dst, const char* src) {
	if (dst == NULL || src == NULL)
		return NULL;

	return strcat(dst, src);
}

char* str_dup(const char* src) {
	if (src == NULL)
		return NULL;

	return strdup(src);
}

char* str_str(const char* haystack, const char* needle) {
	if (haystack == NULL || needle == NULL)
		return NULL;

	return strstr(haystack, needle);
}

char* str_chr(const char* s, int c)
{
	return strchr(s,c);
}

int str_cmp(const char* s1, const char* s2) {
	return strcmp(s1, s2);
}

int str_ncmp(const char* s1, const char* s2, size_t len) {
	return strncmp(s1, s2, len);
}

int str_casecmp(const char* s1, const char* s2)
{
	return strcasecmp(s1, s2);
}

int str_ncasecmp(const char* s1, const char* s2, size_t len) {
	return strncasecmp(s1, s2, len);
}

bool str_replaceFirst(char *src, char *pattern, char *by)
{
	char *p = strstr(src, pattern);

	if (p) {
		size_t len_p = strlen(p);
		size_t len_pattern = strlen(pattern);
		size_t len_by = strlen(by);

		if (len_pattern != len_by)
			memmove(p + len_by, p + len_pattern, (len_p - len_pattern + 1));

		strncpy(p, by, len_by);
		return true;
	}

	return false;
}

void str_replaceAll(char* src, char* pattern, char* by) {
	while (str_replaceFirst(src, pattern, by));
}

bool str_endWith(char* src, char* end) {
	int i = strlen(src) - strlen(end);

	if (i < 0)
		return false;
	
	return strcmp(src + i, end);
}

List* str_split(const char* str, const char delim) {
	size_t size = str_len(str);
	char* p = (char*)str;
	char* last;

	if (size == 0)
		return NULL;

	List* res = list_new(true);
	if (*p == '/') {
		p++;
	}

	while (p) {
		last = p;
		p = str_chr(p, delim);

		if (p == NULL) {
			list_add(res, (Any)str_dup(last));
			break;
		}

		size = p - last;
		char* data = (char*)memory_alloc(sizeof(char) * (size + 1), false);
		str_ncpy(data, last, size);
		data[size] = '\0';

		list_add(res, (Any)data);
		p++;
	};

	return res;
}

bool str_rtrim(char* str) {
	int index;
	size_t strLen;

	if (str == NULL)
		return false;

	strLen = strlen(str);

	if (strLen == 0)
		return false;

	for (index = strLen; index >= 0; index--) {
		if (str[index] > 32)
			break;

		str[index] = '\0';
	}

	return true;
}

bool str_ltrim(char* str) {
	if (str == NULL)
		return false;

	if (strlen(str) == 0)
		return false;

	while (str[0] < 33)
		memmove(str, str+1, strlen(str));

	return true;
}

bool str_trim(char* str) {
	return str_ltrim(str) && str_rtrim(str);
}

bool str_toBool(const char* str) {
	char* t = strdup(str);
	bool res;

	str_trim(t);
	res = ((strcasecmp(t, "1") == 0) || (strcasecmp(t, "true") == 0) || (strcasecmp(t, "yes") == 0));
	memory_free(t);

	return res;
}

int str_toInt(const char* str) {
	return atoi(str);
}


long long str_toSize(const char* str) {
	char* temp = str_dup(str);
	int coef = 0;

	str_trim(temp);

	char lastChar = temp[strlen(temp) - 1];
	switch (lastChar) {
	case 'K':
	case 'k':
		coef = 3;
		break;

	case 'M':
	case 'm':
		coef = 6;
		break;

	case 'G':
	case 'g':
		coef = 9;
		break;

	case 'T':
	case 't':
		coef = 12;
		break;

	case 'P':
	case 'p':
		coef = 15;
		break;

	default:
		if (!isdigit(lastChar)) {
			logWarn("Invalid value '%s'", temp);
			return -1;
		}
		break;
	}

	if (!isdigit(lastChar)) {
		temp[strlen(temp) - 1] = '\0';
	}

	return (long long)(atol(temp) * exp10(coef));
}


char* str_fromSize(long long size) {
	char unit[] = " KMGTP";
	char res[20];
	int coef;

	if (size < 1000) {
		str_sprintf(res, "%d", (unsigned int)size);
		return str_dup(res);
	}

	coef = log10(size);
	if (coef > 0)
		coef /= 3;

	if (coef > 5)
		coef = 5;

	size /= exp10(coef * 3);

	str_sprintf(res, "%d%c", (unsigned int)size, unit[coef]);

	return str_dup(res);
}


void str_unquote(char* data) {
	int s;
	str_trim(data);

	if (data == NULL || strlen(data) == 0)
		return;

	if ((data[0] == '\'') || data[0] == '"') {
		memmove(data, data+1, strlen(data));
	}

	s = strlen(data) - 1;

	if ((data[s] == '\'') || data[s] == '"')
		data[s] = '\0';
}

int str_sprintf(char* dest, const char* format, ...) {
	va_list va;
	int size;

	va_start(va, format);
	size = vsprintf(dest, format, va);
	va_end(va);

	return size;
}

void str_snprintf(char* dest, int len, const char* format, ...) {
	va_list va;

	va_start(va, format);
	vsnprintf(dest, len, format, va);
	va_end(va);
}

const char* str_lastOf(const char* src, const char* sub) {
	const char* p = src;
	const char* last = NULL;

	while((p = str_str(p, sub)) != NULL)
		last = p++;

	return last;
}

const char* str_geterror() {
	return strerror(errno);
}
