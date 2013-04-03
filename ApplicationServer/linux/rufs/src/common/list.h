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

#ifndef _COMMON_LIST_
#define _COMMON_LIST_

#include "types.h"


#define DEFAULT_LIST_SIZE 10
#define NOT_FOUND (Any)-1


typedef struct _List
{
	Any* values;
	int size;
	int allocSize;
	int freeOnDelete;
} List;


List* list_new(bool freeOnDelete);
void list_delete(List* list);

void list_add(List* list, Any item);
void list_remove(List* self, size_t index);
void list_clear(List* list);

Any list_get(List* list, size_t index);
size_t list_getIndex(List* self, Any value);
char* list_dumpStr(List* list, char* separator);

#endif
