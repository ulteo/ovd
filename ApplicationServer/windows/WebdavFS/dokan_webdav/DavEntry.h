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

#ifndef DAVENTRY_H_
#define DAVENTRY_H_

#include <objbase.h>
#include <atlbase.h>

#define FILE_TYPE_COUNT  3

class DavEntry {
public:
	enum FileType {file, directory, executableFile};
	static const WCHAR* FileTypeString[FILE_TYPE_COUNT];


private:
	WCHAR* path;
	FileType type;
	long length;
	FILETIME creationTime;
	FILETIME lastModifiedTime;

public:
	DavEntry();
	DavEntry(const DavEntry &entry);
	DavEntry(const WCHAR* path_);
	~DavEntry();

	HRESULT setPath(const WCHAR* path_);
	WCHAR* getPath();

	HRESULT setType(const WCHAR* type_);
	HRESULT setType(FileType type_);
	FileType getType();

	HRESULT setCreationTime(const WCHAR* creationTime_);
	HRESULT setCreationTime(FILETIME creationTime_);
	FILETIME getCreationTime();

	HRESULT setLastModifiedTime(const WCHAR* lastModifiedTime_);
	HRESULT setLastModifiedTime(FILETIME lastModifiedTime_);
	FILETIME getLastModifiedTime();

	HRESULT DavEntry::setLength(const WCHAR* length_);
	HRESULT DavEntry::setLength(long length_);
	long DavEntry::getLength();

	WCHAR* unicodeConvert(const WCHAR* str);

};


#endif /* DAVENTRY_H_ */
