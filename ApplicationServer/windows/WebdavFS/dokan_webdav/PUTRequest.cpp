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

#include "PUTRequest.h"


PUTRequest::PUTRequest(WCHAR* url, LONGLONG totalLength): DavRequest(url) {
	this->method = L"PUT";
	this->totalLength = totalLength;
}


PUTRequest::PUTRequest(WCHAR* url): DavRequest(url) {
	this->method = L"PUT";
	this->totalLength = 0;
}


PUTRequest::~PUTRequest() {}


HRESULT PUTRequest::perform() {
	if (! this->hRequest)
		return E_INVALIDARG;

	if (! WinHttpSendRequest(this->hRequest, WINHTTP_NO_ADDITIONAL_HEADERS, 0, WINHTTP_NO_REQUEST_DATA, 0, (DWORD)this->totalLength, 0)){
		this->status = 500;
		return E_FAIL;
	}

	this->status = 200;
	return S_OK;
}


HRESULT PUTRequest::send(LPVOID buffer, DWORD bufferLength, LPDWORD writted) {
	if (! buffer)
		return E_INVALIDARG;

	if (! WinHttpWriteData( this->hRequest, (LPVOID)buffer, bufferLength, writted))
		return E_FAIL;

	WinHttpReceiveResponse(this->hRequest, NULL);
	this->updateStatus();

	return ERROR_SUCCESS;
}


HRESULT PUTRequest::exportPath(WCHAR* localPath) {
	HANDLE handle = NULL;
	char buffer[DAV_DATA_CHUNCK] = {0};
	DWORD NumberOfBytesRead = 0;
	DWORD NumberOfBytesWritten = 0;


	handle = CreateFile( localPath, GENERIC_READ, 0, NULL, OPEN_EXISTING, FILE_ATTRIBUTE_NORMAL, NULL);

	if (handle == INVALID_HANDLE_VALUE)
		return  E_FAIL;

	do {
		if (!ReadFile(handle, buffer, DAV_DATA_CHUNCK, &NumberOfBytesRead, NULL)) {
			CloseHandle(handle);
			return E_FAIL;
		}

		if (NumberOfBytesRead == 0)
			break;

		if (! WinHttpWriteData( this->hRequest, (LPVOID)buffer, NumberOfBytesRead, &NumberOfBytesWritten)) {
			CloseHandle(handle);
			return E_FAIL;
		}

	} while (NumberOfBytesRead > 0);

	WinHttpReceiveResponse(this->hRequest, NULL);
	this->updateStatus();

	CloseHandle(handle);
	return ERROR_SUCCESS;
}


