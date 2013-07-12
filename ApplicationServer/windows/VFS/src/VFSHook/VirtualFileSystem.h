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

#ifndef _VirtualFileSystem_H_
#define _VirtualFileSystem_H_

#include <string>
#include <vector>
#include <map>
#include <Winternl.h>	//Nt
#include "VFSRule.h"


class VirtualFileSystem
{
public:
	// Initial function.
	// Note: Most run init before hooked up.
	bool init();

	// Read and parse file system configuration
	// Return True for success, False otherwise.
	bool initFileSystem();
	
	// Read and parse registry system from configuration
	// Return True for success, False otherwise.
	bool initRegSystem();
	
	// Substitue original path to virtual system path.
	// _IN_ szPathRef: Origin path to check for substitution
	// _IN_ szSrcRef: Source path to be substituted
	// _IN_ szDstRef: Destination path to substitute to
	// _IN_ bSubstituteSelf: True to substitute if szPath fully equals to szTargetPath; false otherwise
	// _IN_OUT_ pszSubstitutedPath: Path after substituted
	// Return True if path is subsituted, False otherwise.
	// Usage example: 
	//	substitutePath("C:\\Temp\\Path", "Temp", "Redirect", false, &szResult)
	//	szResult == "C:\\Redirect\\Path"
	bool substitutePath(const std::wstring& szPathRef, 
						const std::wstring& szSrcRef, 
						const std::wstring& szDstRef, 
						bool bSubstituteSelf,
						std::wstring& pszSubstitutedPath);

	// Substitue original path to virtual system path.
	// _IN_ szPathRef: Origin path to check for substitution
	// _IN_ vPathPair: Pair of src paths and corresponding redirect-to dst paths.
	// _IN_ bSubstituteSelf: True to substitute if szPath fully equals to szTargetPath; false otherwise
	// _IN_OUT_ pszSubstitutedPath: Path after substituted
	bool substitutePath(const std::wstring& szPathRef, 
						const std::map<std::wstring, std::wstring>& vPathPair,
						bool bSubstituteSelf,
						std::wstring& pszSubstitutedPath);

	// Redirect file path.
	// _IN_OUT_ ObjectAttributesPtr: Input data of file attributes, output modified result
	// _IN_OUT_ result: transformed path
	// Return True if path has redirected, False otherwise.
	bool redirectFilePath(POBJECT_ATTRIBUTES ObjectAttributesPtr, std::wstring& result);


	// Redirect file path.
	// _IN_OUT_ path: Input path
	// _IN_OUT_ result: transformed path
	// Return True if path has redirected, False otherwise.
	bool redirectFilePath(const std::wstring& path, std::wstring& result);

	// Virtual file space files to be redirect to.
	void setVirtualFileSpace(std::wstring szFileSpacePath);
	
	// Redirect registry path.
	// _IN_OUT_ ObjectAttributesPtr: Input data of file attributes, output modified result 
	// Return True if path has redirected, False otherwise.
	bool redirectRegPath(POBJECT_ATTRIBUTES ObjectAttributesPtr);
	
	// Redirect registry path.
	// _IN_OUT_ ObjectAttributesPtr: Input data of file attributes, output modified result 
	// Return True if path has redirected, False otherwise.
	bool redirectRegPath(WCHAR Name[1], ULONG* pNameLength);

	// Add registry path src to be redirect into dst.
	// _IN_ szSrc: Source path
	// _IN_ szDst: Destination path
	void addRegRedirectionPath(std::wstring szSrc, std::wstring szDst);
	
	// Get virtual file space files to be redirect to.
	std::wstring getVirtualFileSpace(){return m_szVirtualFileSpace;}

public:
	VirtualFileSystem();
	~VirtualFileSystem();
	
	//---------------------------------------------------------------------------//
	// File redirection helper function:
	//---------------------------------------------------------------------------//
	// Get file path from by ObjectAttributes.
	// If ObjectAttributes has file path, return true, else return false.
	// File path saved to strPath as output.
	// _IN_ ObjectAttributes: input data to find file path
	// _OUT_ szPath: output file path
	// Return True if success, False otherwise.
	bool _getFilePathByObjectAttributesPtr(POBJECT_ATTRIBUTES ObjectAttributesPtr, WCHAR* szPath);

	// Get Reg path from by ObjectAttributes.
	// If ObjectAttributes has file path, return true, else return false.
	// File path saved to strPath as output.
	// _IN_ ObjectAttributes: input data to find file path
	// _OUT_ szPath: output file path
	// Return True if success, False otherwise.
	bool _getRegPathByObjectAttributesPtr(POBJECT_ATTRIBUTES ObjectAttributesPtr, WCHAR* szPath);
	
	// Check if file/folder exist in list
	// _IN_ szFileRef: File/Folder to be checked
	// _IN_ vListRef: List to check
	// Return True if file exist in list, False otherwise.
	bool _isFileInList(const std::wstring& szFileRef, std::vector<std::wstring>& vListRef);
	
	// Is two path equals 
	bool _isFilePathEqual(const std::wstring& szSrcRef, const std::wstring& szDstRef);
	
	// Get parent path if source path is parent of Path 
	// _IN_ szPathRef: Path
	// _IN_ szSrc: Source path to do parent check
	// Return szPathRef if szPathRef is parent path of Path, else return "".
	// Example:
	// C:\A, C:\A return C:\A
	// C:\A\B, C:\A return C:\A
	// C:\Aa, C:\A return ""
	std::wstring VirtualFileSystem::_getParent( const std::wstring& szPathRef, const std::wstring& szSrcRef );
	
	// Check if registry key is HK_CU
	// Return true if Root key is HK_CU
	bool _isRootKeyCurrentUser( PWSTR pwszKey );

	//---------------------------------------------------------------------------//
	// Paser function:
	//---------------------------------------------------------------------------//
	// Configurate if flag represents for blacklist
	// flag 0 represents for CSIDL blacklist
	// flag 1 represents for allowed CSIDL 
	// Return True if flag represents blacklist item.
	bool _isCsidlBlacklist(const int flag);
	
	// Get folder name without UserProfile path
	// _IN_ csidl: CSIDL id
	// Return folder path without UserProfile path
	// Example: getCSIDLFolderName(CSIDL_DESKTOP) returns "Desktop"
	std::wstring _getCSIDLFolderName(const int csidl);

private:
	//---------------------------------------------------------------------------//
	// File system attributes
	//---------------------------------------------------------------------------//
	// Desire file space to be redirect
	std::wstring					m_szVirtualFileSpace;

	// Origin User Profile path length
	size_t								m_iUserProfileStringLength;

	// Origin User Profile path with DevicePrefix //??//
	std::wstring					m_szDeviceUserProfilePath;

	// File redirection rules
	std::vector<VFSRule*> fsRules;

	// Redirection list of pairs of registries 
	// <Origin path, Redirection path>
	std::map<std::wstring, std::wstring>	m_mRegRedirectPaths;
};

#endif
