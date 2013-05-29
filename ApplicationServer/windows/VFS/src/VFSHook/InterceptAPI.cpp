// Copyright (C) 2012 
// Author Wei-Jen Chen 2012

#include <common/stdafx.h>
#include "mhook-lib/mhook.h"
#include "InterceptAPI.h"
#include "VirtualFileSystem.h"
#include <common/Logger.h>
#include <shlobj.h> 



// original hooked function pointer
PtrNtCreateFile OriginNtCreateFile = (PtrNtCreateFile)GetProcAddress(GetModuleHandle(L"ntdll"), "NtCreateFile");
PtrNtOpenFile OriginNtOpenFile = (PtrNtOpenFile)GetProcAddress(GetModuleHandle(L"ntdll"), "NtOpenFile");
PtrNtQueryAttributesFile OriginNtQueryAttributesFile = (PtrNtQueryAttributesFile)GetProcAddress(GetModuleHandle(L"ntdll"), "NtQueryAttributesFile");
PtrNtSetInformationFile OriginNtSetInformationFile = (PtrNtSetInformationFile)GetProcAddress(GetModuleHandle(L"ntdll"), "NtSetInformationFile");

PtrNtCreateKey OriginNtCreateKey = (PtrNtCreateKey)GetProcAddress(GetModuleHandle(L"ntdll"), "NtCreateKey");
PtrNtOpenKey OriginNtOpenKey = (PtrNtOpenKey)GetProcAddress(GetModuleHandle(L"ntdll"), "NtOpenKey");
PtrNtOpenKeyEx OriginNtOpenKeyEx = (PtrNtOpenKeyEx)GetProcAddress(GetModuleHandle(L"ntdll"), "NtOpenKeyEx");

VirtualFileSystem vf;


////////////////////////////////////////////////////////////////////////
//	Intercept File API
////////////////////////////////////////////////////////////////////////


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
	if(Logger::getSingleton().isLogging()) {
		return OriginNtCreateFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, AllocationSize, 
				FileAttributes, ShareAccess, CreateDisposition, CreateOptions, EaBuffer, EaLength);
	}

	//Reserve origin data before modified
	UNICODE_STRING uniszRestore;
	bool bStore = false;
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL) {
		bStore = true;
		uniszRestore= *(ObjectAttributes->ObjectName);
	}

	vf.redirectFilePath(ObjectAttributes);
		
	NTSTATUS stus = OriginNtCreateFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, AllocationSize, 
		FileAttributes, ShareAccess, CreateDisposition, CreateOptions, EaBuffer, EaLength);
	
	return stus;
}


NTSTATUS NTAPI myNtOpenFile(PHANDLE FileHandle,
							ACCESS_MASK DesiredAccess,
							POBJECT_ATTRIBUTES ObjectAttributes,
							PIO_STATUS_BLOCK IoStatusBlock,
							ULONG ShareAccess,
							ULONG OpenOptions)
{
	if(Logger::getSingleton().isLogging())
		return OriginNtOpenFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, ShareAccess, OpenOptions);

	//Reserve origin data before modified
	UNICODE_STRING uniszRestore;
	bool bStore = false;
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL) {
		bStore = true;
		uniszRestore= *(ObjectAttributes->ObjectName);
	}

	vf.redirectFilePath(ObjectAttributes);
		
	NTSTATUS stus = OriginNtOpenFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, ShareAccess, OpenOptions);
			
	return stus;
}


NTSTATUS NTAPI myNtQueryAttributesFile(	POBJECT_ATTRIBUTES ObjectAttributes,
										PFILE_BASIC_INFORMATION FileInformation)
{	
	if(Logger::getSingleton().isLogging())
		return OriginNtQueryAttributesFile(ObjectAttributes, FileInformation);

	//Reserve origin data before modified
	UNICODE_STRING uniszRestore;
	bool bStore = false;
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL)
	{
		bStore = true;
		uniszRestore= *(ObjectAttributes->ObjectName);
	}

	vf.redirectFilePath(ObjectAttributes);
			
	NTSTATUS stus = OriginNtQueryAttributesFile(ObjectAttributes, FileInformation);
		
	return stus;
}


NTSTATUS NTAPI myNtSetInformationFile(	HANDLE FileHandle,
										PIO_STATUS_BLOCK IoStatusBlock,
										PVOID FileInformation,
										ULONG FileInformationLength,
										FILE_INFORMATION_CLASS FileInformationClass)
{
	if(Logger::getSingleton().isLogging()) {
		return OriginNtSetInformationFile(FileHandle, IoStatusBlock, FileInformation, 
			FileInformationLength, FileInformationClass);
	}

	//Reserve origin data before modified
	FILE_RENAME_INFORMATION restoreFileRenameInfo;
	bool bStore = false;

	//FileRename
	if(FileInformationClass == FileRenameInformation) {
		PFILE_RENAME_INFORMATION pFileRename = (PFILE_RENAME_INFORMATION)FileInformation;				
		restoreFileRenameInfo = *pFileRename;
		
		if( vf.redirectFilePath(pFileRename->FileName, &(pFileRename->FileNameLength)) )
			bStore = true;
	}

	NTSTATUS stus = OriginNtSetInformationFile(FileHandle, IoStatusBlock, FileInformation, FileInformationLength, FileInformationClass);
	
	return stus;
}

////////////////////////////////////////////////////////////////////////
//	Intercept Registry API
////////////////////////////////////////////////////////////////////////

NTSTATUS NTAPI myNtCreateKey(	PHANDLE KeyHandle,
								ACCESS_MASK DesiredAccess,
								POBJECT_ATTRIBUTES ObjectAttributes,
								ULONG TitleIndex,
								PUNICODE_STRING Class,
								ULONG CreateOptions,
								PULONG Disposition)
{
	if(Logger::getSingleton().isLogging())
		return OriginNtCreateKey(KeyHandle, DesiredAccess, ObjectAttributes, TitleIndex, Class, CreateOptions, Disposition);

	//Reserve origin data before modified
	UNICODE_STRING uniszRestore;
	bool bStore = false;
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL) {
		bStore = true;
		uniszRestore= *(ObjectAttributes->ObjectName);
	}

	vf.redirectRegPath(ObjectAttributes);
	
	NTSTATUS stus = OriginNtCreateKey(KeyHandle, DesiredAccess, ObjectAttributes, TitleIndex, Class, CreateOptions, Disposition);
		
	return stus;
}


NTSTATUS NTAPI myNtOpenKey(	PHANDLE KeyHandle,
							ACCESS_MASK DesiredAccess,
							POBJECT_ATTRIBUTES ObjectAttributes)
{
	if(Logger::getSingleton().isLogging())
		return OriginNtOpenKey(KeyHandle, DesiredAccess, ObjectAttributes);

	//Reserve origin data before modified
	UNICODE_STRING uniszRestore;
	bool bStore = false;
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL) {
		bStore = true;
		uniszRestore= *(ObjectAttributes->ObjectName);
	}

	vf.redirectRegPath(ObjectAttributes);
	
	NTSTATUS stus = OriginNtOpenKey(KeyHandle, DesiredAccess, ObjectAttributes);
		
	return stus;
}


NTSTATUS NTAPI myNtOpenKeyEx(	PHANDLE KeyHandle,
								ACCESS_MASK DesiredAccess,
								POBJECT_ATTRIBUTES ObjectAttributes,
								ULONG OpenOptions)
{	
	if(Logger::getSingleton().isLogging())
		return OriginNtOpenKeyEx(KeyHandle, DesiredAccess, ObjectAttributes, OpenOptions);

	//Reserve origin data before modified
	UNICODE_STRING uniszRestore;
	bool bStore = false;
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL) {
		bStore = true;
		uniszRestore= *(ObjectAttributes->ObjectName);
	}

	vf.redirectRegPath(ObjectAttributes);
	
	NTSTATUS stus = OriginNtOpenKeyEx(KeyHandle, DesiredAccess, ObjectAttributes, OpenOptions);
		
	return stus;
}

////////////////////////////////////////////////////////////////////////
//	Util Functions:
////////////////////////////////////////////////////////////////////////
void setupHooks() {
	// Set log file to user profile path by default
	char szLogFile[MAX_PATH] = {};
	SHGetSpecialFolderPathA(NULL, szLogFile, CSIDL_PROFILE, 0);
	lstrcatA(szLogFile, "\\ulteo\\VirtSys.log");
	Logger::getSingleton().setLogFile(szLogFile);
	
	// Read conf file from CSIDL_COMMON_APPDATA\\ulteo\ovd
	WCHAR szConfigFile[MAX_PATH] = {};
	SHGetSpecialFolderPathW(NULL, szConfigFile, CSIDL_COMMON_APPDATA, 0);
	lstrcatW(szConfigFile, L"\\ulteo\\ovd\\");
	lstrcatW(szConfigFile, VIRTUAL_SYSTEM_CONF_FILE);
	
	if( ! vf.init() ) {
		log_error("Failed to initialize Virtual File System!");
		return;
	}

	if( ! vf.parseFileSystem(szConfigFile)) {
		log_error("File blacklist configuration file not found!");
		return;
	}

	if( ! vf.parseRegSystem(szConfigFile)) {
		log_error("Registry redirect-list configuration file not found!");
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

	log_error("Hooked success");
}

void releaseHooks() {
	//File API	
	Mhook_Unhook((PVOID*)&OriginNtCreateFile);	
	Mhook_Unhook((PVOID*)&OriginNtOpenFile);	
	Mhook_Unhook((PVOID*)&OriginNtQueryAttributesFile);	
	Mhook_Unhook((PVOID*)&OriginNtSetInformationFile);	
	
	//Reg API
	Mhook_Unhook((PVOID*)&OriginNtCreateKey);	
	Mhook_Unhook((PVOID*)&OriginNtOpenKey);	
	Mhook_Unhook((PVOID*)&OriginNtOpenKeyEx);

	log_error("Un-Hooked program");
}
