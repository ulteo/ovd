/**
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
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

#ifdef HAVE_CONFIG_H
#include <config.h>
#endif

#include <dirent.h>
#include <string.h>
#include <stdio.h>

#include "Utils.h"

std::vector<std::string>
Utils::StringExplode(const std::string &input, const std::string &separator) {
    std::string str(input);
    std::vector<std::string> results;
    std::string::size_type found = str.find_first_of(separator);

    while(found != std::string::npos){
        if(found >=0)
            results.push_back(str.substr(0, found));

        str = str.substr(found+1);
        found = str.find_first_of(separator);
    }
    if(str.length() > 0)
        results.push_back(str);

    return results;

}

bool
Utils::StringEndsWith(const std::string &input, const std::string &end) {
    int len1 = input.length();
    int len2 = end.length();

    if (len2 >len1)
        return false;

    return (input.substr(len1-len2) == end);
}


bool 
Utils::FolderExists(std::string path) {
    DIR* d = opendir(path.c_str());

    return (d!=NULL);
}

bool
Utils::Exec(const std::string& command, std::string& output) {
    int size = 0;
    char buffer[512];

    FILE* f = popen(command.c_str(), "r");
    if (f == NULL)
        return false;

    output = "";
    do {
        memset(buffer, '\0', 512);
        size = fread(buffer, sizeof(char), 512, f);
        output+=buffer;
    } while (size==512);

    return (pclose(f)==0);
}
