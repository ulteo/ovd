// Copyright (C) 2012 
// Author Wei-Jen Chen 2012

#include <windows.h>
#include <stdio.h>
#include <shlobj.h> 
#include <Psapi.h>
#include <stdlib.h>
#include <shlwapi.h> 

#define HOOK_DLL	L"VFSHook.dll"

typedef void (*set_hooks_proc_t) ();
typedef void (*remove_hooks_proc_t) ();

//Refresh desktop on Explorer hooked
void waitDesktopRefresh()
{	
	Sleep(2500);

	//Refresh Desktop 
	HWND hProgman = FindWindowW(L"Progman", 0);
	if(hProgman)
	{
		HWND hDesktop = FindWindowExW(hProgman, 0, L"SHELLDLL_DefView", 0);
		if(hDesktop)
		{
			PostMessageW(hDesktop, WM_KEYDOWN, VK_F5, 1);
			PostMessageW(hDesktop, WM_KEYUP, VK_F5, 1);
			printf("Desktop refreshed.\n");
		}
	}
	
}
#include <stdlib.h>
#include <string>
#include <iostream>
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
	waitDesktopRefresh();
	
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
