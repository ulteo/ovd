#ifndef _Hook_H
#define _Hook_H

#include <string>
#include <Winternl.h>	//Nt 

void obtainUserSHFolders();

// Filter the input path lpFilePath and check if its contains or based on szTargetPath.
// If the lpFilePath contains szTargetPath, this function replace the szTargetPath part with szRedirectPath folder
// @lpFilePath: input path for comparison
// @szTargetPath: base path for comparison
// @szRedirectPath: the path for lpFilePath to redirect to
// @pszOutputPath: result path after filter
bool filter(LPCWSTR lpFilePath, std::wstring szTargetPath, std::wstring szRedirectPath, std::wstring* pszOutputPath);

// Get file path from by ObjectAttributes
// If ObjectAttributes has file path, return true, else return false
// File path saved to strPath as output.
// @ObjectAttributes: input data to find file path
// @strPath: output file path
BOOL GetPath(POBJECT_ATTRIBUTES ObjectAttributes, WCHAR* strPath);

void setupHooks();
void releaseHooks();
void log(char *fmt,...);

#endif //#ifndef _Hook_H



//Action			dll				API							hook imp
//
//CreateFile
//				SHLWAPI			PathFileExistsW						v
//				Kernelbase		GetFileAttributesW
//				ntdll			ZwQueryAttributesFile				v
//
//CreateFile
//				SHELL32			SHCreateDirectoryExA				v
//				ntdll			NtCreateFile						v
//
//QueryBasicInformationFile
//				kernel32		SHCreateDirectoryExA				v
//				kernel32		GetFileInformationByHandleEx		v
//				ntdll			ZwQueryInformationFile				v
//
//QueryStandardInformationFile
//				kernel32		GetFileInformationByHandleEx		v
//				ntdll			ZwQueryInformationFile				v
//
//QuerryAttributeInformationVolume
//				kernel32		SHCreateDirectoryExA				v
//				kernel32		SHCreateItemFromRelativeName
//				ntdll			ZwQueryVolumeInformationFile		v
//
//QueryBasicInformationFile
//				kernel32		SHCreateDirectoryExA				v
//				kernel32		GetFileInformationByHandleEx		v
//				ntdll			ZwQueryInformationFile				v
//
//CloseFile			
//				shell32			SHCreateDirectoryExA				v
//				Kernel32		CloseHandle
//				ntdll			ZwClose
//
//QueryDirectory
//				shell32			SHGetFolderPathW
//				ntdll			NtQueryDirectoryFile				v
//
//
//
//CreateFile	
//				advapi32		GetNamedSecurityInfoW
//				ntdll			ZwOpenFile							v
//				
//QuerySecurityFile
//				advapi32		GetNamedSecurityInfoW
//				ntdll			ZwQuerySecurityObject				v
//				
//QueryNameInformationFile
//				advapi32		GetNamedSecurityInfoW
//				ntdll			NtQueryObject						v
//
//QueryBasicInformationFile
//				advapi32		GetNamedSecurityInfoW
//				ntdll			ZwQueryInformationFile				v
//				
//CloseFile			
//				shell32			SHCreateDirectoryExA				v
//				Kernel32		CloseHandle
//				ntdll			ZwClose

//FileSystemControl
//				shell32			SHGetFolderPathW
//				ntdll			ZwFsControlFile
				