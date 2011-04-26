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


XMLDavParser::XMLDavParser(HINTERNET hRequest_) : currentEntry(NULL) {
	hRequest = hRequest_;
	hr = S_OK;
}


bool XMLDavParser::ReadElement(LPCWSTR* namespaceUri, LPCWSTR* localName) {  
	XmlNodeType nodeType = XmlNodeType_None;
	const WCHAR* name;
	while (S_OK == pReader->Read(&nodeType)){
		pReader->GetLocalName(&name, NULL);
		if(nodeType == XmlNodeType_Element){  
			pReader->GetNamespaceUri(namespaceUri,NULL);  
			pReader->GetLocalName(localName,NULL);  
			return true;  
		}  
		else if(nodeType == XmlNodeType_EndElement) {
			if (wcscmp((WCHAR*)name, L"response") == 0)
				return false;
			if (wcscmp((WCHAR*)name, L"multistatus") == 0)
				return false;
		}
	}
	return false;  
}  


void XMLDavParser::ReadElementToEnd(){
	const WCHAR* local;
	if (pReader->IsEmptyElement())  
		return;  
	XmlNodeType nodeType = XmlNodeType_None;  
	while (S_OK == pReader->Read(&nodeType)) {
		pReader->GetLocalName(&local, NULL);
		if(nodeType==XmlNodeType_Element) {  
			ReadElementToEnd();
		}  
		else if(nodeType==XmlNodeType_EndElement) {  
			return;  
		}  
	}  
}  


const WCHAR* XMLDavParser::GetNodeValue() {  
	XmlNodeType nodeType = XmlNodeType_None;
	PCWSTR value = 0;
	
	do{  
		pReader->Read(&nodeType);  
	} while(nodeType == XmlNodeType_Whitespace || nodeType == XmlNodeType_Comment);
	
	if(nodeType==XmlNodeType_Text || nodeType==XmlNodeType_CDATA)  
		pReader->GetValue(&value,NULL);  
	return value;  
}  


DavEntry* XMLDavParser::readProp(void) {
	const WCHAR* localName;
	const WCHAR* namespaceUri;
	const WCHAR* value;
	DWORD size;
	DavEntry* entry = NULL;

	while(ReadElement(&namespaceUri,&localName)) {
		
		TagElement current = unknow;
		for (int val = 0 ; val < TAG_ELEMENT_COUNT ; val++) {
			if ( wcscmp(localName, XMLDavParser::TagElementString[val]) == 0) {
				current = (TagElement)val;
			}
		}

		switch (current) {
			case path:
				value = GetNodeValue();
				size = lstrlen(value) + 1;
				entry = new DavEntry(value);
				break;

			case type:
				value = GetNodeValue();
				entry->setType(value);
				break;

			case creationdate:
				value = GetNodeValue();
				entry->setCreationTime(value);
				break;

			case lastmodified:
				value = GetNodeValue();
				entry->setLastModifiedTime(value);
				break;

			case length:
				value = GetNodeValue();
				entry->setLength(value);
				break;

			case iscollection:
				value = GetNodeValue();
				if (_wcsnicmp(value, L"true", lstrlen(L"true")) == 0) {
					entry->setType(DavEntry::directory);
				}
				break;

			case unknow:
				break;

			default:
				break;
		}
	}
	return entry;
}


HRESULT XMLDavParser::parse(void) {
	XmlNodeType nodeType;
	const WCHAR* localName;
	const WCHAR* namespaceUri;
	DavEntry* entry = NULL;

	if (FAILED(hr = pReader->Read(&nodeType))) {
		DbgPrint(L"Error getting prefix, error is %08.8lx %08.8lx", hr, nodeType);
	}
	result.clear();
	while(ReadElement(&namespaceUri,&localName)) {
		if (wcscmp(localName, L"response") == 0) {
			entry = readProp();
			if (entry) {
				result.push_back(*entry);
				delete entry;
				entry = NULL;
			}
		}
	}
	return S_OK;
}

std::list<DavEntry> XMLDavParser::getResult(void) {
	return result;
}


HRESULT XMLDavParser::init(void) {
	WinHTTPStream *stream = new WinHTTPStream(hRequest);
	spStream.Attach(stream);  
	
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
	return S_OK;
}

HRESULT XMLDavParser::getLastError(void) {
	return hr;
}
