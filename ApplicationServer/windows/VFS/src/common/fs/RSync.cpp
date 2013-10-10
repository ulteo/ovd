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

#include "RSync.h"
#include <fstream>
#include <sstream>
#include <common/StringUtil.h>
#include <common/Logger.h>
#include <common/fs/File.h>



RSync::RSync(std::wstring& src, std::wstring& dst, std::wstring& filter) {
	this->convertPath(src, this->src);
	this->convertPath(dst, this->dst);
	this->convertPath(filter, this->filter);
	this->process = NULL;
	this->src.append(L"/");
}


RSync::~RSync() {
	if (this->process)
		delete process;
}


void RSync::convertPath(std::wstring& in, std::wstring& out) {
	File f(in);
	wchar_t letter;

	if (f.isAbsolute()) {
		std::wstringstream ss;
		letter = f.path()[0];
		in.erase(0, 2);
		ss<<L"/cygdrive/"<<(char)tolower(letter)<<"/"<<in;
		out = ss.str();
	}

	StringUtil::replaceAll(out, L"\\", L"/");
	StringUtil::replaceAll(out, L"//", L"/");
}


bool RSync::init() {
	char buffer[1024] = {0};
	std::wstring line;

	if (this->filter.empty())
		return true;

	// Creating process
	this->process = new Process(L"rsync.exe");
	this->process->addArgs(L"-rvltD");

	if (! this->filter.empty())
		this->process->addArgs(L"--include-from=\""+this->filter+L"\"");

	this->process->addArgs(L"--delete");
	this->process->addArgs(L"--delete-excluded");

	this->process->addArgs(L"\""+this->src+L"\"");
	this->process->addArgs(L"\""+this->dst+L"\"");

	return true;
}


bool RSync::start() {
	int status;

	if (this->process == NULL) {
		log_warn(L"rsync is not inited");
		return false;
	}

	log_info(L"rsync from %s to %s", this->src.c_str(), this->dst.c_str());

	this->process->start(true);

	status = this->process->getStatus();
	if (status != 0) {
		log_warn(L"Processus stop with status %i\n", status);
		return false;
	}

	return true;
}
