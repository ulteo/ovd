#ifndef _Hook_H
#define _Hook_H

#include <string>

// This function filter the input path lpFilePath and check if its contains or based on szTargetPath.
// If the lpFilePath contains szTargetPath, this function replace the szTargetPath part with szRedirectPath folder
// @lpFilePath: input path for comparison
// @szTargetPath: base path for comparison
// @szRedirectPath: the path for lpFilePath to redirect to
// @pszOutputPath: result path after filter
bool filter(LPCWSTR lpFilePath, std::wstring szTargetPath, std::wstring szRedirectPath, std::wstring* pszOutputPath);

void setupHooks();
void releaseHooks();
void log(char *fmt,...);

#endif //#ifndef _Hook_H