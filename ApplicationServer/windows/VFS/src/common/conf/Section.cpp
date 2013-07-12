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

#include "Section.h"
#include <stdlib.h>
#include <common/StringUtil.h>
#include <common/UException.h>


Section::Section(std::wstring name): name(name) { }

Section::~Section() { }

std::wstring Section::getName() {
	return this->name;
}

std::vector<std::wstring>& Section::getKeys() {
	return this->keys;
}

std::vector<std::wstring>& Section::getValues() {
	return this->values;
}


std::wstring& Section::getString(std::wstring key) {
	size_t length = this->keys.size();

	for (size_t i = 0 ; i < length ; i++)
		if (this->keys[i].compare(key) == 0)
			return this->values[i];

	throw UException(L"key %s do not exist in the section: %s", key.c_str(), this->name.c_str());
}

int Section::getInt(std::wstring key) {
	std::wstring value = this->getString(key);

	return _wtoi(value.c_str());
}

bool Section::getBool(std::wstring key) {
	std::wstring value = this->getString(key);

	bool ret = false;
	ret = (StringUtil::caseCompare(value, L"Yes") == 0) || (StringUtil::caseCompare(value, L"True") == 0)	|| (value.compare(L"1") == 0);

	return ret;
}

void Section::addValue(std::wstring key, std::wstring value) {
	this->keys.insert(this->keys.end(), key);
	this->values.insert(this->values.end(), value);
}

bool Section::hasKey(std::wstring key) {
	size_t length = this->keys.size();

	for (size_t i = 0 ; i < length ; i++)
		if (this->keys[i].compare(key) == 0)
			return true;

	return false;
}


std::wostream& operator <<(std::wostream& out, Section& section) {
	std::vector<std::wstring>& keys = section.getKeys();
	std::vector<std::wstring>& values = section.getValues();

	out<<"["<<section.getName()<<"]"<<std::endl;
	std::vector<std::wstring>::iterator keyIterator = keys.begin();
	std::vector<std::wstring>::iterator valueIterator = values.begin();

	while(keyIterator != keys.end() && valueIterator != values.end()) {
		out<<(*keyIterator)<<" = "<<(*valueIterator)<<std::endl;
		valueIterator++;
		keyIterator++;
	}
	out<<std::endl;

	return out;
}
