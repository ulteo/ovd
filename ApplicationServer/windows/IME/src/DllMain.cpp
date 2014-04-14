//////////////////////////////////////////////////////////////////////
//
//  THIS CODE AND INFORMATION IS PROVIDED "AS IS" WITHOUT WARRANTY OF
//  ANY KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED
//  TO THE IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
//  PARTICULAR PURPOSE.
//
//  Copyright (C) 2003  Microsoft Corporation.  All rights reserved.
//
//  DllMain.cpp
//
//          DllMain module entry point.
//
//////////////////////////////////////////////////////////////////////

#include "Globals.h"
#include "PopupWindow.h"
#include <string>


//+---------------------------------------------------------------------------
//
// DllMain
//
//----------------------------------------------------------------------------

BOOL WINAPI DllMain(HINSTANCE hInstance, DWORD dwReason, LPVOID pvReserved)
{
	char modname[MAX_PATH] = {0};
	if (GetModuleFileName(NULL, modname, sizeof(modname)) > 0) {
		std::string path(modname);
		std::string::size_type pos = path.find_last_of("\\");

		if (pos != std::string::npos) {
			path = path.substr(pos + 1, std::string::npos);
		}

		if (path.find("Dbgview") != std::string::npos) {
			return FALSE;
		}
	}

    switch (dwReason)
    {
        case DLL_PROCESS_ATTACH:

            g_hInst = hInstance;

            if (!InitializeCriticalSectionAndSpinCount(&g_cs, 0))
                return FALSE;


            CPropertyPopupWindow::StaticInit();

            break;

        case DLL_PROCESS_DETACH:

            DeleteCriticalSection(&g_cs);

            break;
    }

    return TRUE;
}
