/*
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
 */

#ifndef RSYNC_H_
#define RSYNC_H_


#include "memory.h"
#include "log.h"

#define RSYNC_PATH  "/usr/bin/rsync"


typedef struct _RSync {
	char* src;
	char* dst;
	char* includeFilter;
	char* excludeFilter;
	int status;
	char* statusMessage;
} RSync;


RSync* RSync_create();
void Rsync_delete(RSync* rsync);
bool Rsync_INSync(RSync* rsync);
bool Rsync_OUTSync(RSync* rsync);
void Rsync_dumpStatus(RSync* rsync);


#endif /* RSYNC_H_ */
