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

#ifndef _XMLDAVPARSER_H_
#define _XMLDAVPARSER_H_

#include "WinHTTPStream.h"
#include "DavEntry.h"
#include <list>

#define TAG_ELEMENT_COUNT  8

class XMLDavParser {
private:
	enum TagElement {unknow = -1, multistatus = 0, response, path, type, creationdate, lastmodified, length, iscollection};
	static const WCHAR *TagElementString[TAG_ELEMENT_COUNT];


	TagElement last;
	HRESULT hr;
	HINTERNET hRequest;
	CComPtr<IXmlReader> pReader;
	
	HGLOBAL	hMem;
	void* pOutBuffer;
	CComPtr<IStream> spStream;

	DavEntry* currentEntry;
	std::list<DavEntry> result;


public:
	XMLDavParser(HINTERNET hRequest_);
	HRESULT init(void);
	HRESULT release(void);
	HRESULT parse(void);
	HRESULT parse(std::list<DavEntry> &list);

	bool ReadElement(LPCWSTR* namespaceUri, LPCWSTR* localName);
	void ReadElementToEnd();
	const WCHAR* GetNodeValue();
	DavEntry* XMLDavParser::readProp(void);
	HRESULT getLastError(void);
	std::list<DavEntry> getResult(void);

};
#endif
