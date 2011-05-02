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

#ifndef PUTREQUEST_H_
#define PUTREQUEST_H_

#include "DavRequest.h"
#include <iostream>
#include <string>
#include <sstream>
#include <list>
#include "XMLDavParser.h"
#include "DavEntry.h"


using namespace std;

class PUTRequest: public DavRequest {
private:
	LONGLONG totalLength;

public:
	PUTRequest(WCHAR* url, LONGLONG totalLength);
	PUTRequest(WCHAR* url);
	virtual ~PUTRequest();

	HRESULT perform();
	HRESULT send(LPVOID buffer, DWORD bufferLength, LPDWORD writted);
	HRESULT exportPath(WCHAR* localPath);
};

#endif /* PUTREQUEST_H_ */
