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

#include "memory.h"
#include "ini.h"
#include "log.h"
#include "str.h"


void section_delete(Section* s) {
	if (s == NULL)
		return;

	list_delete(s->keys);
	list_delete(s->values);
	memory_free(s);
}


Ini* ini_new() {
	Ini* res = memory_alloc(sizeof(Ini), true);
	res->sections = list_new(false);

	return res;
}

char* ini_getKey(Ini* ini, const char* sectionName, const char* key) {
	int i;
	Section* section = NULL;

	if (ini == NULL) {
		logWarn("Invalid ini object");
		return NULL;
	}

	if (key == NULL) {
		logWarn("Key is NULL");
		return NULL;
	}

	for (i = 0 ; i < ini->sections->size ; i++) {
		Section* current_section = (Section*)list_get(ini->sections, i);
		if (current_section == NULL)
			continue;

		if (str_ncmp(current_section->name, sectionName, str_len(current_section->name)) == 0) {
			section = current_section;
			break;
		}
	}

	if ((section == NULL) || (section->keys == NULL) || (section->values == NULL))
		return NULL;

	for (i = 0 ; i < section->keys->size ; i++) {
		char* currentKey = (char*)list_get(section->keys, i);
		if (str_ncmp(key, currentKey, 255) == 0)
			return (char*)list_get(section->values, i);
	}

	return NULL;
}


bool ini_addSection(Ini* ini, const char* sectionName) {
	if (ini == NULL) {
		logWarn("Invalid ini object");
		return false;
	}

	if (sectionName == NULL) {
		logWarn("Section name is NULL");
		return false;
	}

	if (ini_get(ini, sectionName) != NULL) {
		logWarn("Section '%s' already exist", sectionName);
		return false;
	}

	Section* section = memory_new(Section, true);
	str_ncpy(section->name, sectionName, sizeof(section->name));
	section->keys = list_new(true);
	section->values = list_new(true);

	list_add(ini->sections, (Any)section);

	return true;
}


bool ini_addKey(Ini* ini, Section* section, const char* key, const char* value) {
	if (section == NULL) {
		logWarn("The last section is not valid");
		return false;
	}

	logDebug("Adding key '%s=%s' to section '%s'", key, value, section->name);
	list_add(section->keys, (Any)str_dup(key));
	list_add(section->values, (Any)str_dup(value));

	return true;
}


void ini_delete(Ini* ini) {
	int i;

	if (ini == NULL)
		return;

	for (i = 0 ; i < ini->sections->size ; i++)
		section_delete((Section*)list_get(ini->sections, i));

	list_delete(ini->sections);
}

Section* ini_get(Ini* ini, const char* section) {
	int i;

	if (ini == NULL || (ini->sections->size == 0) || (section == NULL))
		return NULL;

	for (i = 0 ; i < ini->sections->size ; i++) {
		Section* sec = (Section*)list_get(ini->sections, i);
		if (sec->name == NULL)
			return NULL;

		if (str_cmp(sec->name, section) == 0)
			return sec;
	}

	return NULL;
}

static bool ini_parseKey(Ini* ini, char* line) {
	int i = 0;
	char key[256];
	char value[1024];
	int strLen = str_len(line);
	bool keyFound = false;
	Section* section;

	key[0] = '\0';
	value[0] = '\0';

	for (i = 0; i < strLen; i++) {
		if (keyFound) {
			str_ncpy(value, (line + i), strLen - i);
			value[strLen - i] = '\0';
			break;
		}

		if ((line[i] == '=') && keyFound == false) {
			keyFound = true;
			str_ncpy(key, line, i);
			key[i] = '\0';
			continue;
		}
	}

	str_trim(key);
	str_trim(value);

	if (str_len(key) == 0) {
		logWarn("No key in line '%s'", line);
		return false;
	}

	if (str_len(value) == 0) {
		logWarn("No value in line '%s'", line);
		return false;
	}

	if (ini->sections->size == 0) {
		logWarn("There is no section to add the key");
		return false;
	}

	// Get the last section added
	section = (Section*)list_get(ini->sections, (ini->sections->size - 1));
	return ini_addKey(ini, section, key, value);
}

static bool ini_parseSection(Ini* ini, char* line) {
	if (line == NULL)
		return false;

	int strLen = str_len(line);

	if (line[0] != '[' || line[strLen - 1] != ']' || (strLen < 3)) {
		logWarn("Invalid Section syntax '%s'", line);
		return false;
	}

	char* sectionName = str_dup(line+1);
	sectionName[strLen -2] = '\0';

	logDebug("Adding section '%s'", sectionName);
	return ini_addSection(ini, sectionName);
}

bool ini_parseLine(Ini* ini, char* line) {
	str_trim(line);

	if (str_len(line) == 0)
		return true;

	switch (line[0]) {
	case ';':
	case '#':
		return true;  // This is a comment

	case '[':
		return ini_parseSection(ini, line);

	default:
		return ini_parseKey(ini, line);
	}

	return false;
}


bool ini_parse(Ini* ini, const char* filename) {
	char line[1024];
	int fd = file_open(filename);

	if (fd < 0)
		return false;

	while (file_readLine(fd, line))
		ini_parseLine(ini, line);

	return true;
}

void ini_dump(Ini* ini) {
	int i, j;
	logInfo("Ini dump");

	if (ini->sections->size == 0) {
		logInfo("Ini is empty");
		return;
	}

	for(i = 0 ; i < ini->sections->size ; i++) {
		Section* section = (Section*)list_get(ini->sections, i);
		if (section == NULL) {
			logWarn("Invalid section");
			return;
		}

		logInfo("[%s]", section->name);
		for(j = 0 ; j < section->keys->size ; j++) {
			char* key = (char*)list_get(section->keys, j);
			char* value = (char*)list_get(section->values, j);
			logInfo("\t %s = %s", key, value);
		}
	}
}

