/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
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

#ifndef WEBDAVSERVER_H_
#define WEBDAVSERVER_H_

#include <stdio.h>
#include <stdlib.h>
#include <Shlwapi.h>
#include <windows.h>
#include <winhttp.h>


#define ULTEO_USER_AGENT        L"Ulteo WebDav Driver/1.0"
#define HTTP_VERSION            L"HTTP/1.1"
#define DEFAULT_PROPFIND        L"<?xml version=\"1.0\" ?><D:propfind xmlns:D=\"DAV:\"><D:allprop/></D:propfind>"
#define STRUCT_FILEINFO         1
#define DAV_DATA_CHUNCK         2048


typedef struct _DAVFILEINFO {
	WCHAR name[MAX_PATH];
	BOOL isDir;
	FILETIME creationTime;
	FILETIME lastModified;
	DWORD fileAttributes;
	DWORD nFileSizeHigh;
	DWORD nFileSizeLow;
} DAVFILEINFO, *PDAVFILEINFO;




class WebdavServer {
private:
	HINTERNET hSession;
	HINTERNET hConnect;
	WCHAR user[256];
	WCHAR password[256];
	WCHAR address[MAX_PATH];
	WCHAR prefixe[MAX_PATH];
	BOOL useHTTPS;
	int port;

	LPSTR getData( HINTERNET hRequest, PDWORD dwSize, PDWORD status);
	BOOL path_join(WCHAR* dest_, WCHAR* prefixe_, WCHAR* path_);
	void getAbsolutePath(WCHAR* dest, WCHAR* path);

public:
	WebdavServer(wchar_t* address, int port, wchar_t* prefixe, wchar_t* username, wchar_t* password, BOOL useHTTPS);
	~WebdavServer();

	HRESULT init();
	BOOL connect();
	BOOL disconnect();
	HINTERNET requestNew(wchar_t* method, wchar_t* path );
	BOOL requestDel(HINTERNET hRequest );
	WCHAR* getRedirectPath(HINTERNET hRequest);
	DWORD getStatus(HINTERNET hRequest);


	DAVFILEINFO DAVFSInfoNew();
	HINTERNET DAVPROPFind(wchar_t** path, wchar_t* body, int depth);
	BOOL DAVOpen(wchar_t* path );
	DAVFILEINFO* DAVGetFileInformations(LPCWSTR path);
	DAVFILEINFO* DAVGetDirectoryList(LPCWSTR path, PDWORD count );


	BOOL DAVGetFileContent(wchar_t* path, LPDWORD ReadLength, LONGLONG	Offset, DWORD BufferLength, LPVOID Buffer, BOOL redirected );
	BOOL DAVImportFileContent(wchar_t* remotePath, wchar_t* localPath, BOOL redirected );
	BOOL DAVExportFileContent(wchar_t* remotePath, wchar_t* locaPath, BOOL redirected );
	BOOL DAVWriteFile(wchar_t* path, LPCVOID Buffer, LPDWORD ReadLength, LONGLONG	Offset, DWORD BufferLength, BOOL redirected );
	BOOL DAVMKCOL(wchar_t* path, BOOL redirected );
	BOOL DAVDELETE(wchar_t* path, BOOL redirected );
	BOOL DAVMOVE(wchar_t* from, wchar_t* to, BOOL redirected, BOOL replaceIfExisting);
};

#endif /* WEBDAVSERVER_H_ */
