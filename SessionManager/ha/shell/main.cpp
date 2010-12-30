/**
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
 * Author Arnaud LEGRAND <arnaud@ulteo.com>  2010
 * Author Samuel BOVEE <samuel@ulteo.com>  2010
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

#define CONF_FILE SYSCONFDIR "/ulteo/ovd/ha/resources.conf"

static std::string cmd_name;

int usage() {
    std::cerr << "usage: " << cmd_name << " action ..." << std::endl
              << "\t ls directory" << std::endl
              << "\t lsd directory|file" << std::endl
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
    if (name == "crm_mon")
	return new Action_crm_mon();
    if (name == "cibadmin")
        return new Action_cibadmin();
    if (name == "crm_standby")
        return new Action_crm_node();
    if (name == "crm_resource_cleanup")
        return new Action_crm_resource_cleanup();
    if (name == "view_logs")
	return new Action_view_logs();
    if (name == "cleanup_logs")
        return new Action_cleanup_logs();
    // No secure
    if (name == "get_conf_file")
        return new Action_get_content_from_file();
    if (name == "set_conf_file")
        return new Action_put_content_to_file();
	if (name == "shell_cmd")
        return new Action_shell_cmd();
    return NULL;
}


int main (int argc, char *argv[]) {
    cmd_name = std::string(argv[0]);

    if (argc < 3) {
        usage();
        return 1;
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

    std::string action_name = std::string(argv[1]);
    std::vector<std::string> args;
    for (int i=2; i<argc; i++)
        args.push_back(std::string(argv[i]));

    Action * action = getAction(action_name);
    if (action == NULL) {
        std::cerr << "Unknown action '"<< action_name
                  << "'" << std::endl;
        usage();
        return 1;
    }

    action->init(args);
    
    if (action->nb_args()!=args.size()) {
        std::cerr << "Bad argument length for action '"
                  << action_name << "'" << std::endl;
        usage();
        return 1;
    }
    
    action->build_cmd();

    return action->perform();
}

