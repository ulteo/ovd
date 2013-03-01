/*
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2012
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

#include "common/list.h"
#include "common/types.h"
#include "common/fs.h"
#include "common/ini.h"



#define PACKAGE_VERSION            "0.1"
#define DEFAULT_CONFIGURATION_PATH "/etc/ulteo/default.conf"
#define MAIN_CONFIGURATION_SECTION "main"
#define TRANS_CONFIGURATION_SECTION "translation"
#define MAIN_UNION_CONFIGURATION_KEY "union"
#define MAIN_BIND_CONFIGURATION_KEY "bind"
#define MAIN_BIND_DESTINATION_CONFIGURATION_KEY "bindDestination"

#define LOG_CONFIGURATION_SECTION "log"
#define LOG_LEVEL_CONFIGURATION_KEY "level"
#define LOG_PROGRAM_CONFIGURATION_KEY "program"
#define LOG_DEVEL_CONFIGURATION_KEY "enableDevelOutput"
#define LOG_STDOUT_CONFIGURATION_KEY "enableStdOutput"
#define LOG_OUTFILE_CONFIGURATION_KEY "outputFilename"

#define UNION_PATH_CONFIGURATION_KEY "path"
#define UNION_ACCEPT_CONFIGURATION_KEY "accept"
#define UNION_REJECT_CONFIGURATION_KEY "reject"


typedef struct _Union {
	char name[256];
	char path[PATH_MAX];
	List* accept;
	List* reject;
} Union;


typedef struct _Translation {
	char in[PATH_MAX];
	char out[PATH_MAX];
} Translation;


typedef struct _Configuration {
	char* user;
	bool bind;
	char* source_path;
	char* destination_path;
	char bind_path[PATH_MAX];
	List* unions;
	List* translations;
} Configuration;


Configuration* configuration_new();
bool configuration_free(Configuration* conf);
bool configuration_parse (const char* path, Configuration* conf);
void configuration_dump (Configuration* conf);

