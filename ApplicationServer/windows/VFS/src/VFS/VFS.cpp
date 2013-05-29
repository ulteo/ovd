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

#include "VFS.h"
#include <Shlwapi.h>
#include <common/Logger.h>
#include <common/conf/Configuration.h>
#include <common/conf/Union.h>
#include <common/sys/System.h>
#include <common/fs/RSync.h>
#include <common/fs/File.h>


VFS::VFS() { }

VFS::~VFS() { }


VFS::status VFS::init(std::string path) {
	File f(path);

	if (path.empty()) {
		log_error("%s is invalid for a source path", path);
		return WRONG_SRC;
	}

	if (!f.exist()) {
		log_error("source %s must exist", path.c_str());
		return SRC_DO_NOT_EXIST;
	}

	Configuration& conf = Configuration::getInstance();
	conf.setSrcPath(path);

	if (!conf.load()) {
		log_error("Failed to load configuration file");
		return INVALID_CONF;
	}

	return SUCCESS;
}


VFS::status VFS::start() {
	Configuration& conf = Configuration::getInstance();
	Process hook32(VFS_HOOK_LOADER_32);
	Process hook64(VFS_HOOK_LOADER_64);
	std::list<Process*> processList;

	// Manage rsync if needed
	std::list<Union> unionList = conf.getUnions();
	std::list<Union>::iterator it;

	for (it = unionList.begin() ; it != unionList.end() ; it++) {
		Union& u = (*it);
		File f(u.getPath());

		f.mkdirs();
		if (!f.exist()) {
			log_error("Union src %s do not exist and can not be created", u.getPath());
			return INVALID_UNION;
		}

		if (!u.getRsyncSrc().empty()) {
			RSync rsync(u.getRsyncSrc(), u.getPath(), u.getRsyncFilter());
			rsync.init();
			rsync.start();
		}
	}

	// start hook launcher
	System::setEnv("VFS_SRC", Configuration::getInstance().getSrcPath());
	hook32.start(false);
	processList.push_back(&hook32);

	if (System::is64()) {
		hook64.start(false);
		processList.push_back(&hook64);
	}

	Process::wait(processList, INFINITE);
	return SUCCESS;
}


VFS::status VFS::stop() {
	// TODO manage signal
	Configuration& conf = Configuration::getInstance();

	// Manage rsync if needed
	std::list<Union> unionList = conf.getUnions();
	std::list<Union>::iterator it;

	for (it = unionList.begin() ; it != unionList.end() ; it++) {
		Union& u = (*it);
		File f(u.getPath());

		f.mkdirs();
		if (!f.exist()) {
			log_error("Union src %s do not exist and can not be created", u.getPath());
			return INVALID_UNION;
		}

		if (!u.getRsyncSrc().empty()) {
			RSync rsync(u.getPath(), u.getRsyncSrc(), u.getRsyncFilter());
			rsync.init();
			rsync.start();
		}
	}

	return SUCCESS;
}
