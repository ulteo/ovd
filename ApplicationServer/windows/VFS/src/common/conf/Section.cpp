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
#include <exception>
#include <common/StringUtil.h>
#include <common/UException.h>


Section::Section(std::string name): name(name) { }

Section::~Section() { }

std::string Section::getName() {
	return this->name;
}

std::vector<std::string>& Section::getKeys() {
	return this->keys;
}

std::vector<std::string>& Section::getValues() {
	return this->values;
}


std::string& Section::getString(std::string key) {
	int length = this->keys.size();

	for (int i = 0 ; i < length ; i++)
		if (this->keys[i].compare(key) == 0)
			return this->values[i];

	throw UException("key %s do not exist in the section: %s", key.c_str(), this->name.c_str());
}

int Section::getInt(std::string key) {
	std::string value = this->getString(key);

	return atoi(value.c_str());
}

bool Section::getBool(std::string key) {
	std::string value = this->getString(key);

	bool ret = false;
	ret = (StringUtil::caseCompare(value, "Yes") == 0) || (StringUtil::caseCompare(value, "True") == 0)	|| (value.compare("1") == 0);

	return ret;
}

void Section::addValue(std::string key, std::string value) {
	this->keys.insert(this->keys.end(), key);
	this->values.insert(this->values.end(), value);
}

bool Section::hasKey(std::string key) {
	int length = this->keys.size();

	for (int i = 0 ; i < length ; i++)
		if (this->keys[i].compare(key) == 0)
			return true;

	return false;
}


std::ostream& operator <<(std::ostream& out, Section& section) {
	std::vector<std::string>& keys = section.getKeys();
	std::vector<std::string>& values = section.getValues();

	out<<"["<<section.getName()<<"]"<<std::endl;
	std::vector<std::string>::iterator keyIterator = keys.begin();
	std::vector<std::string>::iterator valueIterator = values.begin();

	while(keyIterator != keys.end() && valueIterator != values.end())
	{
		out<<(*keyIterator)<<" = "<<(*valueIterator)<<std::endl;
		valueIterator++;
		keyIterator++;
	}
	out<<std::endl;

	return out;
}
