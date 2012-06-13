/*
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2012
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

#include <windows.h>
#include <signal.h>
#include <stdio.h>

typedef void (*set_hooks_proc_t) ();
typedef void (*remove_hooks_proc_t) ();
typedef int (*get_instance_count_proc_t) ();

static boolean run = TRUE;

static HMODULE hookdll64;
static set_hooks_proc_t set_hooks_fn64;
static remove_hooks_proc_t remove_hooks_fn64;
static get_instance_count_proc_t instance_count_fn64;

void sighandler(int sig) {
	signal(SIGABRT, SIG_IGN);
	signal(SIGTERM, SIG_IGN);
	signal(SIGINT, SIG_IGN);

	fprintf(stdout, "Received signal(%d), stopping\n", sig);

	remove_hooks_fn64();

	FreeLibrary(hookdll64);

	run = FALSE;
}

int WINAPI WinMain(HINSTANCE instance, HINSTANCE prev_instance, LPSTR cmdline, int cmdshow) {
	signal(SIGABRT, &sighandler);
	signal(SIGTERM, &sighandler);
	signal(SIGINT, &sighandler);

	hookdll64 = LoadLibrary("seamlessrdp_x64.dll");
	if (!hookdll64)
	{
		fprintf(stderr, "Could not load hook DLL (64 bits): error %lu. Unable to continue.", GetLastError());
		return -1;
	}

	set_hooks_fn64 = (set_hooks_proc_t) GetProcAddress(hookdll64, "SetHooks");
	remove_hooks_fn64 = (remove_hooks_proc_t) GetProcAddress(hookdll64, "RemoveHooks");
	instance_count_fn64 = (get_instance_count_proc_t) GetProcAddress(hookdll64, "GetInstanceCount");

	if (!set_hooks_fn64 || !remove_hooks_fn64 || !instance_count_fn64)
	{
		FreeLibrary(hookdll64);
		fprintf(stderr, "Hook DLL (64 bits) doesn't contain the correct functions. Unable to continue.");
		return -2;
	}

	// Check if the DLL is already loaded
	if (instance_count_fn64() != 1)
	{
		FreeLibrary(hookdll64);
		fprintf(stderr, "Another running instance of Seamless RDP (64 bits) detected.");
		return -3;
	}

	set_hooks_fn64();

	while (run) {
		Sleep(1000);
	}

	return 0;
}

