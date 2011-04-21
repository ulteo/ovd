/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2010
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


#include "XMLDavParser.h"
#include "debug.h"

extern BOOL g_UseStdErr;
extern BOOL g_DebugMode;

const WCHAR* XMLDavParser::TagElementString[TAG_ELEMENT_COUNT] = {L"multistatus", L"response", L"href", L"getcontenttype", L"creationdate", L"getlastmodified", L"getcontentlength", L"iscollection"};


XMLDavParser::XMLDavParser(CHAR* xmlData) : currentEntry(NULL) {
	int size = 0;

	if (xmlData == NULL) {
		hr = HRESULT(-1);
		return;
	}
	size = strlen(xmlData);

	hMem = ::GlobalAlloc(GMEM_MOVEABLE, size+1);
	if (!hMem) {
		DbgPrint(L"Unable to allocate data");
	    hr = HRESULT(-1);
	    return;
	}

	pOutBuffer = ::GlobalLock(hMem);
	memcpy((void*)pOutBuffer, xmlData, size+1);
	hr = S_OK;
}

HRESULT XMLDavParser::parse(void) {
	XmlNodeType nodeType;
	const WCHAR* pwszLocalName;
	const WCHAR* pwszValue;
	WCHAR escaped[2048];
	DWORD size;
	TagElement current = unknow;


	if (FAILED(hr = pReader->Read(&nodeType))) {
		DbgPrint(L"Error getting prefix, error is %08.8lx %08.8lx", hr, nodeType);
	}
	last = unknow;
	// read until there are no more nodes
	while (S_OK == (hr = pReader->Read(&nodeType))) {
		switch (nodeType) {

		case XmlNodeType_Element:
			if (FAILED(hr = pReader->GetLocalName(&pwszLocalName, NULL))) {
				DbgPrint(L"Error getting local name, error is %08.8lx", hr);
				return -1;
			}

			if (pReader->IsEmptyElement()) {
				break;
			}

			for (int val = 0 ; val < TAG_ELEMENT_COUNT ; val++) {
				if ( wcscmp(pwszLocalName, XMLDavParser::TagElementString[val]) == 0) {
					last = (TagElement)val;
				}
			}

			if (FAILED(hr = pReader->MoveToElement())) {
				DbgPrint(L"Error moving to the element that owns the current attribute node, error is %08.8lx", hr);
				return -1;
			}
			break;

		case XmlNodeType_EndElement:
			if (FAILED(hr = pReader->GetLocalName(&pwszLocalName, NULL))) {
				DbgPrint(L"Error getting local name, error is %08.8lx", hr);
				return -1;
			}
			for (int val = 0 ; val < TAG_ELEMENT_COUNT ; val++) {
				if ( wcscmp(pwszLocalName, XMLDavParser::TagElementString[val]) == 0) {
					current = (TagElement)val;
				}
			}

			if (current == response)
			{
				result.push_back(*currentEntry);
				delete currentEntry;
				currentEntry = NULL;
			}

			if (current == unknow) {
				break;
			}
			if (current == last ) {
				last = unknow;
			}
			break;

		case XmlNodeType_Text:
			if (FAILED(hr = pReader->GetValue(&pwszValue, NULL))) {
				DbgPrint(L"Error getting value, error is %08.8lx", hr);
				return -1;
			}
			switch (last) {
			case path:
				size = lstrlen(pwszValue) + 1;

				if (FAILED(hr = UrlUnescape((LPWSTR)pwszValue, escaped, &size, URL_DONT_UNESCAPE_EXTRA_INFO))) {
					DbgPrint(L"Unable to unescape string, error is %08.8lx  %i ", hr, size);
					break;
				}
				currentEntry = new DavEntry(escaped);
				break;

			case type:
				currentEntry->setType(pwszValue);
				break;

			case creationdate:
				currentEntry->setCreationTime(pwszValue);
				break;

			case lastmodified:
				currentEntry->setLastModifiedTime(pwszValue);
				break;

			case length:
				currentEntry->setLength(pwszValue);
				break;

			case iscollection:
				if (_wcsnicmp(pwszValue, L"true", lstrlen(L"true")) == 0) {
					currentEntry->setType(DavEntry::directory);
				}
				break;

			case unknow:
				break;

			default:
				break;
			}
			break;
		}
	}
	return S_OK;
}

std::list<DavEntry> XMLDavParser::getResult(void) {
	return result;
}



HRESULT XMLDavParser::init(void) {
	hr = ::CreateStreamOnHGlobal(hMem,FALSE,&spStream);

	if (FAILED(hr = CreateXmlReader(__uuidof(IXmlReader), (void**) &pReader, NULL))) {
		DbgPrint(L"Error creating xml reader, error is %08.8lx", hr);
		return hr;
	}

	if (FAILED(hr = pReader->SetProperty(XmlReaderProperty_DtdProcessing, DtdProcessing_Prohibit))) {
		DbgPrint(L"Error setting XmlReaderProperty_DtdProcessing, error is %08.8lx", hr);
		return hr;
	}

	if (FAILED(hr = pReader->SetInput(spStream))) {
		DbgPrint(L"Error setting input for reader, error is %08.8lx", hr);
		return hr;
	}
	return S_OK;
}


HRESULT XMLDavParser::release(void) {
	delete pReader;

	::GlobalUnlock(hMem);

	return S_OK;
}

HRESULT XMLDavParser::getLastError(void) {
	return hr;
}


