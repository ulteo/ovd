#include "stdafx.h"

#include "Hook.h"
#include "mhook-lib/mhook.h"

#include <stdlib.h>
#include <stdio.h>
#include <windows.h>
#include <ctime>
#include <shlobj.h> 
#include <shlwapi.h> 
#include <string>

#if _WIN32 || _WIN64
#if _WIN64
#define LOG_FILE "D:\\HookTest\\HookLog64.txt"
#else
#define LOG_FILE "D:\\HookTest\\HookLog32.txt"
#endif //#if _WIN64
#endif //#if _WIN32 || _WIN64

#define REDIRECT_PATH	L"D:\\HookTest"
//#define REDIRECT_PATH	L"U:"
#define	SEPERATOR		L"\\"
#define DESKTOP_FOLDER	L"Desktop"
#define DOCUMENT_FOLDER	L"Documents"
#define HOOK_AND_LOG_FAILURE(pOri, pInt, szFunc)	if(!Mhook_SetHook(pOri, pInt)){log("Failed to hook %s", szFunc);}


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

WCHAR g_szOriginDesktop[MAX_PATH] = {0};
WCHAR g_szOriginDocuments[MAX_PATH] = {0};
void obtainUserSHFolders()
{
	// target path : Desktop & Document
	SHGetSpecialFolderPath(NULL, g_szOriginDesktop, CSIDL_DESKTOP, 0);
	SHGetSpecialFolderPath(NULL, g_szOriginDocuments, CSIDL_PERSONAL, 0);
}

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
			szResult = szRedirectPath;
		}
	}

	*pszOutputPath = szResult;
	return bRedirected;
}

BOOL GetPath(POBJECT_ATTRIBUTES ObjectAttributes, WCHAR* strPath)
{
	if (!ObjectAttributes->RootDirectory && !ObjectAttributes->ObjectName)
	{
		return FALSE;
	}

	if (ObjectAttributes->RootDirectory)
	{
		//TODO: ObjectAttributes->RootDirectory
		//if (STATUS_SUCCESS != GetFullPathByHandle(ObjectAttributes->RootDirectory, strPath))
		//{
		//	return FALSE;
		//}
	}
	if (NULL != ObjectAttributes && NULL != ObjectAttributes->ObjectName && ObjectAttributes->ObjectName->Length > 0)
	{
		lstrcatW(strPath,ObjectAttributes->ObjectName->Buffer);
	}
	return TRUE;
}

void redirectUserPath(WCHAR* strTargetPath, PUNICODE_STRING* punistrPathOutput)
{	
	std::wstring out;
	WCHAR szNewP[MAX_PATH] = {0};
	lstrcatW(szNewP, L"\\??\\");
	lstrcatW(szNewP, g_szOriginDesktop);
	if ( filter(strTargetPath, szNewP, std::wstring(REDIRECT_PATH) + SEPERATOR + DESKTOP_FOLDER, &out) )
	{			
		WCHAR temp[MAX_PATH]  = {0};
		lstrcatW(temp, L"\\??\\");
		lstrcatW(temp, out.c_str());

		log("NEW filename=%S", temp);

		(*punistrPathOutput)->Buffer = temp;   
		(*punistrPathOutput)->Length = (4 + out.length()) * 2;  // 4 for \\??\\, * 2 for wchar 
		(*punistrPathOutput)->MaximumLength = (4 + out.length()) * 2 + 2; // + 2 for "/0"
	}	
	else if( filter(strTargetPath, g_szOriginDocuments, std::wstring(REDIRECT_PATH) + SEPERATOR + DOCUMENT_FOLDER, &out) )
	{
		WCHAR szModifiedPath[MAX_PATH] = {0};
		wcscpy(szModifiedPath, out.c_str());
		log("NEW filename=%S", szModifiedPath);
	}
}
//-------------------------------------------------------------------//
//NtCreateFile
typedef NTSTATUS (WINAPI* _pNtCreateFile)(	PHANDLE FileHandle,
											ACCESS_MASK DesiredAccess,
											POBJECT_ATTRIBUTES ObjectAttributes,
											PIO_STATUS_BLOCK IoStatusBlock,
											PLARGE_INTEGER AllocationSize,
											ULONG FileAttributes,
											ULONG ShareAccess,
											ULONG CreateDisposition,
											ULONG CreateOptions,
											PVOID EaBuffer,
											ULONG EaLength);
_pNtCreateFile OriginNtCreateFile = (_pNtCreateFile)GetProcAddress(GetModuleHandle(L"ntdll"), "NtCreateFile");
NTSTATUS WINAPI myNtCreateFile(	PHANDLE FileHandle,
								ACCESS_MASK DesiredAccess,
								POBJECT_ATTRIBUTES ObjectAttributes,
								PIO_STATUS_BLOCK IoStatusBlock,
								PLARGE_INTEGER AllocationSize,
								ULONG FileAttributes,
								ULONG ShareAccess,
								ULONG CreateDisposition,
								ULONG CreateOptions,
								PVOID EaBuffer,
								ULONG EaLength)
{	
	if(gs_Logging)
	{
		return OriginNtCreateFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, AllocationSize, 
			FileAttributes, ShareAccess, CreateDisposition, CreateOptions, EaBuffer, EaLength);
	}

	log("NtCreateFile: handle=%x, filename=%wZ", FileHandle, ObjectAttributes->ObjectName);

	//return OriginNtCreateFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, AllocationSize, 
	//	FileAttributes, ShareAccess, CreateDisposition, CreateOptions, EaBuffer, EaLength);
			
	PUNICODE_STRING pOriginObjectName = ObjectAttributes->ObjectName;
	WCHAR szFilePath[MAX_PATH] = {0};
	if(GetPath(ObjectAttributes, szFilePath))
	{
		redirectUserPath(szFilePath, &ObjectAttributes->ObjectName);
	}
	
	NTSTATUS stus = OriginNtCreateFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, AllocationSize, 
		FileAttributes, ShareAccess, CreateDisposition, CreateOptions, EaBuffer, EaLength);

	ObjectAttributes->ObjectName = pOriginObjectName;
	
	return stus;
}

//-------------------------------------------------------------------//
//NtQueryDirectoryFile
typedef NTSTATUS (NTAPI* _pNtQueryDirectoryFile)(	HANDLE FileHandle, 
													HANDLE Event,  
													PIO_APC_ROUTINE ApcRoutine,  
													PVOID ApcContext,
													PIO_STATUS_BLOCK IoStatusBlock, 
													PVOID FileInformation,  
													ULONG Length,  
													FILE_INFORMATION_CLASS FileInformationClass,
													BOOLEAN ReturnSingleEntry,
													PUNICODE_STRING FileName,
													BOOLEAN RestartScan );
_pNtQueryDirectoryFile OriginNtQueryDirectoryFile = (_pNtQueryDirectoryFile)GetProcAddress(GetModuleHandle(L"ntdll"), "NtQueryDirectoryFile");
NTSTATUS NTAPI myNtQueryDirectoryFile(	HANDLE FileHandle, 
										HANDLE Event,  
										PIO_APC_ROUTINE ApcRoutine,  
										PVOID ApcContext,
										PIO_STATUS_BLOCK IoStatusBlock, 
										PVOID FileInformation,  
										ULONG Length,  
										FILE_INFORMATION_CLASS FileInformationClass,
										BOOLEAN ReturnSingleEntry,
										PUNICODE_STRING FileName,
										BOOLEAN RestartScan )
{
	log("NtQueryDirectoryFile handle=%x, filename=%wZ", FileHandle, FileName);

	//NOTE: FileName here is relative path, for ex: Desktop, not C:\Users\User\Desktop
	return OriginNtQueryDirectoryFile(FileHandle, Event, ApcRoutine, ApcContext, IoStatusBlock,
		FileInformation, Length, FileInformationClass, ReturnSingleEntry, FileName,	RestartScan);
}
	
//-------------------------------------------------------------------//
//NtQueryObject
typedef NTSTATUS (NTAPI* _pNtQueryObject)(	HANDLE Handle,
											OBJECT_INFORMATION_CLASS ObjectInformationClass,
											PVOID ObjectInformation,
											ULONG ObjectInformationLength,
											PULONG ReturnLength);
_pNtQueryObject OriginNtQueryObject = (_pNtQueryObject)GetProcAddress(GetModuleHandle(L"ntdll"), "NtQueryObject");
NTSTATUS NTAPI myNtQueryObject(	HANDLE Handle, 
								OBJECT_INFORMATION_CLASS ObjectInformationClass,
								PVOID ObjectInformation,
								ULONG ObjectInformationLength,
								PULONG ReturnLength)
{
	log("NtQueryObject: handle=%x", Handle);

	return OriginNtQueryObject(Handle, ObjectInformationClass, ObjectInformation, ObjectInformationLength, ReturnLength);
}

//-------------------------------------------------------------------//
//ZwOpenFile
typedef NTSTATUS (NTAPI* _pZwOpenFile)(	PHANDLE FileHandle,
										ACCESS_MASK DesiredAccess,
										POBJECT_ATTRIBUTES ObjectAttributes,
										PIO_STATUS_BLOCK IoStatusBlock,
										ULONG ShareAccess,
										ULONG OpenOptions);
_pZwOpenFile OriginZwOpenFile = (_pZwOpenFile)GetProcAddress(GetModuleHandle(L"ntdll"), "ZwOpenFile");
NTSTATUS NTAPI myZwOpenFile(PHANDLE FileHandle,
							ACCESS_MASK DesiredAccess,
							POBJECT_ATTRIBUTES ObjectAttributes,
							PIO_STATUS_BLOCK IoStatusBlock,
							ULONG ShareAccess,
							ULONG OpenOptions)
{
	if(gs_Logging)
	{
		return OriginZwOpenFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, ShareAccess, OpenOptions);
	}

	log("ZwOpenFile: handle=%x, filename=%wZ", FileHandle, ObjectAttributes->ObjectName);

	PUNICODE_STRING pOriginObjectName = ObjectAttributes->ObjectName;
	WCHAR szFilePath[MAX_PATH] = {0};
	if(GetPath(ObjectAttributes, szFilePath))
	{
		redirectUserPath(szFilePath, &ObjectAttributes->ObjectName);
	}
	
	NTSTATUS stus = OriginZwOpenFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, ShareAccess, OpenOptions);

	ObjectAttributes->ObjectName = pOriginObjectName;
	
	log("ZwOpenFile: return stus = %ld", stus);
	
	return stus;
	
	//return OriginZwOpenFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, ShareAccess, OpenOptions);
}

//-------------------------------------------------------------------//
//ZwQuerySecurityObject
typedef NTSTATUS (NTAPI* _pZwQuerySecurityObject)(	HANDLE Handle,
													SECURITY_INFORMATION SecurityInformation,
													PSECURITY_DESCRIPTOR SecurityDescriptor,
													ULONG Length,
													PULONG LengthNeeded);
_pZwQuerySecurityObject OriginZwQuerySecurityObject = 
	(_pZwQuerySecurityObject)GetProcAddress(GetModuleHandle(L"ntdll"), "ZwQuerySecurityObject");
NTSTATUS NTAPI myZwQuerySecurityObject(	HANDLE Handle,
										SECURITY_INFORMATION SecurityInformation,
										PSECURITY_DESCRIPTOR SecurityDescriptor,
										ULONG Length,
										PULONG LengthNeeded)
{
	log("ZwQuerySecurityObject: handle=%x", Handle);

	return OriginZwQuerySecurityObject(Handle, SecurityInformation, SecurityDescriptor, Length, LengthNeeded);
}

//-------------------------------------------------------------------//
//ZwQueryAttributesFile
typedef struct _FILE_BASIC_INFORMATION {
  LARGE_INTEGER CreationTime;
  LARGE_INTEGER LastAccessTime;
  LARGE_INTEGER LastWriteTime;
  LARGE_INTEGER ChangeTime;
  ULONG         FileAttributes;
} FILE_BASIC_INFORMATION, *PFILE_BASIC_INFORMATION;

typedef NTSTATUS (NTAPI* _pZwQueryAttributesFile)(
						POBJECT_ATTRIBUTES ObjectAttributes,
						PFILE_BASIC_INFORMATION FileInformation);
_pZwQueryAttributesFile OriginZwQueryAttributesFile = (_pZwQueryAttributesFile)GetProcAddress(GetModuleHandle(L"ntdll"), "ZwQueryAttributesFile");
NTSTATUS NTAPI myZwQueryAttributesFile(	POBJECT_ATTRIBUTES ObjectAttributes,
										PFILE_BASIC_INFORMATION FileInformation)
{	
	if(gs_Logging)
	{
		return OriginZwQueryAttributesFile(ObjectAttributes, FileInformation);
	}

	log("ZwQueryAttributesFile: filename=%wZ", ObjectAttributes->ObjectName);
			

	PUNICODE_STRING pOriginObjectName = ObjectAttributes->ObjectName;
	WCHAR szFilePath[MAX_PATH] = {0};
	if(GetPath(ObjectAttributes, szFilePath))
	{
		redirectUserPath(szFilePath, &ObjectAttributes->ObjectName);
	}
	
	NTSTATUS stus = OriginZwQueryAttributesFile(ObjectAttributes, FileInformation);

	ObjectAttributes->ObjectName = pOriginObjectName;

	return stus;

	//return OriginZwQueryAttributesFile(ObjectAttributes, FileInformation);
}

//-------------------------------------------------------------------//
//ZwQueryInformationFile
typedef NTSTATUS (NTAPI* _pZwQueryInformationFile)(	HANDLE FileHandle,
													PIO_STATUS_BLOCK IoStatusBlock, 
													PVOID FileInformation,
													ULONG FileInformationLength,
													FILE_INFORMATION_CLASS FileInformationClass);
_pZwQueryInformationFile OriginZwQueryInformationFile = (_pZwQueryInformationFile)GetProcAddress(GetModuleHandle(L"ntdll"), "ZwQueryInformationFile");
NTSTATUS NTAPI myZwQueryInformationFile(HANDLE FileHandle,
										PIO_STATUS_BLOCK IoStatusBlock, 
										PVOID FileInformation,
										ULONG FileInformationLength,
										FILE_INFORMATION_CLASS FileInformationClass)
{
	if(gs_Logging)
	{
		return OriginZwQueryInformationFile(FileHandle, IoStatusBlock,  FileInformation,
			FileInformationLength, FileInformationClass);
	}

	log("ZwQueryInformationFile: handle=%x", FileHandle);

	return OriginZwQueryInformationFile(FileHandle, IoStatusBlock,  FileInformation,
			FileInformationLength, FileInformationClass);
}

//-------------------------------------------------------------------//
//ZwQueryVolumeInformationFile
typedef enum  { 
  FileFsVolumeInformation        = 1,
  FileFsLabelInformation         = 2,
  FileFsSizeInformation          = 3,
  FileFsDeviceInformation        = 4,
  FileFsAttributeInformation     = 5,
  FileFsControlInformation       = 6,
  FileFsFullSizeInformation      = 7,
  FileFsObjectIdInformation      = 8,
  FileFsDriverPathInformation    = 9,
  FileFsVolumeFlagsInformation   = 10,
  FileFsSectorSizeInformation    = 11 
} FS_INFORMATION_CLASS;

typedef NTSTATUS (NTAPI* _pZwQueryVolumeInformationFile)(
													HANDLE FileHandle,
													PIO_STATUS_BLOCK IoStatusBlock, 
													PVOID VolumeInformation,
													ULONG VolumeInformationLength,
													FS_INFORMATION_CLASS VolumeInformationClass);
_pZwQueryVolumeInformationFile OriginZwQueryVolumeInformationFile = 
	(_pZwQueryVolumeInformationFile)GetProcAddress(GetModuleHandle(L"ntdll"), "ZwQueryVolumeInformationFile");
NTSTATUS NTAPI myZwQueryVolumeInformationFile(	HANDLE FileHandle,
												PIO_STATUS_BLOCK IoStatusBlock, 
												PVOID VolumeInformation,
												ULONG VolumeInformationLength,
												FS_INFORMATION_CLASS VolumeInformationClass)
{	
	log("ZwQueryVolumeInformationFile: handle=%x", FileHandle);

	return OriginZwQueryVolumeInformationFile(FileHandle, IoStatusBlock,  VolumeInformation,
		VolumeInformationLength, VolumeInformationClass);
}

//-------------------------------------------------------------------//
//SHCreateDirectoryExA
typedef int (*_pSHCreateDirectoryExA)(HWND hWnd, LPCSTR path, LPSECURITY_ATTRIBUTES sec);
_pSHCreateDirectoryExA OriginSHCreateDirectoryExA = 
	(_pSHCreateDirectoryExA)GetProcAddress(GetModuleHandle(L"SHELL32"), "SHCreateDirectoryExA");
int mySHCreateDirectoryExA(HWND hWnd, LPCSTR path, LPSECURITY_ATTRIBUTES sec)
{
	log("SHCreateDirectoryExA: handle=%x, filename=%s", hWnd, path);

	return OriginSHCreateDirectoryExA(hWnd, path, sec);
}

//-------------------------------------------------------------------//
//PathFileExistsW
typedef BOOL (WINAPI* _pPathFileExistsW)(LPCWSTR lpszPath);
_pPathFileExistsW OriginPathFileExistsW = 
	(_pPathFileExistsW)GetProcAddress(GetModuleHandle(L"Shlwapi"), "PathFileExistsW");
BOOL WINAPI myPathFileExistsW(LPCWSTR lpszPath)
{
	log("PathFileExistsW: filename=%s", lpszPath);

	return OriginPathFileExistsW(lpszPath);
}

//-------------------------------------------------------------------//
//GetFileInformationByHandleEx
typedef enum _FILE_INFO_BY_HANDLE_CLASS { 
  FileBasicInfo                    = 0,
  FileStandardInfo                 = 1,
  FileNameInfo                     = 2,
  FileRenameInfo                   = 3,
  FileDispositionInfo              = 4,
  FileAllocationInfo               = 5,
  FileEndOfFileInfo                = 6,
  FileStreamInfo                   = 7,
  FileCompressionInfo              = 8,
  FileAttributeTagInfo             = 9,
  FileIdBothDirectoryInfo          = 10,
  // 0xA  FileIdBothDirectoryRestartInfo   = 11,
  // 0xB  FileIoPriorityHintInfo           = 12,
  // 0xC  FileRemoteProtocolInfo           = 13,
  // 0xD  FileFullDirectoryInfo            = 14,
  // 0xE  FileFullDirectoryRestartInfo     = 15,
  // 0xF  FileStorageInfo                  = 16,
  // 0x10  FileAlignmentInfo                = 17,
  // 0x11  FileIdInfo                       = 18,
  // 0x12  FileIdExtdDirectoryInfo          = 19,
  // 0x13  FileIdExtdDirectoryRestartInfo   = 20,
  // 0x14  MaximumFileInfoByHandlesClass 
} FILE_INFO_BY_HANDLE_CLASS, *PFILE_INFO_BY_HANDLE_CLASS;

typedef BOOL (WINAPI* _pGetFileInformationByHandleEx)(	HANDLE hFile,
														FILE_INFO_BY_HANDLE_CLASS FileInformationClass,
														LPVOID lpFileInformation,
														DWORD dwBufferSize);
_pGetFileInformationByHandleEx OriginGetFileInformationByHandleEx = 
	(_pGetFileInformationByHandleEx)GetProcAddress(GetModuleHandle(L"Kernel32"), "GetFileInformationByHandleEx");
BOOL WINAPI myGetFileInformationByHandleEx( HANDLE hFile,
											FILE_INFO_BY_HANDLE_CLASS FileInformationClass,
											LPVOID lpFileInformation,
											DWORD dwBufferSize)
{
	if(gs_Logging)
	{
		return OriginGetFileInformationByHandleEx(hFile, FileInformationClass, lpFileInformation, dwBufferSize);
	}

	log("GetFileInformationByHandleEx: handle = %x", hFile);
	return OriginGetFileInformationByHandleEx(hFile, FileInformationClass, lpFileInformation, dwBufferSize);
}

////////////////////////////////////////////////////////////////////////
//	Util Functions:
////////////////////////////////////////////////////////////////////////
void setupHooks()
{
	obtainUserSHFolders();

	//NOTE: For some dlls you have to load it so you can hook it. 
	//		(Maybe previlieges are needed to hook it without loading.)
	//		The dlls:	Shlwapi, SHELL32
	//HOOK_AND_LOG_FAILURE((PVOID*)&OriginPathFileExistsW, myPathFileExistsW, "PathFileExistsW");
	//HOOK_AND_LOG_FAILURE((PVOID*)&OriginSHCreateDirectoryExA, mySHCreateDirectoryExA, "SHCreateDirectoryExA");

	//Win
	//HOOK_AND_LOG_FAILURE((PVOID*)&OriginGetFileInformationByHandleEx, myGetFileInformationByHandleEx, "GetFileInformationByHandleEx");

	//Nt
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtCreateFile, myNtCreateFile, "NtCreateFile");
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtQueryDirectoryFile, myNtQueryDirectoryFile, "NtQueryDirectoryFile");	
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtQueryObject, myNtQueryObject, "NtQueryObject");	
	
	//Zw	
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginZwOpenFile, myZwOpenFile, "ZwOpenFile");
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginZwQuerySecurityObject, myZwQuerySecurityObject, "ZwQuerySecurityObject");
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginZwQueryAttributesFile, myZwQueryAttributesFile, "ZwQueryAttributesFile");
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginZwQueryInformationFile, myZwQueryInformationFile, "ZwQueryInformationFile");
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginZwQueryVolumeInformationFile, myZwQueryVolumeInformationFile, "ZwQueryVolumeInformationFile");
	
	
	log(" ======= Hooked =======");
}

void releaseHooks()
{
	//Mhook_Unhook((PVOID*)&OriginPathFileExistsW);	
	//Mhook_Unhook((PVOID*)&OriginSHCreateDirectoryExA);

	//Win
	//Mhook_Unhook((PVOID*)&OriginGetFileInformationByHandleEx);	

	//Nt
	Mhook_Unhook((PVOID*)&OriginNtCreateFile);	
	Mhook_Unhook((PVOID*)&OriginNtQueryDirectoryFile);	
	Mhook_Unhook((PVOID*)&OriginNtQueryObject);	
	
	//Zw	
	Mhook_Unhook((PVOID*)&OriginZwOpenFile);	
	Mhook_Unhook((PVOID*)&OriginZwQuerySecurityObject);		
	Mhook_Unhook((PVOID*)&OriginZwQueryAttributesFile);	
	Mhook_Unhook((PVOID*)&OriginZwQueryInformationFile);	
	Mhook_Unhook((PVOID*)&OriginZwQueryVolumeInformationFile);	

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
