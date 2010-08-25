/**
 * Copyright (C) 2009, 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010
 * Author Arnaud LEGRAND <arnaud@ulteo.com>  2010
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


Action::Action(){}


void
Action::init(const std::vector<std::string>& _args) {
    args = _args;
}

void
Action::build_cmd() {
    return;
}

int
Action::perform() {
    int buffer_size = 4096;
    char buffer[buffer_size];
    // std::cout<<cmd<<std::endl;
    FILE* f = popen(command.c_str(), "r");
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
