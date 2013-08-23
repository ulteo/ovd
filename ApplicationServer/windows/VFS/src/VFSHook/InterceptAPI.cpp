/*
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2013
 * Author Wei-Jen Chen 2012
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

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
PtrNtQueryDirectoryFile OriginNtQueryDirectoryFile = (PtrNtQueryDirectoryFile)GetProcAddress(GetModuleHandle(L"ntdll"), "NtQueryDirectoryFile");
PtrNtClose OriginNtClose = (PtrNtClose)GetProcAddress(GetModuleHandle(L"ntdll"), "NtClose");

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

	std::wstring result;

	if (vf.redirectFilePath(ObjectAttributes, result)) {
		std::wstring p = std::wstring(ObjectAttributes->ObjectName->Buffer, 0, ObjectAttributes->ObjectName->Length>>1);

		log_debug(L"myNtCreateFile %s", p.c_str());
		NTSTATUS res;
		OBJECT_ATTRIBUTES out;
		UNICODE_STRING uni;

		uni.Buffer = (PWSTR)result.c_str();
		uni.Length = (USHORT)result.length() * 2;
		uni.MaximumLength = uni.Length;

		InitializeObjectAttributes(&out, &uni, ObjectAttributes->Attributes, 0, ObjectAttributes->SecurityDescriptor);

		res = OriginNtCreateFile(FileHandle, DesiredAccess, &out, IoStatusBlock, AllocationSize,
						FileAttributes, ShareAccess, CreateDisposition, CreateOptions, EaBuffer, EaLength);

		if (SUCCEEDED(res)) {
			log_debug(L"add %ui => %s", *FileHandle, p);
			vf.addHandle(*FileHandle, p);
		}
		return res;
	}
		
	return OriginNtCreateFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, AllocationSize,
			FileAttributes, ShareAccess, CreateDisposition, CreateOptions, EaBuffer, EaLength);
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
	std::wstring result;

	if (vf.redirectFilePath(ObjectAttributes, result)) {
		std::wstring p = std::wstring(ObjectAttributes->ObjectName->Buffer, 0, ObjectAttributes->ObjectName->Length>>1);
		log_debug(L"myNtOpenFile %s", p.c_str());

		OBJECT_ATTRIBUTES out;
		UNICODE_STRING uni;
		NTSTATUS res;

		uni.Buffer = (PWSTR)result.c_str();
		uni.Length = (USHORT)result.length() * 2;
		uni.MaximumLength = uni.Length;

		InitializeObjectAttributes(&out, &uni, ObjectAttributes->Attributes, 0, ObjectAttributes->SecurityDescriptor);

		res = OriginNtOpenFile(FileHandle, DesiredAccess, &out, IoStatusBlock, ShareAccess, OpenOptions);

		if (SUCCEEDED(res)) {
			log_debug(L"add %lu => %s", *FileHandle, p);
			vf.addHandle(*FileHandle, p);
		}
		return res;
	}

	return OriginNtOpenFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, ShareAccess, OpenOptions);
}


NTSTATUS NTAPI myNtQueryAttributesFile(	POBJECT_ATTRIBUTES ObjectAttributes,
										PFILE_BASIC_INFORMATION FileInformation)
{	
	if(Logger::getSingleton().isLogging())
		return OriginNtQueryAttributesFile(ObjectAttributes, FileInformation);

	std::wstring result;

	if (vf.redirectFilePath(ObjectAttributes, result)) {
		log_debug(L"myNtQueryAttributesFile");

		OBJECT_ATTRIBUTES out;
		UNICODE_STRING uni;

		uni.Buffer = (PWSTR)result.c_str();
		uni.Length = (USHORT)result.length() * 2;
		uni.MaximumLength = uni.Length;

		InitializeObjectAttributes(&out, &uni, ObjectAttributes->Attributes, 0, ObjectAttributes->SecurityDescriptor);

		return OriginNtQueryAttributesFile(&out, FileInformation);
	}

	return OriginNtQueryAttributesFile(ObjectAttributes, FileInformation);
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

	//FileRename
	if(FileInformationClass == FileRenameInformation) {
		log_debug(L"myNtSetInformationFile");

		PFILE_RENAME_INFORMATION pFileRename = (PFILE_RENAME_INFORMATION)FileInformation;
		std::wstring path = std::wstring(pFileRename->FileName, 0, pFileRename->FileNameLength>>1);
		std::wstring result;

		if( vf.redirectFilePath(path, result)) {
			char buffer[sizeof(FILE_RENAME_INFORMATION) + MAX_PATH * sizeof(wchar_t)];
			PFILE_RENAME_INFORMATION fr = (PFILE_RENAME_INFORMATION)buffer;

			fr->ReplaceIfExists = pFileRename->ReplaceIfExists;
			fr->RootDirectory = pFileRename->RootDirectory;
			fr->FileNameLength = (DWORD)result.length() * 2;
			wcscpy_s(&fr->FileName[0], MAX_PATH * sizeof(wchar_t), result.c_str());

			return OriginNtSetInformationFile(FileHandle, IoStatusBlock, (PVOID)fr, sizeof(buffer), FileInformationClass);
		}
	}

	return OriginNtSetInformationFile(FileHandle, IoStatusBlock, FileInformation, FileInformationLength, FileInformationClass);

}


void dumpDirectoryList(PVOID FileInformation, FILE_INFORMATION_CLASS FileInformationClass) {
	LPBYTE buffer = (LPBYTE) FileInformation;
	ULONG nextOffset = 0;
	wchar_t* data;
	ULONG length;


	do {
		switch (FileInformationClass)
		{
		case FileBothDirectoryInformation:
			data = &((PFILE_BOTH_DIR_INFORMATION)buffer)->FileName[0];
			length = ((PFILE_BOTH_DIR_INFORMATION)buffer)->FileNameLength;
			nextOffset = ((PFILE_BOTH_DIR_INFORMATION)buffer)->NextEntryOffset;
			break;

		case FileDirectoryInformation1:
			data = &((PFILE_DIRECTORY_INFORMATION)buffer)->FileName[0];
			length = ((PFILE_DIRECTORY_INFORMATION)buffer)->FileNameLength;
			nextOffset = ((PFILE_DIRECTORY_INFORMATION)buffer)->NextEntryOffset;
			break;

		case FileFullDirectoryInformation:
			data = &((PFILE_FULL_DIR_INFORMATION)buffer)->FileName[0];
			length = ((PFILE_FULL_DIR_INFORMATION)buffer)->FileNameLength;
			nextOffset = ((PFILE_FULL_DIR_INFORMATION)buffer)->NextEntryOffset;
			break;

		case FileIdBothDirectoryInformation:
			data = &((PFILE_ID_BOTH_DIR_INFORMATION)buffer)->FileName[0];
			length = ((PFILE_ID_BOTH_DIR_INFORMATION)buffer)->FileNameLength;
			nextOffset = ((PFILE_ID_BOTH_DIR_INFORMATION)buffer)->NextEntryOffset;
			break;

		case FileNameInformation:
			data = &((PFILE_NAMES_INFORMATION)buffer)->FileName[0];
			length = ((PFILE_NAMES_INFORMATION)buffer)->FileNameLength;
			nextOffset = ((PFILE_NAMES_INFORMATION)buffer)->NextEntryOffset;
			break;
		default:
			log_error(L"Wrong FileInformationClass value");
			nextOffset = 0;
			data = NULL;
			length = 0;
			break;
		}

		if (length > 1) {
			std::wstring filename(data, length>>1);
			log_error(L"Filename %s", filename.c_str());
		}

		buffer += nextOffset;
	}
	while(nextOffset != 0);

}


// This function return the content removing doublon
int finalizeDirectoryListing(PVOID FileInformation, ULONG Length, FILE_INFORMATION_CLASS FileInformationClass, std::map<std::wstring, int>& content) {
	LPBYTE buffer = (LPBYTE) FileInformation;
	LPBYTE lastBuffer = (LPBYTE) FileInformation;
	ULONG nextOffset = 0;
	PULONG lastNextOffsetPtr = NULL;
	wchar_t* data;
	ULONG length;
	int count = 0;
	ULONG currentLength = Length;

	do {
		currentLength -= nextOffset;

		switch (FileInformationClass)
		{
		case FileBothDirectoryInformation:
			data = &((PFILE_BOTH_DIR_INFORMATION)buffer)->FileName[0];
			length = ((PFILE_BOTH_DIR_INFORMATION)buffer)->FileNameLength;
			nextOffset = ((PFILE_BOTH_DIR_INFORMATION)buffer)->NextEntryOffset;
			lastNextOffsetPtr = &((PFILE_BOTH_DIR_INFORMATION)lastBuffer)->NextEntryOffset;
			break;

		case FileDirectoryInformation1:
			data = &((PFILE_DIRECTORY_INFORMATION)buffer)->FileName[0];
			length = ((PFILE_DIRECTORY_INFORMATION)buffer)->FileNameLength;
			nextOffset = ((PFILE_DIRECTORY_INFORMATION)buffer)->NextEntryOffset;
			lastNextOffsetPtr = &((PFILE_DIRECTORY_INFORMATION)lastBuffer)->NextEntryOffset;
			break;

		case FileFullDirectoryInformation:
			data = &((PFILE_FULL_DIR_INFORMATION)buffer)->FileName[0];
			length = ((PFILE_FULL_DIR_INFORMATION)buffer)->FileNameLength;
			nextOffset = ((PFILE_FULL_DIR_INFORMATION)buffer)->NextEntryOffset;
			lastNextOffsetPtr = &((PFILE_FULL_DIR_INFORMATION)lastBuffer)->NextEntryOffset;
			break;

		case FileIdBothDirectoryInformation:
			data = &((PFILE_ID_BOTH_DIR_INFORMATION)buffer)->FileName[0];
			length = ((PFILE_ID_BOTH_DIR_INFORMATION)buffer)->FileNameLength;
			nextOffset = ((PFILE_ID_BOTH_DIR_INFORMATION)buffer)->NextEntryOffset;
			lastNextOffsetPtr = &((PFILE_ID_BOTH_DIR_INFORMATION)lastBuffer)->NextEntryOffset;
			break;

		case FileNameInformation:
			data = &((PFILE_NAMES_INFORMATION)buffer)->FileName[0];
			length = ((PFILE_NAMES_INFORMATION)buffer)->FileNameLength;
			nextOffset = ((PFILE_NAMES_INFORMATION)buffer)->NextEntryOffset;
			lastNextOffsetPtr = &((PFILE_NAMES_INFORMATION)lastBuffer)->NextEntryOffset;
			break;
		default:
			log_error(L"Wrong FileInformationClass value");
			nextOffset = 0;
			length = 0;
			break;
		}

		if (length > 1) {
			std::wstring filename(data, length>>1);
			if (content.find(filename) == content.end()) {
				content[filename] = 0;
				count++;
			}
			else {
				if (nextOffset != 0) {
					memmove((char*)buffer, (char*)buffer + nextOffset, currentLength - nextOffset);
					continue;
				}
				else {
					*lastNextOffsetPtr = 0;
					return count;
				}
			}
		}

		lastBuffer = buffer;
		buffer += nextOffset;
	}
	while(nextOffset != 0);

	return count;
}



NTSTATUS NTAPI myNtQueryDirectoryFile(	HANDLE FileHandle,
										HANDLE Event,
										PVOID ApcRoutine,
										PVOID ApcContext,
										PIO_STATUS_BLOCK IoStatusBlock,
										PVOID FileInformation,
										ULONG Length,
										FILE_INFORMATION_CLASS FileInformationClass,
										BOOLEAN ReturnSingleEntry,
										PUNICODE_STRING FileName,
										BOOLEAN RestartScan)
{
	if(Logger::getSingleton().isLogging()) {
		return OriginNtQueryDirectoryFile(FileHandle, Event, ApcRoutine, ApcContext, IoStatusBlock, FileInformation, Length,
				FileInformationClass, ReturnSingleEntry, FileName, RestartScan);
	}

	if (FileInformationClass ==  FileReparsePointInformation  || FileInformationClass == FileObjectIdInformation)
		OriginNtQueryDirectoryFile(FileHandle, Event, ApcRoutine, ApcContext, IoStatusBlock, FileInformation, Length,
					FileInformationClass, ReturnSingleEntry, FileName, RestartScan);


	// are we interrested by this directory ??
	if (vf.containHandle(FileHandle)) {
		DirEntry& entry = vf.getHandle(FileHandle);
		log_debug(L"MyNtQueryDirectoryFile %s, buffer size %lu", entry.path.c_str(), Length);

		NTSTATUS st;
		IO_STATUS_BLOCK status;
		std::list<std::wstring> res;
		std::list<std::wstring>::iterator it;
		std::list<HANDLE>::iterator it2;

		// Generating repos list if it is empty
		if (entry.repos.empty()) {
			vf.getSubstitutedRepositoriesPath(entry.path, res);
			for (it = res.begin() ; it != res.end() ; it++) {
				HANDLE handle;
				OBJECT_ATTRIBUTES obj;
				UNICODE_STRING uni;

				// We check if it is a directory
				uni.Buffer = (PWSTR)(*it).c_str();
				uni.Length = (USHORT)(*it).length() * 2;
				uni.MaximumLength = uni.Length;

				InitializeObjectAttributes(&obj, &uni, OBJ_CASE_INSENSITIVE, 0, NULL);

				NTSTATUS st = OriginNtOpenFile(&handle, GENERIC_READ | SYNCHRONIZE, &obj, &status, FILE_SHARE_READ, FILE_DIRECTORY_FILE | FILE_SYNCHRONOUS_IO_NONALERT);
				if (FAILED(st)) {
					log_debug(L"\tFailed to open directory %s 0x%x", (*it).c_str(), st);
					continue;
				}
				entry.repos.push_back(handle);

				log_debug(L"\t=> HANDLE %s => %lu", (*it).c_str(), (handle));

			}

			if (entry.repos.empty()) {
				return OriginNtQueryDirectoryFile(FileHandle, Event, ApcRoutine, ApcContext, IoStatusBlock, FileInformation, Length,
								FileInformationClass, ReturnSingleEntry, FileName, RestartScan);
			}
		}

		if (RestartScan)
			entry.content.clear();

		// We list all the repositories content
		for (it2 = entry.repos.begin() ; it2 != entry.repos.end() ; it2++) {
			log_debug(L"\tlisting the content of %lu, need one entry: %d", (*it2), ReturnSingleEntry);
			if (FileName)
				log_debug(L"\tlisting the content with filter %s", FileName->Buffer);

			st = OriginNtQueryDirectoryFile((*it2), Event, ApcRoutine, ApcContext, IoStatusBlock, FileInformation, Length,
										FileInformationClass, ReturnSingleEntry, FileName, RestartScan);

			log_debug(L"\tstatus 0x%x", st);

			if (st == STATUS_NO_MORE_FILES) {
				log_debug(L"\tno more file in the repos");
				continue;
			}

			if (FAILED(st)) {
				log_error(L"\tfunction failed to list directory with status 0x%x", st);
				IoStatusBlock->Status = st;
				return st;
			}

			if (finalizeDirectoryListing(FileInformation, Length, FileInformationClass, entry.content) == 0) {
				log_debug(L"\tthere is an empty listing", st);
				continue;
			}

			log_debug(L"============ Result listing ================");
			dumpDirectoryList(FileInformation, FileInformationClass);
			return st;
		}

		log_debug(L"\tno more file in all repositories");
		IoStatusBlock->Status = STATUS_NO_MORE_FILES;
		return STATUS_NO_MORE_FILES;
	}

	return OriginNtQueryDirectoryFile(FileHandle, Event, ApcRoutine, ApcContext, IoStatusBlock, FileInformation, Length,
			FileInformationClass, ReturnSingleEntry, FileName, RestartScan);
}


NTSTATUS NTAPI myNtClose(HANDLE FileHandle)
{
	if(Logger::getSingleton().isLogging())
			return OriginNtClose(FileHandle);

	if (vf.containHandle(FileHandle)) {
		log_debug(L"myNtClose %lu", FileHandle);

		DirEntry& entry = vf.getHandle(FileHandle);
		std::list<HANDLE>::iterator it;

		entry.content.clear();
		for (it = entry.repos.begin() ; it != entry.repos.end() ; it++)
			OriginNtClose(*it);

		vf.rmHandle(FileHandle);
	}

	return OriginNtClose(FileHandle);
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
		log_error(L"Registry key %s do not exist", REGISTRY_PATH_KEY);
		return;
	}

	if (! reg.get(L"ProfileSrc", src)) {
		log_error(L"Failed to get registry key variable %s", REGISTRY_PATH_KEY);
		return;
	}

	conf.setSrcPath(src);

	if (!conf.load()) {
		log_error(L"Failed to load configuration file");
		return;
	}

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
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtQueryDirectoryFile, myNtQueryDirectoryFile, "NtQueryDirectoryFile");
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtClose, myNtClose, "NtClose");
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

	log_debug(L"Hooked success");
}

void releaseHooks() {
	//File API	
	Mhook_Unhook((PVOID*)&OriginNtCreateFile);	
	Mhook_Unhook((PVOID*)&OriginNtOpenFile);	
	Mhook_Unhook((PVOID*)&OriginNtQueryAttributesFile);	
	Mhook_Unhook((PVOID*)&OriginNtSetInformationFile);	
	Mhook_Unhook((PVOID*)&OriginNtQueryDirectoryFile);
	Mhook_Unhook((PVOID*)&OriginNtClose);
	
	//Reg API
	Mhook_Unhook((PVOID*)&OriginNtCreateKey);	
	Mhook_Unhook((PVOID*)&OriginNtOpenKey);	
	Mhook_Unhook((PVOID*)&OriginNtOpenKeyEx);
	log_debug(L"Un-Hooked program");
}
