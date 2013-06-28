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

#ifndef _WINDOWUTIL_H_
#define _WINDOWUTIL_H_

#include <windows.h>

#define TITLE_SIZE	150
#define CONSOLE_WINDOW_CLASS_NAME "ConsoleWindowClass"
#define CONSOLE_WINDOW_CLASS_SIZE (sizeof(CONSOLE_WINDOW_CLASS_NAME))

PSIZE WindowUtil_getScreenSize();
BOOL WindowUtil_isToplevel(HWND hwnd);
BOOL WindowUtil_isVisible(HWND hwnd);
int WindowUtil_getFlags(HWND hwnd);
HWND WindowUtil_getParent(HWND hwnd);
BOOL WindowUtil_isFocused(HWND hwnd);
BOOL WindowUtil_setFocus(HWND hwnd);
int WindowUtil_getState(HWND hwnd);
BOOL WindowUtil_setState(HWND hwnd, int state);
HICON WindowUtil_getIcon(HWND hwnd, int large);
int WindowUtil_extractIcon(HICON icon, char *buffer, int maxlen);

#endif // _WINDOWUTIL_H_
