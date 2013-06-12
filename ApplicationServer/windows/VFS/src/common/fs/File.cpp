/*
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2013
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

#include "File.h"
#include "string.h"
#include <common/Logger.h>
#include <common/sys/System.h>
#include <Windows.h>
#include <Shlwapi.h>
#include <Shlobj.h>
#include <iostream>
#include "CSIDL.h"



File::File(std::wstring path): pathValue(path), separator(L"\\") { }

File::~File() { }

std::wstring& File::path() { return this->pathValue; }

std::wstring File::fullname() {
	std::wstring::size_type pos = this->pathValue.find_last_of(this->separator);

	if (pos != std::wstring::npos)
		return this->pathValue.substr(pos + 1, std::wstring::npos);
	else
		return this->pathValue;
}

std::wstring File::shortname() {
	return fullname().substr(0, fullname().find_last_of(L"."));
}


std::wstring File::parent() {
	return this->pathValue.substr(0, this->pathValue.find_last_of(L"/"));
}


std::wstring File::extention() {
    std::wstring::size_type pos = this->pathValue.find_last_of(L".");
    if (pos != std::wstring::npos)
        return this->pathValue.substr(pos + 1, std::wstring::npos);
    else
        return L"";
}


void File::join(std::wstring path) {
	if ((this->pathValue[this->pathValue.length() - 1] == this->separator[0]) || (path[0] == this->separator[0]))
		this->pathValue += path;
	else
		this->pathValue += this->separator + path;
}


bool File::isAbsolute() {
	wchar_t letter = tolower((int)this->pathValue[0]);

	return (this->pathValue[1] == L':') && (this->pathValue[2] = L'\\') && letter >= L'a' && letter <= L'z';
}



bool File::expand() {
	wchar_t temp[1024];
	std::wstring res = this->pathValue;
	std::wstring csidl;
	std::wstring csidlPath;
	size_t pos = 0;
	CSIDL c;

	// We are searching for CSIDL constant
	if (res.find(L"%{") == 0) {
		pos = res.find(L"}");
		if (pos == std::wstring::npos) {
			log_warn(L"%s do not contain valid information", this->pathValue.c_str());
			return false;
		}

		csidl = res.substr(2, pos - 2);
		if (c.getPath(csidl, csidlPath))
			res.replace(0, csidl.length() + 3, csidlPath);

	}

	pos = 0;
	while(res.find(L"${", pos) != std::wstring::npos) {
		pos = res.find(L"${");
		size_t posEnd;
		std::wstring sub = res.substr(pos);
		if (sub.find(L"}") == std::wstring::npos)
			break;

		posEnd = sub.find(L"}");
		std::wstring env = sub.substr(2, posEnd - 2);
		if (env.compare(L"UOS_VERSION") == 0) {
			std::wstring v;
			System::getVersionName(v);
			res.replace(pos, env.length()+3, v);
		}
		else {
			int status = GetEnvironmentVariable(env.c_str(), temp, sizeof(temp));

			if (status > 0)
				res.replace(pos, env.length()+3, temp);
			else
				log_warn(L"%s is not a right environment variable", env.c_str());
		}

		pos +=2;
	}
	this->pathValue = res;

	return true;
}


bool File::expand(const std::wstring& base) {
	bool res = this->expand();

	if (!this->isAbsolute()) {
		File f(base);
		f.join(this->pathValue);
		this->pathValue = f.path();
	}

	return res;
}

bool File::exist() {
	return (PathFileExists(this->pathValue.c_str()) == TRUE);
}


bool File::remove() {
	return (DeleteFile(this->pathValue.c_str()) == TRUE);
}


bool File::mkdirs() {
	return (SHCreateDirectoryEx(NULL, this->pathValue.c_str(), NULL) == ERROR_SUCCESS);
}


bool File::chdir() {
	return (_wchdir(this->pathValue.c_str()) == 0);
}
