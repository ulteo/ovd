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

#ifndef FILE_H_
#define FILE_H_

#include <string>
#include <list>


class File {
protected:
	std::string separator;
	std::string pathValue;

public:
	File(std::string path);
	virtual ~File();


	std::string path();
	std::string parent();
	std::string fullname();
	std::string shortname();
	std::string extention();
	void join(std::string path);
	bool expand();
};

#endif /* FILE_H_ */
