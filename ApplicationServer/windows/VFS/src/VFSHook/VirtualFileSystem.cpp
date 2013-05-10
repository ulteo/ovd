// Copyright (C) 2012 
// Author Wei-Jen Chen 2012
// Author Shen-Hao chen 2012

#include "stdafx.h"
#include "VirtualFileSystem.h"

#include <stdlib.h>
#include <shlobj.h> 
#include <shlwapi.h> 

#define	SEPERATOR		L"\\"
#define DEVICE_PREFIX	L"\\??\\"

VirtualFileSystem* VirtualFileSystem::m_sInstance = NULL;

VirtualFileSystem::VirtualFileSystem():
m_szVirtualFileSpace(L""),
m_iUserProfileStringLength(0),
m_szDeviceUserProfilePath(L"")
{
}

VirtualFileSystem::~VirtualFileSystem()
{
}

VirtualFileSystem& VirtualFileSystem::getSingleton()
{
	if(m_sInstance == NULL) 
	{
		m_sInstance = new VirtualFileSystem();
	}
	return (*m_sInstance);
}

VirtualFileSystem* VirtualFileSystem::getSingletonPtr()
{
	if(m_sInstance == NULL) 
	{
		m_sInstance = new VirtualFileSystem();
	}
	return m_sInstance;
}

bool VirtualFileSystem::init()
{	
	//Obtain USERPROFILE path
	WCHAR szUserProfilePath[MAX_PATH];
	SHGetSpecialFolderPathW(NULL, szUserProfilePath, CSIDL_PROFILE, 0);
		
	//Profile path string length
	m_iUserProfileStringLength = std::wstring(szUserProfilePath).length();

	// Profile path with device prefiex
	m_szDeviceUserProfilePath.append(DEVICE_PREFIX);
	m_szDeviceUserProfilePath.append(szUserProfilePath);
	
	return true;
}

void VirtualFileSystem::setVirtualFileSpace(std::wstring szFileSpacePath)
{
	m_szVirtualFileSpace = szFileSpacePath;
}

bool VirtualFileSystem::parseFileSystem(std::wstring szConfFilename)
{	
	// Check if conf file not exist
	WIN32_FIND_DATAW FindFileData;
	HANDLE hFind = FindFirstFileW(szConfFilename.c_str(), &FindFileData);
	if (hFind == INVALID_HANDLE_VALUE) 
	{
		return false;
	}
	else
	{
		FindClose(hFind);
	}

	// Parse virtual file space
	WCHAR szPath[MAX_PATH];
	GetPrivateProfileStringW(L"VirtualFileSpace", L"Profile", L"", szPath, MAX_PATH, szConfFilename.c_str());
	std::wstring szVirtualFileSpace(szPath);
	if( szVirtualFileSpace.length() == 0)
	{
		return false;
	}
	else
	{
		setVirtualFileSpace(szVirtualFileSpace);
	}

	// Parse CSIDL black list
	int iDefault = 0;
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"CSIDL_DESKTOP", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( _getCSIDLFolderName(CSIDL_DESKTOP) );
	}
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"CSIDL_PERSONAL", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( _getCSIDLFolderName(CSIDL_PERSONAL) );
	}
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"CSIDL_FAVORITES", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( _getCSIDLFolderName(CSIDL_FAVORITES) );
	}
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"CSIDL_MYMUSIC", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( _getCSIDLFolderName(CSIDL_MYMUSIC) );
	}
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"CSIDL_MYVIDEO", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( _getCSIDLFolderName(CSIDL_MYVIDEO) );
	}
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"CSIDL_MYPICTURES", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( _getCSIDLFolderName(CSIDL_MYPICTURES) );
	}
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"CSIDL_SENDTO", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( _getCSIDLFolderName(CSIDL_SENDTO) );
	}
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"CSIDL_STARTMENU", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( _getCSIDLFolderName(CSIDL_STARTMENU) );
	}
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"CSIDL_NETHOOD", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( _getCSIDLFolderName(CSIDL_NETHOOD) );
	}
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"CSIDL_APPDATA", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( _getCSIDLFolderName(CSIDL_APPDATA) );
	}
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"CSIDL_PRINTHOOD", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( _getCSIDLFolderName(CSIDL_PRINTHOOD) );
	}
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"CSIDL_LOCAL_APPDATA", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( _getCSIDLFolderName(CSIDL_LOCAL_APPDATA) );
	}
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"CSIDL_TEMPLATES", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( _getCSIDLFolderName(CSIDL_TEMPLATES) );
	}
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"CSIDL_COOKIES", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( _getCSIDLFolderName(CSIDL_COOKIES) );
	}
	// The following are not defined CSIDL, but usually comes with UserProfile
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"DOWNLOADS", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( L"Downloads");
	}
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"LINKS", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( L"Links");
	}
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"SEARCHES", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( L"Searches");
	}
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"CONTACTS", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( L"Contacts");
	}
	if( _isCsidlBlacklist( GetPrivateProfileIntW(L"CSIDL", L"SAVED_GAMES", iDefault, szConfFilename.c_str())) )
	{
		addFileBlacklistPath( L"Saved Games");
	}
	
	// Parse SELF_DEFINE black list
	int charsCount;
	WCHAR szBlackList[4096] = {};
	charsCount = GetPrivateProfileSectionW(L"SELF_DEFINE", szBlackList, 4096, szConfFilename.c_str());
	int start = 0;
	for (int i = 0; i < charsCount; i++)
	{
		WCHAR curChar = szBlackList[i];
		if (curChar == L'\0')
		{
			std::wstring item( szBlackList + start, i);
			addFileBlacklistPath(item);
			start = i+1;			
		}
	}

	return true;
}

bool VirtualFileSystem::parseRegSystem(std::wstring szConfFilename)
{
	// Check if conf file not exist
	WIN32_FIND_DATAW FindFileData;
	HANDLE hFind = FindFirstFileW(szConfFilename.c_str(), &FindFileData);
	if (hFind == INVALID_HANDLE_VALUE) 
	{
		return false;
	}
	else
	{
		FindClose(hFind);
	}

	// Parse Registry Redirect list
	int charsCount;
	WCHAR szRedirectList[4096] = {};
	std::wstring szSrc, szDst;
	charsCount = GetPrivateProfileSectionW(L"Registry", szRedirectList, 4096, szConfFilename.c_str());
	int start = 0;

	for (int i = 0; i < charsCount; i++)
	{
		WCHAR curChar = szRedirectList[i];
				
		if (curChar == L'=')
		{
			// strip out spaces before '='
			int lastChar = i-1;
			while( szRedirectList[lastChar] == L' ' || szRedirectList[lastChar] == L'\t')
			{
				lastChar--;
			}

			szSrc = std::wstring( szRedirectList + start, lastChar - start + 1 );
			start = i + 1;
		}
		if (curChar == L'\0')
		{
			// To make pair, need a src path before dst path
			if ( !szSrc.empty() )	
			{				
				// strip out spaces after '='
				while( szRedirectList[start] == L' ' || szRedirectList[start] == L'\t')
				{
					start++;
				}

				szDst = std::wstring( szRedirectList + start, i - start );
				start = i + 1;
				
				addRegRedirectionPath(szSrc, szDst);
				szSrc.clear();
				szDst.clear();
			}
		}
	}

	return true;
}

bool VirtualFileSystem::_isCsidlBlacklist(const int flag)
{
	return flag == 0 ? true:false;
}

std::wstring VirtualFileSystem::_getCSIDLFolderName(const int csidl)
{
	WCHAR temp[MAX_PATH];
	SHGetSpecialFolderPathW(NULL, temp, csidl, 0);
	return std::wstring(temp).substr(m_iUserProfileStringLength + 1);
}

void VirtualFileSystem::addFileBlacklistPath(std::wstring szPath)
{
	m_vFileBlacklist.push_back(szPath);
}

void VirtualFileSystem::addRegRedirectionPath(std::wstring szSrc, std::wstring szDst)
{
	m_mRegRedirectPaths[szSrc] = szDst;
}

std::wstring VirtualFileSystem::_getParent( const std::wstring& szPathRef, const std::wstring& szSrcRef )
{
	std::wstring szPathParent = L"";

	// If Source path is longer than Path, most not be parent
	if( szSrcRef.length() > szPathRef.length() )	
		;
	else
	{
		// clip parent path
		szPathParent = szPathRef.substr( 0, szSrcRef.length() );
		std::wstring szSepCheck = szPathRef.substr( szSrcRef.length(), 1 );
		
		// check if last character of source path is \\ or \0, 
		// if not, parent path is not szPathRef[0, szSrcRef.length()]
		if ( szSepCheck == SEPERATOR || szSepCheck == L"\0")
		{
			;
		}
		else
		{
			szPathParent = L"";
		}
	}

	return szPathParent;
}

bool VirtualFileSystem::substitutePath(	const std::wstring& szPathRef, 
										const std::wstring& szSrcRef, 
										const std::wstring& szDstRef, 
										bool bSubstituteSelf,
										std::wstring* pszSubstitutedPath)
{	
	bool bSubstituted = false;
	std::wstring szPathParent = _getParent( szPathRef, szSrcRef );

	// Path contains target folder
	if( _isFilePathEqual( szPathParent, szSrcRef ) )
	{
		// Path is target path
		if ( _isFilePathEqual( szPathRef, szSrcRef ) )
		{
			if(bSubstituteSelf)
			{
				*pszSubstitutedPath = szDstRef + SEPERATOR;
				bSubstituted = true;
			}
		}
		else // Path is children of the target folder
		{
			// szPathRemain: the remain path without SEPERATOR at begin
			std::wstring szRemainPath = szPathRef.substr(szSrcRef.length() + 1);
			
			// Check if szRemainPath is in black list
			if(_isFileInList(szRemainPath, m_vFileBlacklist))
			{
				;
			}
			else
			{
				*pszSubstitutedPath = szDstRef + SEPERATOR + szRemainPath;
				bSubstituted = true;
			}
		}
	}

	return bSubstituted;
}

bool VirtualFileSystem::substitutePath(	const std::wstring& szPathRef, 
										const std::map<std::wstring, std::wstring>& vPathPair,
										bool bSubstituteSelf,
										std::wstring* pszSubstitutedPath)
{
	std::map<std::wstring, std::wstring>::const_iterator itr = vPathPair.begin();
	for(; itr!= vPathPair.end(); ++itr)
	{
		if	(substitutePath(szPathRef, 
							itr->first,
							itr->second,
							bSubstituteSelf,
							pszSubstitutedPath)
			)
		{
			return true;
		}
	}

	return false;
}

bool VirtualFileSystem::redirectFilePath(POBJECT_ATTRIBUTES ObjectAttributesPtr)
{	
	WCHAR szFilePath[MAX_PATH] = {0};
	if ( !_getFilePathByObjectAttributesPtr(ObjectAttributesPtr, szFilePath))
	{
		return false;
	}

	PUNICODE_STRING* puniszRedirectPathPtr = &ObjectAttributesPtr->ObjectName;

	std::wstring szResult;
	if (substitutePath(	szFilePath, 
						m_szDeviceUserProfilePath, 
						m_szVirtualFileSpace, 
						true, 
						&szResult)
		)
	{
		int originLength = (*puniszRedirectPathPtr)->Length / 2;
		szResult = DEVICE_PREFIX + szResult;
		(*puniszRedirectPathPtr)->Length = szResult.length() * 2;  // * 2 for wchar 
		(*puniszRedirectPathPtr)->MaximumLength = szResult.length() * 2 + 2; // + 2 for "/0"
		memcpy((*puniszRedirectPathPtr)->Buffer, szResult.c_str(), (*puniszRedirectPathPtr)->Length);
		memset( (*puniszRedirectPathPtr)->Buffer + szResult.length() , '\0', originLength -  szResult.length() );

		return true;
	}

	return false;
}

bool VirtualFileSystem::redirectFilePath(WCHAR FileName[1], ULONG* pFileNameLength)
{
	unsigned long length = *pFileNameLength / 2; // wide char / 2
	std::wstring szFilePath(FileName, length);
		
	std::wstring szResult;
	// Reset File_RENAME_INFO, redirect if substitutePath success
	if( substitutePath(	szFilePath.c_str(), 
						m_szDeviceUserProfilePath, 
						m_szVirtualFileSpace, 
						true, 
						&szResult) )
	{		
		szResult = DEVICE_PREFIX + szResult;
		*pFileNameLength = szResult.length() * 2;// wide char * 2
		memcpy(FileName, szResult.c_str(), *pFileNameLength);

		return true;
	}

	return false;
}

bool VirtualFileSystem::redirectRegPath(POBJECT_ATTRIBUTES ObjectAttributesPtr)
{
	WCHAR szFilePath[MAX_PATH] = {0};
	if ( !_getRegPathByObjectAttributesPtr(ObjectAttributesPtr, szFilePath))
	{
		return false;
	}
	
	PUNICODE_STRING* puniszRedirectPathPtr = &ObjectAttributesPtr->ObjectName;

	std::wstring szResult;
	if (substitutePath(	szFilePath, 
						m_mRegRedirectPaths,
						true, 
						&szResult)
		)
	{
		int originLength = (*puniszRedirectPathPtr)->Length / 2;
		(*puniszRedirectPathPtr)->Length = szResult.length() * 2;  // * 2 for wchar 
		(*puniszRedirectPathPtr)->MaximumLength = szResult.length() * 2 + 2; // + 2 for "/0"
		memcpy( (*puniszRedirectPathPtr)->Buffer, szResult.c_str(), (*puniszRedirectPathPtr)->Length);
		memset( (*puniszRedirectPathPtr)->Buffer + szResult.length() , '\0', originLength -  szResult.length() );
		
		return true;
	}
	
	return false;
}

bool VirtualFileSystem::redirectRegPath(WCHAR Name[1], ULONG* pNameLength)
{	
	unsigned long length = *pNameLength / 2; // wide char / 2
	std::wstring szRegPath(Name, length);
		
	std::wstring szResult;
	// Reset File_RENAME_INFO, redirect if substitutePath success
	if( substitutePath(	szRegPath, 
						m_mRegRedirectPaths,
						true, 
						&szResult) )
	{		
		szResult = DEVICE_PREFIX + szResult;
		*pNameLength = szResult.length() * 2;// wide char * 2
		memcpy(Name, szResult.c_str(), *pNameLength);

		return true;
	}
	
	return false;
}

bool VirtualFileSystem::_getFilePathByObjectAttributesPtr(	POBJECT_ATTRIBUTES ObjectAttributesPtr, 
															WCHAR* szPath)
{
	if( !ObjectAttributesPtr->RootDirectory && !ObjectAttributesPtr->ObjectName)
	{
		return false;
	}

	if( ObjectAttributesPtr->RootDirectory )
	{
		return false;
	}

	if( ObjectAttributesPtr != NULL && 
		ObjectAttributesPtr->ObjectName != NULL && 
		ObjectAttributesPtr->ObjectName->Length > 0)
	{
		lstrcatW(szPath, ObjectAttributesPtr->ObjectName->Buffer);
		return true;
	}

	return false;
}

bool VirtualFileSystem::_getRegPathByObjectAttributesPtr(POBJECT_ATTRIBUTES ObjectAttributesPtr, WCHAR* szPath)
{
	if( !ObjectAttributesPtr->RootDirectory && !ObjectAttributesPtr->ObjectName)
	{
		return false;
	}
	
	if( ObjectAttributesPtr != NULL && 
		ObjectAttributesPtr->ObjectName != NULL && 
		ObjectAttributesPtr->ObjectName->Length > 0)
	{
		if( _isRootKeyCurrentUser( ObjectAttributesPtr->ObjectName->Buffer ) )
		{
			return false;
		}

		lstrcatW(szPath, ObjectAttributesPtr->ObjectName->Buffer);
		return true;
	}

	return false;
}

bool VirtualFileSystem::_isFileInList(const std::wstring& szFileRef, std::vector<std::wstring>& vListRef)
{
	for(int i=0; i<vListRef.size(); ++i)
	{
		std::wstring szBlackItm = vListRef[i];
		int len = 0;
		if(szBlackItm[szBlackItm.length()-1] == L'*')// * wild character  
		{
			len = szBlackItm.length() - 1;
		}
		else
		{
			len = szBlackItm.length();
		}
		std::wstring szFileItm = szFileRef.substr(0, len);
		szBlackItm = szBlackItm.substr(0, len);

		if(_isFilePathEqual(szFileItm, szBlackItm))
		{
			return true;
		}
	}
	return false;
}

bool VirtualFileSystem::_isFilePathEqual(const std::wstring& szSrc, const std::wstring& szDst)
{
	return bool( wcscmp( szSrc.c_str(), szDst.c_str()) == 0);
}

bool VirtualFileSystem::_isRootKeyCurrentUser( PWSTR pwszKey )
{
	size_t found;
	std::wstring szKey( pwszKey );
	found = szKey.find(L"\\REGISTRY\\USER");
	if (found != std::string::npos)
		return true;

	return false;
}