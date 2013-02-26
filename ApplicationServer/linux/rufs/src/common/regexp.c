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

#include "regexp.h"
#include "log.h"
#include "memory.h"
#include "str.h"


Regexp* regexp_create(const char* expression) {
	Regexp* res = memory_new(Regexp, true);

	if (regcomp (&res->exp, expression, REG_EXTENDED) != 0) {
		logWarn("Failed to create regular expression from %s\n", expression);
		return NULL;
	}
	res->expression = str_dup(expression);

	return res;
}


void regexp_delete(Regexp* reg) {
	regfree (&reg->exp);
	memory_free(reg->expression);
	memory_free(reg);
}


bool regexp_match(Regexp* reg, const char* data) {
	return (regexec (&reg->exp, data, 0, NULL, 0) != REG_NOMATCH);
}

