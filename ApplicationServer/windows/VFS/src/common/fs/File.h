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
	std::wstring separator;
	std::wstring pathValue;

public:
	File(std::wstring path);
	virtual ~File();


	std::wstring& path();
	std::wstring parent();
	std::wstring fullname();
	std::wstring shortname();
	std::wstring extention();
	bool isAbsolute();
	void join(std::wstring path);
	bool expand();
	bool expand(const std::wstring& base);

	bool exist();
	bool remove();
	bool rmdirs();

	bool mkdirs();
	bool chdir();
};

#endif /* FILE_H_ */
