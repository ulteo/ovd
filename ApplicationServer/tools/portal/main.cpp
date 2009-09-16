/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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
#include <string>
#include <vector>

#include <pwd.h>

#include "Action.h"
#include "Utils.h"

#define CONF_FILE SYSCONFDIR "/ulteo-ovd.conf"

static std::string cmd_name;

int usage() {
    std::cerr << "usage: " << cmd_name << " user action ..." << std::endl
              << "\t ls directory" << std::endl
              << "\t lsd directory|file" << std::endl
              << "\t cp|mv src dst" << std::endl
              << "\t rm file" << std::endl
              << "\t mkdir path" << std::endl
              << "\t touch path" << std::endl
              << "\t get file" << std::endl
              << "\t put file" << std::endl
              << "\t pwd" << std::endl
              << std::endl;
    return 1;
}


bool load_config(const std::string& key, std::string& result) {
    std::string command(". "+std::string(CONF_FILE)+"; echo $"+key);
    std::string buffer;
    if (! Utils::Exec(command, buffer))
        return false;

    std::vector<std::string> results = Utils::StringExplode(buffer, "\n");
    if (results.size()<1)
        return false;

    result.append(results[0]);
    if (result.length()==0)
        return false;

    return true;
}


bool check_effective_user(const std::string& user) {

    std::string login;
    uid_t u1,u2,u3;
    int retval;

    retval = getresuid(&u1, &u2, &u3);
    if(retval) {
        std::cerr << "Unable to getresuid " <<std::endl;
        return false;
    }

    struct passwd * buffer = getpwuid(u1);
    if (buffer==NULL) {
        std::cerr << "Unable to getpwuid " << u1 << std::endl;
        return false;
    }

    login = std::string(buffer->pw_name);
    if (login != user)
        return false;

    retval = setresuid(u3, u3, u3);
    if(retval) {
        std::cerr << "Unable to setresuid " <<std::endl;
        return false;
    }

    return true;
}


Action * getAction(const std::string& name) {
    if (name == "pwd")
        return new Action_pwd();
    if (name == "touch")
        return new Action_touch();
    if (name == "mkdir")
        return new Action_mkdir();
    if (name == "ls")
        return new Action_ls();
    if (name == "lsd")
        return new Action_lsd();
    if (name == "rm")
        return new Action_rm();
    if (name == "cp")
        return new Action_cp();
    if (name == "mv")
        return new Action_mv();
    if (name == "get")
        return new Action_get();
    if (name == "put")
        return new Action_put();

    return NULL;
}


int main (int argc, char *argv[]) {
    cmd_name = std::string(argv[0]);

    if (argc < 3) {
        usage();
        return 1;
    }

    std::string chroot;
    if (! load_config("CHROOT", chroot)) {
        return 2;
    }

    std::string authorized_user;
    if (! load_config("WWW_USER", authorized_user)) {
        std::cerr << "Missing WWW_USER in config file "
                  << CONF_FILE << std::endl;
        return 2;
    }

    if (! check_effective_user(authorized_user)) {
        std::cerr << "Unauthorized access" << std::endl;
        return 2;
    }

    std::string user = std::string(argv[1]);
    std::string action_name = std::string(argv[2]);
    std::vector<std::string> args;
    for (int i=3; i<argc; i++)
        args.push_back(std::string(argv[i]));

    Action * action = getAction(action_name);
    if (action == NULL) {
        std::cerr << "Unknown action '"<< action_name
                  << "'" << std::endl;
        usage();
        return 1;
    }

    action->init(user, chroot, args);

    if (! action->user_exist()) {
        std::cerr << "Unknown user" << std::endl;
        return 1;
    }
    
    if (action->nb_args()!=args.size()) {
        std::cerr << "Bad argument length for action '"
                  << action_name << "'" << std::endl;
        usage();
        return 1;
    }
    
    action->build_cmd();

    return action->perform();
}

    /*  
    for (int i=1; i<argc; i++) {
        if (strcmp(argv[i], "-u") == 0) {
            if (i+1==argc) {
                std::cerr << "Missing username after -u argument" <<std::endl;
                return usage();
            }
            username = argv[++i];
        }
        else if (strcmp(argv[i], "--auto") == 0) {
            if (read_auto_launch_config() != AUTO_LAUNCH_NO_CONFIG)
                return 2;
        }
        else {
        std::cerr << "Unknown option '" << argv[i] << "'" <<std::endl;
        return usage();
        }
    }
    */
