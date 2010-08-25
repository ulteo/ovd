/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
 * Author Arnaud LEGRAND <arnaud@ulteo.com>  2010
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
 **/

#ifndef UTILS_H
#define UTILS_H

#include <string>
#include <vector>

namespace Utils {
    bool StringEndsWith(const std::string &input, const std::string &end);
    std::vector<std::string>
        StringExplode(const std::string &, const std::string &);


    bool FolderExists(std::string path);
    bool Exec(const std::string& command, std::string& output);
}
#endif
