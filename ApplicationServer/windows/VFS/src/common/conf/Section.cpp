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

KeyMap* Section::getMap() {
	return &this->keys;
}

std::string& Section::getString(std::string key) {
	if ((this->keys.find(key) == this->keys.end()))
		throw UException("key %s do not exist in the section: %s", key.c_str(), this->name.c_str());

	return this->keys[key];
}

int Section::getInt(std::string key) {
	if ((this->keys.find(key) == this->keys.end()))
		throw UException("key %s do not exist in the section: %s", key.c_str(), this->name.c_str());

	return atoi(this->keys[key].c_str());
}

bool Section::getBool(std::string key) {
	if ((this->keys.find(key) == this->keys.end()))
		throw UException("key %s do not exist in the section: %s", key.c_str(), this->name.c_str());

	bool ret = false;
	ret = (StringUtil::caseCompare(this->keys[key], "Yes") == 0) || (StringUtil::caseCompare(this->keys[key], "True") == 0)	|| (this->keys[key].compare("1") == 0);

	return ret;
}

void Section::addValue(std::string key, std::string value) {
	this->keys[key] = value;
}

bool Section::hasKey(std::string key) {
	return (this->keys.find(key) != this->keys.end());
}


std::ostream& operator <<(std::ostream& out, Section& section) {
	KeyMap* map = section.getMap();

	out<<"["<<section.getName()<<"]"<<std::endl;
	KeyMap::iterator valueIterator = map->begin();

	while(valueIterator != map->end())
	{
		out<<(*valueIterator).first<<" = "<<(*valueIterator).second<<std::endl;
		valueIterator++;
	}
	out<<std::endl;

	return out;
}
