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
//NtQueryDirectoryFile
typedef NTSTATUS (NTAPI *PtrNtQueryDirectoryFile)(
										IN HANDLE FileHandle,
										IN HANDLE Event OPTIONAL,
										IN PVOID ApcRoutine OPTIONAL,
										IN PVOID ApcContext OPTIONAL,
										OUT PIO_STATUS_BLOCK IoStatusBlock,
										OUT PVOID FileInformation,
										IN ULONG Length,
										IN FILE_INFORMATION_CLASS FileInformationClass,
										IN BOOLEAN ReturnSingleEntry,
										IN PUNICODE_STRING FileName OPTIONAL,
										IN BOOLEAN RestartScan);



#define STATUS_SUCCESS          0x00000000
#define STATUS_NO_MORE_FILES    0x80000006

//-------------------------------------------------------------------//
//NtClose
typedef NTSTATUS (NTAPI *PtrNtClose)(IN HANDLE Handle);


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
	FileDirectoryInformation1 = 1,
	FileFullDirectoryInformation,
	FileBothDirectoryInformation,
	FileBasicInformation,
	FileStandardInformation,
	FileInternalInformation,
	FileEaInformation,
	FileAccessInformation,
	FileNameInformation,
	FileRenameInformation,
	FileLinkInformation,
	FileNamesInformation,
	FileDispositionInformation,
	FilePositionInformation,
	FileFullEaInformation,
	FileModeInformation,
	FileAlignmentInformation,
	FileAllInformation,
	FileAllocationInformation,
	FileEndOfFileInformation,
	FileAlternateNameInformation,
	FileStreamInformation,
	FilePipeInformation,
	FilePipeLocalInformation,
	FilePipeRemoteInformation,
	FileMailslotQueryInformation,
	FileMailslotSetInformation,
	FileCompressionInformation,
	FileObjectIdInformation,
	FileCompletionInformation,
	FileMoveClusterInformation,
	FileQuotaInformation,
	FileReparsePointInformation,
	FileNetworkOpenInformation,
	FileAttributeTagInformation,
	FileTrackingInformation,
	FileIdBothDirectoryInformation,
	FileIdFullDirectoryInformation,
	FileValidDataLengthInformation,
	FileShortNameInformation,
	FileIoCompletionNotificationInformation,
	FileIoStatusBlockRangeInformation,
	FileIoPriorityHintInformation,
	FileSfioReserveInformation,
	FileSfioVolumeInformation,
	FileHardLinkInformation,
	FileProcessIdsUsingFileInformation,
	FileNormalizedNameInformation,
	FileNetworkPhysicalNameInformation,
	FileIdGlobalTxDirectoryInformation,
	FileIsRemoteDeviceInformation,
	FileAttributeCacheInformation,
	FileNumaNodeInformation,
	FileStandardLinkInformation,
	FileRemoteProtocolInformation,
	FileMaximumInformation
} MY_FILE_INFORMATION_CLASS, *PMY_FILE_INFORMATION_CLASS;

typedef struct _FILE_LINK_RENAME_INFORMATION{ // Info Classes 10 and 11
	BOOLEAN ReplaceIfExists;
	HANDLE RootDirectory;
	ULONG FileNameLength;
	WCHAR FileName[1];
}FILE_LINK_INFORMATION, *PFILE_LINK_INFORMATION, FILE_RENAME_INFORMATION, *PFILE_RENAME_INFORMATION;


typedef struct _FILE_BOTH_DIR_INFORMATION {
	ULONG         NextEntryOffset;
	ULONG         FileIndex;
	LARGE_INTEGER CreationTime;
	LARGE_INTEGER LastAccessTime;
	LARGE_INTEGER LastWriteTime;
	LARGE_INTEGER ChangeTime;
	LARGE_INTEGER EndOfFile;
	LARGE_INTEGER AllocationSize;
	ULONG         FileAttributes;
	ULONG         FileNameLength;
	ULONG         EaSize;
	CCHAR         ShortNameLength;
	WCHAR         ShortName[12];
	WCHAR         FileName[1];
} FILE_BOTH_DIR_INFORMATION, *PFILE_BOTH_DIR_INFORMATION;


typedef struct _FILE_DIRECTORY_INFORMATION {
	ULONG         NextEntryOffset;
	ULONG         FileIndex;
	LARGE_INTEGER CreationTime;
	LARGE_INTEGER LastAccessTime;
	LARGE_INTEGER LastWriteTime;
	LARGE_INTEGER ChangeTime;
	LARGE_INTEGER EndOfFile;
	LARGE_INTEGER AllocationSize;
	ULONG         FileAttributes;
	ULONG         FileNameLength;
	WCHAR         FileName[1];
} FILE_DIRECTORY_INFORMATION, *PFILE_DIRECTORY_INFORMATION;


typedef struct _FILE_FULL_DIR_INFORMATION {
	ULONG         NextEntryOffset;
	ULONG         FileIndex;
	LARGE_INTEGER CreationTime;
	LARGE_INTEGER LastAccessTime;
	LARGE_INTEGER LastWriteTime;
	LARGE_INTEGER ChangeTime;
	LARGE_INTEGER EndOfFile;
	LARGE_INTEGER AllocationSize;
	ULONG         FileAttributes;
	ULONG         FileNameLength;
	ULONG         EaSize;
	WCHAR         FileName[1];
} FILE_FULL_DIR_INFORMATION, *PFILE_FULL_DIR_INFORMATION;


typedef struct _FILE_ID_BOTH_DIR_INFORMATION {
	ULONG         NextEntryOffset;
	ULONG         FileIndex;
	LARGE_INTEGER CreationTime;
	LARGE_INTEGER LastAccessTime;
	LARGE_INTEGER LastWriteTime;
	LARGE_INTEGER ChangeTime;
	LARGE_INTEGER EndOfFile;
	LARGE_INTEGER AllocationSize;
	ULONG         FileAttributes;
	ULONG         FileNameLength;
	ULONG         EaSize;
	CCHAR         ShortNameLength;
	WCHAR         ShortName[12];
	LARGE_INTEGER FileId;
	WCHAR         FileName[1];
} FILE_ID_BOTH_DIR_INFORMATION, *PFILE_ID_BOTH_DIR_INFORMATION;


typedef struct _FILE_ID_FULL_DIR_INFORMATION {
	ULONG         NextEntryOffset;
	ULONG         FileIndex;
	LARGE_INTEGER CreationTime;
	LARGE_INTEGER LastAccessTime;
	LARGE_INTEGER LastWriteTime;
	LARGE_INTEGER ChangeTime;
	LARGE_INTEGER EndOfFile;
	LARGE_INTEGER AllocationSize;
	ULONG         FileAttributes;
	ULONG         FileNameLength;
	ULONG         EaSize;
	LARGE_INTEGER FileId;
	WCHAR         FileName[1];
} FILE_ID_FULL_DIR_INFORMATION, *PFILE_ID_FULL_DIR_INFORMATION;


typedef struct _FILE_NAMES_INFORMATION {
	ULONG NextEntryOffset;
	ULONG FileIndex;
	ULONG FileNameLength;
	WCHAR FileName[1];
} FILE_NAMES_INFORMATION, *PFILE_NAMES_INFORMATION;


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
