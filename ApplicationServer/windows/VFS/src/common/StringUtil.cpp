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

#include "StringUtil.h"
#include <algorithm>


StringUtil::StringUtil() { }

StringUtil::~StringUtil() { }


void StringUtil::rtrim(std::string& str) {
	if (str.empty())
		return;

	std::string::size_type pos = str.find_first_not_of(" ");
	if (pos != std::string::npos)
		str.erase(0,pos);

	pos = str.find_first_not_of("\t");
	if (pos != std::string::npos)
		str.erase(0,pos);
}

void StringUtil::ltrim(std::string& str) {
	if (str.empty())
		return;

	std::string::size_type pos = str.find_last_not_of(" ");
	if (pos != std::string::npos)
		str.erase(pos+1);

	pos = str.find_last_not_of("\t");
	if (pos != std::string::npos)
		str.erase(pos+1);
}

void StringUtil::atrim(std::string& str) {
	ltrim(str);
	rtrim(str);
}


void StringUtil::unquote(std::string& str) {
	std::string::iterator it;
	atrim(str);

	if (str.empty())
		return;

	it = str.begin();
	if ((*it == '\'') || *it == '"')
		str.erase(it);

	it = str.end() - 1;
	if ((*it == '\'') || *it == '"')
		str.erase(it);
}


bool StringUtil::startWith(std::string &str, std::string &begin) {
	return str.compare(0, begin.size(), begin) == 0;
}

std::string StringUtil::toLower(std::string str) {
	std::transform(str.begin(), str.end(), str.begin(), toupper);
	return str;
}

int StringUtil::caseCompare(std::string &str, std::string &str2) {
	return toLower(str).compare(toLower(str2));
}

int StringUtil::caseCompare(std::string &str, const char* str2) {
	std::string s = str2;
	return toLower(str).compare(toLower(s));
}


int StringUtil::split(std::vector<std::string>& vec, std::string str, char delim) {
	vec.clear();

	std::string::size_type pos = str.find(delim);

	while(pos != std::string::npos) {
		vec.push_back(str.substr(0, pos));
		str = str.substr(pos + 1);
		pos = str.find(delim);
	}

	vec.push_back(str);

	return vec.size();
}

int StringUtil::split(std::list<std::string>& list, std::string str, char delim) {
	list.clear();

	std::string::size_type pos = str.find(delim);

	while(pos != std::string::npos) {
		list.push_back(str.substr(0, pos));
		str = str.substr(pos + 1);
		pos = str.find(delim);
	}

	list.push_back(str);

	return list.size();
}

std::string StringUtil::getCommonPart(std::list<std::string> list) {
	if (list.size() == 0)
		return "";

	if (list.size() == 1)
		return *list.begin();

	std::list<std::string>::iterator j = list.begin();
	std::string commonPart = (*j++);

	while (j != list.end()) {
		for (int i = commonPart.length() ; i > 0 ; i--) {
			if ((*j).find(commonPart) == 0)
				break;
			commonPart = commonPart.substr(0, i);
		}
		j++;
	}

	return commonPart;
}


void StringUtil::replaceAll(std::string& src, const std::string& from, const std::string to) {
	size_t pos = 0;

	while((pos = src.find(from, pos)) != std::string::npos) {
	         src.replace(pos, from.length(), to);
	         pos += to.length();
	}
}
