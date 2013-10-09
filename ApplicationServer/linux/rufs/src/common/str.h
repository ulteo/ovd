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

#ifndef _COMMON_STR_
#define _COMMON_STR_

#include <stdarg.h>
#include "list.h"


size_t str_len(const char* txt);
char* str_cpy(char* dst, const char* src);
char* str_ncpy(char* dst, const char* src, size_t len);
char* str_cat(char* dst, const char* src);
char* str_dup(const char* src);
char* str_str(const char* haystack, const char* needle);
char* str_chr(const char* s, int c);
int str_cmp(const char* s1, const char* s2);
int str_ncmp(const char* s1, const char* s2, size_t len);
int str_casecmp(const char* s1, const char* s2);
int str_ncasecmp(const char* s1, const char* s2, size_t len);
bool str_replaceFirst(char *src, char *pattern, char *by);
void str_replaceAll(char* src, char* pattern, char* by);
bool str_endWith(char* src, char* end);
List* str_split(const char* str, const char delim);
bool str_rtrim(char* str);
bool str_ltrim(char* str);
bool str_trim(char* str);
bool str_toBool(const char* str);
int str_toInt(const char* str);
int str_toOct(const char* str);
long long str_toSize(const char* str);
char* str_fromSize(long long size);
void str_unquote();
int str_sprintf(char* dest, const char* format, ...);
void str_snprintf(char* dest, int len, const char* format, ...);
bool format_time();
const char* str_lastOf(const char* src, const char* sub);
const char* str_geterror();

#endif
