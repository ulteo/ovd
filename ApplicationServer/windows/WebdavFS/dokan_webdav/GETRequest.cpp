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

#include "GETRequest.h"


GETRequest::GETRequest(WCHAR* url, LONGLONG offset, DWORD length): DavRequest(url) {
	this->method = L"GET";
	this->offset = offset;
	this->length = length;
}

GETRequest::GETRequest(WCHAR* url): DavRequest(url){
	this->method = L"GET";
	this->offset = -1;
	this->length = 0;
}


GETRequest::~GETRequest() {}


HRESULT GETRequest::perform() {
	wstring rangeProperty;
	wostringstream ss;
	BOOL  bResults = FALSE;

	if (! this->hRequest)
		return E_INVALIDARG;

	if (this->offset > 0 && this->length > 0) {
		ss<<L"Range: bytes="<<(int)this->offset<<L"-"<<(int)(this->offset+this->length);
		rangeProperty = ss.str();
		WinHttpAddRequestHeaders(this->hRequest, rangeProperty.c_str(), (DWORD)-1, WINHTTP_ADDREQ_FLAG_REPLACE|WINHTTP_ADDREQ_FLAG_ADD);
	}

	bResults = WinHttpSendRequest(this->hRequest, WINHTTP_NO_ADDITIONAL_HEADERS, 0, WINHTTP_NO_REQUEST_DATA, 0, 0, 0);

	if (! bResults)
		return E_FAIL;

	bResults = WinHttpReceiveResponse(this->hRequest, NULL);
	this->updateStatus();

	return S_OK;
}


HRESULT GETRequest::get(LPDWORD readLength, LPVOID buffer) {
	DWORD tempSize = 1;
	DWORD dwDownloaded = 0;
	char* buf = (char*)buffer;

	if (this->length == 0)
		return E_INVALIDARG;
	
	*readLength = 0;
	while (tempSize > 0) {
		// Check for available data.
		tempSize = 0;
		if (!WinHttpQueryDataAvailable( this->hRequest, &tempSize)) {
			return E_FAIL;
		}

		if (*readLength + tempSize > this->length)
			tempSize = this->length - *readLength;

		if (tempSize == 0) {
			break;
		}

		// Read the Data.
		if (!WinHttpReadData( hRequest, buf, tempSize, &dwDownloaded)) {
			return E_FAIL;
		}

		*readLength += dwDownloaded;
		buf += dwDownloaded;
	};
	return ERROR_SUCCESS;
}

HRESULT GETRequest::import(WCHAR* localPath) {
	HANDLE	handle = 0;
	char buffer[DAV_DATA_CHUNCK] = {0};
	DWORD dwDownloaded = 1;
	DWORD NumberOfBytesWritten = 0;

	if (!buffer)
		return E_INVALIDARG;

	handle = CreateFile( localPath,
	                     GENERIC_READ|GENERIC_WRITE|GENERIC_EXECUTE,
	                     0,
	                     NULL, // security attribute
	                     CREATE_ALWAYS,
	                     FILE_ATTRIBUTE_NORMAL,
	                     NULL); // template file handle

	if (handle == INVALID_HANDLE_VALUE)
		return E_FAIL;

	while (dwDownloaded > 0) {
		// Check for available data.
		if (!WinHttpQueryDataAvailable( hRequest, &dwDownloaded))
			return E_FAIL;

		// Read the Data.
		if (!WinHttpReadData( hRequest, buffer, DAV_DATA_CHUNCK, &dwDownloaded))
			return E_FAIL;

		if (!WriteFile(handle, buffer, dwDownloaded, &NumberOfBytesWritten, NULL))
			return E_FAIL;
	};
	CloseHandle(handle);

	return ERROR_SUCCESS;
}


HRESULT GETRequest::close() {
	DavRequest::close();
	return S_OK;
}
