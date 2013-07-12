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

#ifndef REGISTRY_H_
#define REGISTRY_H_

#include <windows.h>
#include <string>


class Registry {
private:
	HKEY hkey;
	std::wstring subkey;


public:
	Registry(std::wstring key);
	virtual ~Registry();

	bool create();
	bool exist();

	bool set(const std::wstring& key, const std::wstring& value);
	bool get(const std::wstring& key, std::wstring& value);
};

#endif /* REGISTRY_H_ */
