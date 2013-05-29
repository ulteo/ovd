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



RSync::RSync(std::string& src, std::string& dst, std::string& filter) {
	this->convertPath(src, this->src);
	this->convertPath(dst, this->dst);
	this->convertPath(filter, this->filter);
	this->process = NULL;
}


RSync::~RSync() {
	if (this->process)
		delete process;
}


void RSync::convertPath(std::string& in, std::string& out) {
	File f(in);
	char letter;

	if (f.isAbsolute()) {
		std::stringstream ss;
		letter = f.path()[0];
		in.erase(0, 2);
		ss<<"/cygdrive/"<<(char)tolower(letter)<<"/"<<in;
		out = ss.str();
	}

	StringUtil::replaceAll(out, "\\", "/");
	StringUtil::replaceAll(out, "//", "/");
}


bool RSync::init() {
	char buffer[1024] = {0};
	std::string line;

	if (this->filter.empty())
		return true;

	// Creating process
	this->process = new Process("rsync.exe");
	this->process->addArgs("-rvltD");

	if (! this->filter.empty())
		this->process->addArgs("--include-from=\""+this->filter+"\"");

	this->process->addArgs("\""+this->src+"\"");
	this->process->addArgs("\""+this->dst+"\"");

	return false;
}


bool RSync::start() {
	int status;

	if (this->process == NULL) {
		log_warn("rsync is not inited");
		return false;
	}

	this->process->start(true);

	status = this->process->getStatus();
	if (status != 0) {
		log_warn("Processus stop with status %i\n", status);
		return false;
	}

	return true;
}
