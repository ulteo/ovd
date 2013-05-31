// Copyright (C) 2012 
// Author Wei-Jen Chen 2012

#ifndef _Hook_H
#define _Hook_H

#include <string>
#include <vector>
#include <Winternl.h>	//Nt 

#define VIRTUAL_SYSTEM_CONF_FILE	L"VirtualSystem.conf"

#define HOOK_AND_LOG_FAILURE(pOri, pInt, szFunc)	if(!Mhook_SetHook(pOri, pInt))\
													{\
														Logger::getSingleton().debug(L"Failed to hook %s", szFunc);\
													}


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


//-------------------------------------------------------------------//
//NtOpenFile
typedef NTSTATUS (NTAPI* PtrNtOpenFile)(PHANDLE FileHandle,
										ACCESS_MASK DesiredAccess,
										POBJECT_ATTRIBUTES ObjectAttributes,
										PIO_STATUS_BLOCK IoStatusBlock,
										ULONG ShareAccess,
										ULONG OpenOptions);


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


//-------------------------------------------------------------------//
//NtCreateKey
typedef NTSTATUS (NTAPI* PtrNtCreateKey)(	PHANDLE KeyHandle,
											ACCESS_MASK DesiredAccess,
											POBJECT_ATTRIBUTES ObjectAttributes,
											ULONG TitleIndex,
											PUNICODE_STRING Class,
											ULONG CreateOptions,
											PULONG Disposition);


//-------------------------------------------------------------------//
//NtOpenKey
typedef NTSTATUS (NTAPI* PtrNtOpenKey)(	PHANDLE KeyHandle,
										ACCESS_MASK DesiredAccess,
										POBJECT_ATTRIBUTES ObjectAttributes);


//-------------------------------------------------------------------//
//NtOpenKeyEx
typedef NTSTATUS (NTAPI* PtrNtOpenKeyEx)(	PHANDLE KeyHandle,
											ACCESS_MASK DesiredAccess,
											POBJECT_ATTRIBUTES ObjectAttributes,
											ULONG OpenOptions);








void setupHooks();
void releaseHooks();

#endif //#ifndef _Hook_H
