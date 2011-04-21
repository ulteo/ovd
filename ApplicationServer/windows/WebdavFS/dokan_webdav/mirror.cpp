/*

Copyright (c) 2007, 2008 Hiroki Asakawa info@dokan-dev.net

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

#include <stdio.h>
#include <stdlib.h>
#include <windows.h>
#include <dokan.h>
#include <fileinfo.h>


#include "WebdavServer.h"
#include "DavCache.h"
//#include "debug.h"
#include <winhttp.h>


#pragma warning( disable : 4305 )


WebdavServer* server = NULL;
DavCache* davCache = NULL;

BOOL g_UseStdErr;
BOOL g_DebugMode;

static WCHAR MountPoint[MAX_PATH] = L"M:";

static void DbgPrint(LPCWSTR format, ...)
{
        if (g_DebugMode) {
                WCHAR buffer[512];
                va_list argp;
                va_start(argp, format);
                vswprintf_s(buffer, sizeof(buffer)/sizeof(WCHAR), format, argp);
                va_end(argp);
                if (g_UseStdErr) {
                        fwprintf(stderr, buffer);
                } else {
                        OutputDebugStringW(buffer);
                }
        }
}



static int replace(wchar_t* str, wchar_t oc, wchar_t nc)
{
    int n = 0;
	int i = 0;
    if (str) {
        for (i = 0; i<(int)wcslen(str); i++) {
		if (str[i] == oc) {
                str[i] = nc;
                n++;
            }
        }
    }
    return n;
}



static void
GetFilePath(LPCWSTR FileName, WCHAR* newFilename)
{
	wcscpy_s(newFilename, MAX_PATH, FileName);
	replace((wchar_t*)newFilename, '\\', '/');
	//_wcslwr_s((wchar_t*)newFilename, MAX_PATH);
}


#define MirrorCheckFlag(val, flag) if (val&flag) { DbgPrint(L"\t" L#flag L"\n"); }

static int
MirrorCreateFile(
	LPCWSTR					FileName,
	DWORD					AccessMode,
	DWORD					ShareMode,
	DWORD					CreationDisposition,
	DWORD					FlagsAndAttributes,
	PDOKAN_FILE_INFO		DokanFileInfo)
{
	WCHAR filePath[MAX_PATH];
	int ret;
	DWORD byteWritten;

	UNREFERENCED_PARAMETER(FlagsAndAttributes);
	UNREFERENCED_PARAMETER(ShareMode);
	UNREFERENCED_PARAMETER(AccessMode);

	ret = -2;
	GetFilePath(FileName, filePath);


	if (CreationDisposition == CREATE_NEW || CreationDisposition == CREATE_ALWAYS)
	{
		if (server->DAVWriteFile((WCHAR*)filePath+1,NULL, &byteWritten, 0, 0, FALSE)){
			ret = 0;
		}
	}

	if (CreationDisposition == OPEN_ALWAYS)
		DbgPrint(L"\tOPEN_ALWAYS\n");
	if (CreationDisposition == OPEN_EXISTING)
		DbgPrint(L"\tOPEN_EXISTING\n");
	if (CreationDisposition == TRUNCATE_EXISTING)
		DbgPrint(L"\tTRUNCATE_EXISTING\n");


	DbgPrint(L"DavOpen : '%s'\n", filePath);
	if (! server->DAVOpen((wchar_t*)filePath+1)) {
		DokanFileInfo->Context = 0;
		DbgPrint(L"\tUnable to find the filename %ls\n", filePath);
		return -2; // error codes are negated value of Windows System Error codes
	}
	else
	{
		ret = 0;
	}
	
	// save the file handle in Context
	DokanFileInfo->Context = davCache->add(filePath);
	DbgPrint(L"Cache handle %i\n", DokanFileInfo->Context);

	return ret;
}


static int
MirrorCreateDirectory(
	LPCWSTR					FileName,
	PDOKAN_FILE_INFO		DokanFileInfo)
{
	WCHAR filePath[MAX_PATH];
	//GetFilePath(filePath, FileName);
	GetFilePath(FileName, filePath);


	DbgPrint(L"CreateDirectory : %ls\n", (WCHAR*)filePath);

	if (! server->DAVMKCOL(filePath, FALSE )) {
		DbgPrint(L"\tUnable to find the directory %ls\n", filePath);
		return -2; // error codes are negated value of Windows System Error codes
	}

	// save the file handle in Context
	DokanFileInfo->Context = (ULONG64)1;
	return 0;
}


static int
MirrorOpenDirectory(
	LPCWSTR					FileName,
	PDOKAN_FILE_INFO		DokanFileInfo)
{
	WCHAR	filePath[MAX_PATH];
//	HANDLE handle;
//	DWORD attr;
//	printf("MirrorOpenDirectory on %ls\n", FileName);

	//GetFilePath(filePath, FileName);
	GetFilePath(FileName, filePath);

	DbgPrint(L"OpenDirectory : %s\n", filePath);
	//test if it is a directory
	if (! server->DAVOpen((wchar_t*)filePath+1)) {
		DbgPrint(L"\tUnable to find the directory %ls\n", filePath);
		return -2; // error codes are negated value of Windows System Error codes
	}
	DokanFileInfo->Context = 0;
	return 0;
}


static int
MirrorCloseFile(
	LPCWSTR					FileName,
	PDOKAN_FILE_INFO		DokanFileInfo)
{
	DAVCACHEENTRY* cacheEntry = NULL;
	ULONG64 cacheHandle = 0;

	UNREFERENCED_PARAMETER(FileName);

	cacheHandle = DokanFileInfo->Context;
	if ( cacheHandle == 0)
	{
		return 0;
	}

	if ( cacheHandle == -1)
	{
		DbgPrint(L"Handle is invalid %u", cacheHandle);
		return -1;
	}

	cacheEntry = davCache->getFromHandle(cacheHandle);
	if (cacheEntry == NULL)
	{
		DbgPrint(L"Entry returned by the cache is NULL\n");
		return -1;
	}
	davCache->remove(cacheHandle);
	DokanFileInfo->Context = 0;
	return 0;
}


static int
MirrorCleanup(
	LPCWSTR					FileName,
	PDOKAN_FILE_INFO		DokanFileInfo)
{
	DAVCACHEENTRY* cacheEntry = NULL;
	ULONG64 cacheHandle = 0;

	UNREFERENCED_PARAMETER(FileName);

	cacheHandle = DokanFileInfo->Context;
	if ( cacheHandle == 0)
	{
		return 0;
	}
	if ( cacheHandle == -1)
	{
		DbgPrint(L"Handle is invalid %u", cacheHandle);
		return -1;
	}

	cacheEntry = davCache->getFromHandle(cacheHandle);
	if (cacheEntry == NULL)
	{
		DbgPrint(L"Entry returned by the cache is NULL\n");
		return -1;
	}
	davCache->remove(cacheHandle);
	DokanFileInfo->Context = 0;
	return 0;
}


static int
MirrorReadFile(
	LPCWSTR				FileName,
	LPVOID				Buffer,
	DWORD				BufferLength,
	LPDWORD				ReadLength,
	LONGLONG			Offset,
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	WCHAR filePath[MAX_PATH];
	ULONG64 cacheHandle = DokanFileInfo->Context;
	HANDLE	handle = 0;
	ULONG	offset = (ULONG)Offset;
	DAVCACHEENTRY* cacheEntry = NULL;

	DbgPrint(L"BufferLength : %i\n", BufferLength);
	DbgPrint(L"ReadLength : %i\n", *ReadLength);
	DbgPrint(L"Offset : %i\n", Offset);

	if (BufferLength == 0)
	{
		DbgPrint(L"Can not read zero length data");
		*ReadLength = 0;
		return 0;
	}

	GetFilePath(FileName, filePath);
	DbgPrint(L"MirrorReadFile on %ls\n", filePath);
	DbgPrint(L"ReadFile : %s\n", filePath);
	
	cacheEntry = davCache->getFromHandle(cacheHandle);
	if (cacheEntry == NULL)
	{
		DbgPrint(L"Entry returned by the cache is NULL\n");
		return -1;
	}

	if (wcscmp(cacheEntry->remotePath, filePath) != 0)
	{
		DbgPrint(L"Entry did not match the file %ls\n", filePath);
		return -1;
	}

	handle = CreateFile(cacheEntry->cachePath, GENERIC_READ, FILE_SHARE_READ, NULL, OPEN_EXISTING, 0, NULL);
	if (handle == INVALID_HANDLE_VALUE) {
		DbgPrint(L"CreateFile error : %u\n", GetLastError());
		return -1;
	}

	if (SetFilePointer(handle, offset, NULL, FILE_BEGIN) == 0xFFFFFFFF) {
		DbgPrint(L"seek error, offset = %u\n\n", offset);
		return -1;
	}

	if (!ReadFile(handle, Buffer, BufferLength, ReadLength,NULL)) {
		DbgPrint(L"read error = %u, buffer length = %d, read length = %d\n", GetLastError(), BufferLength, *ReadLength);
		return -1;

	}
	else {
		DbgPrint(L"\tread %d, offset %d\n\n", *ReadLength, offset);
	}

	if (*ReadLength > BufferLength) {
		*ReadLength = BufferLength;
	}
	DbgPrint(L"size returned : %i\n", *ReadLength);
	return 0;
}


static int
MirrorWriteFile(
	LPCWSTR		FileName,
	LPCVOID		Buffer,
	DWORD		NumberOfBytesToWrite,
	LPDWORD		NumberOfBytesWritten,
	LONGLONG			Offset,
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	WCHAR	filePath[MAX_PATH];
	ULONG64 cacheHandle = DokanFileInfo->Context;
	HANDLE	handle = 0;
	ULONG	offset = (ULONG)Offset;
	DAVCACHEENTRY* cacheEntry = NULL;

	//GetFilePath(filePath, FileName);
//	printf("MirrorWriteFile on %ls\n", FileName);

	DbgPrint(L"WriteFile : %s, offset %I64d, length %d\n", filePath, Offset, NumberOfBytesToWrite);
	DbgPrint(L"NumberOfBytesToWrite : %i\n", NumberOfBytesToWrite);
	DbgPrint(L"NumberOfBytesWritten : %i\n", *NumberOfBytesWritten);
	DbgPrint(L"Offset : %i\n", Offset);


	if (NumberOfBytesToWrite == 0)
	{
		*NumberOfBytesWritten = 0;
		DbgPrint(L"Can not write zero length data");
		return 0;
	}

	GetFilePath(FileName, filePath);
	DbgPrint(L"MirrorWriteFile on %ls\n", filePath);
	DbgPrint(L"WriteFile : %s\n", filePath);

	cacheEntry = davCache->getFromHandle(cacheHandle);
	if (cacheEntry == NULL)
	{
		DbgPrint(L"Entry returned by the cache is NULL\n");
		return -1;

	}

	if (wcscmp(cacheEntry->remotePath, filePath) != 0)
	{
		DbgPrint(L"Entry did not match the file %ls", filePath);
		return -1;
	}

	handle = CreateFile(cacheEntry->cachePath, GENERIC_WRITE, FILE_SHARE_WRITE, NULL, OPEN_EXISTING, 0, NULL);
	if (handle == INVALID_HANDLE_VALUE) {
		DbgPrint(L"CreateFile error : %u\n", GetLastError());
		return -1;
	}

	if (DokanFileInfo->WriteToEndOfFile) {
		if (SetFilePointer(handle, 0, NULL, FILE_END) == INVALID_SET_FILE_POINTER) {
			DbgPrint(L"seek error, offset = EOF, error = %u\n", GetLastError());
			return -1;
		}
	} else if (SetFilePointer(handle, offset, NULL, FILE_BEGIN) == INVALID_SET_FILE_POINTER) {
		DbgPrint(L"seek error, offset = %d, error = %u\n", offset, GetLastError());
		return -1;
	}

	if (!WriteFile(handle, Buffer, NumberOfBytesToWrite, NumberOfBytesWritten, NULL)) {
		DbgPrint(L"write error = %u, buffer length = %d, write length = %d\n",	GetLastError(), NumberOfBytesToWrite, *NumberOfBytesWritten);
		return -1;

	} else {
		DbgPrint(L"\twrite %d, offset %d\n\n", *NumberOfBytesWritten, offset);
	}

	return 0;
}


static int
MirrorGetFileInformation(
	LPCWSTR							FileName,
	LPBY_HANDLE_FILE_INFORMATION	HandleFileInformation,
	PDOKAN_FILE_INFO				DokanFileInfo)
{
	WCHAR	filePath[MAX_PATH];
	DAVFILEINFO* fsinfo;
	GetFilePath(FileName, filePath);

	UNREFERENCED_PARAMETER(DokanFileInfo);

	DbgPrint(L"MirrorGetFileInformation on %s\n", filePath);
	fsinfo = server->DAVGetFileInformations(filePath, NULL);
	if (fsinfo == NULL || fsinfo->isDir)
	{
		HandleFileInformation->dwFileAttributes = FILE_ATTRIBUTE_DIRECTORY;
		return 0;
	}
	

	HandleFileInformation->dwFileAttributes = fsinfo->fileAttributes;
	HandleFileInformation->ftCreationTime = fsinfo->creationTime;
	HandleFileInformation->ftLastAccessTime = fsinfo->lastModified;
	HandleFileInformation->ftLastWriteTime = fsinfo->lastModified;
	HandleFileInformation->nFileSizeHigh = fsinfo->nFileSizeHigh;
	HandleFileInformation->nFileSizeLow = fsinfo->nFileSizeLow;
	DbgPrint(L"\tFindFiles OK, file size = %d\n", HandleFileInformation->nFileSizeLow);
//	FindClose(handle);


	return 0;
}


static int
MirrorFindFiles(
	LPCWSTR				FileName,
	PFillFindData		FillFindData, // function pointer
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	WCHAR	filePath[MAX_PATH];
	WIN32_FIND_DATAW point;
	DWORD	count = 0;
	DAVFILEINFO** fileList = NULL;
	int i;

	GetFilePath(FileName, filePath);
	DbgPrint(L"MirrorFindFiles on %ls\n", FileName);


	DbgPrint(L"find %ls\n",filePath );
	fileList = server->DAVGetDirectoryList(filePath, &count);

	ZeroMemory(&point, sizeof(WIN32_FIND_DATAW));
	point.dwFileAttributes = FILE_ATTRIBUTE_DIRECTORY;
	wcscpy_s(point.cFileName, MAX_PATH , L".");
	FillFindData(&point, DokanFileInfo);

	ZeroMemory(&point, sizeof(WIN32_FIND_DATAW));
	point.dwFileAttributes = FILE_ATTRIBUTE_DIRECTORY;
	wcscpy_s(point.cFileName, MAX_PATH , L"..");
	FillFindData(&point, DokanFileInfo);


	for(i=0 ; i < (int)count ; i++) {
		WIN32_FIND_DATAW data;
		ZeroMemory(&data, sizeof(WIN32_FIND_DATAW));
		data.dwFileAttributes = FILE_ATTRIBUTE_NORMAL;

		if (fileList[i]->isDir)
		{
			data.dwFileAttributes = FILE_ATTRIBUTE_DIRECTORY;
		}

		data.ftCreationTime.dwLowDateTime = 0;
		data.ftCreationTime.dwHighDateTime = 0;
		data.ftLastWriteTime.dwLowDateTime = 0;
		data.ftLastWriteTime.dwHighDateTime = 0;

		data.nFileSizeHigh = fileList[i]->nFileSizeHigh;
		data.nFileSizeLow = fileList[i]->nFileSizeLow;

		wcscpy_s(data.cFileName, MAX_PATH , fileList[i]->name);

		FillFindData(&data, DokanFileInfo);
	}
	return 0;
}


static int
MirrorDeleteFile(
	LPCWSTR				FileName,
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	WCHAR filePath[MAX_PATH];
	GetFilePath(FileName, filePath);


	DbgPrint(L"MirrorDeleteFile : %ls\n", (WCHAR*)filePath);

	if (! server->DAVDELETE(filePath, FALSE )) {
		DbgPrint(L"\tUnable to find the directory %ls\n", filePath);
		return -2; // error codes are negated value of Windows System Error codes
	}

	// save the file handle in Context
	DokanFileInfo->Context = (ULONG64)1;
	return 0;
}


static int
MirrorDeleteDirectory(
	LPCWSTR				FileName,
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	WCHAR filePath[MAX_PATH];
	//GetFilePath(filePath, FileName);
	GetFilePath(FileName, filePath);


	DbgPrint(L"MirrorDeleteDirectory : %ls\n", (WCHAR*)filePath);

	if (! server->DAVDELETE(filePath, FALSE )) {
		DbgPrint(L"\tUnable to find the directory %ls\n", filePath);
		return -2; // error codes are negated value of Windows System Error codes
	}

	// save the file handle in Context
	DokanFileInfo->Context = (ULONG64)1;
	return 0;


}


static int
MirrorMoveFile(
	LPCWSTR				FileName, // existing file name
	LPCWSTR				NewFileName,
	BOOL				ReplaceIfExisting,
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	WCHAR			filePath[MAX_PATH];
	WCHAR			newFilePath[MAX_PATH];
	BOOL			status;
	HANDLE			handle;

	UNREFERENCED_PARAMETER(FileName);
	UNREFERENCED_PARAMETER(NewFileName);
	//GetFilePath(filePath, FileName);
	//GetFilePath(newFilePath, NewFileName);
	handle = (HANDLE)DokanFileInfo->Context;



	DbgPrint(L"MoveFile %s -> %s\n\n", filePath, newFilePath);

	if (DokanFileInfo->Context) {
		// should close? or rename at closing?
		CloseHandle(handle);
		DokanFileInfo->Context = 0;
	}

	if (ReplaceIfExisting)
		status = MoveFileEx(filePath, newFilePath, MOVEFILE_REPLACE_EXISTING);
	else
		status = MoveFile(filePath, newFilePath);

	if (status == FALSE) {
		DWORD error = GetLastError();
		DbgPrint(L"\tMoveFile failed status = %d, code = %d\n", status, error);
		return -(int)error;
	} else {
		return 0;
	}
}


static int
MirrorLockFile(
	LPCWSTR				FileName,
	LONGLONG			ByteOffset,
	LONGLONG			Length,
	PDOKAN_FILE_INFO	DokanFileInfo)
{
//	WCHAR	filePath[MAX_PATH];
	HANDLE	handle;
	LARGE_INTEGER offset;
	LARGE_INTEGER length;

	UNREFERENCED_PARAMETER(FileName);

	//GetFilePath(filePath, FileName);

//	DbgPrint(L"LockFile %s\n", filePath);

	handle = (HANDLE)DokanFileInfo->Context;
	if (!handle || handle == INVALID_HANDLE_VALUE) {
		DbgPrint(L"\tinvalid handle\n\n");
		return -1;
	}

	length.QuadPart = Length;
	offset.QuadPart = ByteOffset;

	if (LockFile(handle, offset.HighPart, offset.LowPart, length.HighPart, length.LowPart)) {
		DbgPrint(L"\tsuccess\n\n");
		return 0;
	} else {
		DbgPrint(L"\tfail\n\n");
		return -1;
	}
}


static int
MirrorSetEndOfFile(
	LPCWSTR				FileName,
	LONGLONG			ByteOffset,
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	UNREFERENCED_PARAMETER(FileName);
	UNREFERENCED_PARAMETER(ByteOffset);
	UNREFERENCED_PARAMETER(DokanFileInfo);
	return 0;
}


static int
MirrorSetAllocationSize(
	LPCWSTR				FileName,
	LONGLONG			AllocSize,
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	UNREFERENCED_PARAMETER(FileName);
	UNREFERENCED_PARAMETER(AllocSize);
	UNREFERENCED_PARAMETER(DokanFileInfo);
	return 0;
}


static int
MirrorSetFileAttributes(
	LPCWSTR				FileName,
	DWORD				FileAttributes,
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	WCHAR	filePath[MAX_PATH];
	
	UNREFERENCED_PARAMETER(FileName);
	UNREFERENCED_PARAMETER(DokanFileInfo);
	//GetFilePath(filePath, FileName);

	DbgPrint(L"SetFileAttributes %s\n", filePath);

	if (!SetFileAttributes(filePath, FileAttributes)) {
		DWORD error = GetLastError();
		DbgPrint(L"\terror code = %d\n\n", error);
		return error * -1;
	}

	DbgPrint(L"\n");
	return 0;
}


static int
MirrorSetFileTime(
	LPCWSTR				FileName,
	CONST FILETIME*		CreationTime,
	CONST FILETIME*		LastAccessTime,
	CONST FILETIME*		LastWriteTime,
	PDOKAN_FILE_INFO	DokanFileInfo)
{
//	WCHAR	filePath[MAX_PATH];
	HANDLE	handle;

	UNREFERENCED_PARAMETER(FileName);

	//GetFilePath(filePath, FileName);

//	DbgPrint(L"SetFileTime %s\n", filePath);

	handle = (HANDLE)DokanFileInfo->Context;

	if (!handle || handle == INVALID_HANDLE_VALUE) {
		DbgPrint(L"\tinvalid handle\n\n");
		return -1;
	}

	if (!SetFileTime(handle, CreationTime, LastAccessTime, LastWriteTime)) {
		DWORD error = GetLastError();
		DbgPrint(L"\terror code = %d\n\n", error);
		return error * -1;
	}

	DbgPrint(L"\n");
	return 0;
}



static int
MirrorUnlockFile(
	LPCWSTR				FileName,
	LONGLONG			ByteOffset,
	LONGLONG			Length,
	PDOKAN_FILE_INFO	DokanFileInfo)
{
//	WCHAR	filePath[MAX_PATH];
	HANDLE	handle;
	LARGE_INTEGER	length;
	LARGE_INTEGER	offset;

	UNREFERENCED_PARAMETER(FileName);

	//GetFilePath(filePath, FileName);

//	DbgPrint(L"UnlockFile %s\n", filePath);

	handle = (HANDLE)DokanFileInfo->Context;
	if (!handle || handle == INVALID_HANDLE_VALUE) {
		DbgPrint(L"\tinvalid handle\n\n");
		return -1;
	}

	length.QuadPart = Length;
	offset.QuadPart = ByteOffset;

	if (UnlockFile(handle, offset.HighPart, offset.LowPart, length.HighPart, length.LowPart)) {
		DbgPrint(L"\tsuccess\n\n");
		return 0;
	} else {
		DbgPrint(L"\tfail\n\n");
		return -1;
	}
}


static int
MirrorUnmount(
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	UNREFERENCED_PARAMETER(DokanFileInfo);
	wprintf(L"Unmount\n");

	return 0;
}



int _cdecl
main(ULONG argc, PCHAR argv[]) {
	int status;
	ULONG command;
	WCHAR username[MAX_PATH] = L"";
	WCHAR password[MAX_PATH] = L"";
	WCHAR url[MAX_PATH] = L"";
	BOOL bResults = FALSE;
	URL_COMPONENTS urlComp;
	BOOL useHTTPS = FALSE;
	PDOKAN_OPERATIONS dokanOperations = (PDOKAN_OPERATIONS)malloc(sizeof(DOKAN_OPERATIONS));
	PDOKAN_OPTIONS dokanOptions = (PDOKAN_OPTIONS)malloc(sizeof(DOKAN_OPTIONS));

	if (argc < 2) {
		fprintf(stderr, "davfs.exe\n"
			"  /u url : complete address of the WebDav server \n"
			"  /o username : (username to use) \n"
			"  /w password : (password for the user) \n"
			"  /s (use ssl) \n"
			"  /d (enable debug output)\n"
			"  /e (use stderr for output)\n"
			"  /l letter : Drive letter\n"
		);
		return -1;
	}

	g_DebugMode = FALSE;
	g_UseStdErr = TRUE;

	ZeroMemory(dokanOptions, sizeof(DOKAN_OPTIONS));
	dokanOptions->ThreadCount = 0; // use default
    dokanOptions->Version = DOKAN_VERSION;

	for (command = 1; command < argc; command++) {
		switch (tolower(argv[command][1])) {
		case 'u':
			command++;
			mbstowcs(url, argv[command], strlen(argv[command]));
			DbgPrint(L"Url: %s\n", url);
			break;
		case 'o':
			command++;
			mbstowcs(username, argv[command], strlen(argv[command]));
			DbgPrint(L"Username: %s\n", username);
			break;
		case 'w':
			command++;
			mbstowcs(password, argv[command], strlen(argv[command]));
			DbgPrint(L"Password: %s\n", password);
			break;
		case 'l':
			command++;
			MountPoint[0] =  argv[command][0];
			dokanOptions->MountPoint = MountPoint;
			wprintf(L"Pouet: %s\n", dokanOptions->MountPoint);

			break;
		case 't':
			command++;
			dokanOptions->ThreadCount = (USHORT)atoi(argv[command]);
			break;
		case 'd':
			g_DebugMode = TRUE;
			break;
		case 'e':
			g_UseStdErr = TRUE;
			break;
		default:
			fprintf(stderr, "unknown command: %s\n", argv[command]);
			return -1;
		}
	}


	ZeroMemory(&urlComp, sizeof(urlComp));
	if (lstrlen(url) > 0) {
		//crack the url
		urlComp.dwSchemeLength    = (DWORD)MAX_PATH;
		urlComp.dwHostNameLength  = (DWORD)MAX_PATH;
		urlComp.dwUrlPathLength   = (DWORD)MAX_PATH;
		urlComp.lpszScheme    = (WCHAR*)malloc(MAX_PATH*sizeof(WCHAR));
		urlComp.lpszHostName  = (WCHAR*)malloc(MAX_PATH*sizeof(WCHAR));
		urlComp.lpszUrlPath   = (WCHAR*)malloc(MAX_PATH*sizeof(WCHAR));
		urlComp.dwExtraInfoLength = (DWORD)-1;
		urlComp.dwStructSize = sizeof(urlComp);

		// Crack the URL.
		bResults = WinHttpCrackUrl(url, (DWORD)wcslen(url), 0, &urlComp);
		if (!bResults)
		{
			fprintf(stderr, "Unable to separate component of the url (%u)\n", GetLastError());
			return -1;
		}
		if (_wcsnicmp(urlComp.lpszScheme, L"HTTPS", lstrlen(L"HTTPS")) == 0)
		{
			useHTTPS = TRUE;
		}
	}


	dokanOptions->Options |= DOKAN_OPTION_NETWORK;
//	dokanOptions->Options |= DOKAN_OPTION_REMOVABLE;

	server = new WebdavServer(urlComp.lpszHostName, urlComp.nPort, urlComp.lpszUrlPath, username, password, useHTTPS);

	server->init();
	davCache = new DavCache();
	davCache->init(server);

//	if (g_DebugMode)
//		dokanOptions->Options |= DOKAN_OPTION_DEBUG;
//	if (g_UseStdErr)
//		dokanOptions->Options |= DOKAN_OPTION_STDERR;

	dokanOptions->Options |= DOKAN_OPTION_KEEP_ALIVE;
	dokanOptions->ThreadCount = 1;
	ZeroMemory(dokanOperations, sizeof(DOKAN_OPERATIONS));
	dokanOperations->CreateFile = MirrorCreateFile;                      //OK
	dokanOperations->OpenDirectory = MirrorOpenDirectory;                //OK
	dokanOperations->CreateDirectory = MirrorCreateDirectory;            //OK
	dokanOperations->Cleanup = MirrorCleanup;                            //OK
	dokanOperations->CloseFile = MirrorCloseFile;                        //OK
	dokanOperations->ReadFile = MirrorReadFile;                          //OK
	dokanOperations->WriteFile = MirrorWriteFile;                        //OK
	dokanOperations->FlushFileBuffers = NULL;
	dokanOperations->GetFileInformation = MirrorGetFileInformation;      //OK
	dokanOperations->FindFiles = MirrorFindFiles;                        //OK
	dokanOperations->FindFilesWithPattern = NULL;
	dokanOperations->SetFileAttributes = MirrorSetFileAttributes;        //OK
	dokanOperations->SetFileTime = MirrorSetFileTime;                    
	dokanOperations->DeleteFile = MirrorDeleteFile;                      //OK
	dokanOperations->DeleteDirectory = MirrorDeleteDirectory;            //OK
	dokanOperations->MoveFile = MirrorMoveFile;                          //OK
	dokanOperations->SetEndOfFile = MirrorSetEndOfFile;                  //OK
	dokanOperations->SetAllocationSize = MirrorSetAllocationSize;        //OK
	dokanOperations->LockFile = MirrorLockFile;                          //OK
	dokanOperations->UnlockFile = MirrorUnlockFile;                      //OK
	dokanOperations->GetDiskFreeSpace = NULL;
	dokanOperations->GetVolumeInformation = NULL;
	dokanOperations->Unmount = MirrorUnmount;

	status = DokanMain(dokanOptions, dokanOperations);
	switch (status) {
		case DOKAN_SUCCESS:
			break;
		case DOKAN_ERROR:
			fprintf(stderr, "Error\n");
			break;
		case DOKAN_DRIVE_LETTER_ERROR:
			fprintf(stderr, "Bad Drive letter\n");
			break;
		case DOKAN_DRIVER_INSTALL_ERROR:
			fprintf(stderr, "Can't install driver\n");
			break;
		case DOKAN_START_ERROR:
			fprintf(stderr, "Driver something wrong\n");
			break;
		case DOKAN_MOUNT_ERROR:
			fprintf(stderr, "Can't assign a drive letter\n");
			break;
		default:
			fprintf(stderr, "Unknown error: %d\n", status);
			break;
	}

	free(dokanOptions);
	free(dokanOperations);
	return 0;
}

