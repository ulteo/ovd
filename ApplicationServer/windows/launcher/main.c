/*
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
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
#include <windows.h>
#include <shlwapi.h>

typedef struct _Config {
	TCHAR command_line[MAX_PATH];
	TCHAR working_dir[MAX_PATH];
	int show_mode;
	int delay;
} Config;


int launch(Config);
BOOL conf_init(Config *);
BOOL read_conf(LPSTR, Config *);
void my_message_box(LPSTR, ...);


int main(int argc, LPSTR argv[]) {
	int ret;
	Config conf;
	TCHAR conf_file[MAX_PATH];
	
	conf_init(&conf);
	
	ret = GetModuleFileName(NULL, conf_file, MAX_PATH);
	if (ret <= 0) {
		my_message_box("Unable to GetModuleFileName ... (code: %d)\n", ret);
		return 1;
	}
	
	PathRemoveExtension(conf_file);
	PathAddExtension(conf_file, ".ini");
	      
	if (! PathFileExists(conf_file)) {
		my_message_box("No such file %s\n", conf_file);
		return 1;
	}
	
	read_conf(conf_file, &conf);
	
	if (conf.delay != 0)
		Sleep(conf.delay * 1000);
	
	ret = launch(conf);
	return ret;
}

int launch(Config conf) {
	STARTUPINFO si;
	PROCESS_INFORMATION pi;
	DWORD lpExitCode;
	DWORD dwCreationFlags = 0;
	DWORD pid;
	BOOL ret;
	
	ZeroMemory( &si, sizeof(si) );
	si.cb = sizeof(si);
	ZeroMemory( &pi, sizeof(pi) );
	
	si.dwFlags = STARTF_USESHOWWINDOW|STARTF_USESTDHANDLES;
	si.wShowWindow = conf.show_mode;
	
	si.hStdError = GetStdHandle(STD_OUTPUT_HANDLE); 
	si.hStdOutput = GetStdHandle(STD_OUTPUT_HANDLE); 
	
	ret = CreateProcess(NULL, conf.command_line, NULL, NULL, FALSE, dwCreationFlags, NULL, conf.working_dir, &si, &pi);
        if(ret == FALSE) {
		my_message_box("Unabled to start '%s' in '%s'", conf.command_line, conf.working_dir);
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

BOOL conf_init(Config * conf) {
	int ret;
	
	ret = GetCurrentDirectory(MAX_PATH, conf->working_dir);
	if (ret == 0) {
		my_message_box("Unable to get current directory\n");
		exit(1);
	}
	
	conf->show_mode = SW_SHOWDEFAULT;
	conf->delay = 0;
	
	return FALSE;
}


BOOL read_conf(LPSTR filename, Config * conf) {
	int ret;
	TCHAR buffer[MAX_PATH];
	
	ret = GetPrivateProfileString("main", "command_line", "", conf->command_line, MAX_PATH, filename);
	if (ret<=0) {
		my_message_box("Error: missing 'command_line' key in configuration file", NULL , MB_OK);
		exit(1);
	}
	
	ret = GetPrivateProfileString("main", "working_dir", NULL, buffer, MAX_PATH, filename);
	if (ret >0)
		strncpy(conf->working_dir, buffer, MAX_PATH);
	
	ret = GetPrivateProfileString("main", "show_mode", NULL, buffer, MAX_PATH, filename);
	if (ret >0) {
		if (strcmp(buffer, "HIDE") == 0)
			conf->show_mode = SW_HIDE;
		else if (strcmp(buffer, "MAXIMIZED") == 0)
			conf->show_mode = SW_SHOWMAXIMIZED;
		else if (strcmp(buffer, "MINIMIZED") == 0)
			conf->show_mode = SW_SHOWMINIMIZED;
		else if (strcmp(buffer, "NOACTIVATE") == 0)
			conf->show_mode = SW_SHOWNOACTIVATE;
		else if (strcmp(buffer, "NORMAL") == 0)
			conf->show_mode = SW_SHOWNORMAL;
	}
	
	conf->delay = GetPrivateProfileInt("main", "delay", 0, filename);
	return TRUE;
}

void my_message_box(LPSTR message, ...) {
	char buf[2048];
	va_list ap;
	
	va_start(ap, message);
	vsnprintf(buf, sizeof(buf), message, ap);
	va_end(ap);
	
	MessageBox(NULL, buf, NULL , MB_OK);
}
