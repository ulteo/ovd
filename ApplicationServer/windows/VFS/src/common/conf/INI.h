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


typedef std::map<std::string, Section*> Sections;

class INI {
protected:
	std::string filename;
	Sections sections;

public:
	INI(const std::string& filename);
	virtual ~INI();

	void parse();

	std::string& getString(std::string section, std::string key);
	int getInt(std::string section, std::string key);
	bool getBool(std::string section, std::string key);

	Sections& getSections();
	Section* getSection(std::string section);
	void addSection(std::string section);
	void addValue(std::string section, std::string key, std::string value);
	std::string dump();
};

#endif /* INI_H_ */
