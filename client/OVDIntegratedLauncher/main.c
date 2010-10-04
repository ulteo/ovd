/*
 * Copyright (C) 2009 Ulteo SAS
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

#ifdef WIN32 //WINDOWS

#ifndef _WIN32_IE
#define _WIN32_IE 0x0500
#endif //WIN32_IE

#endif //WIN32

#include <stdio.h>
#include <stdlib.h>

#include "spool.h"
#include "platform.h"

#ifdef WIN32 //WINDOWS

#include <windows.h>
#include <shlobj.h>

#define OVDIntegratedClientPath "ulteo\\ovd\\remoteApps"

#else //POSIX

#include <string.h>
#include <time.h>
#include <unistd.h>

#define OVDIntegratedClientPath ".ulteo/ovd/remoteApps"

#endif //WINDOWS/POSIX

BOOL getRemoteAppsPath(LPSTR path, LPSTR arg);


void usage(const char *name) {
    fprintf(stderr, "Usage: %s INSTANCE APP_ID [args...]\n", name);
    fprintf(stderr, "\t-d INSTANCE: the spool directory name to use\n");
    fprintf(stderr, "\tAPP_ID: the Ulteo OVD Application id to start\n");
}


int main(int argc, LPSTR argv[]) {
    TCHAR spool_dir[MAX_PATH];
    SPOOL * spool = NULL;
    int instance;
    LPSTR args = NULL;
    
    if (argc < 3) {
        fprintf(stderr, "Missing arguments.\n");
        usage(argv[0]);
        return 1;
    }

    getRemoteAppsPath(spool_dir, argv[1]);
    
    spool = spool_create(spool_dir);
    if (spool == NULL) {
        fprintf(stderr, "Internal error.\n");
        return 2;
    }

    if (spool_still_running(spool) == FALSE) {
        fprintf(stderr, "No spool directory on %s.\n", spool->path);
        return 2;
    }

    if (argc > 3) {
        args = argv[3];
    }

    instance = spool_instance_create(spool, argv[2], args);
    if (instance == -1) {
        fprintf(stderr, "Internal error 2\n");
        return 2;
    }

    while(spool_still_running(spool) 
          && spool_instance_isAlive(spool, instance)) {
#ifdef WIN32 //WINDOWS
        Sleep(1000);
#else //POSIX
        sleep(1);
#endif //WINDOWS/POSIX
    }

    return 0;
}

BOOL getRemoteAppsPath(LPSTR path, LPSTR arg) {
#ifdef WIN32 //WINDOWS
    if (SHGetSpecialFolderPath(NULL, path, CSIDL_APPDATA, 0) == FALSE)
#else //POSIX
    strncpy(path, getenv("HOME"), MAX_PATH);
    if (! path)
#endif //WINDOWS/POSIX
        return FALSE;

    if (PathAppend(path, OVDIntegratedClientPath) == FALSE)
        return FALSE;

    if (PathAppend(path, arg) == FALSE)
        return FALSE;

    return TRUE;
}
