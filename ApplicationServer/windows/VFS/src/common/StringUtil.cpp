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


void StringUtil::towstring(const std::string& in, std::wstring& out) {
	out.assign(in.begin(), in.end());
}


void StringUtil::rtrim(std::wstring& str) {
	if (str.empty())
		return;

	std::wstring::size_type pos = str.find_first_not_of(L" ");
	if (pos != std::wstring::npos)
		str.erase(0,pos);

	pos = str.find_first_not_of(L"\t");
	if (pos != std::wstring::npos)
		str.erase(0,pos);
}

void StringUtil::ltrim(std::wstring& str) {
	if (str.empty())
		return;

	std::wstring::size_type pos = str.find_last_not_of(L" ");
	if (pos != std::wstring::npos)
		str.erase(pos+1);

	pos = str.find_last_not_of(L"\t");
	if (pos != std::wstring::npos)
		str.erase(pos+1);
}

void StringUtil::atrim(std::wstring& str) {
	ltrim(str);
	rtrim(str);
}


void StringUtil::unquote(std::wstring& str) {
	std::wstring::iterator it;
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


bool StringUtil::startWith(std::wstring &str, std::wstring &begin) {
	return str.compare(0, begin.size(), begin) == 0;
}

std::wstring StringUtil::toLower(std::wstring str) {
	std::transform(str.begin(), str.end(), str.begin(), toupper);
	return str;
}

int StringUtil::caseCompare(const std::wstring &str, const std::wstring &str2) {
	return toLower(str).compare(toLower(str2));
}

int StringUtil::split(std::vector<std::wstring>& vec, std::wstring str, wchar_t delim) {
	vec.clear();

	std::wstring::size_type pos = str.find(delim);

	while(pos != std::wstring::npos) {
		vec.push_back(str.substr(0, pos));
		str = str.substr(pos + 1);
		pos = str.find(delim);
	}

	vec.push_back(str);

	return vec.size();
}

int StringUtil::split(std::list<std::wstring>& list, std::wstring str, wchar_t delim) {
	list.clear();

	std::wstring::size_type pos = str.find(delim);

	while(pos != std::wstring::npos) {
		list.push_back(str.substr(0, pos));
		str = str.substr(pos + 1);
		pos = str.find(delim);
	}

	list.push_back(str);

	return list.size();
}

std::wstring StringUtil::getCommonPart(std::list<std::wstring> list) {
	if (list.size() == 0)
		return L"";

	if (list.size() == 1)
		return *list.begin();

	std::list<std::wstring>::iterator j = list.begin();
	std::wstring commonPart = (*j++);

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


void StringUtil::replaceAll(std::wstring& src, const std::wstring& from, const std::wstring to) {
	size_t pos = 0;

	while((pos = src.find(from, pos)) != std::wstring::npos) {
	         src.replace(pos, from.length(), to);
	         pos += to.length();
	}
}
