/*
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2013
 * Author Wei-Jen Chen 2012
 * Author Shen-Hao chen 2012
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
#include "VirtualFileSystem.h"

#include <stdlib.h>
#include <shlobj.h> 
#include <shlwapi.h>
#include <common/sys/System.h>
#include <common/conf/Configuration.h>
#include <common/conf/Rule.h>


#define	SEPERATOR		L"\\"
#define DEVICE_PREFIX	L"\\??\\"

VirtualFileSystem::VirtualFileSystem():
m_szVirtualFileSpace(L""),
m_iUserProfileStringLength(0),
m_szDeviceUserProfilePath(L"")
{
}

VirtualFileSystem::~VirtualFileSystem() { }


bool VirtualFileSystem::init()
{
	Configuration& conf = Configuration::getInstance();

	//Obtain USERPROFILE path
	WCHAR szUserProfilePath[MAX_PATH];
	SHGetSpecialFolderPath(NULL, szUserProfilePath, CSIDL_PROFILE, 0);
		
	//Profile path string length
	m_iUserProfileStringLength = std::wstring(szUserProfilePath).length();

	// Profile path with device prefixe
	m_szDeviceUserProfilePath.append(DEVICE_PREFIX);
	m_szDeviceUserProfilePath.append(szUserProfilePath);
	
	return true;
}


void VirtualFileSystem::setVirtualFileSpace(std::wstring szFileSpacePath)
{
	m_szVirtualFileSpace = szFileSpacePath;
}


bool VirtualFileSystem::initFileSystem()
{	
	Configuration& conf = Configuration::getInstance();
	this->m_szVirtualFileSpace = conf.getSrcPath();
	std::wstring profilePath = this->m_szDeviceUserProfilePath;
	profilePath.erase(0, wcslen(DEVICE_PREFIX));

	// Converting Rules to regexp
	std::list<Rule> rules = conf.getRules();
	std::list<Rule>::iterator it;


	for(it = rules.begin() ; it != rules.end() ; it++) {
		std::wstring rule = (*it).getPattern();
		Union& u = conf.getUnions((*it).getUnion());

		if (rule.find(profilePath) == 0)
			rule = rule.erase(0, profilePath.length() + 1);

		StringUtil::replaceAll(rule, L"\\", L"\\\\");

		VFSRule* vfsrule = new VFSRule(rule, u.getPath(), u.needTranslate());

		vfsrule->compile();
		this->fsRules.push_back(vfsrule);
	}
	
	return true;
}

bool VirtualFileSystem::initRegSystem()
{
//	// Check if conf file not exist
//	WIN32_FIND_DATAW FindFileData;
//	HANDLE hFind = FindFirstFileW(szConfFilename.c_str(), &FindFileData);
//	if (hFind == INVALID_HANDLE_VALUE)
//	{
//		return false;
//	}
//	else
//	{
//		FindClose(hFind);
//	}
//
//	// Parse Registry Redirect list
//	int charsCount;
//	WCHAR szRedirectList[4096] = {};
//	std::wstring szSrc, szDst;
//	charsCount = GetPrivateProfileSectionW(L"Registry", szRedirectList, 4096, szConfFilename.c_str());
//	int start = 0;
//
//	for (int i = 0; i < charsCount; i++)
//	{
//		WCHAR curChar = szRedirectList[i];
//
//		if (curChar == L'=')
//		{
//			// strip out spaces before '='
//			int lastChar = i-1;
//			while( szRedirectList[lastChar] == L' ' || szRedirectList[lastChar] == L'\t')
//			{
//				lastChar--;
//			}
//
//			szSrc = std::wstring( szRedirectList + start, lastChar - start + 1 );
//			start = i + 1;
//		}
//		if (curChar == L'\0')
//		{
//			// To make pair, need a src path before dst path
//			if ( !szSrc.empty() )
//			{
//				// strip out spaces after '='
//				while( szRedirectList[start] == L' ' || szRedirectList[start] == L'\t')
//				{
//					start++;
//				}
//
//				szDst = std::wstring( szRedirectList + start, i - start );
//				start = i + 1;
//
//				addRegRedirectionPath(szSrc, szDst);
//				szSrc.clear();
//				szDst.clear();
//			}
//		}
//	}

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
										std::wstring& pszSubstitutedPath)
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
				pszSubstitutedPath = szDstRef + SEPERATOR;
				bSubstituted = true;
			}
		}
		else // Path is children of the target folder
		{
			std::vector<VFSRule*>::iterator it;
			// szPathRemain: the remain path without SEPERATOR at begin
			std::wstring szRemainPath = szPathRef.substr(szSrcRef.length() + 1);
			Translation& trans = Configuration::getInstance().getTranslation();

			// Check if szRemainPath is in black list
			// match
			Logger::getSingleton().debug(L"check path %s", szRemainPath.c_str());
			for (it = this->fsRules.begin() ; it != this->fsRules.end() ; it++) {
				Logger::getSingleton().debug(L"   => test rules %s", (*it)->getRule().c_str());

				if ((*it)->match(szRemainPath)) {
					Logger::getSingleton().debug(L"   => match %s -> %s", (*it)->getRule().c_str(), (*it)->getDestination());
					std::wstring remain = szRemainPath;

					if ((*it)->needTranslate())
						remain = trans.translate(szRemainPath, true);

					pszSubstitutedPath = (*it)->getDestination() + SEPERATOR + remain;
					Logger::getSingleton().debug(L"   => replace by is %s %s", pszSubstitutedPath.c_str(), (szDstRef + SEPERATOR + szRemainPath));
					bSubstituted = true;

					break;
				}
			}

		}
	}

	return bSubstituted;
}

bool VirtualFileSystem::substitutePath(	const std::wstring& szPathRef, 
										const std::map<std::wstring, std::wstring>& vPathPair,
										bool bSubstituteSelf,
										std::wstring& pszSubstitutedPath)
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

bool VirtualFileSystem::redirectFilePath(POBJECT_ATTRIBUTES ObjectAttributesPtr, std::wstring& result)
{
	WCHAR szFilePath[MAX_PATH] = {0};
	std::wstring path;
	if ( !_getFilePathByObjectAttributesPtr(ObjectAttributesPtr, szFilePath))
	{
		return false;
	}

	path = std::wstring(ObjectAttributesPtr->ObjectName->Buffer, 0, ObjectAttributesPtr->ObjectName->Length>>1);

	if (substitutePath(path, m_szDeviceUserProfilePath, m_szVirtualFileSpace, true, result)) {
		result = DEVICE_PREFIX + result;
		return true;
	}

	return false;
}

bool VirtualFileSystem::redirectFilePath(const std::wstring& path, std::wstring& result)
{
	// Reset File_RENAME_INFO, redirect if substitutePath success
	if( substitutePath(	path, m_szDeviceUserProfilePath, m_szVirtualFileSpace, true, result)) {
		result = DEVICE_PREFIX + result;
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
						szResult)
		)
	{
		int originLength = (*puniszRedirectPathPtr)->Length / 2;
		(*puniszRedirectPathPtr)->Length = (USHORT)szResult.length() * 2;  // * 2 for wchar
		(*puniszRedirectPathPtr)->MaximumLength = (USHORT)szResult.length() * 2 + 2; // + 2 for "/0"
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
						szResult) )
	{		
		szResult = DEVICE_PREFIX + szResult;
		*pNameLength = (ULONG)szResult.length() * 2;// wide char * 2
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
	for(unsigned int i=0; i<vListRef.size(); ++i)
	{
		std::wstring szBlackItm = vListRef[i];
		size_t len = 0;
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
