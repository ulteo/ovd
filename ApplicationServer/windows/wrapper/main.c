/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
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


#ifndef _WIN32_IE
#define _WIN32_IE 0x0500
#endif //WIN32_IE


#include <stdio.h>
#include <stdlib.h>
#include <windows.h>
#include <shlobj.h>
#include <shlwapi.h>


int launch(const LPSTR name, const LPSTR command_line);

int main(int argc, LPSTR argv[]) {
    int i;
    TCHAR cmd_line[4096];
    int ret;
    TCHAR myself_filename[MAX_PATH];
    TCHAR name[MAX_PATH];
    TCHAR path[4096];
    TCHAR search_path[MAX_PATH];
    char* parse = NULL;
    BOOL found = FALSE;


    ret = GetModuleFileName(NULL, myself_filename, MAX_PATH);
    if (ret <= 0) {
        fprintf(stderr, "Unable to GetModuleFileName ... (code: %d)\n", ret);
        return 1;
    }
    
    sprintf(name, "%s", PathFindFileName(myself_filename));
    printf("Name: '%s'\n", name);

    ret = GetEnvironmentVariable("PATH", path, 4096);
    if (ret <= 0) {
        fprintf(stderr, "Something wrong while getenv('path') ... (code: %d)\n", ret);
        return 1;
    }

    for (parse = strtok (path, ";"); parse != NULL; parse = strtok (NULL, ";")) {
        PathCombine(search_path, parse, name);
        if (PathFileExists(search_path) == FALSE)
            continue;
        
        if (strcmp(search_path, myself_filename) == 0)
            continue;

        printf (" - FOUND  '%s'\n", search_path);
        found = TRUE;
        break;
    }
    
    if (found == FALSE) {
        fprintf(stderr, "Unable to find another '%s' from %s folders\n", name, "%PATH%");
        return 2;
    }
    
    sprintf(cmd_line, "\"%s\"", search_path);
    for (i=1; i<argc; i++)
        sprintf(cmd_line, "%s \"%s\"", cmd_line, argv[i]);

    printf("Final cmd_line '%s'\n", cmd_line);
    ret = launch(search_path, cmd_line);
    printf("program returned %d\n",ret);
    return ret;
}


int launch(const LPSTR name, const LPSTR command_line) {
    STARTUPINFO si;
    PROCESS_INFORMATION pi;
    DWORD lpExitCode;
    DWORD dwCreationFlags = 0;
    DWORD pid;

    ZeroMemory( &si, sizeof(si) );
    si.cb = sizeof(si);
    ZeroMemory( &pi, sizeof(pi) );
  
    si.dwFlags = STARTF_USESHOWWINDOW|STARTF_USESTDHANDLES;
    si.wShowWindow = SW_HIDE;
    si.hStdError = GetStdHandle(STD_OUTPUT_HANDLE); 
    si.hStdOutput = GetStdHandle(STD_OUTPUT_HANDLE); 
    
    printf("Launch '%s'\n", command_line);
    
    if( !CreateProcess(name, command_line, NULL, NULL, FALSE, dwCreationFlags, NULL, NULL, &si, &pi ))
        return -1;

    pid = pi.dwProcessId;
    
    // Wait until child process exits.
    WaitForSingleObject( pi.hProcess, INFINITE );
    GetExitCodeProcess(pi.hProcess, &lpExitCode);
    
    // Close process and thread handles. 
    CloseHandle(pi.hProcess);
    CloseHandle(pi.hThread);
  
    return (int)lpExitCode;  
}
