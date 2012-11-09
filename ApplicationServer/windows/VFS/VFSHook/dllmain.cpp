#include "stdafx.h"
#include "InterceptAPI.h"

#define DLL_EXPORT extern "C" __declspec(dllexport)

static HINSTANCE g_instance = NULL;
static HHOOK g_cbt_hook = NULL;
static LRESULT CALLBACK cbt_hook_proc(int code, WPARAM wparam, LPARAM lparam)
{	
	return CallNextHookEx(g_cbt_hook, code, wparam, lparam);
}

DLL_EXPORT void SetHooks()
{
	if (!g_cbt_hook)
	{
		//dwThreadId: 0 for global hook
		g_cbt_hook = SetWindowsHookEx(WH_CBT, cbt_hook_proc, g_instance, 0);
	}
}

DLL_EXPORT void RemoveHooks()
{
	if (g_cbt_hook)
	{
		UnhookWindowsHookEx(g_cbt_hook);
	}
}

BOOL APIENTRY DllMain( HMODULE hModule,
                       DWORD  ul_reason_for_call,
                       LPVOID lpReserved
					 )
{
	switch (ul_reason_for_call)
	{
	case DLL_PROCESS_ATTACH:
		g_instance = hModule;
		setupHooks();
		break;
	case DLL_THREAD_ATTACH:
		break;
	case DLL_THREAD_DETACH:
		break;
	case DLL_PROCESS_DETACH:
		releaseHooks();
		break;
	}
	return TRUE;
}