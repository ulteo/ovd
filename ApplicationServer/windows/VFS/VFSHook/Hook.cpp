#include "stdafx.h"
#include "Hook.h"
#include "mhook-lib/mhook.h"
#include <stdio.h>
#include <ctime>
#include <windows.h>

#if _WIN32 || _WIN64
#if _WIN64
#define LOG_FILE "D:\\HookTest\\HookLog64.txt"
#else
#define LOG_FILE "D:\\HookTest\\HookLog32.txt"
#endif //#if _WIN64
#endif //#if _WIN32 || _WIN64

////////////////////////////////////////////////////////////////////////
//	Function Redefine
////////////////////////////////////////////////////////////////////////

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
	//log("CreateFileA: filename=%s", lpFileName);

	if(strcmp(lpFileName, "D:\\HookTest\\AppOutput.txt") == 0)
	{
		lpFileName = "D:\\HookTest\\AppOutput_REDIRECT.txt";
	}
	return OriginCreateFileA(lpFileName, dwDesiredAccess, dwShareMode, lpSecurityAttributes,
		dwCreationDisposition, dwFlagsAndAttributes, hTemplateFile);
}

////////////////////////////////////////////////////////////////////////
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
	char fname[MAX_PATH];
	WideCharToMultiByte( CP_ACP, 0, lpFileName, -1, fname, sizeof(fname), NULL, NULL);  

	if(wcscmp(lpFileName, L"D:\\HookTest\\AppOutput.txt") == 0)
	{
		lpFileName = L"D:\\HookTest\\AppOutput_REDIRECT.txt";
	}
	//log("CreateFileW: filename=%s", fname);

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
	log("WriteFileEx: handle=%x", hFile);

	return WriteFileEx(hFile, lpBuffer, nNumberOfBytesToWrite, lpOverlapped, lpCompletionRoutine);
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

void setupHooks()
{
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

	log("Hooked");
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

	log("UnHooked");
}

void log(char *fmt,...)
{
	va_list args;
	char modname[200];

	char temp[5000];
	HANDLE hFile;

	GetModuleFileNameA(NULL, modname, sizeof(modname));

	if((hFile = OriginCreateFileA(LOG_FILE, GENERIC_WRITE, 0, NULL, OPEN_ALWAYS, FILE_ATTRIBUTE_NORMAL, NULL)) <0)
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
		timeinfo->tm_mon, 
		timeinfo->tm_mday,
		timeinfo->tm_hour, 
		timeinfo->tm_min, 
		timeinfo->tm_sec);
	OriginWriteFile(hFile, temp, strlen(temp), &dw, NULL);

	wsprintfA(temp, "%s : ", modname);
	OriginWriteFile(hFile, temp, strlen(temp), &dw, NULL);
	
	va_start(args,fmt);
	vsprintf_s(temp, fmt, args);
	va_end(args);
	OriginWriteFile(hFile, temp, strlen(temp), &dw, NULL);

	wsprintfA(temp, "\r\n");
	OriginWriteFile(hFile, temp, strlen(temp), &dw, NULL);


	_lclose((HFILE)hFile);
}
