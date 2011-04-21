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

DavEntry::DavEntry() {}

DavEntry::DavEntry(const DavEntry &entry) {
	if (entry.path != NULL)
		path = _wcsdup(entry.path);
	creationTime = entry.creationTime;
	lastModifiedTime = entry.lastModifiedTime;
	length = entry.length;
	type = entry.type;
}


DavEntry::DavEntry(const WCHAR* path_) {
	path = unicodeConvert(path_);
}


DavEntry::~DavEntry() {
	if (path)
		free(path);
}

HRESULT DavEntry::setPath(const WCHAR* path_) {
	path = unicodeConvert(path_);
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
	//TODO umplemented
	UNREFERENCED_PARAMETER(creationTime_);
	creationTime.dwLowDateTime = 10;
	creationTime.dwHighDateTime = 10;
	return S_OK;
}

HRESULT DavEntry::setCreationTime(FILETIME creationTime_) {
	creationTime = creationTime_;
	return S_OK;
}

FILETIME DavEntry::getCreationTime() {
	return creationTime;
}

HRESULT DavEntry::setLastModifiedTime(const WCHAR* lastModifiedTime_) {
	//TODO umplemented
	UNREFERENCED_PARAMETER(lastModifiedTime_);
	lastModifiedTime.dwLowDateTime = 10;
	lastModifiedTime.dwHighDateTime = 10;
	return S_OK;
}

HRESULT DavEntry::setLastModifiedTime(FILETIME lastModifiedTime_) {
	lastModifiedTime = lastModifiedTime_;
	return S_OK;
}

FILETIME DavEntry::getLastModifiedTime() {
	return lastModifiedTime;
}

WCHAR* DavEntry::unicodeConvert(const WCHAR* str)
{
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
