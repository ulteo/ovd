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


#ifndef _COMMON_INI_
#define _COMMON_INI_

#include "types.h"
#include "list.h"


typedef struct _Section {
	char name[256];
	List* keys;
	List* values;

} Section;

typedef struct _Ini {
	List* sections;
	
} Ini;


Ini* ini_new();
void ini_delete(Ini* ini);
bool ini_parse(Ini* ini, const char* filename);

Section* ini_get(Ini* ini, const char* section);
char* ini_getKey(Ini* ini, const char* sectionName, const char* key);
bool ini_addKey(Ini* ini, Section* section, const char* key, const char* value);
bool ini_addSection(Ini* ini, const char* sectionName);

void ini_dump(Ini* ini);

#endif
