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
	this->src = src;
	this->dst = dst;
	this->filter = filter;
}

RSync::~RSync() {
	File f(this->realFilter);

	if (f.exist())
		f.remove();
}


bool RSync::init(bool sessionStart) {
	char buffer[1024] = {0};
	std::string line;

	if (this->filter.empty())
		return true;

	// convert filter file
	std::ifstream fileStream(this->filter);
	if (!fileStream.good())
		return false;

	while (!fileStream.eof()) {
		fileStream.getline(buffer, sizeof(buffer));
		line = buffer;
		StringUtil::atrim(line);

		if (line.find("#") == 0)
			continue;

		if (line.empty())
			continue;

		// TODO
	}

	return false;
}


bool RSync::start() {

	return false;
}
