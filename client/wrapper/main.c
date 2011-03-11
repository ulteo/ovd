/*
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
 * Author David LECHEVALIER <david@ulteo.com> 2011
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

#define JAR_FILE "OVDNativeClient.jar"

int launch(const char *command_line, ...);
void change_directory();

void usage(const char *name) {
    fprintf(stderr, "Usage: %s [-a jar-file]\n", name);
    fprintf(stderr, "\t-a jar-file: the jar name to execute instead of the default one\n");
}


int main(int argc, LPSTR argv[]) {
    TCHAR cmd_line[2048];
    int ret;
    int i;

    change_directory();
    
    sprintf(cmd_line, "jre\\bin\\java.exe -jar \"%s\"", JAR_FILE);
    for (i=1; i<argc; i++)
        sprintf(cmd_line, "%s %s", cmd_line, argv[i]);

    //  PathCombine();
    printf("Before jre\n");
    ret = launch(cmd_line);
    printf("After jre %d\n",ret);
    return ret;
}


int launch(const char *command_line, ...) {
  STARTUPINFO si;
  PROCESS_INFORMATION pi;
  DWORD lpExitCode;
  DWORD dwCreationFlags = 0;
  DWORD pid;
  char buf[2048];
  va_list ap;
  
  va_start(ap, command_line);
  vsnprintf(buf, sizeof(buf), command_line, ap);
  va_end(ap);
  
  ZeroMemory( &si, sizeof(si) );
  si.cb = sizeof(si);
  ZeroMemory( &pi, sizeof(pi) );
  
  si.dwFlags = STARTF_USESHOWWINDOW|STARTF_USESTDHANDLES;
  si.wShowWindow = SW_HIDE;
  si.hStdError = GetStdHandle(STD_OUTPUT_HANDLE); 
  si.hStdOutput = GetStdHandle(STD_OUTPUT_HANDLE); 
  
  printf("Launch '%s'\n", buf);

  if( !CreateProcess( NULL, (LPSTR)buf, NULL, NULL, FALSE, dwCreationFlags, NULL, NULL, &si, &pi )) {
    return -1;
  }
  pid = pi.dwProcessId;
  
  // Wait until child process exits.
  WaitForSingleObject( pi.hProcess, INFINITE );
  GetExitCodeProcess(pi.hProcess, &lpExitCode);
  
  // Close process and thread handles. 
  CloseHandle(pi.hProcess);
  CloseHandle(pi.hThread);
  
  return (int)lpExitCode;  
}

void change_directory() {
    BOOL ret;
    TCHAR pwd[MAX_PATH];
    TCHAR path[MAX_PATH];

    GetCurrentDirectory(MAX_PATH, pwd);

    ret = GetModuleFileName(NULL, path, MAX_PATH);
    if (ret <= 0) {
        fprintf(stderr, "Unable to GetModuleFileName ... (code: %d)\n", ret);
        return ;
    }

    ret = PathRemoveFileSpec(path);
    if (ret == FALSE  || strlen(path)==0)
        return;

    if (strcmp(pwd, path) == 0)
        return;

    ret = SetCurrentDirectory(path);
    if (ret == FALSE) {
        fprintf(stderr, "Unable to cd into '%s'\n", path);
        return;
    }

    printf("Switched PWD from '%s' to '%s'\n", pwd, path);
}
