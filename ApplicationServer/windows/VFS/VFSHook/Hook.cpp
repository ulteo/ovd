#include "stdafx.h"

#include "Hook.h"
#include "mhook-lib/mhook.h"

#include <stdio.h>
#include <windows.h>
#include <ctime>
#include <shlobj.h> 
#include <string>

//#include <boost/filesystem.hpp>

#if _WIN32 || _WIN64
#if _WIN64
#define LOG_FILE "D:\\HookTest\\HookLog64.txt"
#else
//#define LOG_FILE "D:\\HookTest\\HookLog32.txt"
#define LOG_FILE "U:\\HookLog32.txt"
#endif //#if _WIN64
#endif //#if _WIN32 || _WIN64

//#define REDIRECT_PATH	L"D:\\HookTest"
#define REDIRECT_PATH	L"U:\\"
#define	SEPERATOR		L"\\"
#define DESKTOP_FOLDER	L"Desktop"
#define DOCUMENT_FOLDER	L"Documents"

static bool gs_Logging = false;
////////////////////////////////////////////////////////////////////////
//	File system related API
//
//	Winodws Local File Systems referece:
//		http://msdn.microsoft.com/en-us/library/windows/desktop/aa364407(v=vs.85).aspx
//	Directory Management Functions:
//		http://msdn.microsoft.com/en-us/library/windows/desktop/aa363950(v=vs.85).aspx
//	File Management Functions:
//		http://msdn.microsoft.com/en-us/library/windows/desktop/aa364232(v=vs.85).aspx
//	Disk Management Functions:
//		http://msdn.microsoft.com/en-us/library/windows/desktop/aa363983(v=vs.85).aspx
////////////////////////////////////////////////////////////////////////

bool filter(LPCWSTR lpFilePath, 
			std::wstring szTargetPath, 
			std::wstring szRedirectPath, 
			std::wstring* pszOutputPath)
{
	bool bRedirected = false;
	std::wstring szResult(lpFilePath);//reusult == input path by default

	std::wstring szFileTargetRoot = std::wstring(lpFilePath).substr(0, szTargetPath.length());

	//is in target folder
	if( wcscmp(szFileTargetRoot.c_str(), szTargetPath.c_str()) == 0 )
	{
		bRedirected = true;
		//is children folder/file of the target folder
		if(std::wstring(lpFilePath).length() > szFileTargetRoot.length())
		{
			std::wstring szRemainPath = std::wstring(lpFilePath).substr(szTargetPath.length() + 1);
			szResult = szRedirectPath + SEPERATOR + szRemainPath;
		}
		//is target folder
		else 
		{
			szResult = szTargetPath;
		}
	}

	*pszOutputPath = szResult;
	return bRedirected;
}
////////////////////////////////////////////////////////////////////////
//CreateFileA
typedef HANDLE (WINAPI* _pCreateFileA)(LPCSTR lpFileName, 
									   DWORD dwDesiredAccess, 
									   DWORD dwShareMode, 
									   LPSECURITY_ATTRIBUTES lpSecurityAttributes, 
									   DWORD dwCreationDisposition, 
									   DWORD dwFlagsAndAttributes, 
									   HANDLE hTemplateFile);
_pCreateFileA OriginCreateFileA = (_pCreateFileA)GetProcAddress(GetModuleHandle(L"Kernel32"), "CreateFileA");
HANDLE WINAPI myCreateFileA(LPCSTR lpFileName, 
							DWORD dwDesiredAccess, 
							DWORD dwShareMode, 
							LPSECURITY_ATTRIBUTES lpSecurityAttributes, 
							DWORD dwCreationDisposition, 
							DWORD dwFlagsAndAttributes, 
							HANDLE hTemplateFile)
{          
	if(gs_Logging)
	{
		return OriginCreateFileA(lpFileName, dwDesiredAccess, dwShareMode, lpSecurityAttributes,
			dwCreationDisposition, dwFlagsAndAttributes, hTemplateFile);
	}

	log("CreateFileA: filename=%s", lpFileName);

	return OriginCreateFileA(lpFileName, dwDesiredAccess, dwShareMode, lpSecurityAttributes,
		dwCreationDisposition, dwFlagsAndAttributes, hTemplateFile);
}

//CreateFileW
typedef HANDLE (WINAPI* _pCreateFileW)(LPCWSTR lpFileName, 
									   DWORD dwDesiredAccess, 
									   DWORD dwShareMode, 
									   LPSECURITY_ATTRIBUTES lpSecurityAttributes, 
									   DWORD dwCreationDisposition, 
									   DWORD dwFlagsAndAttributes, 
									   HANDLE hTemplateFile);
_pCreateFileW OriginCreateFileW = (_pCreateFileW)GetProcAddress(GetModuleHandle(L"Kernel32"), "CreateFileW");
HANDLE WINAPI myCreateFileW(LPCWSTR lpFileName, 
							DWORD dwDesiredAccess, 
							DWORD dwShareMode, 
							LPSECURITY_ATTRIBUTES lpSecurityAttributes, 
							DWORD dwCreationDisposition, 
							DWORD dwFlagsAndAttributes, 
							HANDLE hTemplateFile)
{	
	if(gs_Logging)
	{
		return OriginCreateFileW(lpFileName, dwDesiredAccess, dwShareMode, lpSecurityAttributes,
			dwCreationDisposition, dwFlagsAndAttributes, hTemplateFile);
	}

	char fname[MAX_PATH];
	WideCharToMultiByte( CP_ACP, 0, lpFileName, -1, fname, sizeof(fname), NULL, NULL);  
	log("CreateFileW: filename=%s", fname);
	
	// target path : Desktop & Document
	WCHAR szDesktop[MAX_PATH];
	WCHAR szDocumnet[MAX_PATH];
	SHGetSpecialFolderPath(NULL, szDesktop, CSIDL_DESKTOP, 0);
	SHGetSpecialFolderPath(NULL, szDocumnet, CSIDL_PERSONAL, 0);
	
	std::wstring out;
	if ( filter(
		lpFileName, szDesktop, std::wstring(REDIRECT_PATH) + SEPERATOR + DESKTOP_FOLDER, &out) )
	{
		lpFileName = out.c_str();
	}	
	else if( filter(
		lpFileName, szDocumnet, std::wstring(REDIRECT_PATH) + SEPERATOR + DOCUMENT_FOLDER, &out) )
	{
		lpFileName = out.c_str();
	}	

	return OriginCreateFileW(lpFileName, dwDesiredAccess, dwShareMode, lpSecurityAttributes,
		dwCreationDisposition, dwFlagsAndAttributes, hTemplateFile);
}

////////////////////////////////////////////////////////////////////////
//DeleteFileA
typedef BOOL (WINAPI* _pDeleteFileA)(LPCSTR lpFileName);
_pDeleteFileA OriginDeleteFileA = (_pDeleteFileA)GetProcAddress(GetModuleHandle(L"Kernel32"), "DeleteFileA");
BOOL WINAPI myDeleteFileA(LPCSTR lpFileName)
{
	log("DeleteFileA: filename=%s", lpFileName);

	return OriginDeleteFileA(lpFileName);
}
////////////////////////////////////////////////////////////////////////
//DeleteFileW
typedef BOOL (WINAPI* _pDeleteFileW)(LPCWSTR lpFileName);
_pDeleteFileW OriginDeleteFileW = (_pDeleteFileW)GetProcAddress(GetModuleHandle(L"Kernel32"), "DeleteFileW");
BOOL WINAPI myDeleteFileW(LPCWSTR lpFileName)
{
	char fname[MAX_PATH];
	WideCharToMultiByte( CP_ACP, 0, lpFileName, -1, fname, sizeof(fname), NULL, NULL);  

	log("DeleteFileW: filename=%s", fname);

	return OriginDeleteFileW(lpFileName);
}
////////////////////////////////////////////////////////////////////////
//ReadFile
typedef BOOL (WINAPI* _pReadFile)(HANDLE hFile, 
								  LPVOID lpBuffer, 
								  DWORD nNumberOfBytesToRead, 
								  LPDWORD lpNumberOfBytesRead, 
								  LPOVERLAPPED lpOverlapped);
_pReadFile OriginReadFile = (_pReadFile)GetProcAddress(GetModuleHandle(L"Kernel32"), "ReadFile");
BOOL WINAPI myReadFile(HANDLE hFile, 
					   LPVOID lpBuffer, 
					   DWORD nNumberOfBytesToRead, 
					   LPDWORD lpNumberOfBytesRead, 
					   LPOVERLAPPED lpOverlapped)
{
	log("ReadFile: handle=%x", hFile);

	return OriginReadFile(hFile, lpBuffer, nNumberOfBytesToRead, lpNumberOfBytesRead, lpOverlapped);
}
////////////////////////////////////////////////////////////////////////
//ReadFileEx
typedef BOOL (WINAPI* _pReadFileEx)(HANDLE hFile, 
									LPVOID lpBuffer, 
									DWORD nNumberOfBytesToRead,
									LPOVERLAPPED lpOverlapped, 
									LPOVERLAPPED_COMPLETION_ROUTINE lpCompletionRoutine);
_pReadFileEx OriginReadFileEx = (_pReadFileEx)GetProcAddress(GetModuleHandle(L"Kernel32"), "ReadFileEx");
BOOL WINAPI myReadFileEx(HANDLE hFile, 
						 LPVOID lpBuffer, 
						 DWORD nNumberOfBytesToRead,
						 LPOVERLAPPED lpOverlapped, 
						 LPOVERLAPPED_COMPLETION_ROUTINE lpCompletionRoutine)
{
	log("ReadFileEx: handle=%x", hFile);

	return OriginReadFileEx(hFile, lpBuffer, nNumberOfBytesToRead, lpOverlapped, lpCompletionRoutine);
}	
////////////////////////////////////////////////////////////////////////
//WriteFile
typedef BOOL (WINAPI* _pWriteFile)(HANDLE hFile, 
								   LPCVOID lpBuffer, 
								   DWORD nNumberOfBytesToWrite,
								   LPDWORD lpNumberOfBytesWritten, 
								   LPOVERLAPPED lpOverlapped);
_pWriteFile OriginWriteFile = (_pWriteFile)GetProcAddress(GetModuleHandle(L"Kernel32"), "WriteFile");
BOOL WINAPI myWriteFile(HANDLE hFile, 
						LPCVOID lpBuffer, 
						DWORD nNumberOfBytesToWrite,
						LPDWORD lpNumberOfBytesWritten, 
						LPOVERLAPPED lpOverlapped)
{
	if(gs_Logging)
	{
		return OriginWriteFile(hFile, lpBuffer, nNumberOfBytesToWrite, lpNumberOfBytesWritten, lpOverlapped);
	}

	log("WriteFile: handle=%x", hFile);

	return OriginWriteFile(hFile, lpBuffer, nNumberOfBytesToWrite, lpNumberOfBytesWritten, lpOverlapped);
}
////////////////////////////////////////////////////////////////////////
//WriteFileEx
typedef BOOL (WINAPI* _pWriteFileEx)(HANDLE hFile, 
									 LPCVOID lpBuffer, 
									 DWORD nNumberOfBytesToWrite,
									 LPOVERLAPPED lpOverlapped, 
									 LPOVERLAPPED_COMPLETION_ROUTINE lpCompletionRoutine);
_pWriteFileEx OriginWriteFileEx = (_pWriteFileEx)GetProcAddress(GetModuleHandle(L"Kernel32"), "WriteFileEx");
BOOL WINAPI myWriteFileEx(HANDLE hFile, 
						  LPCVOID lpBuffer, 
						  DWORD nNumberOfBytesToWrite,
						  LPOVERLAPPED lpOverlapped, 
						  LPOVERLAPPED_COMPLETION_ROUTINE lpCompletionRoutine)
{
	if(gs_Logging)
	{
		return OriginWriteFileEx(hFile, lpBuffer, nNumberOfBytesToWrite, lpOverlapped, lpCompletionRoutine);
	}

	log("WriteFileEx: handle=%x", hFile);

	return OriginWriteFileEx(hFile, lpBuffer, nNumberOfBytesToWrite, lpOverlapped, lpCompletionRoutine);
}
////////////////////////////////////////////////////////////////////////
//CreateProcessW
typedef DWORD (WINAPI* _pCreateProcessW)(LPCWSTR lpApplicationName,
									  LPWSTR lpCommandLine, 
									  LPSECURITY_ATTRIBUTES lpProcessAttributes,
									  LPSECURITY_ATTRIBUTES lpThreadAttributes,
									  BOOL bInheritHandles,
									  DWORD dwCreationFlags,
									  LPVOID lpEnvironment,
									  LPCWSTR lpCurrentDirectory,
									  LPSTARTUPINFOW lpStartupInfo,
									  LPPROCESS_INFORMATION lpProcessInformation);
_pCreateProcessW OriginCreateProcessW = (_pCreateProcessW)GetProcAddress(GetModuleHandle(L"Kernel32"), "CreateProcessW");
DWORD WINAPI myCreateProcessW(LPCWSTR lpApplicationName,
							  LPWSTR lpCommandLine, 
							  LPSECURITY_ATTRIBUTES lpProcessAttributes,
							  LPSECURITY_ATTRIBUTES lpThreadAttributes,
							  BOOL bInheritHandles,
							  DWORD dwCreationFlags,
							  LPVOID lpEnvironment,
							  LPCWSTR lpCurrentDirectory,
							  LPSTARTUPINFOW lpStartupInfo,
							  LPPROCESS_INFORMATION lpProcessInformation)
{	
	char fcmd[MAX_PATH];
	WideCharToMultiByte( CP_ACP, 0, lpCommandLine, -1, fcmd, sizeof(fcmd), NULL, NULL);  

	log("CreateProcessW :cmd=%s", fcmd);

	BOOL ifsuccess = OriginCreateProcessW
		(lpApplicationName, 
		lpCommandLine, 
		lpProcessAttributes,	
		lpThreadAttributes, 
		bInheritHandles, 
		dwCreationFlags, 
		lpEnvironment,
		lpCurrentDirectory, 
		lpStartupInfo, 
		lpProcessInformation);

	DWORD err =GetLastError();
	SetLastError(err);
	
	return static_cast<DWORD>(ifsuccess);
}
////////////////////////////////////////////////////////////////////////
//CreateProcessA
typedef DWORD (WINAPI* _pCreateProcessA)(
									  LPCSTR lpApplicationName,
									  LPSTR lpCommandLine, 
									  LPSECURITY_ATTRIBUTES lpProcessAttributes,
									  LPSECURITY_ATTRIBUTES lpThreadAttributes,
									  BOOL bInheritHandles,
									  DWORD dwCreationFlags,
									  LPVOID lpEnvironment,
									  LPCSTR lpCurrentDirectory,
									  LPSTARTUPINFO lpStartupInfo,
									  LPPROCESS_INFORMATION lpProcessInformation);
_pCreateProcessA OriginCreateProcessA = (_pCreateProcessA)GetProcAddress(GetModuleHandle(L"Kernel32"), "CreateProcessA");
DWORD WINAPI myCreateProcessA(LPCSTR lpApplicationName,
							  LPSTR lpCommandLine, 
							  LPSECURITY_ATTRIBUTES lpProcessAttributes,
							  LPSECURITY_ATTRIBUTES lpThreadAttributes,
							  BOOL bInheritHandles,
							  DWORD dwCreationFlags,
							  LPVOID lpEnvironment,
							  LPCSTR lpCurrentDirectory,
							  LPSTARTUPINFO lpStartupInfo,
							  LPPROCESS_INFORMATION lpProcessInformation)
{
	log("CreateProcessA :cmd=%s", lpCommandLine);

	BOOL ifsuccess = OriginCreateProcessA(
		lpApplicationName,
		lpCommandLine, 
		lpProcessAttributes,
		lpThreadAttributes, 
		bInheritHandles, 
		dwCreationFlags, 
		lpEnvironment,
		lpCurrentDirectory, 
		lpStartupInfo, 
		lpProcessInformation);

	DWORD err =GetLastError();
	SetLastError(err);

	return static_cast<DWORD>(ifsuccess);
}

////////////////////////////////////////////////////////////////////////
//	Registry related API
//	Registry Functions:
//		http://msdn.microsoft.com/en-us/library/windows/desktop/ms724875(v=vs.85).aspx
////////////////////////////////////////////////////////////////////////
char *GetRootKey(HKEY hKey)
{
	if(hKey == HKEY_CLASSES_ROOT)
		return "HKEY_CLASSES_ROOT";
	else if(hKey == HKEY_CURRENT_CONFIG)
		return "KEY_CURRENT_CONFIG";
	else if(hKey ==HKEY_CURRENT_USER)
		return "HKEY_CURRENT_USER";
	else if(hKey == HKEY_LOCAL_MACHINE)
		return "HKEY_LOCAL_MACHINE";
	else if(hKey == HKEY_USERS)
		return "HKEY_USERS";
	else if(hKey == HKEY_PERFORMANCE_DATA)
		return "HKEY_PERFORMANCE_DATA";
	else
		return "UNKNOWN_KEY";
}
////////////////////////////////////////////////////////////////////////
//RegOpenKeyA
typedef DWORD (WINAPI* _pRegOpenKeyA)(
									HKEY hKey, 
									LPCSTR lpSubKey, 
									PHKEY phkResult);
_pRegOpenKeyA OriginRegOpenKeyA = (_pRegOpenKeyA)GetProcAddress(GetModuleHandle(L"Advapi32"), "RegOpenKeyA");
DWORD WINAPI myRegOpenKeyA(HKEY hKey, LPCSTR lpSubKey, PHKEY phkResult)
{
	log("RegOpenKeyA: hKey=%s, SubKey=%s", GetRootKey(hKey), lpSubKey);

	return OriginRegOpenKeyA(hKey, lpSubKey, phkResult);
}
////////////////////////////////////////////////////////////////////////
//RegOpenKeyW
typedef DWORD (WINAPI* _pRegOpenKeyW)(
									HKEY hKey, 
									LPCWSTR lpSubKey, 
									PHKEY phkResult);
_pRegOpenKeyW OriginRegOpenKeyW = (_pRegOpenKeyW)GetProcAddress(GetModuleHandle(L"Advapi32"), "RegOpenKeyW");
DWORD WINAPI myRegOpenKeyW(HKEY hKey, LPCWSTR lpSubKey, PHKEY phkResult)
{
	char subkey[200];
	int len =WideCharToMultiByte( CP_ACP, 0, lpSubKey, -1, subkey, sizeof(subkey),NULL,NULL); 
	subkey[len] =0;
	log("RegOpenKeyW: hKey=%s, SubKey=%s", GetRootKey(hKey), subkey);

	return OriginRegOpenKeyW(hKey, lpSubKey, phkResult);
}
////////////////////////////////////////////////////////////////////////
//RegOpenKeyExA
typedef DWORD (WINAPI* _pRegOpenKeyExA)(
									HKEY hKey,
									LPCSTR lpSubKey,
									DWORD ulOptions,
									REGSAM samDesired,
									PHKEY phkResult);
_pRegOpenKeyExA OriginRegOpenKeyExA = (_pRegOpenKeyExA)GetProcAddress(GetModuleHandle(L"Advapi32"), "RegOpenKeyExA");
DWORD WINAPI myRegOpenKeyExA(
						HKEY hKey,			
						LPCSTR lpSubKey,
						DWORD ulOptions,
						REGSAM samDesired,
						PHKEY phkResult)
{
	log("RegOpenKeyExA: hKey=%s, SubKey=%s", GetRootKey(hKey), lpSubKey);

	return OriginRegOpenKeyExA(hKey, lpSubKey, ulOptions, samDesired, phkResult);
}
////////////////////////////////////////////////////////////////////////
//RegOpenKeyExA
typedef DWORD (WINAPI* _pRegOpenKeyExW)(
									HKEY hKey,
									LPCWSTR lpSubKey,
									DWORD ulOptions,
									REGSAM samDesired,
									PHKEY phkResult);
_pRegOpenKeyExW OriginRegOpenKeyExW = (_pRegOpenKeyExW)GetProcAddress(GetModuleHandle(L"Advapi32"), "RegOpenKeyExW");
DWORD WINAPI myRegOpenKeyExW(
						HKEY hKey,
						LPCWSTR lpSubKey,
						DWORD ulOptions,
						REGSAM samDesired,
						PHKEY phkResult)
{
	char subkey[200];
	int len =WideCharToMultiByte( CP_ACP, 0, lpSubKey, -1, subkey, sizeof(subkey),NULL,NULL); 
	subkey[len] =0;
	log("RegOpenKeyExW: hKey=%s, SubKey=%s", GetRootKey(hKey), lpSubKey);

	return OriginRegOpenKeyExW(hKey, lpSubKey, ulOptions, samDesired, phkResult);
}
////////////////////////////////////////////////////////////////////////
//RegQueryValueA
typedef DWORD (WINAPI* _pRegQueryValueA)(
									HKEY hKey, 
									LPCSTR lpSubKey, 
									LPSTR lpValue, 
									PLONG lpcbValue);
_pRegQueryValueA OriginRegQueryValueA = (_pRegQueryValueA)GetProcAddress(GetModuleHandle(L"Advapi32"), "RegQueryValueA");
DWORD WINAPI myRegQueryValueA(HKEY hKey, LPCSTR lpSubKey, LPSTR lpValue, PLONG lpcbValue)
{
	log("RegQueryValueA: hKey=%s, SubKey=%s", GetRootKey(hKey), lpSubKey);

	return OriginRegQueryValueA(hKey, lpSubKey, lpValue, lpcbValue);
}
////////////////////////////////////////////////////////////////////////
//RegQueryValueW
typedef DWORD (WINAPI* _pRegQueryValueW)(
									HKEY hKey,
									LPCWSTR lpSubKey, 
									LPWSTR lpValue, 
									PLONG lpcbValue);
_pRegQueryValueW OriginRegQueryValueW = (_pRegQueryValueW)GetProcAddress(GetModuleHandle(L"Advapi32"), "RegQueryValueW");
DWORD WINAPI myRegQueryValueW(HKEY hKey,LPCWSTR lpSubKey, LPWSTR lpValue, PLONG lpcbValue)
{
	char subkey[200];
	int len =WideCharToMultiByte( CP_ACP, 0, lpSubKey, -1, subkey, sizeof(subkey),NULL,NULL); 
	subkey[len] =0;
	log("RegQueryValueW: hKey=%s, SubKey=%s", GetRootKey(hKey), subkey);

	return OriginRegQueryValueW(hKey, lpSubKey, lpValue, lpcbValue);
}
////////////////////////////////////////////////////////////////////////
//RegQueryValueExA
typedef DWORD (WINAPI* _pRegQueryValueExA)(
										HKEY hKey, 
										LPSTR lpValueName, 
										LPDWORD lpReserved, 
										LPDWORD lpType, 
										LPBYTE lpData, 
										LPDWORD lpcbData);
_pRegQueryValueExA OriginRegQueryValueExA = (_pRegQueryValueExA)GetProcAddress(GetModuleHandle(L"Advapi32"), "RegQueryValueExA");
DWORD WINAPI myRegQueryValueExA(HKEY hKey, 
								LPSTR lpValueName, 
								LPDWORD lpReserved, 
								LPDWORD lpType, 
								LPBYTE lpData, 
								LPDWORD lpcbData)
{
	log("RegQueryValueExA:hKey=%s,ValueName=%s", GetRootKey(hKey), lpValueName);
		
	return OriginRegQueryValueExA(hKey, lpValueName, lpReserved, lpType, lpData, lpcbData);
}
////////////////////////////////////////////////////////////////////////
//RegQueryValueExW
typedef DWORD (WINAPI* _pRegQueryValueExW)(
										HKEY hKey, 
										LPWSTR lpValueName, 
										LPDWORD lpReserved, 
										LPDWORD lpType, 
										LPBYTE lpData, 
										LPDWORD lpcbData);
_pRegQueryValueExW OriginRegQueryValueExW = (_pRegQueryValueExW)GetProcAddress(GetModuleHandle(L"Advapi32"), "RegQueryValueExW");
DWORD WINAPI myRegQueryValueExW(
								HKEY hKey, 
								LPWSTR lpValueName,
								LPDWORD lpReserved, 
								LPDWORD lpType, 
								LPBYTE lpData, 
								LPDWORD lpcbData)
{
	char value[200];
	int len =WideCharToMultiByte( CP_ACP, 0, lpValueName, -1, value, sizeof(value),NULL,NULL); 
	value[len] =0;
	log("RegQueryValueExW: hKey=%s, ValueName=%s", GetRootKey(hKey), value);
	
	return OriginRegQueryValueExW(hKey, lpValueName, lpReserved, lpType, lpData, lpcbData);
}

////////////////////////////////////////////////////////////////////////
//	Util Functions:
////////////////////////////////////////////////////////////////////////
void setupHooks()
{
	//File system APIs
	if (!Mhook_SetHook((PVOID*)&OriginCreateFileA, myCreateFileA)) 
	{
		log("Failed to hook CreateFileA");
	}
	if (!Mhook_SetHook((PVOID*)&OriginCreateFileW, myCreateFileW)) 
	{
		log("Failed to hook CreateFileW");
	}
	if (!Mhook_SetHook((PVOID*)&OriginDeleteFileA, myDeleteFileA)) 
	{
		log("Failed to hook DeleteFileA");
	}
	if (!Mhook_SetHook((PVOID*)&OriginDeleteFileW, myDeleteFileW)) 
	{
		log("Failed to hook DeleteFileW");
	}
	if (!Mhook_SetHook((PVOID*)&OriginReadFile, myReadFile)) 
	{
		log("Failed to hook ReadFile");
	}
	if (!Mhook_SetHook((PVOID*)&OriginReadFileEx, myReadFileEx)) 
	{
		log("Failed to hook ReadFileEx");
	}
	if (!Mhook_SetHook((PVOID*)&OriginWriteFile, myWriteFile)) 
	{
		log("Failed to hook WriteFile");
	}
	if (!Mhook_SetHook((PVOID*)&OriginWriteFileEx, myWriteFileEx)) 
	{
		log("Failed to hook WriteFileEx");
	}
	if (!Mhook_SetHook((PVOID*)&OriginCreateProcessA, myCreateProcessA)) 
	{
		log("Failed to hook CreateProcessA");
	}
	if (!Mhook_SetHook((PVOID*)&OriginCreateProcessW, myCreateProcessW)) 
	{
		log("Failed to hook CreateProcessW");
	}

	//Reg APIs
	if (!Mhook_SetHook((PVOID*)&OriginRegOpenKeyA, myRegOpenKeyA)) 
	{
		log("Failed to hook RegOpenKeyA");
	}
	if (!Mhook_SetHook((PVOID*)&OriginRegOpenKeyW, myRegOpenKeyW)) 
	{
		log("Failed to hook RegOpenKeyW");
	}
	if (!Mhook_SetHook((PVOID*)&OriginRegOpenKeyExA, myRegOpenKeyExA)) 
	{
		log("Failed to hook RegOpenKeyExA");
	}
	if (!Mhook_SetHook((PVOID*)&OriginRegOpenKeyExW, myRegOpenKeyExW)) 
	{
		log("Failed to hook RegOpenKeyExW");
	}	
	if (!Mhook_SetHook((PVOID*)&OriginRegQueryValueA, myRegQueryValueA)) 
	{
		log("Failed to hook RegQueryValueA");
	}
	if (!Mhook_SetHook((PVOID*)&OriginRegQueryValueW, myRegQueryValueW)) 
	{
		log("Failed to hook RegQueryValueW");
	}
	if (!Mhook_SetHook((PVOID*)&OriginRegQueryValueExA, myRegQueryValueExA)) 
	{
		log("Failed to hook RegQueryValueExA");
	}
	if (!Mhook_SetHook((PVOID*)&OriginRegQueryValueExW, myRegQueryValueExW)) 
	{
		log("Failed to hook RegQueryValueExW");
	}
	
	log(" ======= Hooked =======");
}

void releaseHooks()
{
	Mhook_Unhook((PVOID*)&OriginCreateFileA);
	Mhook_Unhook((PVOID*)&OriginCreateFileW);	
	Mhook_Unhook((PVOID*)&OriginDeleteFileA);
	Mhook_Unhook((PVOID*)&OriginDeleteFileW);
	Mhook_Unhook((PVOID*)&OriginReadFile);
	Mhook_Unhook((PVOID*)&OriginReadFileEx);
	Mhook_Unhook((PVOID*)&OriginWriteFile);
	Mhook_Unhook((PVOID*)&OriginWriteFileEx);
	Mhook_Unhook((PVOID*)&OriginCreateProcessA);
	Mhook_Unhook((PVOID*)&OriginCreateProcessW);
	
	Mhook_Unhook((PVOID*)&OriginRegOpenKeyA);
	Mhook_Unhook((PVOID*)&OriginRegOpenKeyW);
	Mhook_Unhook((PVOID*)&OriginRegOpenKeyExA);
	Mhook_Unhook((PVOID*)&OriginRegOpenKeyExW);	
	Mhook_Unhook((PVOID*)&OriginRegQueryValueA);
	Mhook_Unhook((PVOID*)&OriginRegQueryValueW);
	Mhook_Unhook((PVOID*)&OriginRegQueryValueExA);
	Mhook_Unhook((PVOID*)&OriginRegQueryValueExW);

	log(" ======= UnHooked =======");
}

void log(char *fmt,...)
{
	gs_Logging = true;

	va_list args;
	char modname[200];

	char temp[5000];
	HANDLE hFile;

	GetModuleFileNameA(NULL, modname, sizeof(modname));

	if((hFile = CreateFileA(LOG_FILE, GENERIC_WRITE, 0, NULL, OPEN_ALWAYS, FILE_ATTRIBUTE_NORMAL, NULL)) <0)
	{
		return;
	}
	
	_llseek((HFILE)hFile, 0, SEEK_END);

	
	DWORD dw;

	time_t * rawtime = new time_t;
	struct tm * timeinfo;
	time(rawtime);
	timeinfo = localtime(rawtime);
	wsprintfA(temp, "[%d/%02d/%02d %02d:%02d:%02d] ", 
		timeinfo->tm_year + 1900, 
		timeinfo->tm_mon + 1, 
		timeinfo->tm_mday,
		timeinfo->tm_hour, 
		timeinfo->tm_min, 
		timeinfo->tm_sec);
	WriteFile(hFile, temp, strlen(temp), &dw, NULL);

	wsprintfA(temp, "%s : ", modname);
	WriteFile(hFile, temp, strlen(temp), &dw, NULL);
	
	va_start(args,fmt);
	vsprintf_s(temp, fmt, args);
	va_end(args);
	WriteFile(hFile, temp, strlen(temp), &dw, NULL);

	wsprintfA(temp, "\r\n");
	WriteFile(hFile, temp, strlen(temp), &dw, NULL);

	_lclose((HFILE)hFile);

	gs_Logging = false;
}
