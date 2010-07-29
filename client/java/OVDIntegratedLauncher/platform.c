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

#include "platform.h"

#ifdef WIN32 //WINDOWS

#else //POSIX

#include <stdio.h>
#include <string.h>
#include <unistd.h>

BOOL PathAppend(LPSTR path, LPSTR suffix) {
    TCHAR prefix[MAX_PATH];

    strncpy(prefix, path, MAX_PATH);

    if (prefix[strlen(prefix)-1] == '/')
        prefix[strlen(prefix)-1] = '\0';
    if (suffix[0] == '/')
        suffix++;

    if (snprintf(path, MAX_PATH, "%s%c%s", prefix, '/', suffix) < 0)
        return FALSE;

    return TRUE;
}

BOOL PathFileExists(LPSTR path) {
    if (access(path, F_OK) != 0)
        return FALSE;
    return TRUE;
}
#endif //POSIX
