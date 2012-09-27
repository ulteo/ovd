#include "stdafx.h"

#include "Hook.h"
#include "mhook-lib/mhook.h"

#include <stdlib.h>
#include <stdio.h>
#include <windows.h>
#include <ctime>
#include <shlobj.h> 
#include <shlwapi.h> 
/*
#if _WIN32 || _WIN64
#if _WIN64
//#define LOG_FILE "U:\\VFSLog64.log"
#else
//#define LOG_FILE "U:\\VFSLog32.log"
#endif //#if _WIN64
#endif //#if _WIN32 || _WIN64
*/

//ENABLE_LOGGING	
// 0 disable log, 1 enable log
#define ENABLE_LOGGING			0

#define LOG_FILE				L"U:\\.VFSLog.log"

#define REDIRECT_PATH			L"U:"
#define	SEPERATOR				L"\\"
#define DEVICE_PREFIX			L"\\??\\"
#define BLACKLIST_CONF_FILE		L"blacklist.conf"

#define HOOK_AND_LOG_FAILURE(pOri, pInt, szFunc)	if(!Mhook_SetHook(pOri, pInt))\
													{\
														log("Failed to hook %s", szFunc);\
													}

// is path d0 equals to d1
#define SAME_DIR(d0, d1)							wcscmp(d0, d1) == 0

//0 represents for CSIDL blacklist
//1 represents for allowed CSIDL 
#define IS_CSIDL_BLACK_LIST(flag)					flag == 0 ? true:false


////////////////////////////////////////////////////////////////////////
//	Global attributes
////////////////////////////////////////////////////////////////////////

bool g_bLogging = false;

//blacklist of folders should not be redirected 
//"PATH", "PATH/Temp" for example; or "PATH*" for any file or folder starts with "PATH"
std::vector<std::wstring>		g_vBlacklistFolder;

//Origin User Profile path
int g_iUserProfileStringLength = 0;

//Origin User Profile path with DevicePrefix //??//
std::wstring g_szDeviceUserProfilePath;

////////////////////////////////////////////////////////////////////////
//	Utility functions
////////////////////////////////////////////////////////////////////////

void setupBlackList(std::vector<std::wstring>* pvBlackList)
{	
	//Default blacklist
	pvBlackList->push_back(std::wstring(L"ntuser*"));
	pvBlackList->push_back(std::wstring(L"NTUSER*"));

	//Read conf file from CSIDL_COMMON_APPDATA\\ulteo\ovd
	WCHAR filename[MAX_PATH];
	SHGetSpecialFolderPathW(NULL, filename, CSIDL_COMMON_APPDATA, 0);
	lstrcatW(filename, L"\\ulteo\\ovd\\");
	lstrcatW(filename, BLACKLIST_CONF_FILE);
		
	//Check if conf file not exist
	WIN32_FIND_DATAW FindFileData;
	HANDLE hFind = FindFirstFileW(filename, &FindFileData);
	if (hFind == INVALID_HANDLE_VALUE) 
	{
		log("Blacklist configuration file not found. Setting CSIDL folders into blacklist by default.");

		//If conf file not exist, put every folder to black list except CSIDL_DESKTOP and CSIDL_PERSONAL
		//pvBlackList->push_back( getCSIDLFolderName(CSIDL_DESKTOP) );
		//pvBlackList->push_back( getCSIDLFolderName(CSIDL_PERSONAL) );
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_FAVORITES) );
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_MYMUSIC) );
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_MYVIDEO) );
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_MYPICTURES) );
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_SENDTO) );
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_STARTMENU) );
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_NETHOOD) );
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_APPDATA) );
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_PRINTHOOD) );
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_LOCAL_APPDATA) );
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_TEMPLATES) );
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_COOKIES) );
		pvBlackList->push_back( L"Downloads");
		pvBlackList->push_back( L"Links");
		pvBlackList->push_back( L"Searches");
		pvBlackList->push_back( L"Contacts");
		pvBlackList->push_back( L"Saved Games");

		//Don't need to continue if the conf file is not exist
		return;
	}
	else
	{
		FindClose(hFind);
	}

	//Parse CSIDL black list
	int iDefault = 0;
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_DESKTOP", iDefault, filename)) )
	{
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_DESKTOP) );
	}
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_PERSONAL", iDefault, filename)) )
	{
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_PERSONAL) );
	}
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_FAVORITES", iDefault, filename)) )
	{
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_FAVORITES) );
	}
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_MYMUSIC", iDefault, filename)) )
	{
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_MYMUSIC) );
	}
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_MYVIDEO", iDefault, filename)) )
	{
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_MYVIDEO) );
	}
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_MYPICTURES", iDefault, filename)) )
	{
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_MYPICTURES) );
	}
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_SENDTO", iDefault, filename)) )
	{
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_SENDTO) );
	}
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_STARTMENU", iDefault, filename)) )
	{
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_STARTMENU) );
	}
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_NETHOOD", iDefault, filename)) )
	{
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_NETHOOD) );
	}
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_APPDATA", iDefault, filename)) )
	{
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_APPDATA) );
	}
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_PRINTHOOD", iDefault, filename)) )
	{
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_PRINTHOOD) );
	}
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_LOCAL_APPDATA", iDefault, filename)) )
	{
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_LOCAL_APPDATA) );
	}
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_TEMPLATES", iDefault, filename)) )
	{
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_TEMPLATES) );
	}
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CSIDL_COOKIES", iDefault, filename)) )
	{
		pvBlackList->push_back( getCSIDLFolderName(CSIDL_COOKIES) );
	}
	//The following are not defined CSIDL, but usually comes with UserProfile
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"DOWNLOADS", iDefault, filename)) )
	{
		pvBlackList->push_back( L"Downloads");
	}
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"LINKS", iDefault, filename)) )
	{
		pvBlackList->push_back( L"Links");
	}
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"SEARCHES", iDefault, filename)) )
	{
		pvBlackList->push_back( L"Searches");
	}
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"CONTACTS", iDefault, filename)) )
	{
		pvBlackList->push_back( L"Contacts");
	}
	if( IS_CSIDL_BLACK_LIST(GetPrivateProfileIntW(L"CSIDL", L"SAVED_GAMES", iDefault, filename)) )
	{
		pvBlackList->push_back( L"Saved Games");
	}
		
	//Parse SELF_DEFINE black list
	int charsCount;
	WCHAR szBlackList[4096] = {};
	charsCount = GetPrivateProfileSectionW(L"SELF_DEFINE", szBlackList, 4096, filename);
	int start = 0;
	for (int i = 0; i < charsCount; i++)
	{
		WCHAR curChar = szBlackList[i];
		if (curChar == L'\0')
		{
			std::wstring item( szBlackList + start, i);
			pvBlackList->push_back(item);
			start = i+1;			
		}
	}
}

void obtainCSIDLFolders()
{
	//Obtain USERPROFILE path
	WCHAR szUserProfilePath[MAX_PATH];
	SHGetSpecialFolderPathW(NULL, szUserProfilePath, CSIDL_PROFILE, 0);
		
	//Profile path string length
	g_iUserProfileStringLength = std::wstring(szUserProfilePath).length();

	// Profile path with device prefiex
	g_szDeviceUserProfilePath.append(DEVICE_PREFIX);
	g_szDeviceUserProfilePath.append(szUserProfilePath);
}

std::wstring getCSIDLFolderName(int csidl)
{
	WCHAR temp[MAX_PATH];
	SHGetSpecialFolderPathW(NULL, temp, csidl, 0);
	return std::wstring(temp).substr(g_iUserProfileStringLength + 1);
}

bool substitutePath(std::wstring szPath, 
					std::wstring szTargetPath, 
					std::wstring szRedirectPath, 
					bool bSubstituteSelf,
					std::wstring* pszSubstitutedPath)
{	
	bool bRet = false;
	std::wstring szPathParent = szPath.substr(0, szTargetPath.length());
	
	//Path contains target folder
	if( SAME_DIR(szPathParent.c_str(), szTargetPath.c_str()))
	{
		//Path is children of the target folder
		if(szPath.length() > szPathParent.length())
		{
			//szPathRemain: the remain path without SEPERATOR at begin
			std::wstring szRemainPath = szPath.substr(szTargetPath.length() + 1);
			
			//Check if szRemainPath is in black list
			if(hasFolder(szRemainPath, g_vBlacklistFolder))
			{
				;
			}
			else
			{
				*pszSubstitutedPath = szRedirectPath + SEPERATOR + szRemainPath;
				bRet = true;
			}
		}
		//Path is target path
		else 
		{
			if(bSubstituteSelf)
			{
				*pszSubstitutedPath = szRedirectPath + SEPERATOR;
				bRet = true;
			}
		}
	}

	return bRet;
}

bool getPath(POBJECT_ATTRIBUTES ObjectAttributes, WCHAR* strPath)
{
	if (!ObjectAttributes->RootDirectory && !ObjectAttributes->ObjectName)
	{
		return false;
	}
	if (ObjectAttributes->RootDirectory)
	{
		return false;
	}
	if (NULL != ObjectAttributes && NULL != ObjectAttributes->ObjectName && ObjectAttributes->ObjectName->Length > 0)
	{
		lstrcatW(strPath, ObjectAttributes->ObjectName->Buffer);
	}
	return true;
}

bool hasFolder(std::wstring szFolder, std::vector<std::wstring> vList)
{
	for(int i=0; i<vList.size(); ++i)
	{
		std::wstring blackItm = vList[i];
		int len;
		if(blackItm[blackItm.length()-1] == L'*')// any word
		{
			len = blackItm.length() - 1;
		}
		else
		{
			len = blackItm.length();
		}
		std::wstring path = szFolder.substr(0, len);
		blackItm = blackItm.substr(0, len);

		if(SAME_DIR(path.c_str(), blackItm.c_str()))
		{
			return true;
		}
	}
	return false;
}

bool redirectFilePath(WCHAR* pszFilePath, PUNICODE_STRING* puniszRedirectPath, WCHAR* pszReserve)
{	
	std::wstring szResult;
	if ( substitutePath(pszFilePath, 
			g_szDeviceUserProfilePath, 
			std::wstring(REDIRECT_PATH), 
			true, 
			&szResult) )
	{
		lstrcatW(pszReserve, DEVICE_PREFIX);
		lstrcatW(pszReserve, szResult.c_str());
		
		log("Redirected filename=%S", pszReserve);

		(*puniszRedirectPath)->Buffer = pszReserve;   
		(*puniszRedirectPath)->Length = (4 + szResult.length()) * 2;  // 4 for \\??\\, * 2 for wchar 
		(*puniszRedirectPath)->MaximumLength = (4 + szResult.length()) * 2 + 2; // + 2 for "/0"
		
		return true;
	}

	return false;
}

void logErrorStatus(NTSTATUS stus)
{
	log("Return STATUS Error, id = %ld", stus);
}

////////////////////////////////////////////////////////////////////////
//	API Hooking
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
	if(g_bLogging)
	{
		return OriginNtCreateFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, AllocationSize, 
			FileAttributes, ShareAccess, CreateDisposition, CreateOptions, EaBuffer, EaLength);
	}
	
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL)
	{
		log("NtCreateFile: handle=%x, filename=%wZ", FileHandle, ObjectAttributes->ObjectName);
	}

	//Reserve origin data before modified
	UNICODE_STRING uniszRestore;
	bool bStore = false;
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL)
	{
		bStore = true;
		uniszRestore= *(ObjectAttributes->ObjectName);
	}

	WCHAR szFilePath[MAX_PATH] = {0};
	WCHAR szTemp[MAX_PATH]  = {0};
	if(getPath(ObjectAttributes, szFilePath))
	{
		redirectFilePath(szFilePath, &ObjectAttributes->ObjectName, szTemp);
	}
	
	NTSTATUS stus = OriginNtCreateFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, AllocationSize, 
		FileAttributes, ShareAccess, CreateDisposition, CreateOptions, EaBuffer, EaLength);

	logErrorStatus(stus);
	
	//Restore original data after modified
	if(bStore)
	{
		ObjectAttributes->ObjectName->Buffer = uniszRestore.Buffer; 
		ObjectAttributes->ObjectName->Length = uniszRestore.Length; 
		ObjectAttributes->ObjectName->MaximumLength = uniszRestore.MaximumLength; 
	}

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
	if(g_bLogging)
	{
		return OriginNtOpenFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, ShareAccess, OpenOptions);
	}
		
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL)
	{
		log("NtOpenFile: handle=%x, filename=%wZ", FileHandle, ObjectAttributes->ObjectName);
	}

	//Reserve origin data before modified
	UNICODE_STRING uniszRestore;
	bool bStore = false;
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL)
	{
		bStore = true;
		uniszRestore= *(ObjectAttributes->ObjectName);
	}

	WCHAR szFilePath[MAX_PATH] = {0};
	WCHAR szTemp[MAX_PATH]  = {0};
	if(getPath(ObjectAttributes, szFilePath))
	{
		redirectFilePath(szFilePath, &ObjectAttributes->ObjectName, szTemp);
	}
		
	NTSTATUS stus = OriginNtOpenFile(FileHandle, DesiredAccess, ObjectAttributes, IoStatusBlock, ShareAccess, OpenOptions);
		
	logErrorStatus(stus);
	
	//Restore original data after modified
	if(bStore)
	{
		ObjectAttributes->ObjectName->Buffer = uniszRestore.Buffer; 
		ObjectAttributes->ObjectName->Length = uniszRestore.Length; 
		ObjectAttributes->ObjectName->MaximumLength = uniszRestore.MaximumLength; 
	}
		
	return stus;
}

//-------------------------------------------------------------------//
//NtQueryAttributesFile
typedef struct _FILE_BASIC_INFORMATION {
  LARGE_INTEGER CreationTime;
  LARGE_INTEGER LastAccessTime;
  LARGE_INTEGER LastWriteTime;
  LARGE_INTEGER ChangeTime;
  ULONG         FileAttributes;
} FILE_BASIC_INFORMATION, *PFILE_BASIC_INFORMATION;

typedef NTSTATUS (NTAPI* PtrNtQueryAttributesFile)(
						POBJECT_ATTRIBUTES ObjectAttributes,
						PFILE_BASIC_INFORMATION FileInformation);
PtrNtQueryAttributesFile OriginNtQueryAttributesFile = (PtrNtQueryAttributesFile)GetProcAddress(GetModuleHandle(L"ntdll"), "NtQueryAttributesFile");
NTSTATUS NTAPI myNtQueryAttributesFile(	POBJECT_ATTRIBUTES ObjectAttributes,
										PFILE_BASIC_INFORMATION FileInformation)
{	
	if(g_bLogging)
	{
		return OriginNtQueryAttributesFile(ObjectAttributes, FileInformation);
	}
	
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL)
	{
		log("NtQueryAttributesFile: filename=%wZ", ObjectAttributes->ObjectName);
	}

	//Reserve origin data before modified
	UNICODE_STRING uniszRestore;
	bool bStore = false;
	if(ObjectAttributes != NULL && ObjectAttributes->ObjectName != NULL)
	{
		bStore = true;
		uniszRestore= *(ObjectAttributes->ObjectName);
	}

	WCHAR szFilePath[MAX_PATH] = {0};
	WCHAR szTemp[MAX_PATH]  = {0};
	if(getPath(ObjectAttributes, szFilePath))
	{
		redirectFilePath(szFilePath, &ObjectAttributes->ObjectName, szTemp);
	}
		
	NTSTATUS stus = OriginNtQueryAttributesFile(ObjectAttributes, FileInformation);

	logErrorStatus(stus);
	
	//Restore original data after modified
	if(bStore)
	{
		ObjectAttributes->ObjectName->Buffer = uniszRestore.Buffer; 
		ObjectAttributes->ObjectName->Length = uniszRestore.Length; 
		ObjectAttributes->ObjectName->MaximumLength = uniszRestore.MaximumLength; 
	}
	
	return stus;
}

//-------------------------------------------------------------------//
//NtSetInformationFile

//NOTE: 
//	http://www.koders.com/c/fid85C174ABA2F3B414046CE7B78DAF9E789A36267B.aspx
//	http://topic.csdn.net/u/20110622/12/1977f8ab-7c89-42d3-9d9a-3bc4239c5b63.html
typedef enum _MY_FILE_INFORMATION_CLASS {
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
} MY_FILE_INFORMATION_CLASS, *MY_PFILE_INFORMATION_CLASS;

typedef struct _FILE_LINK_RENAME_INFORMATION { // Info Classes 10 and 11
BOOLEAN ReplaceIfExists;
HANDLE RootDirectory;
ULONG FileNameLength;
WCHAR FileName[1];
} FILE_LINK_INFORMATION, *PFILE_LINK_INFORMATION, FILE_RENAME_INFORMATION, *PFILE_RENAME_INFORMATION;

#define FILE_RENAME_SIZE  MAX_PATH +sizeof(FILE_RENAME_INFORMATION)

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
	log("NtSetInformationFile: handle=%x, fInfoId=%d", FileHandle, FileInformationClass);

	//Rename
	if(FileInformationClass == FileRenameInformation)
	{
		FILE_RENAME_INFORMATION* pFileRename = (PFILE_RENAME_INFORMATION)FileInformation;
		unsigned long length =  pFileRename->FileNameLength/2; // wide char / 2
		std::wstring szOriginPath(pFileRename->FileName, length);
		
		std::wstring szResult;
		//Reset File_RENAME_INFO, redirect if substitutePath success
		if( substitutePath(	szOriginPath.c_str(), 
							g_szDeviceUserProfilePath, 
							std::wstring(REDIRECT_PATH), 
							true, 
							&szResult) )
		{
			szResult = DEVICE_PREFIX + szResult;

			FILE_RENAME_INFORMATION* pNewFileRename;
			USHORT Buffer[FILE_RENAME_SIZE];
			pNewFileRename = (FILE_RENAME_INFORMATION*)Buffer;

			pNewFileRename->ReplaceIfExists = pFileRename->ReplaceIfExists;
			pNewFileRename->RootDirectory = pFileRename->RootDirectory;
			pNewFileRename->FileNameLength = szResult.length() * 2;// wide char * 2
			memcpy(pNewFileRename->FileName, szResult.c_str(), pFileRename->FileNameLength);

			log("FileRenameInformation: NEW Filename : %S", szResult.c_str());

			return OriginNtSetInformationFile(FileHandle, IoStatusBlock, 
				pNewFileRename, FILE_RENAME_SIZE, FileInformationClass);
		}
		

	}

	return OriginNtSetInformationFile(FileHandle, IoStatusBlock, FileInformation, 
		FileInformationLength, FileInformationClass);
}
////////////////////////////////////////////////////////////////////////
//	Util Functions:
////////////////////////////////////////////////////////////////////////
void setupHooks()
{
	obtainCSIDLFolders();
	setupBlackList(&g_vBlacklistFolder);

	//Nt
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtCreateFile, myNtCreateFile, "NtCreateFile");
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtOpenFile, myNtOpenFile, "NtOpenFile");
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtQueryAttributesFile, myNtQueryAttributesFile, "NtQueryAttributesFile");
	HOOK_AND_LOG_FAILURE((PVOID*)&OriginNtSetInformationFile, myNtSetInformationFile, "NtSetInformationFile");
	
	log(" ======= Hooked =======");
}

void releaseHooks()
{
	//Nt
	Mhook_Unhook((PVOID*)&OriginNtCreateFile);	
	Mhook_Unhook((PVOID*)&OriginNtOpenFile);	
	Mhook_Unhook((PVOID*)&OriginNtQueryAttributesFile);	
	Mhook_Unhook((PVOID*)&OriginNtSetInformationFile);	

	log(" ======= UnHooked =======");
}

void log(char *fmt,...)
{
	if(!ENABLE_LOGGING)
		return;

	g_bLogging = true;

	va_list args;
	char modname[200];

	char temp[5000];
	HANDLE hFile;

	GetModuleFileNameA(NULL, modname, sizeof(modname));

	if((hFile = CreateFileW(LOG_FILE, GENERIC_WRITE, 0, NULL, OPEN_ALWAYS, FILE_ATTRIBUTE_NORMAL, NULL)) <0)
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

	g_bLogging = false;
}