// Copyright (C) 2012 
// Author Wei-Jen Chen 2012

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



#ifdef _WIN64
	#define HOOK_DLL	L"VFSHook64.dll"
#else
	#define HOOK_DLL	L"VFSHook32.dll"
#endif

typedef void (*set_hooks_proc_t) ();
typedef void (*remove_hooks_proc_t) ();


int main()
{
	set_hooks_proc_t set_hooks_fn;
	remove_hooks_proc_t remove_hooks_fn;
	
	HMODULE hookdll = LoadLibraryW(HOOK_DLL);
	
	if (!hookdll)
	{
		printf("Could not load hook DLL. Unable to continue.\n");
		return -1;
	}

	set_hooks_fn = (set_hooks_proc_t)GetProcAddress(hookdll, "SetHooks");
	remove_hooks_fn = (remove_hooks_proc_t)GetProcAddress(hookdll, "RemoveHooks");

	set_hooks_fn();
	
	//refresh desktop on dll loaded by Explorer
	System::refreshDesktop();

	printf("Running... \n");

	bool run = true;

	while(run)
	{
		//TODO: End condition, or just let Windows terminate this program on user exit.
		Sleep(1000);
	}
		
	remove_hooks_fn();

	FreeLibrary(hookdll);

	return 0;
}
