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

#include "MOVERequest.h"


MOVERequest::MOVERequest(WCHAR* url, WCHAR* destination, BOOL replace): DavRequest(url) {
	this->method = L"MOVE";
	this->destination = destination;
	this->replace = replace;
}


MOVERequest::~MOVERequest() {}


HRESULT MOVERequest::perform() {
	wstring destinationProperty;
	wostringstream ss;
	BOOL  bResults = FALSE;

	if (! this->hRequest)
		return E_INVALIDARG;

	ss<<L"Destination: "<<this->destination;
	if (redirectedPath)
		ss<<L"/";
	
	destinationProperty = ss.str();
	WinHttpAddRequestHeaders(this->hRequest, destinationProperty.c_str(), (DWORD)-1, WINHTTP_ADDREQ_FLAG_REPLACE|WINHTTP_ADDREQ_FLAG_ADD);

	if (this->replace)
		WinHttpAddRequestHeaders(this->hRequest, L"Overwrite:T", (DWORD)-1, WINHTTP_ADDREQ_FLAG_REPLACE|WINHTTP_ADDREQ_FLAG_ADD);

	bResults = WinHttpSendRequest(this->hRequest, WINHTTP_NO_ADDITIONAL_HEADERS, 0, WINHTTP_NO_REQUEST_DATA, 0, 0, 0);

	if (! bResults)
		return E_FAIL;

	bResults = WinHttpReceiveResponse(this->hRequest, NULL);
	this->updateStatus();

	return S_OK;
}
