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

#include "Registry.h"
#include <common/Logger.h>
#include <common/StringUtil.h>



Registry::Registry(std::wstring key) {
	if (key.find(L"HKEY_CLASSES_ROOT") == 0) {
		this->hkey = HKEY_CLASSES_ROOT;
		this->subkey = key.substr(wcslen(L"HKEY_CLASSES_ROOT") + 1);
		return;
	}

	if (key.find(L"HKEY_CURRENT_CONFIG") == 0) {
		this->hkey = HKEY_CURRENT_CONFIG;
		this->subkey = key.substr(wcslen(L"HKEY_CURRENT_CONFIG") + 1);
		return;
	}

	if (key.find(L"HKEY_CURRENT_USER") == 0) {
		this->hkey = HKEY_CURRENT_USER;
		this->subkey = key.substr(wcslen(L"HKEY_CURRENT_USER") + 1);
		return;
	}

	if (key.find(L"HKEY_LOCAL_MACHINE") == 0) {
		this->hkey = HKEY_LOCAL_MACHINE;
		this->subkey = key.substr(wcslen(L"HKEY_LOCAL_MACHINE") + 1);
		return;
	}

	if (key.find(L"HKEY_USERS") == 0) {
		this->hkey = HKEY_USERS;
		this->subkey = key.substr(wcslen(L"HKEY_USERS") + 1);
		return;
	}

	log_error(L"key %s is not a valid key", key.c_str());
	this->hkey = 0;
}


Registry::~Registry() { }


bool Registry::exist() {
	HKEY k;

	if (RegOpenKeyEx(this->hkey, this->subkey.c_str(), 0, KEY_READ|KEY_WOW64_32KEY, &k) != ERROR_SUCCESS)
		return false;

	RegCloseKey(hkey);

	return true;
}


bool Registry::create() {
	std::list<std::wstring> keyList;
	std::list<std::wstring>::iterator it;
	StringUtil::split(keyList, this->subkey, L'\\');
	std::wstring currentKey = L"";
	DWORD dispo = REG_OPENED_EXISTING_KEY;
	DWORD res;
	HKEY k;

	for (it = keyList.begin() ; it != keyList.end() ; it ++) {
		if ((*it).empty())
			continue;

		currentKey.append((*it));

		res = RegCreateKeyEx(this->hkey, currentKey.c_str(), 0, NULL, 0, KEY_CREATE_SUB_KEY | KEY_WOW64_32KEY, NULL, &k, &dispo);
		if (res != ERROR_SUCCESS) {
			log_error(L"Failed to create key %s %u", currentKey.c_str(), res);
			return false;
		}

		currentKey.append(L"\\");

		RegCloseKey(k);
	}

	return true;
}


bool Registry::set(const std::wstring& key, const std::wstring& value) {
	HKEY k;
	DWORD res;

	res = RegOpenKeyEx(hkey, this->subkey.c_str(), 0, KEY_SET_VALUE, &k);
	if (res != ERROR_SUCCESS) {
		log_error(L"Failed to open key %s", this->subkey.c_str());
		return false;
	}

	res = RegSetValueEx(k, key.c_str(), 0, REG_SZ, (BYTE *)value.c_str(), (value.length() + 1) * sizeof(wchar_t));
	if (res != ERROR_SUCCESS) {
		log_error(L"Failed to set key %s to %s", key.c_str(), value.c_str());
		RegCloseKey(k);
		return false;
	}

	RegCloseKey(k);
	return true;
}


bool Registry::get(const std::wstring& key, std::wstring& value) {
	DWORD size;
	wchar_t* buffer;
	HKEY k;
	DWORD res;

	res = RegOpenKeyEx(hkey, this->subkey.c_str(), 0, KEY_READ, &k);
	if (res != ERROR_SUCCESS) {
		log_error(L"Failed to open key %s: %u", this->subkey.c_str(), res);
		return false;
	}

	res = RegQueryValueEx(k, key.c_str(), NULL, NULL, (LPBYTE) NULL, &size);
	if (res != ERROR_SUCCESS) {
		log_error(L"Unable to access key %s: %u", key.c_str(), res);
		RegCloseKey(k);
		return false;
	}

	buffer = new wchar_t[size];

	res = RegQueryValueEx(k, key.c_str(), NULL, NULL, (LPBYTE)buffer, &size);
	if (res != ERROR_SUCCESS) {
		log_error(L"Unable to get key %s: %u", key.c_str(), res);
		delete[] buffer;
		RegCloseKey(k);

		return false;
	}

	value = buffer;

	delete[] buffer;
	RegCloseKey(k);

	return true;
}
