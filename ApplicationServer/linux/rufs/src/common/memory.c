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

#include "memory.h"
#include <string.h>
#include <stdlib.h>
#include "log.h"


void* memory_alloc(size_t size, bool reset) {
	void* data;

	data = malloc(size);
	if (! data) {
		logError("Error allocating %lu", size);
		return data;
	}

	if (reset)
		memset(data, 0, size);

	return data;
}

void memory_free(void* ptr) {
	if (ptr)
		free(ptr);
}

void* memory_realloc(void* data, size_t size) {
	if (data == NULL)
		return NULL;

	return realloc(data, size);
}

void* memory_realloc2(void* data, size_t size, size_t oldSize, bool reset) {
	if (data == NULL)
		return NULL;

	void* data2 = realloc(data, size);
	if (! data2) {
		logError("Error reallocating %lu", size);
		return data;
	}

	if (reset && (size > oldSize))
		memset(data2+oldSize, 0, (size - oldSize));

	return data2;
}

void memory_set(void* dst, int val, size_t size) {
	memset(dst, val, size);
}

void memory_copy(void* dst, const void* src, size_t size) {
	memcpy(dst, src, size);
}

int memory_cmp(const void* s1, const void* s2, size_t size) {
	return memcmp(s1, s2, size);
}

