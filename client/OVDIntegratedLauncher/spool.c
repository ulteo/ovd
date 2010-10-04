/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
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

#include "spool.h"
#include "platform.h"

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>

#ifndef WIN32
#include <unistd.h>
#endif //WIN32

int spool_get_pid(P_SPOOL spool);
BOOL spool_process_running(P_SPOOL spool);
int spool_build_unique_id(P_SPOOL spool);
BOOL spool_id_used(P_SPOOL spool, int id);


P_SPOOL spool_create(const LPSTR path) {
    SPOOL * spool = NULL;

    spool = (SPOOL *)malloc(sizeof(SPOOL));
    if (spool == NULL)
        return NULL;
    
    spool->path = strdup(path);
    spool->pid = 0;

    return spool;
}

void spool_free(P_SPOOL spool) {
    if (spool != NULL) {
        if (spool->path != NULL)
            free(spool->path);
        free(spool);
    }
}


BOOL spool_still_running(P_SPOOL spool) {
    TCHAR path[MAX_PATH];

    sprintf(path, "%s", spool->path);
    if (! PathAppend(path, SPOOL_DIR_INSTANCE))
        return FALSE;
    
    if (! PathFileExists(path)) {
        // fprintf(stderr, "Missing directory %s", path);
        return FALSE;
    }

    sprintf(path, "%s", spool->path);
    if (! PathAppend(path, SPOOL_DIR_TOSTART))
        return FALSE;
    
    if (! PathFileExists(path)) {
        // fprintf(stderr, "Missing directory %s", path);
        return FALSE;
    }

    //return spool_process_running(spool);
    return TRUE;
}


#ifdef WIN32 //WINDOWS
BOOL spool_process_running(P_SPOOL spool) {
    HANDLE hProcess;
  
    hProcess = OpenProcess(PROCESS_QUERY_INFORMATION, FALSE, spool->pid);
    if (hProcess == NULL) 
        return FALSE;
  
    CloseHandle(hProcess);
    return TRUE;
}
#else //POSIX
BOOL spool_process_running(P_SPOOL spool) {
    TCHAR path[MAX_PATH];

    sprintf(path, "/proc/%d", spool->pid);
    if (PathFileExists(path) == FALSE)
        return FALSE;

    return TRUE;
}
#endif //WINDOWS/POSIX


int spool_get_pid(P_SPOOL spool) {
    TCHAR path[MAX_PATH];
    FILE * f;
    TCHAR buffer[32];
    char *ret;

    sprintf(path, "%s", spool->path);
    if (! PathAppend(path, "pid"))
        return -2;

    if (! PathFileExists(path))
        return -1;

    f = fopen(path, "r");
    if (! f)
        return -2;

    ret = fgets(buffer, sizeof(buffer), f);
    fclose(f);
    if (ret == NULL)
        return -1;
    
    return atoi(buffer);
}

int spool_instance_create(P_SPOOL spool, const LPSTR appId, const LPSTR args) {
    int instance;
    TCHAR path[MAX_PATH];
    TCHAR instance_str[32];
    FILE *f = NULL;

    instance = spool_build_unique_id(spool);

    snprintf(instance_str, sizeof(instance_str), "%d", instance);
    snprintf(path, sizeof(path), "%s", spool->path);
    if (PathAppend(path, SPOOL_DIR_TOSTART) == FALSE) {
        fprintf(stderr, "PathAppend error\n");
        return -1;
    }

    if (PathAppend(path, instance_str) == FALSE) {
        fprintf(stderr, "PathAppend error\n");
        return -1;
    }


    f = fopen(path, "w");
    if (! f)
        return -1;

    fprintf(f, "id = %s", appId);
    if (args != NULL && strlen(args) > 0) {
        fprintf(f, "\narg = %s", args);
    }
    fclose(f);

    return instance;
}

BOOL spool_instance_isAlive(P_SPOOL spool, int instance) {
    TCHAR path[MAX_PATH];
    TCHAR instance_str[32];

    snprintf(instance_str, sizeof(instance_str), "%d", instance);
    snprintf(path, sizeof(path), "%s", spool->path);
    if (PathAppend(path, SPOOL_DIR_INSTANCE) == FALSE) {
        fprintf(stderr, "PathAppend error\n");
        return FALSE;
    }

    if (PathAppend(path, instance_str) == FALSE) {
        fprintf(stderr, "PathAppend error\n");
        return FALSE;
    }

    if (! PathFileExists(path)) {
        snprintf(instance_str, sizeof(instance_str), "%d", instance);
        snprintf(path, sizeof(path), "%s", spool->path);
        if (PathAppend(path, SPOOL_DIR_TOSTART) == FALSE) {
            fprintf(stderr, "PathAppend error\n");
            return FALSE;
        }

        if (PathAppend(path, instance_str) == FALSE) {
            fprintf(stderr, "PathAppend error\n");
            return FALSE;
        }

        return PathFileExists(path);
    }

    return TRUE;
}



/* FIXME: remplacer (int) par (long) quand julien aura fixé le seamlessrdpshell
 * le seamlessrdpshell reçoit un int64 mais ne stocke qu'un int32
 * NE PAS ENVOYER PLUS QU'UN INT32
 */
int spool_build_unique_id(P_SPOOL spool) {
    int id;

    do {
        id = (int)(time(NULL) * rand());
    } while(spool_id_used(spool, id));

    return id;
}

BOOL spool_id_used(P_SPOOL spool, int id) {
    TCHAR path[MAX_PATH];
    TCHAR id_str[32];

    // -1 is used as an error code
    if (id == -1)
	    return TRUE;
    
    sprintf(id_str, "%d", id);
    
    sprintf(path, "%s", spool->path);
    PathAppend(path, SPOOL_DIR_INSTANCE);
    PathAppend(path, id_str);
    if (PathFileExists(path))
        return TRUE;

    sprintf(path, "%s", spool->path);
    PathAppend(path, SPOOL_DIR_TOSTART);
    PathAppend(path, id_str);
    if (PathFileExists(path))
        return TRUE;
    
    return FALSE;
}
