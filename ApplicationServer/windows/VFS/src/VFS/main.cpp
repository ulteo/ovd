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

#include <iostream>
#include <Shlwapi.h>
#include <common/StringUtil.h>
#include <common/Logger.h>
#include <common/conf/Configuration.h>


#define STATUS_SUCCESS          0
#define STATUS_INVALID_ARGUMENT 1
#define STATUS_WRONG_SRC        2
#define STATUS_SRC_DO_NOT_EXIST 3
#define STATUS_INVALID_CONF     4



void usage() {
	std::cout<<"usage: VFS.exe [-h] [-p 'profile path']"<<std::endl;
}


int main(int argc, char** argv) {
	std::string arg;
	std::string path;

	if (argc < 2) {
		usage();
		return STATUS_INVALID_ARGUMENT;
	}

	arg = std::string(argv[1]);

	if (arg.compare("-h") == 0 || arg.compare("/h") == 0) {
		usage();
		return STATUS_SUCCESS;
	}

	if (arg.compare("-p") == 0 || arg.compare("/p") == 0) {
		if (argc != 3) {
			usage();
			return STATUS_INVALID_ARGUMENT;
		}

		path = std::string(argv[2]);
		StringUtil::unquote(path);
	}

	if (path.empty()) {
		log_error("%s is invalid for a source path", path);
		return STATUS_WRONG_SRC;
	}

	if (! PathFileExistsA(path.c_str())) {
		log_error("source %s must exist", path.c_str());
		return STATUS_SRC_DO_NOT_EXIST;
	}

	Configuration& conf = Configuration::getInstance();
	conf.setSrcPath(path);

	if (!conf.load()) {
		log_error("Failed to load configuration file");
		return STATUS_INVALID_CONF;
	}

	return STATUS_SUCCESS;
}
