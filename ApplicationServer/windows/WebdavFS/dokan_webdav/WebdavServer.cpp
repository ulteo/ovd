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

#define COBJMACROS

#include "WebdavServer.h"
#include "XMLDavParser.h"
#include "debug.h"

extern BOOL g_UseStdErr;
extern BOOL g_DebugMode;


WebdavServer::WebdavServer(wchar_t* address_, int port_, wchar_t* prefixe_, wchar_t* username_, wchar_t* password_, BOOL useHTTPS_) {
	int len = 0;
	port = port_;
	wcscpy_s(prefixe, MAX_PATH, prefixe_ );
	wcscpy_s(address, MAX_PATH, address_);

	len = lstrlen(prefixe);
	if (prefixe[len-1] == '/') {
		prefixe[len-1] = '\0';
	}

	hSession = NULL;
	hConnect = NULL;
	useHTTPS = useHTTPS_;

	if (username_) {
		wcscpy_s(user, 256, username_);
		wcscpy_s(password, 256, password_);
	}
}

WebdavServer::~WebdavServer() {
	if (hSession)
		WinHttpCloseHandle(hSession);
}


HRESULT WebdavServer::init() {
	DWORD dwErr = 0;
	hSession = WinHttpOpen( L"Ulteo WebdavFS", WINHTTP_ACCESS_TYPE_DEFAULT_PROXY, WINHTTP_NO_PROXY_NAME, WINHTTP_NO_PROXY_BYPASS, 0 );

	if( hSession == NULL)
	{
		dwErr = GetLastError();
		switch (dwErr)
		{
		case ERROR_WINHTTP_AUTO_PROXY_SERVICE_ERROR:
			DbgPrint(L"ERROR_WINHTTP_AUTO_PROXY_SERVICE_ERROR\n");
		break;
		case ERROR_WINHTTP_INTERNAL_ERROR:
			DbgPrint(L"ERROR_WINHTTP_INTERNAL_ERROR\n");
		break;
		case ERROR_WINHTTP_OPERATION_CANCELLED:
			DbgPrint(L"ERROR_WINHTTP_OPERATION_CANCELLED\n");
		break;
		case ERROR_NOT_ENOUGH_MEMORY:
			DbgPrint(L"ERROR_NOT_ENOUGH_MEMORY\n");
		break;
		case ERROR_WINHTTP_AUTODETECTION_FAILED:
			DbgPrint(L"ERROR_WINHTTP_AUTODETECTION_FAILED\n");
		break;
		case ERROR_WINHTTP_BAD_AUTO_PROXY_SCRIPT:
			DbgPrint(L"ERROR_WINHTTP_BAD_AUTO_PROXY_SCRIPT\n");
		break;
		case ERROR_WINHTTP_INCORRECT_HANDLE_TYPE:
			DbgPrint(L"ERROR_WINHTTP_INCORRECT_HANDLE_TYPE\n");
		break;
		case ERROR_WINHTTP_INVALID_URL:
			DbgPrint(L"ERROR_WINHTTP_INVALID_URL\n");
		break;
		case ERROR_WINHTTP_LOGIN_FAILURE:
			DbgPrint(L"ERROR_WINHTTP_LOGIN_FAILURE\n");
		break;
		case ERROR_WINHTTP_UNABLE_TO_DOWNLOAD_SCRIPT:
			DbgPrint(L"ERROR_WINHTTP_UNABLE_TO_DOWNLOAD_SCRIPT\n");
		break;
		case ERROR_WINHTTP_UNRECOGNIZED_SCHEME:
			DbgPrint(L"ERROR_WINHTTP_UNRECOGNIZED_SCHEME\n");
		break;
		default:
			DbgPrint(L"Unknow Error\n");
		}
	}
	return dwErr;
}

void WebdavServer::getAbsolutePath(WCHAR* dest, WCHAR* path) {
	WCHAR prefix[MAX_PATH] = {0};
	WCHAR* escaped = NULL;

	if (useHTTPS)
		wcscpy_s(prefix, sizeof(prefix), L"https://");
	else
		wcscpy_s(prefix, sizeof(prefix), L"http://");

	wcscat_s(prefix, sizeof(prefix), address);
	if (path[0] == '/') {
		swprintf_s(dest, MAX_PATH, L"%s:%i%s%s", prefix, port, prefixe, path);
	}
	else {
		swprintf_s(dest, MAX_PATH, L"%s:%i%s/%s", prefix, port, prefixe, path);
	}

	escaped = DavEntry::urlencode(dest);
	if (escaped == NULL) {
		return;
	}
	wcscpy_s(dest, MAX_PATH, escaped);
	free(escaped);
}


BOOL WebdavServer::connect() {
	// Specify an HTTP server.
	if (! hSession )
	{
		DbgPrint(L"WinHTTP session did not exist\n");
		return FALSE;
	}
	hConnect = WinHttpConnect( hSession, address, (INTERNET_PORT)port, 0);
	// Create an HTTP request handle.
	if( hConnect ) {
		return TRUE;
	}
	DbgPrint(L"Failed to etablish conncetion to %s:%i\n",address, port );
	return FALSE;
}

BOOL WebdavServer::disconnect() {
	// Specify an HTTP server.
	if (! hSession )
	{
		DbgPrint(L"WinHTTP session did not exist\n");
		return FALSE;
	}
	if ( hConnect) {
		WinHttpCloseHandle(hConnect);
		hConnect = NULL;
		return TRUE;
	}
	DbgPrint(L"Failed to etablish conncetion to %s:%i\n", address, port );
	return FALSE;
}


BOOL WebdavServer::path_join(WCHAR* dest_, WCHAR* prefixe_, WCHAR* path_) {
	if (dest_ == NULL || prefixe_ == NULL || path_ == NULL) {
		return FALSE;
	}

	if (path_[0] == '/') {
		swprintf_s(dest_, MAX_PATH, L"%s%s", prefixe_, path_);
	}
	else {
		swprintf_s(dest_, MAX_PATH, L"%s/%s", prefixe_, path_);
	}
	return TRUE;
}


HRESULT WebdavServer::touch(WCHAR* path) {
	DWORD result;

	PUTRequest req(path);
	if (FAILED(this->sendRequest(req))) {
		DbgPrint(L"touch, failed to send the request\n");
		return -1;
	}

	result = req.getWinStatus();
	req.close();

	return -1 * result;
}


HRESULT WebdavServer::exportPath(WCHAR* remotePath, WCHAR* localPath) {
	DWORD result;
	LARGE_INTEGER fileLength;
	HANDLE handle;

	handle = CreateFile( localPath, GENERIC_READ, 0, NULL, OPEN_EXISTING, FILE_ATTRIBUTE_NORMAL, NULL);
	if (handle == INVALID_HANDLE_VALUE) {
		DbgPrint(L"Unable to open the file %ls [error code = %u]\n", localPath, GetLastError());
		return  E_FAIL;
	}

	if (! GetFileSizeEx(handle, &fileLength)) {
		DbgPrint(L"Failed to get size of the file %s\n", localPath);
		CloseHandle(handle);
		return E_FAIL;
	}
	CloseHandle(handle);

	PUTRequest req(remotePath, fileLength.QuadPart);
	if (FAILED(this->sendRequest(req))) {
		DbgPrint(L"MirrorReadFile, failed to send the request\n");
		return -1;
	}

	result = req.getWinStatus();

	if (FAILED(req.exportPath(localPath))) {
		DbgPrint(L"import, failed to import the file from %s to %s\n");
		return E_FAIL;
	}

	req.close();
	return -1 * result;
}


HRESULT WebdavServer::importURL(WCHAR* remotePath, WCHAR* localPath) {
	DWORD result;

	GETRequest req(remotePath);
	if (FAILED(this->sendRequest(req))) {
		DbgPrint(L"MirrorReadFile, failed to send the request\n");
		return -1;
	}

	result = req.getWinStatus();

	if (FAILED(req.import(localPath))) {
		DbgPrint(L"import, failed to import the file from %s to %s\n");
		return E_FAIL;
	}

	req.close();
	return -1 * result;
}

HRESULT WebdavServer::test() {
	HRESULT ret;
	PROPFINDRequest req(L"/", 0);

	if (FAILED(sendRequest(req))) {
		DbgPrint(L"failed to send the request\n");
		return E_FAIL;
	}
	ret = req.getWinStatus();
	req.close();
	return ret;
}


BOOL WebdavServer::exist(WCHAR* path) {
	BOOL ret = FALSE;
	PROPFINDRequest req(path, 0);
	
	if (FAILED(sendRequest(req))) {
		DbgPrint(L"failed to send the request\n");
		return FALSE;
	}

	ret = (req.getWinStatus() == ERROR_SUCCESS);
	req.close();
	return ret;
}



HRESULT WebdavServer::sendRequest(DavRequest &req) {
	WCHAR url[MAX_PATH] = {0};
	WCHAR* path;
	DWORD status;

	path = (WCHAR*)req.getPath();
	if (path == NULL)
		return E_INVALIDARG;

	if (! path_join(url, prefixe, path)) {
		DbgPrint(L"Unable to merge %s and %s\n", prefixe, path);
		return E_INVALIDARG;
	}
	path = DavEntry::urlencode(url);
	if (FAILED(req.create(this->hConnect, path, this->useHTTPS)))
		return E_FAIL;

	if (this->user)
		req.setCredential(user, password);

	if (FAILED(req.perform())) {
		req.close();
		return E_FAIL;
	}

	status = req.getStatus();
	if ((status == 301)) {
	
		req.updateRedirectedPath();
		path = (WCHAR*)req.getRedirectedPath();
		if (! path) {
			req.close();
			return E_FAIL;
		}	
		req.close();
		if (FAILED(req.create(this->hConnect, path, this->useHTTPS)))
			return E_FAIL;

		if (user)
			req.setCredential(user, password);

		if (FAILED(req.perform())) {
			req.close();
			return E_FAIL;
		}
	}
	return S_OK;
}
