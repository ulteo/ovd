/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

#include <stdio.h>
#include <stdlib.h>
#include <time.h>


#ifdef WIN32 //WINDOWS

#ifndef _WIN32_IE
#define _WIN32_IE 0x0500
#endif //WIN32_IE

#include <windows.h>
#include <shlwapi.h>
#include <shlobj.h>

#define OVDIntegratedClientPath "ulteo\\remoteApps"

#else //POSIX

#include <unistd.h>
#include <string.h>

#define LPSTR char * 
#define TCHAR char
#define MAX_PATH 4096
#define OVDIntegratedClientPath ".ulteo/remoteApps"
enum BOOL_t {TRUE, FALSE};
typedef enum BOOL_t BOOL;

#endif //WINDOWS/POSIX


BOOL isStarted();
BOOL launch(const LPSTR appId, LPSTR path);
BOOL getRemoteAppsPath(LPSTR path);
BOOL getInstancesPath(LPSTR path);
BOOL getToLaunchPath(LPSTR path);
unsigned int generateFileId();
void waitEndApp(LPSTR filename);


#ifndef WIN32 //POSIX

BOOL PathFileExists(LPSTR path);
BOOL PathAppend(LPSTR path, LPSTR suffix);

#endif //POSIX

int main(int argc, LPSTR argv[]) {
    TCHAR path[MAX_PATH];

    if (argc < 2) {
        printf("You must put an application id as parameter.\n");
        return 0;
    }

    if (isStarted(path) == FALSE) {
        printf("Ulteo Remote Applications is not started.\n");
        return 0;
    }

    if (strcmp(argv[1], "quit") == 0) {
        FILE *f;

        if (PathAppend(path, argv[1]) == FALSE)
            return 1;

        f = fopen(path, "w");
        fclose(f);

        return 0;
    }

    if (launch(argv[1], path) == FALSE)
        return 1;

    return 0;
}

BOOL isStarted(LPSTR path) {
    if (getInstancesPath(path) == FALSE)
        return FALSE;
    if (PathFileExists(path) == FALSE)
        return FALSE;

    if (getToLaunchPath(path) == FALSE)
        return FALSE;
    if (PathFileExists(path) == FALSE)
        return FALSE;

    return TRUE;
}

BOOL launch(const LPSTR appId, LPSTR path) {
    TCHAR filename[MAX_PATH];
    FILE * f;

    if (getToLaunchPath(path) == FALSE)
        return FALSE;

    snprintf(filename, sizeof(filename), "%u", generateFileId());

    if (PathAppend(path, filename) == FALSE)
        return FALSE;

    f = fopen(path, "w");
    if (! f)
        return FALSE;

    fprintf(f, "%s", appId);
    fclose(f);

    waitEndApp(filename);

    return TRUE;
}

BOOL getRemoteAppsPath(LPSTR path) {
#ifdef WIN32 //WINDOWS
    if (SHGetSpecialFolderPath(NULL, path, CSIDL_APPDATA, 0) == FALSE)
#else //POSIX
    strncpy(path, getenv("HOME"), MAX_PATH);
    if (! path)
#endif //WINDOWS/POSIX
        return FALSE;

    if (PathAppend(path, OVDIntegratedClientPath) == FALSE)
        return FALSE;

    return TRUE;
}

BOOL getInstancesPath(LPSTR path) {
    if (getRemoteAppsPath(path) == FALSE)
        return FALSE;

    if (PathAppend(path, "instances") == FALSE)
        return FALSE;

    return TRUE;
}

BOOL getToLaunchPath(LPSTR path) {
    if (getRemoteAppsPath(path) == FALSE)
        return FALSE;

    if (PathAppend(path, "to_launch") == FALSE)
        return FALSE;

    return TRUE;
}

/* FIXME: remplacer (int) par (long) quand julien aura fixé le seamlessrdpshell
 * le seamlessrdpshell reçoit un int64 mais ne stocke qu'un int32
 * NE PAS ENVOYER PLUS QU'UN INT32
 */
unsigned int generateFileId() {
    return (unsigned int)(time(NULL) * rand());
}

void waitEndApp(LPSTR filename) {
    TCHAR path[MAX_PATH];

    if (getInstancesPath(path) == FALSE)
        return;
    if (PathAppend(path, filename) == FALSE)
        return;

    do {
#ifdef WIN32 //WINDOWS
        Sleep(1000);
#else //POSIX
        sleep(1);
#endif //WINDOWS/POSIX
    } while(PathFileExists(path) == TRUE);
}

#ifndef WIN32 //POSIX
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
