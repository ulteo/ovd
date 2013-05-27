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
#include "CSIDL.h"



File::File(std::string path): pathValue(path), separator("\\") { }

File::~File() { }

std::string File::path() { return this->pathValue; }

std::string File::fullname() {
	std::string::size_type pos = this->pathValue.find_last_of(this->separator);

	if (pos != std::string::npos)
		return this->pathValue.substr(pos + 1, std::string::npos);
	else
		return this->pathValue;
}

std::string File::shortname() {
	return fullname().substr(0, fullname().find_last_of("."));
}


std::string File::parent() {
	return this->pathValue.substr(0, this->pathValue.find_last_of("/"));
}


std::string File::extention() {
    std::string::size_type pos = this->pathValue.find_last_of(".");
    if (pos != std::string::npos)
        return this->pathValue.substr(pos + 1, std::string::npos);
    else
        return "";
}


void File::join(std::string path) {
	if ((this->pathValue[this->pathValue.length() - 1] == this->separator[0]) || (path[0] == this->separator[0]))
		this->pathValue += path;
	else
		this->pathValue += this->separator + path;
}


bool File::expand() {
	std::string res = this->pathValue;
	std::string csidl;
	std::string csidlPath;
	CSIDL c;

	// We are searching for CSIDL constant
	if (res.find("%{") == 0) {
		int pos = res.find("}");
		if (pos == std::string::npos) {
			log_warn("%s do not contain valid information", this->pathValue.c_str());
			return false;
		}

		csidl = res.substr(2, pos - 2);
		if (c.getPath(csidl, csidlPath))
			res.replace(0, csidl.length() + 3, csidlPath);

	}

//	while(res.find("${") != std::string::npos) {
//		int pos1 = res.find("${");
//		int pos2;
//		std::string sub = res.substr(pos);
//		if (sub.find("}") == std::string::npos)
//			break;
//
//		pos2 = sub.find("}");
//		std::string env = sub.substr(0, pos2);
//		if (GetEnvironmentVariableA(env.c_str(), ))
//	}
//
//	if () {
//		res.replace
//
//	}
	this->pathValue = res;

	return true;
}
