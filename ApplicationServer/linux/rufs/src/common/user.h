/*
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2012, 2013
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
#ifndef USER_H_
#define USER_H_


#include "types.h"


bool user_getINFO(const char* user, gid_t* gid, uid_t* uid, char* shell, char* dir);
bool user_switch(const char* user, const char* pass);
bool user_setGroup(const char* user, gid_t gid);
bool user_setGID(gid_t gid);
bool user_setUID(uid_t uid);


#endif /* USER_H_ */
