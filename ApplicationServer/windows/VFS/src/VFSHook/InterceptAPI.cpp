// Copyright (C) 2012 
// Author Wei-Jen Chen 2012

#include <common/stdafx.h>
#include "mhook-lib/mhook.h"
#include "InterceptAPI.h"
#include "VirtualFileSystem.h"
#include <common/Logger.h>
#include <shlobj.h> 

#define VIRTUAL_SYSTEM_CONF_FILE	L"VirtualSystem.conf"

#define HOOK_AND_LOG_FAILURE(pOri, pInt, szFunc)	if(!Mhook_SetHook(pOri, pInt))\
													{\
														Logger::getSingleton().log("Failed to hook %s", szFunc);\
													}

////////////////////////////////////////////////////////////////////////
//	Intercept File API
////////////////////////////////////////////////////////////////////////

//-------------------------------------------------------------------//
//NtCreateFile
typedef NTSTATUS (WINAPI* PtrNtCreateFile)(	PHANDLE FileHandle,
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
PtrNtCreateFile OriginNtCreateFile = (PtrNtCreateFile)GetProcAddress(GetModuleHandle(L"ntdll"), "NtCreateFile");
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
	if(Logger::getSingleton().isLogging())
	{
		return OriginNtCreateFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, AllocationSize, 
				FileAttributes, ShareAccess, CreateDisposition, CreateOptions, EaBuffer, EaLength);
	}

	//Reserve origin data before modified
	UNICODE_STRING uniszRestore;
	bool bStore = false;
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL)
	{
		bStore = true;
		uniszRestore= *(ObjectAttributes->ObjectName);
	}

	VirtualFileSystem::getSingleton().redirectFilePath(ObjectAttributes);
		
	NTSTATUS stus = OriginNtCreateFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, AllocationSize, 
		FileAttributes, ShareAccess, CreateDisposition, CreateOptions, EaBuffer, EaLength);
	
	return stus;
}

//-------------------------------------------------------------------//
//NtOpenFile
typedef NTSTATUS (NTAPI* PtrNtOpenFile)(PHANDLE FileHandle,
										ACCESS_MASK DesiredAccess,
										POBJECT_ATTRIBUTES ObjectAttributes,
										PIO_STATUS_BLOCK IoStatusBlock,
										ULONG ShareAccess,
										ULONG OpenOptions);
PtrNtOpenFile OriginNtOpenFile = (PtrNtOpenFile)GetProcAddress(GetModuleHandle(L"ntdll"), "NtOpenFile");
NTSTATUS NTAPI myNtOpenFile(PHANDLE FileHandle,
							ACCESS_MASK DesiredAccess,
							POBJECT_ATTRIBUTES ObjectAttributes,
							PIO_STATUS_BLOCK IoStatusBlock,
							ULONG ShareAccess,
							ULONG OpenOptions)
{
	if(Logger::getSingleton().isLogging())
	{
		return OriginNtOpenFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, ShareAccess, OpenOptions);
	}

	//Reserve origin data before modified
	UNICODE_STRING uniszRestore;
	bool bStore = false;
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL)
	{
		bStore = true;
		uniszRestore= *(ObjectAttributes->ObjectName);
	}

	VirtualFileSystem::getSingleton().redirectFilePath(ObjectAttributes);
		
	NTSTATUS stus = OriginNtOpenFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, ShareAccess, OpenOptions);
			
	return stus;
}

//-------------------------------------------------------------------//
//NtQueryAttributesFile
typedef struct _FILE_BASIC_INFORMATION{
  LARGE_INTEGER CreationTime;
  LARGE_INTEGER LastAccessTime;
  LARGE_INTEGER LastWriteTime;
  LARGE_INTEGER ChangeTime;
  ULONG         FileAttributes;
}FILE_BASIC_INFORMATION, *PFILE_BASIC_INFORMATION;

typedef NTSTATUS (NTAPI* PtrNtQueryAttributesFile)(	POBJECT_ATTRIBUTES ObjectAttributes,
													PFILE_BASIC_INFORMATION FileInformation);
PtrNtQueryAttributesFile OriginNtQueryAttributesFile = (PtrNtQueryAttributesFile)GetProcAddress(GetModuleHandle(L"ntdll"), "NtQueryAttributesFile");
NTSTATUS NTAPI myNtQueryAttributesFile(	POBJECT_ATTRIBUTES ObjectAttributes,
										PFILE_BASIC_INFORMATION FileInformation)
{	
	if(Logger::getSingleton().isLogging())
	{
		return OriginNtQueryAttributesFile(ObjectAttributes, FileInformation);
	}

	//Reserve origin data before modified
	UNICODE_STRING uniszRestore;
	bool bStore = false;
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL)
	{
		bStore = true;
		uniszRestore= *(ObjectAttributes->ObjectName);
	}

	VirtualFileSystem::getSingleton().redirectFilePath(ObjectAttributes);
			
	NTSTATUS stus = OriginNtQueryAttributesFile(ObjectAttributes, FileInformation);
		
	return stus;
}

//-------------------------------------------------------------------//
//NtSetInformationFile


typedef enum _MY_FILE_INFORMATION_CLASS{
	//FileDirectoryInformation = 1, // 1 Y N D
	FileFullDirectoryInformation = 2, // 2 Y N D
	FileBothDirectoryInformation, // 3 Y N D
	FileBasicInformation, // 4 Y Y F
	FileStandardInformation, // 5 Y N F
	FileInternalInformation, // 6 Y N F
	FileEaInformation, // 7 Y N F
	FileAccessInformation, // 8 Y N F
	FileNameInformation, // 9 Y N F
	FileRenameInformation, // 10 N Y F
	FileLinkInformation, // 11 N Y F
	FileNamesInformation, // 12 Y N D
	FileDispositionInformation, // 13 N Y F
	FilePositionInformation, // 14 Y Y F
	FileModeInformation = 16, // 16 Y Y F
	FileAlignmentInformation, // 17 Y N F
	FileAllInformation, // 18 Y N F
	FileAllocationInformation, // 19 N Y F
	FileEndOfFileInformation, // 20 N Y F
	FileAlternateNameInformation, // 21 Y N F
	FileStreamInformation, // 22 Y N F
	FilePipeInformation, // 23 Y Y F
	FilePipeLocalInformation, // 24 Y N F
	FilePipeRemoteInformation, // 25 Y Y F
	FileMailslotQueryInformation, // 26 Y N F
	FileMailslotSetInformation, // 27 N Y F
	FileCompressionInformation, // 28 Y N F
	FileObjectIdInformation, // 29 Y Y F
	FileCompletionInformation, // 30 N Y F
	FileMoveClusterInformation, // 31 N Y F
	FileQuotaInformation, // 32 Y Y F
	FileReparsePointInformation, // 33 Y N F
	FileNetworkOpenInformation, // 34 Y N F
	FileAttributeTagInformation, // 35 Y N F
	FileTrackingInformation // 36 N Y F
}MY_FILE_INFORMATION_CLASS, *MY_PFILE_INFORMATION_CLASS;

typedef struct _FILE_LINK_RENAME_INFORMATION{ // Info Classes 10 and 11
	BOOLEAN ReplaceIfExists;
	HANDLE RootDirectory;
	ULONG FileNameLength;
	WCHAR FileName[1];
}FILE_LINK_INFORMATION, *PFILE_LINK_INFORMATION, FILE_RENAME_INFORMATION, *PFILE_RENAME_INFORMATION;

typedef NTSTATUS (NTAPI* PtrNtSetInformationFile)(	HANDLE FileHandle,
													PIO_STATUS_BLOCK IoStatusBlock,
													PVOID FileInformation,
													ULONG FileInformationLength,
													FILE_INFORMATION_CLASS FileInformationClass);
PtrNtSetInformationFile OriginNtSetInformationFile = (PtrNtSetInformationFile)GetProcAddress(GetModuleHandle(L"ntdll"), "NtSetInformationFile");
NTSTATUS NTAPI myNtSetInformationFile(	HANDLE FileHandle,
										PIO_STATUS_BLOCK IoStatusBlock,
										PVOID FileInformation,
										ULONG FileInformationLength,
										FILE_INFORMATION_CLASS FileInformationClass)
{
	if(Logger::getSingleton().isLogging())
	{
		return OriginNtSetInformationFile(FileHandle, IoStatusBlock, FileInformation, 
			FileInformationLength, FileInformationClass);
	}

	//Reserve origin data before modified
	FILE_RENAME_INFORMATION restoreFileRenameInfo;
	bool bStore = false;

	//FileRename
	if(FileInformationClass == FileRenameInformation)
	{
		PFILE_RENAME_INFORMATION pFileRename = (PFILE_RENAME_INFORMATION)FileInformation;				
		restoreFileRenameInfo = *pFileRename;
		
		if( VirtualFileSystem::getSingleton().redirectFilePath(pFileRename->FileName, &(pFileRename->FileNameLength)) )
		{
			bStore = true;
		}
	}

	NTSTATUS stus = OriginNtSetInformationFile(FileHandle, IoStatusBlock, FileInformation, 
		FileInformationLength, FileInformationClass);
	
	return stus;
}

////////////////////////////////////////////////////////////////////////
//	Intercept Registry API
////////////////////////////////////////////////////////////////////////

//-------------------------------------------------------------------//
//NtCreateKey
typedef NTSTATUS (NTAPI* PtrNtCreateKey)(	PHANDLE KeyHandle,
											ACCESS_MASK DesiredAccess,
											POBJECT_ATTRIBUTES ObjectAttributes,
											ULONG TitleIndex,
											PUNICODE_STRING Class,
											ULONG CreateOptions,
											PULONG Disposition);
PtrNtCreateKey OriginNtCreateKey = (PtrNtCreateKey)GetProcAddress(GetModuleHandle(L"ntdll"), "NtCreateKey");
NTSTATUS NTAPI myNtCreateKey(	PHANDLE KeyHandle,
								ACCESS_MASK DesiredAccess,
								POBJECT_ATTRIBUTES ObjectAttributes,
								ULONG TitleIndex,
								PUNICODE_STRING Class,
								ULONG CreateOptions,
								PULONG Disposition)
{
	if(Logger::getSingleton().isLogging())
	{
		return OriginNtCreateKey(KeyHandle, DesiredAccess, ObjectAttributes, 
				TitleIndex, Class, CreateOptions, Disposition);
	}

	//Reserve origin data before modified
	UNICODE_STRING uniszRestore;
	bool bStore = false;
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL)
	{
		bStore = true;
		uniszRestore= *(ObjectAttributes->ObjectName);
	}

	VirtualFileSystem::getSingleton().redirectRegPath(ObjectAttributes);
	
	NTSTATUS stus = OriginNtCreateKey(KeyHandle, DesiredAccess, ObjectAttributes, 
		TitleIndex, Class, CreateOptions, Disposition);
		
	return stus;
}

//-------------------------------------------------------------------//
//NtOpenKey
typedef NTSTATUS (NTAPI* PtrNtOpenKey)(	PHANDLE KeyHandle,
										ACCESS_MASK DesiredAccess,
										POBJECT_ATTRIBUTES ObjectAttributes);
PtrNtOpenKey OriginNtOpenKey = (PtrNtOpenKey)GetProcAddress(GetModuleHandle(L"ntdll"), "NtOpenKey");
NTSTATUS NTAPI myNtOpenKey(	PHANDLE KeyHandle,
							ACCESS_MASK DesiredAccess,
							POBJECT_ATTRIBUTES ObjectAttributes)
{
	if(Logger::getSingleton().isLogging())
	{
		return OriginNtOpenKey(KeyHandle, DesiredAccess, ObjectAttributes);
	}

	//Reserve origin data before modified
	UNICODE_STRING uniszRestore;
	bool bStore = false;
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL)
	{
		bStore = true;
		uniszRestore= *(ObjectAttributes->ObjectName);
	}

	VirtualFileSystem::getSingleton().redirectRegPath(ObjectAttributes);
	
	NTSTATUS stus = OriginNtOpenKey(KeyHandle, DesiredAccess, ObjectAttributes);
		
	return stus;
}

//-------------------------------------------------------------------//
//NtOpenKeyEx
typedef NTSTATUS (NTAPI* PtrNtOpenKeyEx)(	PHANDLE KeyHandle,
											ACCESS_MASK DesiredAccess,
											POBJECT_ATTRIBUTES ObjectAttributes,
											ULONG OpenOptions);
PtrNtOpenKeyEx OriginNtOpenKeyEx = (PtrNtOpenKeyEx)GetProcAddress(GetModuleHandle(L"ntdll"), "NtOpenKeyEx");
NTSTATUS NTAPI myNtOpenKeyEx(	PHANDLE KeyHandle,
								ACCESS_MASK DesiredAccess,
								POBJECT_ATTRIBUTES ObjectAttributes,
								ULONG OpenOptions)
{	
	if(Logger::getSingleton().isLogging())
	{
		return OriginNtOpenKeyEx(KeyHandle, DesiredAccess, ObjectAttributes, OpenOptions);
	}

	//Reserve origin data before modified
	UNICODE_STRING uniszRestore;
	bool bStore = false;
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL)
	{
		bStore = true;
		uniszRestore= *(ObjectAttributes->ObjectName);
	}

	VirtualFileSystem::getSingleton().redirectRegPath(ObjectAttributes);
	
	NTSTATUS stus = OriginNtOpenKeyEx(KeyHandle, DesiredAccess, ObjectAttributes, OpenOptions);
		
	return stus;
}

////////////////////////////////////////////////////////////////////////
//	Util Functions:
////////////////////////////////////////////////////////////////////////
void setupHooks()
{	
	// Set log file to user profile path by default
	WCHAR szLogFile[MAX_PATH] = {};
	SHGetSpecialFolderPathW(NULL, szLogFile, CSIDL_PROFILE, 0);
	lstrcatW(szLogFile, L"\\ulteo\\VirtSys.log");
	Logger::getSingleton().setLogFile(szLogFile);
	
	// Read conf file from CSIDL_COMMON_APPDATA\\ulteo\ovd
	WCHAR szConfigFile[MAX_PATH] = {};
	SHGetSpecialFolderPathW(NULL, szConfigFile, CSIDL_COMMON_APPDATA, 0);
	lstrcatW(szConfigFile, L"\\ulteo\\ovd\\");
	lstrcatW(szConfigFile, VIRTUAL_SYSTEM_CONF_FILE);
	
	if( ! VirtualFileSystem::getSingleton().init() )
	{
		Logger::getSingleton().log("Failed to initialize Virtual File System!");
		return;
	}

	if( ! VirtualFileSystem::getSingleton().parseFileSystem(szConfigFile) )
	{
		Logger::getSingleton().log("File blacklist configuration file not found!");
		return;
	}

	if( ! VirtualFileSystem::getSingleton().parseRegSystem(szConfigFile) )
	{
		Logger::getSingleton().log("Registry redirect-list configuration file not found!");
		return;
	}
	
	// Windows Nt routines API
	// http://msdn.microsoft.com/en-us/library/windows/hardware/ff557720(v=vs.85).aspx

	//Intercept File API	
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtCreateFile, myNtCreateFile, "NtCreateFile");
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtOpenFile, myNtOpenFile, "NtOpenFile");
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtQueryAttributesFile, myNtQueryAttributesFile, "NtQueryAttributesFile");
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtSetInformationFile, myNtSetInformationFile, "NtSetInformationFile");
	/*
		NtSetVolumeInformationFile 
		NtQueryFullAttributesFile
		NtQueryDirectoryFile
		NtQueryVolumeInformationFile
		NtQueryInformationFile
		NtDeleteFile
		NtSetValueKey
	*/

	//Intercept Reg API	
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtCreateKey, myNtCreateKey, "NtCreateKey");
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtOpenKey, myNtOpenKey, "NtOpenKey");
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtOpenKeyEx, myNtOpenKeyEx, "NtOpenKeyEx");

	Logger::getSingleton().log("Hooked success");
}

void releaseHooks()
{
	//File API	
	Mhook_Unhook((PVOID*)&OriginNtCreateFile);	
	Mhook_Unhook((PVOID*)&OriginNtOpenFile);	
	Mhook_Unhook((PVOID*)&OriginNtQueryAttributesFile);	
	Mhook_Unhook((PVOID*)&OriginNtSetInformationFile);	
	
	//Reg API
	Mhook_Unhook((PVOID*)&OriginNtCreateKey);	
	Mhook_Unhook((PVOID*)&OriginNtOpenKey);	
	Mhook_Unhook((PVOID*)&OriginNtOpenKeyEx);

	Logger::getSingleton().log("Un-Hooked program");
}
