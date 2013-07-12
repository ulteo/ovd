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

#ifndef TRANSLATION_H_
#define TRANSLATION_H_

#include <vector>


class Translation {
private:
	std::vector<std::wstring> keys;
	std::vector<std::wstring> values;

public:
	Translation();
	virtual ~Translation();

	void add(std::wstring key, std::wstring value);
	std::vector<std::wstring>& getKeys();
	std::vector<std::wstring>& getValues();

	std::wstring& translate(std::wstring& path, bool inRequest);
};

#endif /* TRANSLATION_H_ */
