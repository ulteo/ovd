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
#include <common/sys/Registry.h>
#include <common/sys/System.h>
#include <common/fs/RSync.h>
#include <common/fs/File.h>


VFS::VFS() { }

VFS::~VFS() { }


VFS::status VFS::init(std::wstring path) {
	File f(path);

	if (path.empty()) {
		log_error(L"%s is invalid for a source path", path);
		return WRONG_SRC;
	}

	if (!f.exist()) {
		log_error(L"source %s must exist", path.c_str());
		return SRC_DO_NOT_EXIST;
	}

	Configuration& conf = Configuration::getInstance();
	conf.setSrcPath(path);

	if (!conf.load()) {
		log_error(L"Failed to load configuration file");
		return INVALID_CONF;
	}




	return SUCCESS;
}


VFS::status VFS::start() {
	Configuration& conf = Configuration::getInstance();
	Process hook32(VFS_HOOK_LOADER_32);
	Process hook64(VFS_HOOK_LOADER_64);
	Registry reg(REGISTRY_PATH_KEY);
	std::list<Process*> processList;

	// Manage rsync if needed
	std::list<Union> unionList = conf.getUnions();
	std::list<Union>::iterator it;

	for (it = unionList.begin() ; it != unionList.end() ; it++) {
		Union& u = (*it);
		std::list<std::wstring> l;
		std::list<std::wstring>::iterator it;

		File f(u.getPath());

		f.mkdirs();
		if (!f.exist()) {
			log_error(L"Union src %s do not exist and can not be created", u.getPath());
			return INVALID_UNION;
		}

		if (!u.getRsyncSrc().empty()) {
			RSync rsync(u.getRsyncSrc(), u.getPath(), u.getRsyncFilter());
			rsync.init();
			rsync.start();
		}

		l = u.getpredefinedDirectoryList();

		if (l.empty())
			continue;

		for(it = l.begin(); it != l.end() ; it++) {
			File f(*it);
			f.mkdirs();
			if (!f.exist())
				log_error(L"Failed to create directory %s", f.path().c_str());
		}
	}

	// start hook launcher
	if (!reg.create()) {
		log_error(L"Failed to create registry key %s", REGISTRY_PATH_KEY);
		return INTERNAL_ERROR;
	}

	reg.set(L"ProfileSrc", Configuration::getInstance().getSrcPath());

	if (!reg.exist()) {
		log_error(L"Registry key %s do not exist", REGISTRY_PATH_KEY);
		return INTERNAL_ERROR;
	}

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
			log_error(L"Union src %s do not exist and can not be created", u.getPath());
			return INVALID_UNION;
		}

		if (!u.getRsyncSrc().empty()) {
			RSync rsync(u.getPath(), u.getRsyncSrc(), u.getRsyncFilter());
			rsync.init();
			rsync.start();
		}

		if (u.isDeleteOnClose())
			f.rmdirs();
	}

	return SUCCESS;
}
