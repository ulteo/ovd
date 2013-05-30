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

#include "Translation.h"



Translation::Translation() { }


Translation::~Translation() { }


void Translation::add(std::wstring key, std::wstring value) {
	this->keys.push_back(key);
	this->values.push_back(value);
}


std::vector<std::wstring>& Translation::getKeys() {
	return this->keys;
}


std::vector<std::wstring>& Translation::getValues() {
	return this->values;
}


std::wstring& Translation::translate(std::wstring& path, bool inRequest) {
	if (inRequest) {
		for(unsigned int i = 0 ; i < this->values.size() ; i++) {
			if (path.find(this->values[i]) == 0) {
				return path.replace(0, this->values[i].length(), this->keys[i]);
			}
		}
	}
	else {
		for(unsigned int i = 0 ; i < this->keys.size() ; i++) {
			if (path.find(this->keys[i]) == 0) {
				return path.replace(0, this->keys[i].length(), this->values[i]);
			}
		}
	}

	return path;
}
