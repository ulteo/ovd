#include <windows.h>
#include <stdio.h>
#include <shlobj.h> 
#include <Psapi.h>
#include <stdlib.h>
#include <shlwapi.h> 

#define HOOK_DLL	L"VFSHook.dll"
#define EXPLORER	L"Explorer.EXE"
#define EXPLORER_L	L"explorer.exe"

typedef void (*set_hooks_proc_t) ();

//Refresh desktop on Explorer hooked
void waitDesktopRefresh()
{
	Sleep(2500);
	//Refresh Desktop 
	//NOTE: For some reason(?) SHChangeNotify crash the explorer on loader terminated.
	//SHChangeNotify(SHCNE_INTERRUPT, SHCNF_FLUSH, NULL, NULL);
	//SHChangeNotify(SHCNE_ASSOCCHANGED, SHCNF_IDLIST, NULL, NULL);

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
int main()
{
	ShowWindow( GetConsoleWindow(), SW_HIDE );

	set_hooks_proc_t set_hooks_fn;
	
	HMODULE hookdll = LoadLibraryW(HOOK_DLL);
	
	if (!hookdll)
	{
		printf("Could not load hook DLL. Unable to continue.\n");
		return -1;
	}

	set_hooks_fn = (set_hooks_proc_t)GetProcAddress(hookdll, "SetHooks");

	set_hooks_fn();

	//refresh desktop on dll loaded by Explorer
	waitDesktopRefresh();
	
	printf("Running...\n");	
	//system("pause");
	bool run = true;
	while(run)
	{
		//TODO: End condition; Currently Windows force terminate this program on user exit.
		Sleep(1000);
	}

	return 0;
}