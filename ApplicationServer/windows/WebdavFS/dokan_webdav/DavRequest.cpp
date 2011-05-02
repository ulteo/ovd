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

#include "DavRequest.h"


DavRequest::DavRequest() {
	this->method = L"";
	this->path = NULL;
	this->status = 500;
	this->redirectedPath = NULL;
	this->useHTTPS = FALSE;
}


DavRequest::DavRequest(WCHAR* path) {
	this->path = path;
	this->status = 500;
	this->redirectedPath = NULL;
	this->useHTTPS = FALSE;
}


DavRequest::~DavRequest() {
	if (this->redirectedPath)
		delete [] this->redirectedPath;
}


const WCHAR* DavRequest::getMethod() {
	return this->method;
}


DWORD DavRequest::getStatus() {
	return this->status;
}


DWORD DavRequest::getWinStatus() {
	switch (this->status){
	case 200:
	case 201:
	case 202:
	case 203:
	case 204:
	case 206:
	case 207:
		return ERROR_SUCCESS;
		break;
	case 404:
		return ERROR_FILE_NOT_FOUND;
		break;
	case 403:
		return ERROR_ACCESS_DENIED;
	default:
		return ERROR_INVALID_HANDLE;
	}
}


const WCHAR* DavRequest::getRedirectedPath() {
	return this->redirectedPath;
}


const WCHAR* DavRequest::getPath() {
	return this->path;
}


void DavRequest::updateStatus() {
	DWORD size;
	BOOL bResults;

	if (! this->hRequest) {
		this->status = 500;
		return;
	}

	size = sizeof(DWORD);
	bResults = WinHttpQueryHeaders(this->hRequest, WINHTTP_QUERY_STATUS_CODE| WINHTTP_QUERY_FLAG_NUMBER, NULL, &this->status, &size, NULL );
	if (! bResults)
		this->status = 500;
}

void DavRequest::updateRedirectedPath() {
	BOOL bResults;
	LPWSTR redirectURL;
	DWORD dwSize;
	URL_COMPONENTS urlComp;

	this->redirectedPath = NULL;

	WinHttpQueryHeaders(this->hRequest, WINHTTP_QUERY_LOCATION, WINHTTP_HEADER_NAME_BY_INDEX, NULL, &dwSize, WINHTTP_NO_HEADER_INDEX);

	if (GetLastError() != ERROR_INSUFFICIENT_BUFFER ) {
		status = 500;
		return;
	}

	redirectURL = new WCHAR[dwSize];
	// Now, use WinHttpQueryHeaders to retrieve the header.
	bResults = WinHttpQueryHeaders(this->hRequest, WINHTTP_QUERY_LOCATION, WINHTTP_HEADER_NAME_BY_INDEX, redirectURL, &dwSize, WINHTTP_NO_HEADER_INDEX);
	if (! bResults) {
		status = 500;
		return;
	}

	//crack the url
	ZeroMemory(&urlComp, sizeof(urlComp));

	// Set required component lengths to non-zero
	// so that they are cracked.
	urlComp.dwSchemeLength    = (DWORD)MAX_PATH;
	urlComp.dwHostNameLength  = (DWORD)MAX_PATH;
	urlComp.dwUrlPathLength   = (DWORD)MAX_PATH;
	urlComp.lpszScheme    = new WCHAR[MAX_PATH];
	urlComp.lpszHostName  = new WCHAR[MAX_PATH];
	urlComp.lpszUrlPath   = new WCHAR[MAX_PATH];
	urlComp.dwExtraInfoLength = (DWORD)-1;
	urlComp.dwStructSize = sizeof(urlComp);

	// Crack the URL.
	bResults = WinHttpCrackUrl(redirectURL, (DWORD)wcslen(redirectURL), 0, &urlComp);

	if (bResults)
		this->redirectedPath = _wcsdup(urlComp.lpszUrlPath);
	else
		status = 500;

	delete [] urlComp.lpszHostName;
	delete [] urlComp.lpszScheme;
	delete [] urlComp.lpszUrlPath;
	delete [] redirectURL;
}


HRESULT DavRequest::setCredential(WCHAR* username, WCHAR* password) {
	if (!this->hRequest)
		return E_FAIL;

	if (! WinHttpSetCredentials( hRequest, 	WINHTTP_AUTH_TARGET_SERVER,	WINHTTP_AUTH_SCHEME_BASIC, username, password, NULL ))
		return E_FAIL;
	
	return S_OK;
}


HRESULT DavRequest::create(HINTERNET hConnect, WCHAR* url, BOOL useHTTPS) {
	DWORD dwOptionValue = 0;
	DWORD dwFlags = 0;

	if (! hConnect)
		return E_INVALIDARG;

	this->useHTTPS = useHTTPS;
	if (this->useHTTPS) {
		dwFlags |= WINHTTP_FLAG_SECURE;
		dwOptionValue = SECURITY_FLAG_IGNORE_CERT_CN_INVALID
						| SECURITY_FLAG_IGNORE_CERT_DATE_INVALID
						| SECURITY_FLAG_IGNORE_UNKNOWN_CA
						| SECURITY_FLAG_IGNORE_CERT_WRONG_USAGE;
	}

	this->hRequest = WinHttpOpenRequest(hConnect, this->method, url, HTTP_VERSION, WINHTTP_NO_REFERER, WINHTTP_DEFAULT_ACCEPT_TYPES, dwFlags);
	
	WinHttpSetOption(this->hRequest, WINHTTP_OPTION_SECURITY_FLAGS, &dwOptionValue, sizeof(dwOptionValue));
	dwOptionValue = WINHTTP_DISABLE_REDIRECTS;
	WinHttpSetOption(this->hRequest, WINHTTP_OPTION_DISABLE_FEATURE, &dwOptionValue, sizeof(dwOptionValue));

	if (! hRequest)
		return E_FAIL;

	return S_OK;
}


HRESULT DavRequest::perform() {
	BOOL  bResults = FALSE;

	if (! this->hRequest)
		return E_INVALIDARG;

	bResults = WinHttpSendRequest(this->hRequest, WINHTTP_NO_ADDITIONAL_HEADERS, 0, NULL, 0, 0, 0);

	if (! bResults)
		return E_FAIL;

	bResults = WinHttpReceiveResponse(this->hRequest, NULL);
	this->updateStatus();

	return S_OK;
}


HRESULT DavRequest::close() {
	if (!this->hRequest)
		return E_FAIL;
		
	WinHttpCloseHandle(hRequest);
	return S_OK;
}
