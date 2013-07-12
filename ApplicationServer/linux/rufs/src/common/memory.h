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

#ifndef _COMMON_MEMORY_
#define _COMMON_MEMORY_

#include "types.h"


void* memory_alloc(size_t size, bool reset);
void memory_free(void* ptr);
void* memory_realloc(void* data, size_t size);
void* memory_realloc2(void* data, size_t size, size_t oldSize, bool reset);
void memory_set(void* dst, int val, size_t size);
void memory_copy(void* dst, const void* src, size_t size);
int memory_cmp(const void* s1, const void* s2, size_t size);

#define memory_new(type, reset) memory_alloc(sizeof(type), reset)

#endif
