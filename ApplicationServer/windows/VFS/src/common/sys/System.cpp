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
#include <windows.h>
#include <shlwapi.h>
#include <shlobj.h>
#include <common/Logger.h>


System::System() { }

System::~System() { }

bool System::is64() {
	BOOL res = FALSE;
	LPFN_ISWOW64PROCESS fnIsWow64Process;

	//IsWow64Process is not available on all supported versions of Windows.
	//Use GetModuleHandle to get a handle to the DLL that contains the function
	//and GetProcAddress to get a pointer to the function if available.

	fnIsWow64Process = (LPFN_ISWOW64PROCESS) GetProcAddress(GetModuleHandle(L"kernel32"), "IsWow64Process");
	if(fnIsWow64Process) {
		if (!fnIsWow64Process(GetCurrentProcess(), &res)) {
			log_error(L"Failed to determine machine architecture: %u", GetLastError());
			return false;
		}
	}

	return (res == TRUE);
}

bool System::setEnv(const std::wstring& key, const std::wstring& value) {
	return (SetEnvironmentVariable(key.c_str(), value.c_str()) == TRUE);
}


bool System::getEnv(const std::wstring& key, std::wstring& value) {
	DWORD res = GetEnvironmentVariable(key.c_str(), NULL, 0);
	wchar_t* buffer;

	if(res = 0) {
		DWORD err = GetLastError();
		if( err == ERROR_ENVVAR_NOT_FOUND) {
			log_warn(L"Environment variable %s do not exist", key.c_str());
			return false;
		}

		log_warn(L"Failed to get environment variable %s: %u", key.c_str(), err);
		return false;
	}

	buffer = new wchar_t[res];
	if (GetEnvironmentVariable(key.c_str(), buffer, res) == 0) {
		log_error(L"Failed to get environment variable %s: %u", key.c_str(), GetLastError());
		return false;
	}

	value = buffer;
	return true;
}


void System::refreshDesktop() {
	SHChangeNotify(SHCNE_ASSOCCHANGED, SHCNF_IDLIST, 0, 0);
}
