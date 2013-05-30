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

#ifndef UNION_H_
#define UNION_H_

#include <string>


class Union {
private:
	std::wstring name;
	std::wstring path;
	std::wstring rsyncSrc;
	std::wstring rsyncFilter;
	bool deleteOnClose;

public:
	Union(std::wstring& name);
	virtual ~Union();


	void setPath(std::wstring& path);
	void setDeleteOnClose(bool value);
	void setRsyncSrc(std::wstring src);
	void setRsyncFilter(std::wstring filter);

	std::wstring& getName();
	std::wstring& getPath();
	bool isDeleteOnClose();
	std::wstring& getRsyncSrc();
	std::wstring& getRsyncFilter();
};

#endif /* UNION_H_ */
