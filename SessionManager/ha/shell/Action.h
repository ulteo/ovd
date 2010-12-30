/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
 * Author Arnaud LEGRAND <arnaud@ulteo.com>  2010
 * Author Samuel BOVEE <samuel@ulteo.com>  2010
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

#ifndef ACTION_H
#define ACTION_H

#include <string>
#include <vector>

class Action {
 protected:
    std::vector<std::string> args;
    std::string command;

 public:
    Action();
    void init(const std::vector<std::string>& _args);
    virtual void build_cmd();
    int perform();
    virtual unsigned int nb_args();
};

class Action_pwd : public Action {
 public:
    unsigned int nb_args() {
        return 0;
    }
    void build_cmd() {
        command = "pwd";
    }
};


class Action_crm_mon: public Action {
 public:
    unsigned int nb_args() {
        return 1;
    }
    void build_cmd() {
        command = "crm_mon -1";
    }
};
class Action_cibadmin: public Action {
 public:
    unsigned int nb_args() {
        return 1;
    }
    void build_cmd() {
        command = "cibadmin -Ql -o \""+args[0]+"\"";
    }
};
class Action_crm_resource_cleanup: public Action {
 public:
    unsigned int nb_args() {
        return 1;
    }
    void build_cmd() {
        command = "crm_resource --resource "+args[0]+" --cleanup";
    }
};
class Action_crm_node: public Action {
 public:
    unsigned int nb_args() {
        return 2;
    }
    void build_cmd() {
        command = "crm_attribute --type nodes --node "+args[0]+" --name standby --update "+args[1];

    }
};

class Action_view_logs: public Action {
 public:
    unsigned int nb_args() {
        return 1;
    }
    void build_cmd() {
        command = "cat /var/log/ulteo/sessionmanager/ha-debug-hb.log | cut -d' ' -f1-5,7- | grep -E \"heartbeat:|lrmd:\" | grep -E \""+args[0]+"\"";
    }
};
class Action_cleanup_logs: public Action {
 public:
    unsigned int nb_args() {
        return 1;
    }
    void build_cmd() {
        command = "echo \"\" >  /var/log/ulteo/sessionmanager/ha-debug-hb.log";
    }
};

class Action_shell_cmd: public Action {
 public:
    unsigned int nb_args() {
        return 4;
    }
    void build_cmd() {
        command = "screen -dmS ulteolauncher bash /usr/share/ulteo/ovd/ha/su_cmd.sh "+args[0]+" "+args[1]+" "+args[2]+" "+args[3];
    }
};

class Action_get_content_from_file: public Action {
 public:
    unsigned int nb_args() {
        return 1;
    }
    void build_cmd() {
        command = "cat /etc/ulteo/ovd/ha/resources.conf";
    }
};

class Action_put_content_to_file: public Action {
 public:
    unsigned int nb_args() {
        return 1;
    }
    void build_cmd() {
        command = "echo '"+args[0]+"' | sed -e \"s/:/\\n/g\" > /etc/ulteo/ovd/ha/resources.conf";
    }
};

#endif



