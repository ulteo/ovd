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
typedef void (*remove_hooks_proc_t) ();

//NOTE: Better solution on searching process by name
//	http://stackoverflow.com/questions/865152/how-can-i-get-a-process-handle-by-its-name-in-c
bool getExplorerProcessHandle(HANDLE* hProcExplorer)
{
	DWORD aProcesses[1024]; 
	DWORD cbNeeded; 
	DWORD cProcesses;

	// Get the list of process identifiers.
	if ( EnumProcesses( aProcesses, sizeof(aProcesses), &cbNeeded ) )
	{
		// Calculate how many process identifiers were returned.
		cProcesses = cbNeeded / sizeof(DWORD);

		// Get process Explorer
		for (int i = 0; i < cProcesses; i++ )
		{
			DWORD processID = aProcesses[i];
			HANDLE hProcess;
			hProcess = OpenProcess( PROCESS_QUERY_INFORMATION |
								PROCESS_VM_READ,
								FALSE, processID );
			if (hProcess)
			{
				WCHAR szProcName[MAX_PATH];
				if (GetModuleFileNameExW(hProcess, 0, szProcName, MAX_PATH))
				{
					//strip and check "Explorer.EXE" ran
					PathStripPathW(szProcName);
					if(	wcscmp(szProcName, EXPLORER) == 0 || 
						wcscmp(szProcName, EXPLORER_L) == 0)
					{
						*hProcExplorer = hProcess;
						return true;
					}
				}
				CloseHandle(hProcess);
			}
		}
	}

	return false;
}

bool isHookDllLoaded(HANDLE hProcess)
{
	HMODULE hMods[1024];
    DWORD cbNeeded;

	if( EnumProcessModules(hProcess, hMods, sizeof(hMods), &cbNeeded))
    {
        for ( unsigned int i = 0; i < (cbNeeded / sizeof(HMODULE)); i++ )
        {
            WCHAR szModName[MAX_PATH];

            // Get the full path to the module's file.
            if ( GetModuleFileNameExW( hProcess, hMods[i], szModName,
                                      sizeof(szModName) / sizeof(TCHAR)))
            {				
				//strip and check "VFSHook.dll" loaded
				PathStripPathW(szModName);
				if(wcscmp(szModName, HOOK_DLL) == 0)
				{
					return true;
				}
            }
        }
    }

	return false;
}

//Refresh desktop on Explorer hooked
void waitDesktopRefresh()
{
	/*
	int conti = 5;
	
	HANDLE hProcExplorer = NULL;

	while(conti--)
	{
		if( !hProcExplorer )
		{
			getExplorerProcessHandle(&hProcExplorer);
			printf("Found Explorer process.\n");
		}		
		else
		{
			if( isHookDllLoaded(hProcExplorer) )
			{
				printf("Hook dll loaded.\n");
				break;
			}
		}

		//wait 1 sec before next step
		Sleep(1000);
	}

	if(hProcExplorer)
	{
		CloseHandle(hProcExplorer);
	}
	*/
	
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
#include <stdlib.h>
#include <string>
#include <iostream>
int main()
{
	ShowWindow( GetConsoleWindow(), SW_HIDE );
	
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

		/*
		char exit('/0');
		std::cin >> exit;

		if(exit != '/0')
		{
			run = false;
		}
		*/
	}
		
	remove_hooks_fn();

	FreeLibrary(hookdll);

	return 0;
}