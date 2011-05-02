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

#include "PROPFINDRequest.h"


PROPFINDRequest::PROPFINDRequest(WCHAR* url, int depth): DavRequest(url) {
	this->method = L"PROPFIND";
	this->depth = depth;
	this->parser = NULL;
}

PROPFINDRequest::~PROPFINDRequest() {
	// if (this->parser) {
		// parser->release();
		// delete parser;
	// }
}

HRESULT PROPFINDRequest::perform() {
	wstring depthProperty;
	wostringstream ss;
	BOOL  bResults = FALSE;
	int bodyLength = 0;

	if (! this->hRequest)
		return E_INVALIDARG;

	ss<<L"Depth: "<<depth;
	depthProperty = ss.str();
	
	//set Depth
	bodyLength = lstrlen(DEFAULT_PROPFIND)*sizeof(WCHAR);

	WinHttpAddRequestHeaders(this->hRequest, depthProperty.c_str(), (DWORD)-1, WINHTTP_ADDREQ_FLAG_REPLACE|WINHTTP_ADDREQ_FLAG_ADD);
	bResults = WinHttpSendRequest(this->hRequest, WINHTTP_NO_ADDITIONAL_HEADERS, 0, DEFAULT_PROPFIND, bodyLength, bodyLength, 0);

	if (! bResults)
		return E_FAIL;

	bResults = WinHttpReceiveResponse(this->hRequest, NULL);
	this->updateStatus();

	return S_OK;
}


HRESULT PROPFINDRequest::getResult(list<DavEntry> &result) {
	if (! this->hRequest) 
		return E_FAIL;

	this->parser = new XMLDavParser(this->hRequest);
	if (this->parser->getLastError() != S_OK)
		return E_FAIL;

	parser->init();
	parser->parse(result);
	// delete parser;
	return S_OK;
}

HRESULT PROPFINDRequest::close() {
	DavRequest::close();

	// if (this->parser) {
		// parser->release();
		// delete parser;
	// }
	return S_OK;
}
