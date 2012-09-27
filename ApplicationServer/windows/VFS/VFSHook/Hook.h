#ifndef _Hook_H
#define _Hook_H

#include <string>
#include <vector>
#include <Winternl.h>	//Nt 


// Read and parse black list from configuration file
// _IN_OUT_ pvBlackList: vector of black list items
void setupBlackList(std::vector<std::wstring>* pvBlackList);

// Obtain user CSIDL folders' path, function before hook start
void obtainCSIDLFolders();

// Get folder name with out UserProfile path
// For example, getCSIDLFolderName(CSIDL_DESKTOP) returns "Desktop"
// _IN_ csidl: CSIDL id
std::wstring getCSIDLFolderName(int csidl);

//_IN_ szPath: Path to substitute
//_IN_ szTargetPath: Path to be substituted
//_IN_ szRedirectPath: Substitute path
//_IN_ bSubstituteSelf: True to substitute if szPath fully equals to szTargetPath; false otherwise
//_OUT_ pszSubstitutedPath: Path after substituted
bool substitutePath(std::wstring szPath, 
					std::wstring szTargetPath, 
					std::wstring szRedirectPath, 
					bool bSubstituteSelf,
					std::wstring* pszSubstitutedPath);

// Get file path from by ObjectAttributes
// If ObjectAttributes has file path, return true, else return false
// File path saved to strPath as output.
// _IN_ ObjectAttributes: input data to find file path
// _OUT_ strPath: output file path
bool getPath(POBJECT_ATTRIBUTES ObjectAttributes, WCHAR* strPath);

// Check if folder exist in list
bool hasFolder(std::wstring szFolder, std::vector<std::wstring> vList);

// Redirect file path 
// _IN_ pszFilePath: Origin path
// _OUT_ puniszRedirectPath: Redirect path 
// _IN_ pszReserve: Reserved WCHAR pointer for internal usage
// return value true if path has redirected, false if not
bool redirectFilePath(WCHAR* pszFilePath, PUNICODE_STRING* puniszRedirectPath, WCHAR* pszReserve);


void setupHooks();
void releaseHooks();
void log(char *fmt,...);

#endif //#ifndef _Hook_H