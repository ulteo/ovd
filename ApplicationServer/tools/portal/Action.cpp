/**
 * Copyright (C) 2009, 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010
 * Contributor Ronaldo Yamada 2010
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

#include <iostream>
#include <sstream>
#include <string>
#include <vector>
#include <stdio.h>

#include "Utils.h"
#include "Action.h"


Action::Action():
    chroot_cmd(CHROOT_PATH)
{}


void
Action::init(const std::string& _user, 
             const std::string& _chroot,
             const std::vector<std::string>& _args) {
    user = _user;
    chroot = _chroot;
    args = _args;
}

bool
Action::user_exist() {
    std::string cmd;
    std::string buffer;
    
    if (this->is_only_numeric_login()) {
        /**
           http://docs.sun.com/app/docs/doc/819-2240/getent-1m?a=view
           
           If the key value consists only of numeric characters,
           getent assumes that the key value is a numeric user ID and
           searches the user database for a matching user ID.

           If the user ID is not found in the user database or if the
           key value contains any non-numeric characters, getent
           assumes the key value is a user name and searches the user
           database for a matching user name
        **/
        cmd = this->chroot_cmd+" \""+this->chroot+"\" getent passwd |grep ^"+this->user+"\\:";
    }
    else
        cmd = this->chroot_cmd+" \""+this->chroot+"\" getent passwd "+this->user;

    if (! Utils::Exec(cmd, buffer))
        return false;

    std::vector<std::string> results = Utils::StringExplode(buffer, "\n");
    if (results.size()<1)
        return false;

    results = Utils::StringExplode(results[0], ":");
    if (results.size()<6)
        return false;

    //    int id = atoi(results[2].c_str());
    int id = 0;
    std::istringstream istr(results[2]);
    istr >> id;
    std::string home = results[5];

    if (id < 1000)
        return false;

    std::string real_path = chroot+home;
    if (! Utils::FolderExists(real_path))
        return false;

    return true;
}

void
Action::build_cmd() {
    return;
}

int
Action::perform() {
    std::string cmd = this->chroot_cmd+" \""+chroot+"\" su -s /bin/sh - "+user+" -c '"+command+"'";
    int buffer_size = 4096;
    char buffer[buffer_size];
    // std::cout<<cmd<<std::endl;
    FILE* f = popen(cmd.c_str(), "r");
    if (f == NULL)
        return -1;

    int size = 0;
    do {
        size = fread(buffer, 1, buffer_size, f);
        fwrite(buffer, 1, size, stdout);
    } while (size==buffer_size);

    return pclose(f);
}


unsigned int
Action::nb_args() {
    return 0;
}

bool
Action::is_only_numeric_login() {
    std::string pattern = "0123456789";

    for (unsigned int i=0; i< this->user.size(); i++) {
        char t = this->user[i];

        if (pattern.find(t) >= pattern.size())
            return false;
    }

    return true;
}
