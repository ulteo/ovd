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

#include "INI.h"
#include <fstream>
#include <sstream>
#include <common/Logger.h>
#include <common/StringUtil.h>
#include <common/UException.h>



INI::INI(const std::string& filename): filename(filename) {}

INI::~INI() {}


void INI::parse() {
	std::string currentSection;
	char buffer[1024] = {0};
	char section[256] = {0};
	char external[256] = {0};
	std::string line;

	std::ifstream fileStream(this->filename.c_str());
	if (!fileStream.good())
		throw UException("Failed to open %s", this->filename);

	while (!fileStream.eof()) {
		fileStream.getline(buffer, sizeof(buffer));
		line = buffer;
		StringUtil::atrim(line);

		if (line.find("#") == 0)
			continue;

		if (line.empty())
			continue;

		if (sscanf_s(line.c_str(), "[%[^]]", section) == 1) {
			currentSection = section;
			this->addSection(currentSection);
		}
		else {
			std::vector<std::string> v;
			if (StringUtil::split(v, line, '=') != 2)
				throw UException("Wrong INIuration line %s", line);

			std::string keyStr = v[0];
			StringUtil::atrim(keyStr);
			std::string valueStr = v[1];
			StringUtil::atrim(valueStr);
			StringUtil::unquote(valueStr);

			if (!valueStr.empty())
				this->addValue(currentSection, keyStr, valueStr);
		}
	}
}


// Accessor
std::string& INI::getString(std::string section, std::string key) {
	Section* s = this->getSection(section);
	return s->getString(key);
}

int INI::getInt(std::string section, std::string key) {
	Section* s = this->getSection(section);
	return s->getInt(key);
}

bool INI::getBool(std::string section, std::string key) {
	Section* s = this->getSection(section);
	return s->getBool(key);
}

Sections& INI::getSections() {
	return this->sections;
}

Section* INI::getSection(std::string section) {
	if (this->sections.find(section) == this->sections.end())
		throw UException("section %s do not exist", section.c_str());

	return this->sections[section];
}

void INI::addSection(std::string section) {
	Section* s = new Section(section);
	this->sections[section] = s;
}

void INI::addValue(std::string section, std::string key, std::string value) {
	Section* s = this->getSection(section);
	if (s != NULL)
		s->addValue(key, value);
}

std::string INI::dump() {
	std::stringstream dump;
	Sections::iterator sectionsIterator = this->sections.begin();

	while (sectionsIterator != this->sections.end()) {
		dump<<*((*sectionsIterator).second);
		sectionsIterator++;
	}

	return dump.str();
}
