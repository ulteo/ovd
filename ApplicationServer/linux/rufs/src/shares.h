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
#ifndef SHARES_H_
#define SHARES_H_

#include "common/types.h"
#include "configuration.h"


typedef struct _Share {
	char* name;
	char* path;
	long long quota;
	long long spaceUsed;
} Share;


typedef struct _ShareList {
	char* shareFile;
	char* shareDirectory;
	List* list;
	bool needReload;
	long long shareGrace;
} ShareList;


void shares_init(Configuration* conf);
void shares_signalReload(int sig);
ShareList* shares_getInstance();
bool shares_activated(const char* name);
long long shares_getQuota(const char* name);
long long shares_getSpaceUsed(const char* name);
bool shares_quotaExceed(const char* name);
void shares_updateSpace(const char* name, long size);


#endif /* SHARES_H_ */
