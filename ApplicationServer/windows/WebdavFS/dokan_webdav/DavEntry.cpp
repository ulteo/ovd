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


#include "DavEntry.h"

const WCHAR* DavEntry::FileTypeString[FILE_TYPE_COUNT] = {L"unknow", L"httpd/unix-directory", L"application/x-msdos-program"};

const CHAR DavEntry::ACCEPTABLE_URI_CHARS[96] = {
  /*      !    "    #    $    %    &    '    (    )    *    +    ,    -    .    / */
  0x00,0x3F,0x20,0x20,0x28,0x00,0x2C,0x3F,0x3F,0x3F,0x3F,0x2A,0x28,0x3F,0x3F,0x1C,
  /* 0    1    2    3    4    5    6    7    8    9    :    ;    <    =    >    ? */
  0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x38,0x20,0x20,0x2C,0x20,0x20,
  /* @    A    B    C    D    E    F    G    H    I    J    K    L    M    N    O */
  0x38,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,
  /* P    Q    R    S    T    U    V    W    X    Y    Z    [    \    ]    ^    _ */
  0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x20,0x20,0x20,0x20,0x3F,
  /* `    a    b    c    d    e    f    g    h    i    j    k    l    m    n    o */
  0x20,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,
  /* p    q    r    s    t    u    v    w    x    y    z    {    |    }    ~  DEL */
  0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x3F,0x20,0x20,0x20,0x3F,0x20
};

const CHAR DavEntry::HEX_CHARS[17] = "0123456789ABCDEF";


DavEntry::DavEntry() {
	path = NULL;
	creationTime.dwLowDateTime = 0;
	creationTime.dwHighDateTime = 0;
	lastModifiedTime.dwLowDateTime = 0;
	lastModifiedTime.dwHighDateTime = 0;
	length = 0;
	type = file;
}

DavEntry::DavEntry(const DavEntry &entry) {
	if (entry.path)
		 path = _wcsdup(entry.path);
	creationTime = entry.creationTime;
	lastModifiedTime = entry.lastModifiedTime;
	length = entry.length;
	type = entry.type;
}


DavEntry::DavEntry(const WCHAR* path_) {
	path = urldecode(path_);
	lastModifiedTime.dwLowDateTime = 0;
	lastModifiedTime.dwHighDateTime = 0;
	creationTime.dwLowDateTime = 0;
	creationTime.dwHighDateTime = 0;
	length = 0;
	type = file;
}


DavEntry::~DavEntry() {
	if (path)
		free(path);
}

HRESULT DavEntry::setPath(const WCHAR* path_) {
	path = urldecode(path_);
	return S_OK;
}

WCHAR* DavEntry::getPath() {
	return path;
}

HRESULT DavEntry::setType(const WCHAR* type_) {
	if (type_ == NULL) {
		return -1;
	}
	type = file;
	for (int val = 0 ; val < FILE_TYPE_COUNT ; val++) {
		if ( wcscmp(type_, DavEntry::FileTypeString[val]) == 0) {
			type = (FileType)val;
		}
	}
	return S_OK;
}

HRESULT DavEntry::setType(FileType type_) {
	type = type_;
	return S_OK;
}

DavEntry::FileType DavEntry::getType() {
	return type;
}

HRESULT DavEntry::setLength(const WCHAR* length_) {
	if (length_ == NULL) {
		return -1;
	}
	length = _wtol(length_);
	return S_OK;
}

HRESULT DavEntry::setLength(long length_) {
	length = length_;
	return S_OK;
}

long DavEntry::getLength() {
	return length;
}


HRESULT DavEntry::setCreationTime(const WCHAR* creationTime_) {
	SYSTEMTIME time;

	if (WinHttpTimeToSystemTime(creationTime_, &time)) {
		if (SystemTimeToFileTime(&time, &creationTime)) {
			return S_OK;
		}		
	}

	creationTime.dwLowDateTime = 0;
	creationTime.dwHighDateTime = 0;
	return S_OK;
}

HRESULT DavEntry::setCreationTime(FILETIME creationTime_) {
	creationTime = creationTime_;
	return S_OK;
}

FILETIME* DavEntry::getCreationTime() {
	return &creationTime;
}

HRESULT DavEntry::setLastModifiedTime(const WCHAR* lastModifiedTime_) {
	SYSTEMTIME time;

	if (WinHttpTimeToSystemTime(lastModifiedTime_, &time)) {
		if (SystemTimeToFileTime(&time, &lastModifiedTime)) {
			return S_OK;
		}
	}

	lastModifiedTime.dwLowDateTime = 0;
	lastModifiedTime.dwHighDateTime = 0;
	return S_OK;
}

HRESULT DavEntry::setLastModifiedTime(FILETIME lastModifiedTime_) {
	lastModifiedTime = lastModifiedTime_;
	return S_OK;
}

FILETIME* DavEntry::getLastModifiedTime() {
	return &lastModifiedTime;
}

HRESULT DavEntry::convert_path(CHAR* path, CHAR* buffer) {
	int i = 0;
	int buffer_len = 0;
	int path_len = 0;
	unsigned char c = 0;
	char* t = 0;

	path_len = strlen(path);
	buffer_len = path_len * 3 + 1;

	t = buffer;

	/* copy the path component name */
	for (i = 0 ; i< path_len ; i++) {
		c = path[i];
		if (!ACCEPTABLE_URI_CHAR (c) && (c != '\n')) {
			*t++ = '%';
			*t++ = HEX_CHARS[c >> 4];
			*t++ = HEX_CHARS[c & 15];
		}
		else
			*t++ = path[i];
	}
	*t = '\0';

	return 0;
}

WCHAR* DavEntry::urldecode(const WCHAR* str) {
	WCHAR wstr[MAX_PATH] = {0};
	CHAR  astr[MAX_PATH] = {0};
	DWORD alen = 0;
	DWORD wlen = 0;

	wlen = lstrlen(str);

	// Convertion from windows-UTF8 to AINSI
	alen = WideCharToMultiByte(CP_ACP, 0, str, wlen, 0, 0, 0, 0);
	WideCharToMultiByte(CP_ACP, 0, str, wlen, astr, alen, 0, 0);

	//unescape escaped utf8 charactere
	UrlUnescapeA(astr, NULL, &alen, URL_UNESCAPE_INPLACE|URL_DONT_UNESCAPE_EXTRA_INFO);

	//convertion from utf-8 to Unicode
	wlen = MultiByteToWideChar(CP_UTF8, 0, (LPCSTR)astr, -1, NULL, 0);
	MultiByteToWideChar(CP_UTF8, 0, (LPCSTR)astr, -1, wstr, wlen);

	return _wcsdup(wstr);
}

WCHAR* DavEntry::urlencode(const WCHAR* str)
{
	WCHAR wstr[MAX_PATH] = {0};
	CHAR  astr[MAX_PATH] = {0};
	CHAR  escaped[MAX_PATH] = {0};
	DWORD alen = 0;
	DWORD wlen = 0;

	wlen = lstrlen(str);

	// Convertion from windows-UTF8 to AINSI
	alen = WideCharToMultiByte(CP_UTF8, 0, str, wlen, 0, 0, 0, 0);
	WideCharToMultiByte(CP_UTF8, 0, str, wlen, astr, alen, 0, 0);

	alen = MAX_PATH;
	convert_path(astr, escaped);
	mbstowcs(wstr, escaped, strlen(escaped));

	return _wcsdup((WCHAR*)wstr);
}
