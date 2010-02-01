/**
 * Copyright (C) 2009, 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010
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

#ifndef ACTION_H
#define ACTION_H

#include <string>
#include <vector>

class Action {
 protected:
    std::string user;
    std::string chroot;
    std::string chroot_cmd;
    std::vector<std::string> args;
    std::string command;

 public:
    Action();
    void init(const std::string& _user,
              const std::string& _chroot,
              const std::vector<std::string>& _args);
    bool user_exist();
    virtual void build_cmd();
    int perform();
    virtual unsigned int nb_args();

 private:
    bool is_only_numeric_login();
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

class Action_ls : public Action {
 public:
    unsigned int nb_args() {
        return 1;
    }
    void build_cmd() {
        command = "ls -l \""+args[0]+"\"";
    }
};

class Action_lsd: public Action_ls {
 public:
    void build_cmd() {
        command = "ls -ld \""+args[0]+"\"";
    }
};

class Action_rm: public Action_ls {
 public:
    void build_cmd() {
        command = "rm -rf \""+args[0]+"\"";
    }
};

class Action_cp: public Action {
 public:
    unsigned int nb_args() {
        return 2;
    }
    void build_cmd() {
        command = "cp -R \""+args[0]+"\" \""+args[1]+"\"";
    }
};

class Action_mv: public Action_cp {
 public:
    void build_cmd() {
        command = "mv \""+args[0]+"\" \""+args[1]+"\"";
    }
};

class Action_mkdir: public Action_ls {
 public:
    void build_cmd() {
        command = "mkdir \""+args[0]+"\"";
    }
};

class Action_touch: public Action_ls {
 public:
    void build_cmd() {
        command = "touch \""+args[0]+"\"";
    }
};

class Action_get: public Action_ls {
 public:
    void build_cmd() {
        command = "cat \""+args[0]+"\"";
    }
};

class Action_put: public Action_get {
 public:
    void build_cmd() {
        command = "cat >\""+args[0]+"\"";
    }
};

#endif
