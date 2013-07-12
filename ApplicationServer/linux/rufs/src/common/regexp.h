/*
 /**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2013
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

#ifndef REGEXP_H_
#define REGEXP_H_

#include <regex.h>
#include "types.h"

typedef struct _regexp {
	regex_t exp;
	char* expression;
} Regexp;


Regexp* regexp_create(const char* regexp);
void regexp_delete(Regexp* reg);
bool regexp_match(Regexp* reg, const char* data);

#endif /* REGEXP_H_ */
