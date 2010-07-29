/*
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
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

#ifndef PLATFORM_H
#define PLATFORM_H

#ifdef WIN32 //WINDOWS

#include <windows.h>
#include <shlwapi.h>

#else //POSIX

#define LPSTR char * 
#define TCHAR char
#define MAX_PATH 4096

enum BOOL_t {FALSE=0, TRUE=1};
typedef enum BOOL_t BOOL;

#endif //WINDOWS/POSIX

#ifndef WIN32 //POSIX

BOOL PathFileExists(LPSTR path);
BOOL PathAppend(LPSTR path, LPSTR suffix);

#endif //POSIX

#endif //PLATFORM_H
