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
	std::string name;
	std::string path;
	std::string rsyncSrc;
	std::string rsyncFilter;
	bool deleteOnClose;

public:
	Union(std::string& name);
	virtual ~Union();


	void setPath(std::string& path);
	void setDeleteOnClose(bool value);
	void setRsyncSrc(std::string src);
	void setRsyncFilter(std::string filter);

	std::string& getName();
	std::string& getPath();
	bool isDeleteOnClose();
	std::string& getRsyncSrc();
	std::string& getRsyncFilter();
};

#endif /* UNION_H_ */
