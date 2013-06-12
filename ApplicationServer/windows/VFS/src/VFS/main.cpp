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
#include "VFS.h"
#include <common/StringUtil.h>
#include <common/Logger.h>
#include <common/fs/File.h>


void usage() {
	std::cout<<"usage: VFS.exe [-h] [-f] [-p 'profile path']"<<std::endl;
}


int WinMain(HINSTANCE instance, HINSTANCE prev_instance, LPSTR cmdline, int cmdshow) {
	bool stop = false;
	VFS vfs;
	std::wstring arg = L"";
	std::wstring path = L"";
	LPWSTR *argv;
	int argc = 0;
	int index;
	int status;
	File currentDir(L"${USERPROFILE}");
	currentDir.expand();

	if (! currentDir.chdir())
		Logger::getSingleton().debug(L"Failed to switch the current directory to %s", currentDir.path().c_str());

	argv = CommandLineToArgvW(GetCommandLine(), &argc);
	if(argv == NULL) {
		usage();
		return VFS::INVALID_ARGUMENT;
	}

	if (argc < 2) {
		usage();
		return VFS::INVALID_ARGUMENT;
	}

	index = 1;
	while(index < argc) {
		arg = argv[index++];

		if (arg.compare(L"-h") == 0 || arg.compare(L"/h") == 0) {
			usage();
			return VFS::INVALID_ARGUMENT;
		}

		if (arg.compare(L"-p") == 0 || arg.compare(L"/p") == 0) {
			if (index > argc) {
				usage();
				return VFS::INVALID_ARGUMENT;
			}

			path = argv[index++];
			StringUtil::unquote(path);
		}

		if (arg.compare(L"-s") == 0 || arg.compare(L"/s") == 0) {
			if (index > argc) {
				usage();
				return VFS::INVALID_ARGUMENT;
			}

			path = argv[index++];
			StringUtil::unquote(path);
			stop = true;
		}

		if (arg.compare(L"-f") == 0 || arg.compare(L"/f") == 0) {
			AttachConsole(ATTACH_PARENT_PROCESS);
		}
	}

	LocalFree(argv);

	status = vfs.init(path);
	if (status != VFS::SUCCESS)
		return status;

	if (stop)
		return vfs.stop();
	else
		return vfs.start();
}
