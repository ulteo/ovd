#include "stdafx.h"
#include "Hook.h"

//TODO: 
//check seamlessrdp / hook.c for SetWindowsHookEx func info
//How to make a keyboard hook global across processes:
//	http://stackoverflow.com/questions/454477/how-to-make-a-keyboard-hook-global-across-processes
//Dll Injection:
//  http://www.blizzhackers.cc/viewtopic.php?p=2483118
//我快疯了 全局钩子你怎么就不能工作呢:
//  http://bbs.pediy.com/showthread.php?t=130828

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

BOOL APIENTRY DllMain( HMODULE hModule,
                       DWORD  ul_reason_for_call,
                       LPVOID lpReserved
					 )
{
	switch (ul_reason_for_call)
	{
	case DLL_PROCESS_ATTACH:
	case DLL_THREAD_ATTACH:
		// NOTE: remember our instance handle
		g_instance = hModule;
		setupHooks();
		break;
	case DLL_THREAD_DETACH:
	case DLL_PROCESS_DETACH:
		releaseHooks();
		break;
	}
	return TRUE;
}