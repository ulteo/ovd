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

#ifndef INI_H_
#define INI_H_

#include <map>
#include <string>

#include "Section.h"


typedef std::map<std::wstring, Section*> Sections;

class INI {
protected:
	std::wstring filename;
	Sections sections;

public:
	INI(const std::wstring& filename);
	virtual ~INI();

	void parse();

	std::wstring& getString(std::wstring section, std::wstring key);
	int getInt(std::wstring section, std::wstring key);
	bool getBool(std::wstring section, std::wstring key);

	Sections& getSections();
	Section* getSection(std::wstring section);
	void addSection(std::wstring section);
	void addValue(std::wstring section, std::wstring key, std::wstring value);
	std::wstring dump();
};

#endif /* INI_H_ */
