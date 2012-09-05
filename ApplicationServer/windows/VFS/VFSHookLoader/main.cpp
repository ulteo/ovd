#include <windows.h>
#include <stdio.h>

typedef void (*set_hooks_proc_t) ();

int main()
{
	set_hooks_proc_t set_hooks_fn;
	
	//NOTE: strange, for some dlls you have to load it so you can hook it. 
	//		(Maybe previlieges are needed to hook it without loading.)
	//		The dlls:	Shlwapi, SHELL32
	LoadLibrary("Shlwapi.dll");
	LoadLibrary("SHELL32.dll");	

	HMODULE hookdll = LoadLibrary("VFSHook.dll");
	
	if (!hookdll)
	{
		printf("Could not load hook DLL. Unable to continue.");
		system("pause");
		return -1;
	}

	set_hooks_fn = (set_hooks_proc_t)GetProcAddress(hookdll, "SetHooks");

	set_hooks_fn();

	system("pause");
	return 0;
}