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

#include "configuration.h"
#include "common/log.h"
#include "common/memory.h"
#include "common/list.h"
#include "common/str.h"


Configuration* configuration_new() {
	Configuration* conf = memory_new(Configuration, true);
	if (conf == NULL)
		return NULL;

	conf->unions = list_new(false);
	conf->bind = false;
	conf->destination_path[0] = '\0';
	conf->bind_path[0] = '\0';
	
	return conf;
}


bool configuration_free(Configuration* conf) {
	int i;

	if (conf == NULL)
		return false;

	if (conf->unions == NULL) {
		logWarn("Invalid configuration, we need at least one path to union");
		return;
	}

	logInfo("mounted path: %s", conf->destination_path);

	for(i = 0 ; i < conf->unions->size; i++) {
		Union* u = (Union*)list_get(conf->unions, i);

		list_delete(u->accept);
		list_delete(u->reject);

		memory_free(u);
	}

	memory_free(conf);
	return true;
}


bool configuration_parseLog(Ini* ini) {
	char* value;

	if (ini == NULL) {
		logWarn("Invalid config object");
		return false;
	}

	value = ini_getKey(ini, LOG_CONFIGURATION_SECTION, LOG_LEVEL_CONFIGURATION_KEY);
	if (value != NULL)
		log_setLevel(log_str2Level(value));

	value = ini_getKey(ini, LOG_CONFIGURATION_SECTION, LOG_PROGRAM_CONFIGURATION_KEY);
	if (value != NULL)
		log_setProgram(value);

	value = ini_getKey(ini, LOG_CONFIGURATION_SECTION, LOG_DEVEL_CONFIGURATION_KEY);
	if (value != NULL)
		log_enableDevelOutput(str_toBool(value));

	value = ini_getKey(ini, LOG_CONFIGURATION_SECTION, LOG_STDOUT_CONFIGURATION_KEY);
	if (value != NULL)
		log_enableStdOutput(str_toBool(value));

	value = ini_getKey(ini, LOG_CONFIGURATION_SECTION, LOG_STDOUT_CONFIGURATION_KEY);
	if (value != NULL)
		log_enableStdOutput(str_toBool(value));

	value = ini_getKey(ini, LOG_CONFIGURATION_SECTION, LOG_OUTFILE_CONFIGURATION_KEY);
	if (value != NULL)
		log_setOutputFile(value);

	return true;
}


static bool configuration_parseUnion(Ini* ini, Configuration* conf, const char* unionName) {
	Union* unionObject = memory_new(Union, true);
	Section* section = NULL;
	char expandedPath[PATH_MAX];
	char* value;
	int i;

	str_cat(unionObject->name, unionName);
	unionObject->accept = list_new(true);
	unionObject->reject = list_new(true);
	unionObject->path[0] = '\0';

	if (ini == NULL || conf == NULL || unionName == NULL) {
		logWarn("Invalid arguments");
		return false;
	}

	section = ini_get(ini, unionName);

	if (section == NULL) {
		logWarn("there is no section for union %s", unionName);
		return false;
	}

	for (i = 0 ; i < section->keys->size ; i++) {
		char* key = (char*)list_get(section->keys, i);
		char* value = (char*)list_get(section->values, i);
		expandedPath[0] = 0;
		str_trim(key);
		str_unquote(value);

		if (str_ncmp(key, UNION_PATH_CONFIGURATION_KEY, sizeof(UNION_PATH_CONFIGURATION_KEY)) == 0) {
			if (! fs_expandPath(value, expandedPath)) {
				logWarn("Unable to expand %s", value);
				return false;
			}

			str_ncpy(unionObject->path, expandedPath, sizeof(unionObject->path));
			continue;
		}

		if (str_ncmp(key, UNION_ACCEPT_CONFIGURATION_KEY, sizeof(UNION_ACCEPT_CONFIGURATION_KEY)) == 0) {
			if (! fs_expandPath(value, expandedPath)) {
				logWarn("Unable to expand accept path %s", value);
				return false;
			}

			list_add(unionObject->accept, (Any)str_dup(expandedPath));
			continue;
		}

		if (str_ncmp(key, UNION_REJECT_CONFIGURATION_KEY, sizeof(UNION_REJECT_CONFIGURATION_KEY)) == 0) {
			if (! fs_expandPath(value, expandedPath)) {
				logWarn("Unable to expand reject path %s", value);
				return false;
			}

			list_add(unionObject->reject, (Any)str_dup(expandedPath));
			continue;
		}
	}

	if (str_len(unionObject->path) == 0) {
		logWarn("An union need a path !");
		return false;
	}

	list_add(conf->unions, (Any)unionObject);
	return true;
}


Union* configuration_getUnion(Configuration* conf, const char* unionName) {
	int i;
	Union* res = NULL;

	for (i = 0 ; i < conf->unions->size ; i++) {
		res = (Union*)list_get(conf->unions, i);
		if (str_cmp(res->name, unionName) == 0) {
			return res;
		}
	}

	return NULL;
}


static bool configuration_parseMain(Ini* ini, Configuration* conf) {
	char expandedPath[PATH_MAX];
	char* value;
	Union* u;
	int i;

	if (ini == NULL || conf == NULL) {
		logWarn("Invalid config object");
		return false;
	}

	value = ini_getKey(ini, MAIN_CONFIGURATION_SECTION, MAIN_DESTINATION_CONFIGURATION_KEY);
	if (value != NULL) {
		if (! fs_expandPath(value, expandedPath)) {
			logWarn("Unable to expand %s", value);
			return false;
		}

		str_ncpy(conf->destination_path, expandedPath, sizeof(conf->destination_path));
	}

	value = ini_getKey(ini, MAIN_CONFIGURATION_SECTION, MAIN_UNION_CONFIGURATION_KEY);
	if (value == NULL) {
		logWarn("No union found in the configuration file");
		return false;
	}

	List* unionList = str_split(value, ',');
	if (unionList == NULL || unionList->size == 0) {
		logWarn("No union declared");
		list_delete(unionList);
		return false;
	}

	for (i = 0 ; i < unionList->size ; i++) {
		char* unionName = (char*)list_get(unionList, i);
		str_trim(unionName);

		if (! configuration_parseUnion(ini, conf, unionName)) {
			logWarn("Failed to parse union %s", unionName);
			return false;
		}
	}

	value = ini_getKey(ini, MAIN_CONFIGURATION_SECTION, MAIN_BIND_CONFIGURATION_KEY);
	if (value != NULL) {
		conf->bind = str_toBool(value);
	}

	value = ini_getKey(ini, MAIN_CONFIGURATION_SECTION, MAIN_BIND_DESTINATION_CONFIGURATION_KEY);
	if (value != NULL) {
		if (value[0] == '@') {
			u = configuration_getUnion(conf, value+1);
			if (u) {
				str_cat(conf->bind_path, u->path);
			}
			else {
				logWarn("Failed to find union which match %s", value);
			}

		}
		else {
			if (! fs_expandPath(value, expandedPath)) {
				logWarn("Unable to expand %s", value);
				return false;
			}

			str_cat(conf->bind_path, expandedPath);
		}
	}

	if (conf->bind & conf->bind_path[0] == '\0') {
		logWarn("You authorize bind but there is no bind path");
	}

	return true;
}

bool configuration_parse (const char* path, Configuration* conf) {
	logDebug("Parsing configuration...");
	Ini* ini = ini_new();
	bool result;
	ini_parse(ini, path);

	result = configuration_parseLog(ini);
	if (! result) {
		logWarn("Failed to parse log configuration");
		goto end;
	}

	result = configuration_parseMain(ini, conf);
	if (! result) {
		logWarn("Failed to parse main configuration");
		goto end;
	}

end:
	ini_delete(ini);
	return result;
}

void configuration_dump (Configuration* conf) {
	logInfo("Configuration dump:");
	int i, j;

	if (conf == NULL) {
		logWarn("Configuration is empty");
		return;
	}

	if (conf->unions == NULL || conf->unions->size == 0) {
		logWarn("Invalid configuration, we need at least one path to union");
		return;
	}

	logInfo("mounted path: %s", conf->destination_path);

	for(i = 0 ; i < conf->unions->size; i++) {
		Union* u = (Union*)list_get(conf->unions, i);

		logInfo(" unionized path: %s", u->path);
		if (u->accept != NULL) {
			for(j = 0 ; j < u->accept->size; j++)
				logInfo("  accept: %s", list_get(u->accept, j));
		}

		if (u->reject != NULL) {
			for(j = 0 ; j < u->reject->size; j++)
				logInfo("  reject: %s", list_get(u->reject, j));
		}
	}
}
