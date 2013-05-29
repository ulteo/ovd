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

#include "System.h"
#include <common/Logger.h>


System::System() { }

System::~System() { }

bool System::is64Bits() {
	BOOL res = FALSE;

	//IsWow64Process is not available on all supported versions of Windows.
	//Use GetModuleHandle to get a handle to the DLL that contains the function
	//and GetProcAddress to get a pointer to the function if available.

	fnIsWow64Process = (LPFN_ISWOW64PROCESS) GetProcAddress(GetModuleHandle(L"kernel32"), "IsWow64Process");
	if(fnIsWow64Process) {
		if (!fnIsWow64Process(GetCurrentProcess(), &res)) {
			log_error("Failed to determine machine architecture: %u", GetLastError());
			return false;
		}
	}

	return res;
}

bool System::setEnv(const std::string& key, const std::string& value) {
	return (SetEnvironmentVariableA(key.c_str(), value.c_str()) == TRUE);
}
