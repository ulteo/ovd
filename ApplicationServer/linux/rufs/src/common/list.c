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

#include "list.h"
#include "memory.h"
#include "log.h"


List* list_new(bool deleteOnClose) {
	List* list = memory_new(List, true);

	list->allocSize = DEFAULT_LIST_SIZE;
	list->size = 0;
	list->values = (Any*)memory_alloc(sizeof(Any) * DEFAULT_LIST_SIZE, true);
	list->freeOnDelete = deleteOnClose;

	return list;
}

void list_delete(List* list) {
	int i;

	if (list == NULL)
		return;

	if (list->freeOnDelete) {
		for (i = 0 ; i < list->size ; i++) {
			memory_free((void*)list->values[i]);
			list->values[i] = (Any)NULL;
		}
	}

	memory_free(list->values);
	memory_free(list);
}

void list_remove(List* list, size_t index) {
	int i;

	if (index >= list->size)
		return;

	if (list->freeOnDelete) {
		memory_free((void*)list->values[index]);
		list->values[index] = 0;
	}

	for (i = index; i < (list->size - 1); i++)
		list->values[i] = list->values[i + 1];

	list->size--;
}

void list_clear(List* list) {
	int i;

	if (list->freeOnDelete) {
		for (i = 0; i < list->size; i++) {
			memory_free((void*)list->values[i]);
			list->values[i] = 0;
		}
	}

	memory_free(list->values);
	list->size = 0;
	list->allocSize = DEFAULT_LIST_SIZE;
	list->values = (Any*)memory_alloc(sizeof(Any) * DEFAULT_LIST_SIZE, true);
}

void list_add(List* list, Any item) {
	Any* p;
	int i;

	if (list->size >= list->allocSize) {
		p = (Any*)memory_realloc2(list->values, (list->allocSize + DEFAULT_LIST_SIZE) * sizeof(Any), list->allocSize * sizeof(Any), true);
		list->allocSize += DEFAULT_LIST_SIZE;
	}

	list->values[list->size++] = item;
}

Any list_get(List* list, size_t index) {
	if (index >= list->size)
		return (Any)NULL;

	return list->values[index];
}

size_t list_getIndex(List* list, Any value) {
	int i;

	for (i = 0; i < list->size; i++) {
		if (list->values[i] == value)
			return i;
	}

	return NOT_FOUND;
}

