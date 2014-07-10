/*
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2013
 * Author Wei-Jen Chen 2012
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
#include <stdio.h>
#include <shlobj.h> 
#include <Psapi.h>
#include <stdlib.h>
#include <shlwapi.h>
#include <stdlib.h>
#include <string>
#include <iostream>
#include <common/sys/System.h>
#include <common/sys/Event.h>



#ifdef _WIN64
	#define HOOK_DLL	L"VFSHook64.dll"
#else
	#define HOOK_DLL	L"VFSHook32.dll"
#endif

typedef void (*set_hooks_proc_t) ();
typedef void (*remove_hooks_proc_t) ();


int main(int argc, char** argv) {
	bool run = true;
	set_hooks_proc_t set_hooks_fn;
	remove_hooks_proc_t remove_hooks_fn;
	Event event(L"HookInstalled", true);
	
	HMODULE hookdll = LoadLibraryW(HOOK_DLL);
	
	if (!hookdll) {
		printf("Could not load hook DLL. Unable to continue.\n");
		return -1;
	}

	set_hooks_fn = (set_hooks_proc_t)GetProcAddress(hookdll, "SetHooks");
	remove_hooks_fn = (remove_hooks_proc_t)GetProcAddress(hookdll, "RemoveHooks");

	set_hooks_fn();
	
	printf("Running... \n");

	if (! event.create()) {
		return -4;
	}

	if (! event.fire()) {
		return -5;
	}

	while(run) {
		//TODO: End condition, or just let Windows terminate this program on user exit.
		Sleep(1000);
	}
		
	remove_hooks_fn();

	FreeLibrary(hookdll);

	return 0;
}
