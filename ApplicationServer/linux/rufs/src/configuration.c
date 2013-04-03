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
#include "common/regexp.h"


Configuration* configuration_new() {
	Configuration* conf = memory_new(Configuration, true);
	if (conf == NULL)
		return NULL;

	conf->unions = list_new(false);
	conf->translations = list_new(true);
	conf->bind = false;
	conf->bind_path[0] = '\0';
	conf->shareFile = NULL;
	
	return conf;
}


bool configuration_free(Configuration* conf) {
	int i;

	if (conf == NULL)
		return false;

	if (conf->unions == NULL) {
		logWarn("Invalid configuration, we need at least one path to union");
		return false;
	}

	logInfo("mounted path: %s", conf->destination_path);

	for(i = 0 ; i < conf->unions->size; i++) {
		Union* u = (Union*)list_get(conf->unions, i);

		list_delete(u->accept);
		list_delete(u->reject);

		memory_free(u);
	}

	list_delete(conf->translations);
	memory_free(conf->shareFile);

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
	str_unquote(value);
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
	unionObject->rsync_src[0] = '\0';
	unionObject->rsync_filter_filename[0] = '\0';

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

			// Check if the path is relative
			if ((str_len(expandedPath) == 0) || (expandedPath[0] != '/')) {
				if (str_endWith(conf->source_path, "/"))
					str_sprintf(unionObject->path, "%s/%s", conf->source_path, expandedPath);
				else
					str_sprintf(unionObject->path, "%s%s", conf->source_path, expandedPath);
			}
			else
				str_ncpy(unionObject->path, expandedPath, sizeof(unionObject->path));

			fs_mkdir(unionObject->path);
			if (!fs_exist(unionObject->path)) {
				logWarn("%s do not exist and can not be created", unionObject->path);
				return false;
			}

			continue;
		}

		if (str_ncmp(key, UNION_ACCEPT_CONFIGURATION_KEY, sizeof(UNION_ACCEPT_CONFIGURATION_KEY)) == 0) {
			if (! fs_expandPath(value, expandedPath)) {
				logWarn("Unable to expand accept path %s", value);
				return false;
			}

			if (str_len(value) > 0) {
				Regexp* reg = regexp_create(expandedPath);
				if (!reg) {
					logWarn("Invalid accept close in union.");
					regexp_delete(reg);
					return false;
				}

				list_add(unionObject->accept, (Any)reg);
			}
			continue;
		}

		if (str_ncmp(key, UNION_REJECT_CONFIGURATION_KEY, sizeof(UNION_REJECT_CONFIGURATION_KEY)) == 0) {
			if (! fs_expandPath(value, expandedPath)) {
				logWarn("Unable to expand reject path %s", value);
				return false;
			}

			if(str_len(value) > 0) {
				Regexp* reg = regexp_create(expandedPath);
				if (!reg) {
					logWarn("Invalid reject close in union.");
					regexp_delete(reg);
					return false;
				}

				list_add(unionObject->reject, (Any)reg);
			}
			continue;
		}

		if (str_ncmp(key, UNION_RSYNC_CONFIGURATION_KEY, sizeof(UNION_RSYNC_CONFIGURATION_KEY)) == 0) {
			if (! fs_expandPath(value, expandedPath)) {
				logWarn("Unable to expand rsync value path %s", value);
				return false;
			}

			// Check if the path is relative
			if ((str_len(expandedPath) == 0) || (expandedPath[0] != '/')) {
				if (str_endWith(conf->source_path, "/"))
					str_sprintf(unionObject->rsync_src, "%s/%s", conf->source_path, expandedPath);
				else
					str_sprintf(unionObject->rsync_src, "%s%s", conf->source_path, expandedPath);
			}
			else
				str_ncpy(unionObject->rsync_src, expandedPath, sizeof(unionObject->rsync_src));

			continue;
		}

		if (str_ncmp(key, UNION_RFILTER_CONFIGURATION_KEY, sizeof(UNION_RFILTER_CONFIGURATION_KEY)) == 0) {
			if (! fs_expandPath(value, unionObject->rsync_filter_filename)) {
				logWarn("Unable to expand rsync value path %s", value);
				return false;
			}

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


	value = ini_getKey(ini, MAIN_CONFIGURATION_SECTION, MAIN_SHARE_LIST_CONFIGURATION_KEY);
	if (value != NULL) {
		str_unquote(value);
		conf->shareFile = str_dup(value);
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


static bool configuration_parseTranslation(Ini* ini, Configuration* conf) {
	Translation* trans;
	char* key;
	char* value;
	Section* sec;
	int i;

	if (ini == NULL || conf == NULL) {
		logWarn("Invalid config object");
		return false;
	}

	sec = ini_get(ini, TRANS_CONFIGURATION_SECTION);
	if (sec == NULL)
		return false;

	for(i = 0 ; i < sec->keys->size ; i++) {
		trans = memory_new(Translation, true);
		char* key = (char*)list_get(sec->keys, i);
		char* value = (char*)list_get(sec->values, i);
		str_unquote(value);

		str_cpy(trans->in, key);
		fs_expandPath(value, trans->out);
		list_add(conf->translations, (Any)trans);
	}

	return true;
}


bool configuration_parse (Configuration* conf) {
	logDebug("Parsing configuration...");
	Ini* ini = ini_new();
	bool result;

	if (conf->configFile == NULL)
		conf->configFile = str_dup(DEFAULT_CONFIGURATION_PATH);

	ini_parse(ini, conf->configFile);

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

	if (! configuration_parseTranslation(ini, conf))
		logDebug("No translation found");

end:
	ini_delete(ini);
	return result;
}

void configuration_dump (Configuration* conf) {
	logInfo("Configuration dump:");
	Translation* trans;
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

	if (conf->shareFile)
		logInfo("\t shares files %s", conf->shareFile);


	if ( conf->translations->size > 0) {
		logInfo("Translation information");
		for(i = 0 ; i < conf->translations->size; i++) {
			trans = (Translation*)list_get(conf->translations, i);
			logInfo("\t %s => %s", trans->in, trans->out);
		}
	}

	for(i = 0 ; i < conf->unions->size; i++) {
		Union* u = (Union*)list_get(conf->unions, i);

		logInfo(" unionized path: %s", u->path);
		if (u->rsync_src[0]) {
			logInfo("  rsync src: %s", u->rsync_src);
			logInfo("  rsync filter filename: %s", u->rsync_filter_filename);
		}

		if (u->accept != NULL) {
			for(j = 0 ; j < u->accept->size; j++) {
				Regexp* reg = (Regexp*)list_get(u->accept, j);
				logInfo("  accept: %s", reg->expression);
			}
		}

		if (u->reject != NULL) {
			for(j = 0 ; j < u->reject->size; j++) {
				Regexp* reg = (Regexp*)list_get(u->reject, j);
				logInfo("  reject: %s", reg->expression);
			}
		}
	}
}
