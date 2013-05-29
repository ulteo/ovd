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

#ifndef VFS_H_
#define VFS_H_

#include <string>

#define VFS_HOOK_LOADER_32  "VFSHookLoader32.exe"
#define VFS_HOOK_LOADER_64  "VFSHookLoader64.exe"


class VFS {
public:
	enum status {SUCCESS, INVALID_ARGUMENT, WRONG_SRC, SRC_DO_NOT_EXIST, INVALID_CONF, INVALID_UNION};

	VFS();
	virtual ~VFS();

	VFS::status init(std::string path);
	VFS::status start();
	VFS::status stop();
};

#endif /* VFS_H_ */
