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

#include "DavRequest.h"
#include "PROPFINDRequest.h"
#include "GETRequest.h"
#include "PUTRequest.h"


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

	BOOL path_join(WCHAR* dest_, WCHAR* prefixe_, WCHAR* path_);

public:
	WebdavServer(wchar_t* address, int port, wchar_t* prefixe, wchar_t* username, wchar_t* password, BOOL useHTTPS);
	~WebdavServer();

	HRESULT init();
	BOOL connect();
	BOOL disconnect();
	
	HRESULT sendRequest(DavRequest &req);
	HRESULT importURL(WCHAR* remotePath, WCHAR* localPath);
	HRESULT exportPath(WCHAR* remotePath, WCHAR* localPath);
	HRESULT touch(WCHAR* remotePath);
	BOOL exist(WCHAR* path);
	
	void getAbsolutePath(WCHAR* dest, WCHAR* path);
};

#endif /* WEBDAVSERVER_H_ */
