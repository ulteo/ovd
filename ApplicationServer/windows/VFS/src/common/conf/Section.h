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

#ifndef SECTION_H_
#define SECTION_H_

#include <iostream>
#include <map>


typedef std::map<std::string, std::string> KeyMap;


class Section {
private:
	std::string name;
	KeyMap keys;

public:
	Section();
	Section(std::string name);
	virtual ~Section();

	std::string getName();
	KeyMap* getMap();
	int getInt(std::string key);
	bool getBool(std::string key);
	std::string& getString(std::string key);
	void addValue(std::string key, std::string value);
	bool hasKey(std::string key);


	friend std::ostream& operator <<(std::ostream& out, Section& section);
};

#endif /* SECTION_H_ */
