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

#ifndef STRINGUTIL_H_
#define STRINGUTIL_H_

#include <string>
#include <vector>
#include <list>


class StringUtil {
public:
	StringUtil();
	virtual ~StringUtil();

	static void towstring(const std::string& in, std::wstring& out);
	static void rtrim(std::wstring &str);
	static void ltrim(std::wstring &str);
	static void atrim(std::wstring &str);

	static void unquote(std::wstring &str);

	static bool startWith(std::wstring &str, std::wstring &begin);
	static std::wstring toLower(std::wstring str);
	static int caseCompare(const std::wstring &str, const std::wstring &str2);
	static size_t split(std::vector<std::wstring>& vec, std::wstring str, wchar_t delim);
	static size_t split(std::list<std::wstring>& vec, std::wstring str, wchar_t delim);
	static std::wstring getCommonPart(std::list<std::wstring> list);
	static void replaceAll(std::wstring& src, const std::wstring& from, const std::wstring to);
};

#endif /* STRINGUTIL_H_ */
