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



INI::INI(const std::wstring& filename): filename(filename) {}

INI::~INI() {}


void INI::parse() {
	std::wstring currentSection;
	char buffer[1024] = {0};
	wchar_t section[256] = {0};
	wchar_t external[256] = {0};
	std::wstring line;

	std::ifstream fileStream(this->filename.c_str());
	if (!fileStream.good())
		throw UException(L"Failed to open %s", this->filename.c_str());

	while (!fileStream.eof()) {
		fileStream.getline(buffer, sizeof(buffer));
		StringUtil::towstring(buffer, line);
		StringUtil::atrim(line);

		if (line.find(L"#") == 0)
			continue;

		if (line.empty())
			continue;

		if (swscanf_s(line.c_str(), L"[%[^]]", section) == 1) {
			currentSection = section;
			this->addSection(currentSection);
		}
		else {
			std::vector<std::wstring> v;
			if (StringUtil::split(v, line, L'=') != 2)
				throw UException(L"Wrong INIuration line %s", line.c_str());

			std::wstring keyStr = v[0];
			StringUtil::atrim(keyStr);
			std::wstring valueStr = v[1];
			StringUtil::atrim(valueStr);
			StringUtil::unquote(valueStr);

			if (!valueStr.empty())
				this->addValue(currentSection, keyStr, valueStr);
		}
	}
}


// Accessor
std::wstring& INI::getString(std::wstring section, std::wstring key) {
	Section* s = this->getSection(section);
	return s->getString(key);
}

int INI::getInt(std::wstring section, std::wstring key) {
	Section* s = this->getSection(section);
	return s->getInt(key);
}

bool INI::getBool(std::wstring section, std::wstring key) {
	Section* s = this->getSection(section);
	return s->getBool(key);
}

Sections& INI::getSections() {
	return this->sections;
}

Section* INI::getSection(std::wstring section) {
	if (this->sections.find(section) == this->sections.end())
		throw UException(L"section %s do not exist", section.c_str());

	return this->sections[section];
}

void INI::addSection(std::wstring section) {
	Section* s = new Section(section);
	this->sections[section] = s;
}

void INI::addValue(std::wstring section, std::wstring key, std::wstring value) {
	Section* s = this->getSection(section);
	if (s != NULL)
		s->addValue(key, value);
}

std::wstring INI::dump() {
	std::wstringstream dump;
	Sections::iterator sectionsIterator = this->sections.begin();

	while (sectionsIterator != this->sections.end()) {
		dump<<*((*sectionsIterator).second);
		sectionsIterator++;
	}

	return dump.str();
}
