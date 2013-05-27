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

	static void rtrim(std::string &str);
	static void ltrim(std::string &str);
	static void atrim(std::string &str);

	static void unquote(std::string &str);

	static bool startWith(std::string &str, std::string &begin);
	static std::string toLower(std::string str);
	static int caseCompare(std::string &str, std::string &str2);
	static int caseCompare(std::string &str, const char* str2);
	static int split(std::vector<std::string>& vec, std::string str, char delim);
	static int split(std::list<std::string>& vec, std::string str, char delim);
	static std::string getCommonPart(std::list<std::string> list);
};

#endif /* STRINGUTIL_H_ */
