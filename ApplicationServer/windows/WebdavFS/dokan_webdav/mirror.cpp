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
#include <signal.h>
#include <dokan.h>
#include <fileinfo.h>


#include "WebdavServer.h"
#include "DavCache.h"
#include "PROPFindRequest.h"
#include "DELETERequest.h"
#include "MKCOLRequest.h"
#include "MOVERequest.h"
#include "GETRequest.h"
//#include "debug.h"
#include <winhttp.h>


#pragma warning( disable : 4305 )


WebdavServer* server = NULL;
DavCache* davCache = NULL;

BOOL g_UseStdErr;
BOOL g_DebugMode;

static WCHAR MountPoint[MAX_PATH] = L"::";

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

void _cdecl
sigint_handler(int sig ) {
	UNREFERENCED_PARAMETER(sig);

    // Unmount the drive
	DbgPrint(L"SIG_INT HANDLED : unmount %s\n", MountPoint);

	DefineDosDevice(DDD_REMOVE_DEFINITION, MountPoint, NULL);

	if (davCache) {
		davCache->clean();
		delete davCache;
	}
	if (server) {
		server->disconnect();
		delete server;
	}


	exit(0);
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
	DWORD result = ERROR_SUCCESS;
	ULONG64 cacheHandle = INVALID_CACHE_HANDLE;
	DAVCACHEENTRY* cacheEntry = NULL;
	BOOL exist = FALSE;

	UNREFERENCED_PARAMETER(FlagsAndAttributes);
	UNREFERENCED_PARAMETER(ShareMode);
	UNREFERENCED_PARAMETER(AccessMode);

	GetFilePath(FileName, filePath);
	DbgPrint(L"create file %s\n", filePath);
	
	if (! server->exist(filePath)) {
		result = ERROR_FILE_NOT_FOUND;
		DbgPrint(L"\tUnable to find the filename %ls\n", filePath);
	}

	switch (CreationDisposition) {
	case CREATE_NEW:
		if (exist) {
			cacheHandle = davCache->add(filePath);
			result = ERROR_FILE_EXISTS;
			break;
		}

	case CREATE_ALWAYS:
		result = server->touch(filePath);
		if (result == ERROR_SUCCESS) {
			cacheHandle = davCache->add(filePath);
			break;
		}
		break;

	case OPEN_ALWAYS:
		if (! exist) {
			result = server->touch(filePath);
			if (result == ERROR_SUCCESS) {
				cacheHandle = davCache->add(filePath);
				break;
			}
		}
		result = ERROR_SUCCESS;

	case OPEN_EXISTING:
	case TRUNCATE_EXISTING:
		if (exist) {
			cacheHandle = davCache->add(filePath);
			if (cacheHandle != INVALID_CACHE_HANDLE) {
				cacheEntry = davCache->getFromHandle(cacheHandle);
				cacheEntry->needImport = TRUE;
			}
			result = ERROR_SUCCESS;
		}
		break;

	default:
		DbgPrint(L"\tInvalid create disposition %i\n", CreationDisposition);
		result = ERROR_INVALID_PARAMETER;
		break;
	}
	DokanFileInfo->Context = (ULONG64)cacheHandle;
	return -1 * result;
}


static int
MirrorCreateDirectory(
	LPCWSTR					FileName,
	PDOKAN_FILE_INFO		DokanFileInfo)
{
	WCHAR filePath[MAX_PATH];
	DWORD result;
	GetFilePath(FileName, filePath);

	MKCOLRequest req(filePath);
	if (FAILED(server->sendRequest(req))) {
		DbgPrint(L"MirrorDeleteFile, failed to send the request\n");
		return -1;
	}
	
	result = req.getWinStatus();
	req.close();

	// save the file handle in Context
	DokanFileInfo->Context = INVALID_CACHE_HANDLE;
	return -1 * result;
}


static int
MirrorOpenDirectory(
	LPCWSTR					FileName,
	PDOKAN_FILE_INFO		DokanFileInfo)
{
	WCHAR	filePath[MAX_PATH];
//	HANDLE handle;
//	DWORD attr;
	DbgPrint(L"MirrorOpenDirectory on %ls\n", FileName);
	DokanFileInfo->Context = INVALID_CACHE_HANDLE;

	GetFilePath(FileName, filePath);
	DbgPrint(L"OpenDirectory : %s\n", filePath);

	if (server->exist(filePath))
		return ERROR_SUCCESS;

	return ERROR_FILE_NOT_FOUND;
}


static int
MirrorCloseFile(
	LPCWSTR					FileName,
	PDOKAN_FILE_INFO		DokanFileInfo)
{
	UNREFERENCED_PARAMETER(FileName);
	UNREFERENCED_PARAMETER(DokanFileInfo);

	DAVCACHEENTRY* cacheEntry = NULL;
	ULONG64 cacheHandle = DokanFileInfo->Context;
	BOOL res = FALSE;

	cacheEntry = davCache->getFromHandle(cacheHandle);
	if (cacheEntry != NULL ) {
		if (cacheEntry->needExport) {
			res = server->exportPath(cacheEntry->remotePath, cacheEntry->cachePath);
			if (!res) {
				DbgPrint(L"Error while exporting the file %s\n", cacheEntry->remotePath);
				return -1;
			}
		}
		davCache->remove(cacheHandle);
	}
	DokanFileInfo->Context = INVALID_CACHE_HANDLE;
	return 0;
}


static int
MirrorCleanup(
	LPCWSTR					FileName,
	PDOKAN_FILE_INFO		DokanFileInfo)
{
	UNREFERENCED_PARAMETER(FileName);
	UNREFERENCED_PARAMETER(DokanFileInfo);
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
	DWORD result;

	UNREFERENCED_PARAMETER(DokanFileInfo);

	DbgPrint(L"read file %s\n", FileName);
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
	
	GETRequest req(filePath, Offset, BufferLength);
	if (FAILED(server->sendRequest(req))) {
		DbgPrint(L"MirrorReadFile, failed to send the request\n");
		return -1;
	}

	result = req.getWinStatus();
	if (FAILED(req.get(ReadLength, Buffer))) {
		DbgPrint(L"MirrorReadFile, failed to get file content\n");
	}

	req.close();

	if (*ReadLength > BufferLength)
		*ReadLength = BufferLength;

	return  -1 * result;
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
	DWORD createDisposition = OPEN_ALWAYS;
	ULONG	offset = (ULONG)Offset;
	DAVCACHEENTRY* cacheEntry = NULL;
	BOOL res = FALSE;

	//GetFilePath(filePath, FileName);
//	printf("MirrorWriteFile on %ls\n", FileName);
	DbgPrint(L"write file %s\n", FileName);
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

	cacheEntry = davCache->getFromHandle(cacheHandle);
	if (cacheEntry == NULL) {
		DbgPrint(L"Entry returned by the cache is NULL\n");
		return -1;
	}

	cacheEntry->needExport = TRUE;

	if (wcscmp(cacheEntry->remotePath, filePath) != 0) {
		DbgPrint(L"Entry did not match the file %ls", filePath);
		return -1;
	}

	if (cacheEntry->needImport == TRUE) {
		cacheEntry->needImport = FALSE;
		if (FAILED(server->importURL(cacheEntry->remotePath, cacheEntry->cachePath))) {
			DbgPrint(L"Unable to add %s to local cache, Error while importing the file\n", cacheEntry->remotePath);
			return -1;
		}
		createDisposition = OPEN_ALWAYS;
	}

	cacheEntry->needRemove = TRUE;
	handle = CreateFile(cacheEntry->cachePath, GENERIC_WRITE, 0, NULL, createDisposition, FILE_ATTRIBUTE_NORMAL, NULL);

	if (handle == INVALID_HANDLE_VALUE) {
		DbgPrint(L"CreateFile error : %u\n", GetLastError());
		return ~GetLastError();
	}

	if (DokanFileInfo->WriteToEndOfFile) {
		if (SetFilePointer(handle, 0, NULL, FILE_END) == INVALID_SET_FILE_POINTER) {
			DbgPrint(L"seek error, offset = EOF, error = %u\n", GetLastError());
			CloseHandle(handle);
			return -1;
		}
	}
	else if (SetFilePointer(handle, offset, NULL, FILE_BEGIN) == INVALID_SET_FILE_POINTER) {
		DbgPrint(L"seek error, offset = %d, error = %u\n", offset, GetLastError());
		CloseHandle(handle);
		return -1;
	}

	res = WriteFile(handle, Buffer, NumberOfBytesToWrite, NumberOfBytesWritten, NULL); 
	if (res == FALSE) {
		DbgPrint(L"write error = %u, buffer length = %d, write length = %d\n",	GetLastError(), NumberOfBytesToWrite, *NumberOfBytesWritten);
		CloseHandle(handle);
		return -1;

	} else {
		DbgPrint(L"\twrite %d, offset %d %d %i\n\n", *NumberOfBytesWritten, offset, NumberOfBytesToWrite,GetLastError());
	}
	cacheEntry->needRemove = TRUE;
	FlushFileBuffers(handle);
	CloseHandle(handle);
	DokanFileInfo->Context = cacheHandle;
	return 0;
}


static int
MirrorGetFileInformation(
	LPCWSTR							FileName,
	LPBY_HANDLE_FILE_INFORMATION	HandleFileInformation,
	PDOKAN_FILE_INFO				DokanFileInfo)
{
	WCHAR	filePath[MAX_PATH];
	std::list<DavEntry> list;
	LARGE_INTEGER file_size;
	FILETIME* time;
	DWORD result;

	UNREFERENCED_PARAMETER(DokanFileInfo);

	GetFilePath(FileName, filePath);
	DbgPrint(L"MirrorGetFileInformation on %s\n", filePath);
	
	PROPFINDRequest req(filePath, 0);
	if (FAILED(server->sendRequest(req))) {
		DbgPrint(L"GetFileInformation, failed to send the request\n");
		return -1;
	}

	result = req.getWinStatus();
	if (result != ERROR_SUCCESS) {
		req.close();
		return result;
	}

	if (FAILED(req.getResult(list))){
		req.close();
		return -1;
	}
	
	if (list.size() == 0) {
		return ERROR_FILE_NOT_FOUND;
	}
	req.close();
	DavEntry &entry = list.front();
	
	HandleFileInformation->dwFileAttributes = FILE_ATTRIBUTE_NORMAL;
	if (entry.getType() == DavEntry::directory)
		HandleFileInformation->dwFileAttributes = FILE_ATTRIBUTE_DIRECTORY;

	file_size.QuadPart = entry.getLength();
	HandleFileInformation->nFileSizeHigh = 0;
	HandleFileInformation->nFileSizeLow = 0;
	if (file_size.QuadPart != 0) {
		HandleFileInformation->nFileSizeHigh = file_size.HighPart;
		HandleFileInformation->nFileSizeLow = file_size.LowPart;
	}

	time = entry.getCreationTime();
	HandleFileInformation->ftCreationTime.dwLowDateTime = time->dwLowDateTime;
	HandleFileInformation->ftCreationTime.dwHighDateTime = time->dwHighDateTime;
	time = entry.getLastModifiedTime();
	HandleFileInformation->ftLastAccessTime.dwLowDateTime = time->dwLowDateTime;
	HandleFileInformation->ftLastAccessTime.dwHighDateTime = time->dwHighDateTime;
	HandleFileInformation->ftLastWriteTime.dwLowDateTime = time->dwLowDateTime;
	HandleFileInformation->ftLastWriteTime.dwHighDateTime = time->dwHighDateTime;

	return ERROR_SUCCESS;
}


static int
MirrorFindFiles(
	LPCWSTR				FileName,
	PFillFindData		FillFindData, // function pointer
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	WCHAR filePath[MAX_PATH];
	WCHAR* filename;
	int filename_length = 0;
	WIN32_FIND_DATAW point;
	DavEntry* entry = NULL;
	std::list<DavEntry> list;
	std::list <DavEntry>::const_iterator iter;
	LARGE_INTEGER file_size;
	DWORD result;
	int index = 0;

	GetFilePath(FileName, filePath);
	DbgPrint(L"MirrorFindFiles on %ls\n", filePath);

	PROPFINDRequest req(filePath, 1);
	if (FAILED(server->sendRequest(req))) {
		DbgPrint(L"GetFileInformation, failed to send the request\n");
		return FALSE;
	}

	result = req.getWinStatus();
	if (result != ERROR_SUCCESS) {
		req.close();
		return result;
	}

	if (FAILED(req.getResult(list))){
		req.close();
		return -1;
	}

	if (list.size() == 0) {
		return ERROR_FILE_NOT_FOUND;
	}
	req.close();

	ZeroMemory(&point, sizeof(WIN32_FIND_DATAW));
	point.dwFileAttributes = FILE_ATTRIBUTE_DIRECTORY;
	wcscpy_s(point.cFileName, MAX_PATH , L".");
	FillFindData(&point, DokanFileInfo);

	ZeroMemory(&point, sizeof(WIN32_FIND_DATAW));
	point.dwFileAttributes = FILE_ATTRIBUTE_DIRECTORY;
	wcscpy_s(point.cFileName, MAX_PATH , L"..");
	FillFindData(&point, DokanFileInfo);

	for ( iter = list.begin(); iter != list.end( ); iter++ ) {
		if (index == 0) {
			index++;
			continue;
		}

		WIN32_FIND_DATAW data;
		ZeroMemory(&data, sizeof(WIN32_FIND_DATAW));
		data.dwFileAttributes = FILE_ATTRIBUTE_NORMAL;

		entry = (DavEntry*)&*iter;
		if (entry->getType() == DavEntry::directory)
			data.dwFileAttributes = FILE_ATTRIBUTE_DIRECTORY;

		file_size.QuadPart = entry->getLength();
		data.nFileSizeHigh = 0;
		data.nFileSizeLow = 0;
		if (file_size.QuadPart != 0) {
			data.nFileSizeHigh = file_size.HighPart;
			data.nFileSizeLow = file_size.LowPart;
		}

		data.ftCreationTime = *entry->getCreationTime();
		data.ftLastAccessTime = *entry->getLastModifiedTime();
		data.ftLastWriteTime = *entry->getLastModifiedTime();
		
		filename = PathFindFileName(entry->getPath());
		filename_length = lstrlen(filename);
		if (filename[filename_length-1] == '/') {
			filename[filename_length-1] = '\0';
		}
		wcscpy_s(data.cFileName, MAX_PATH , filename);
		
		FillFindData(&data, DokanFileInfo);
		index++;
	}
	return ERROR_SUCCESS;
}


static int
MirrorDeleteFile(
	LPCWSTR				FileName,
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	WCHAR filePath[MAX_PATH];
	ULONG64 cacheHandle = DokanFileInfo->Context;
	DWORD result;

	GetFilePath(FileName, filePath);
	DbgPrint(L"MirrorDeleteFile : %ls\n", (WCHAR*)filePath);

	if (cacheHandle != INVALID_CACHE_HANDLE) {
		davCache->remove(cacheHandle);
	}

	DbgPrint(L"MirrorDeleteFile : %ls\n", (WCHAR*)filePath);

	DELETERequest req(filePath);
	if (FAILED(server->sendRequest(req))) {
		DbgPrint(L"MirrorDeleteFile, failed to send the request\n");
		return -1;
	}

	result = req.getWinStatus();
	req.close();

	// save the file handle in Context
	DokanFileInfo->Context = INVALID_CACHE_HANDLE;
	return -1 * result;
}


static int
MirrorDeleteDirectory(
	LPCWSTR				FileName,
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	WCHAR filePath[MAX_PATH];
	DWORD result;

	GetFilePath(FileName, filePath);

	DbgPrint(L"MirrorDeleteDirectory : %ls\n", (WCHAR*)filePath);

	DELETERequest req(filePath);
	if (FAILED(server->sendRequest(req))) {
		DbgPrint(L"MirrorDeleteFile, failed to send the request\n");
		return -1;
	}

	result = req.getWinStatus();
	req.close();

	// save the file handle in Context
	DokanFileInfo->Context = INVALID_CACHE_HANDLE;
	return -1 * result;


}


static int
MirrorMoveFile(
	LPCWSTR				FileName, // existing file name
	LPCWSTR				NewFileName,
	BOOL				ReplaceIfExisting,
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	WCHAR filePath[MAX_PATH];
	WCHAR newFilePath[MAX_PATH];
	WCHAR destinationURL[MAX_PATH]; 
	DWORD result;

	UNREFERENCED_PARAMETER(DokanFileInfo);

	GetFilePath(FileName, filePath);
	GetFilePath(NewFileName, newFilePath);
	
	DbgPrint(L"MoveFile %s -> %s\n\n", filePath, newFilePath);

	server->getAbsolutePath(destinationURL, newFilePath);
	MOVERequest req(filePath, destinationURL, ReplaceIfExisting);
	if (FAILED(server->sendRequest(req))) {
		DbgPrint(L"MirrorDeleteFile, failed to send the request\n");
		return -1;
	}

	result = req.getWinStatus();
	req.close();

	return -1 * result;
}


static int
MirrorLockFile(
	LPCWSTR				FileName,
	LONGLONG			ByteOffset,
	LONGLONG			Length,
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	UNREFERENCED_PARAMETER(FileName);
	UNREFERENCED_PARAMETER(ByteOffset);
	UNREFERENCED_PARAMETER(Length);
	UNREFERENCED_PARAMETER(DokanFileInfo);
	return ERROR_SUCCESS;
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
	return ERROR_SUCCESS;
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
	return ERROR_SUCCESS;
}


static int
MirrorSetFileAttributes(
	LPCWSTR				FileName,
	DWORD				FileAttributes,
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	UNREFERENCED_PARAMETER(FileName);
	UNREFERENCED_PARAMETER(FileAttributes);
	UNREFERENCED_PARAMETER(DokanFileInfo);
	return ERROR_SUCCESS;
}


static int
MirrorSetFileTime(
	LPCWSTR				FileName,
	CONST FILETIME*		CreationTime,
	CONST FILETIME*		LastAccessTime,
	CONST FILETIME*		LastWriteTime,
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	UNREFERENCED_PARAMETER(FileName);
	UNREFERENCED_PARAMETER(CreationTime);
	UNREFERENCED_PARAMETER(LastAccessTime);
	UNREFERENCED_PARAMETER(LastWriteTime);
	UNREFERENCED_PARAMETER(DokanFileInfo);

	return ERROR_SUCCESS;
}



static int
MirrorUnlockFile(
	LPCWSTR				FileName,
	LONGLONG			ByteOffset,
	LONGLONG			Length,
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	UNREFERENCED_PARAMETER(FileName);
	UNREFERENCED_PARAMETER(ByteOffset);
	UNREFERENCED_PARAMETER(Length);
	UNREFERENCED_PARAMETER(DokanFileInfo);
	return ERROR_SUCCESS;
}


static int
MirrorUnmount(
	PDOKAN_FILE_INFO	DokanFileInfo)
{
	UNREFERENCED_PARAMETER(DokanFileInfo);
	wprintf(L"Unmount\n");

	return ERROR_SUCCESS;
}


WCHAR getNextFreeLetter() {
	WCHAR drive[] = L"E:";
	UINT result = 0;

	for (int i = (int)L'E' ; i <= (int)L'Z'; i++) {
		drive[0] = (WCHAR)i;

		result = GetDriveType((LPCTSTR)drive);

		if (result == DRIVE_NO_ROOT_DIR) {
			return drive[0];
		}
	}
	return L':';
}



int _cdecl
main(ULONG argc, PCHAR argv[]) {
	int status;
	ULONG command;
	HRESULT result;
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
	if (MountPoint[0] == L':') {
		MountPoint[0] =  getNextFreeLetter();
		if (MountPoint[0] == L':') {
			return DOKAN_DRIVE_LETTER_ERROR;
		}
		dokanOptions->MountPoint = MountPoint;
		DbgPrint(L"Next free drive is %s\n", MountPoint);
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
	if (!server->connect()) {
		return DOKAN_ERROR;
	}
	davCache = new DavCache();
	davCache->init(server);

	result = server->test();
	if (result != ERROR_SUCCESS) {
		if (davCache) {
			davCache->clean();
			delete davCache;
		}
		if (server) {
			server->disconnect();
			delete server;
		}
		return result;
	}

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

	signal( SIGINT, sigint_handler );

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

	if (davCache) {
		davCache->clean();
		delete davCache;
	}
	if (server) {
		server->disconnect();
		delete server;
	}
	return status;
}

