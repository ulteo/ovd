#include <windows.h>
#include <stdio.h>

typedef void (*set_hooks_proc_t) ();

int main()
{
	set_hooks_proc_t set_hooks_fn;
	
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