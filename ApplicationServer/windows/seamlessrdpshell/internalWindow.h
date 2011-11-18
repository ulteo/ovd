/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2011
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

#ifndef _INTERNAL_WINDOW_H_
#define _INTERNAL_WINDOW_H_

#include <windows.h>

typedef void (CALLBACK* DATACOPYPROC)(PCOPYDATASTRUCT);

BOOL InternalWindow_create(HINSTANCE instance, DATACOPYPROC lpDataCopyProcessFunc);
HWND InternalWindow_getHandle();

#endif // _INTERNAL_WINDOW_H_
