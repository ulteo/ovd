// Copyright (C) 2012 
// Author Wei-Jen Chen 2012

#include <common/stdafx.h>
#include "mhook-lib/mhook.h"
#include "InterceptAPI.h"
#include "VirtualFileSystem.h"
#include <common/Logger.h>
#include <common/conf/Configuration.h>
#include <common/sys/Registry.h>
#include <iostream>
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

	Logger::getSingleton().debug(L"myNtCreateFile");

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

	Logger::getSingleton().debug(L"myNtOpenFile");
	std::wstring result;

	//Reserve origin data before modified
	UNICODE_STRING uniszRestore;
	bool bStore = false;
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL) {
		bStore = true;
		uniszRestore= *(ObjectAttributes->ObjectName);
		Logger::getSingleton().debug(L"    => uniszRestore %s", uniszRestore.Buffer);
	}


	vf.redirectFilePath(ObjectAttributes);
		
	NTSTATUS stus = OriginNtOpenFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, ShareAccess, OpenOptions);

	Logger::getSingleton().debug(L"return with status %x %x", stus);

	return stus;
}


NTSTATUS NTAPI myNtQueryAttributesFile(	POBJECT_ATTRIBUTES ObjectAttributes,
										PFILE_BASIC_INFORMATION FileInformation)
{	
	if(Logger::getSingleton().isLogging())
		return OriginNtQueryAttributesFile(ObjectAttributes, FileInformation);

	Logger::getSingleton().debug(L"myNtQueryAttributesFile");


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

	Logger::getSingleton().debug(L"myNtSetInformationFile");

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
	// loading configuration
	Registry reg(REGISTRY_PATH_KEY);
	Configuration& conf = Configuration::getInstance();
	std::wstring src;

	// Get the environment variable which contain profile source
	if (! reg.exist()) {
		Logger::getSingleton().debug(L"Registry key %s do not exist", REGISTRY_PATH_KEY);
		log_error(L"Registry key %s do not exist", REGISTRY_PATH_KEY);
		return;
	}

	if (! reg.get(L"ProfileSrc", src)) {
		Logger::getSingleton().debug(L"Failed to get registry key variable %s", REGISTRY_PATH_KEY);
		log_error(L"Failed to get registry key variable %s", REGISTRY_PATH_KEY);
		return;
	}

	conf.setSrcPath(src);

	if (!conf.load()) {
		log_error(L"Failed to load configuration file");
		return;
	}

	conf.dump();

	if( ! vf.init() ) {
		log_error(L"Failed to initialize Virtual File System!");
		return;
	}

	if( ! vf.initFileSystem()) {
		log_error(L"File blacklist configuration file not found!");
		return;
	}

	if( ! vf.initRegSystem()) {
		log_error(L"Registry redirect-list configuration file not found!");
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
	if (conf.supportHookRegistry()) {
		HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtCreateKey, myNtCreateKey, "NtCreateKey");
		HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtOpenKey, myNtOpenKey, "NtOpenKey");
		HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtOpenKeyEx, myNtOpenKeyEx, "NtOpenKeyEx");
	}

	log_error(L"Hooked success");
	Logger::getSingleton().debug(L"Hooked success");
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
	log_error(L"Un-Hooked program");
}
