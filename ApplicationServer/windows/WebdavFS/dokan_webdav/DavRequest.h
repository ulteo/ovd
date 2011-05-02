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

#ifndef DAVREQUEST_H_
#define DAVREQUEST_H_

#include <iostream>
#include <windows.h>
#include <winhttp.h>

#define ULTEO_USER_AGENT        L"Ulteo WebDav Driver/1.0"
#define HTTP_VERSION            L"HTTP/1.1"
#define DAV_DATA_CHUNCK         2048

using namespace std;

class DavRequest {

public:
	DavRequest();
	DavRequest(WCHAR* url1);
	virtual ~DavRequest();

protected:
	const WCHAR* method;
	WCHAR* redirectedPath;
	HINTERNET hRequest;
	DWORD status;
	WCHAR* path;
	BOOL useHTTPS;

	void updateStatus();

public:
	virtual HRESULT perform();
	virtual HRESULT close();

	void updateRedirectedPath();
	const WCHAR* getRedirectedPath();
	const WCHAR* getMethod();
	const WCHAR* getPath();

	DWORD getStatus();
	DWORD getWinStatus();
	HRESULT create(HINTERNET hConnect, WCHAR* url, BOOL useHTTPS);
	HRESULT setCredential(WCHAR* username, WCHAR* password);
};
#endif /* DAVREQUEST_H_ */
