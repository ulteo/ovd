/*
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2013
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

#include "shares.h"
#include "common/log.h"
#include "common/memory.h"
#include "common/str.h"
#include "common/signal.h"
#include <pthread.h>



static ShareList* shareList;
static pthread_mutex_t shareMutex;


ShareList* shares_getInstance() {
	return shareList;
}


void shares_init(Configuration* conf) {
	shareList = memory_new(ShareList, true);
	shareList->needReload = true;
	shareList->list = list_new(false);
	shareList->shareFile = str_dup(conf->shareFile);
	shareList->shareDirectory = str_dup(conf->source_path);
	shareList->shareGrace = conf->shareGrace;

	pthread_mutex_init(&shareMutex, NULL);
}


void shares_clearList() {
	if ((shareList == NULL) || (shareList->list == NULL))
		return;

	int i;
	Share* s;
	List* l = shareList->list;

	for (i = 0 ; i < l->size; i++) {
		s = (Share*)list_get(l, 0);
		memory_free(s->name);
		list_remove(l, 0);
	}
}


void shares_delete() {
	int i;
	List* l;
	Share* s;

	if ((shareList == NULL) || (shareList->list == NULL))
		return;

	l = shareList->list;

	for (i = 0 ; i < l->size; i++) {
		s = (Share*)list_get(l, 0);
		memory_free(s->name);
		memory_free(s->path);
		list_remove(l, 0);
	}

	list_delete(l);
	memory_free(shareList->shareFile);
	memory_free(shareList->shareDirectory);
	memory_free(shareList);
	shareList = NULL;

	pthread_mutex_destroy(&shareMutex);
}


void shares_parse(char* line) {
	List* l;
	Share* s;
	char* strSpaceUsed = NULL;
	char* strQuota = NULL;

	if (line == NULL)
		return;

	str_trim(line);

	if (str_len(line) == 0 || line[0] == ';' || line[0] == '#')
		return;

	l = str_split(line, ',');

	if (l->size != 2) {
		logWarn("Share entry '%s' is not well formated", line);
		return;
	}

	s = memory_new(Share, true);

	s->name = str_dup((char*)list_get(l, 0));
	s->quota = str_toSize((char*)list_get(l, 1));
	s->path = fs_join(shareList->shareDirectory, s->name);
	s->spaceUsed = fs_getSpace(s->path);

	strSpaceUsed = str_fromSize(s->spaceUsed);
	strQuota = str_fromSize(s->quota);

	logDebug("new share %s: %s/%s", s->name, strSpaceUsed, strQuota);
	memory_free(strSpaceUsed);
	memory_free(strQuota);


	list_add(shareList->list, (Any)s);
}


bool shares_reload() {
	pthread_mutex_lock(&shareMutex);
	int fd;
	char* shareFilename = shareList->shareFile;
	char line[PATH_MAX];

	logDebug("share reload");

	if (shareFilename == NULL) {
		logWarn("There is no share list to reload");
		pthread_mutex_unlock(&shareMutex);
		return false;
	}

	if (!fs_exist(shareFilename)) {
		logWarn("The share list file '%s' do not exist", shareFilename);
		pthread_mutex_unlock(&shareMutex);
		return false;
	}

	shares_clearList();

	fd = file_open(shareFilename);

	while (file_readLine(fd, line)) {
		shares_parse(line);
	}

	pthread_mutex_unlock(&shareMutex);
	return true;
}


void shares_signalReload(int sig) {
	long handle = signal_blockSIGHUP(0);
	shares_reload();
	signal_unblockSIGHUP(handle);

	memory_free((void*)handle);
}


void shares_dump() {
	int i;
	Share* s;
	List* l;

	if (shareList == NULL)
		return;

	pthread_mutex_lock(&shareMutex);

	l = shareList->list;

	logInfo("Shares dump");
	for (i = 0 ; i < l->size; i++) {
		s = (Share*)list_get(l, i);
		logInfo("\t %s: quota:%lli", s->name, s->quota);
	}

	pthread_mutex_unlock(&shareMutex);
}


static Share* shares_get(const char* name) {
	int i;
	Share* s;
	List* l;

	l = shareList->list;

	for (i = 0 ; i < l->size; i++) {
		s = (Share*)list_get(l, i);

		if (str_cmp(name, s->name) == 0)
			return s;
	}

	return NULL;
}


bool shares_activated(const char* name) {
	Share* s;
	bool res = true;

	if (shareList == NULL)
		return true;

	pthread_mutex_lock(&shareMutex);
	s = shares_get(name);

	if (s == NULL)
		res = false;

	pthread_mutex_unlock(&shareMutex);
	return res;
}


long long shares_getQuota(const char* name) {
	Share* s;
	long long quota;

	if (shareList == NULL)
		return true;

	pthread_mutex_lock(&shareMutex);

	s = shares_get(name);
	if (s == NULL)
		quota = -1;
	else
		quota = s->quota;

	pthread_mutex_unlock(&shareMutex);
	return quota;
}


long long shares_getSpaceUsed(const char* name) {
	Share* s;
	long long spaceUsed;

	if (shareList == NULL)
		return true;

	pthread_mutex_lock(&shareMutex);

	s = shares_get(name);
	if (s == NULL)
		spaceUsed = -1;
	else
		spaceUsed = s->spaceUsed;

	pthread_mutex_unlock(&shareMutex);
	return spaceUsed;
}


bool shares_quotaExceed(const char* name) {
	Share* s;
	bool res;

	if (shareList == NULL)
		return false;

	pthread_mutex_lock(&shareMutex);

	s = shares_get(name);
	if (s == NULL || s->quota == 0)
		res = false;
	else {
		res = s->spaceUsed > (s->quota + shareList->shareGrace);

		logDebug("check exceed space used(%lli) quota(%lli)", s->spaceUsed, (s->quota + shareList->shareGrace));
	}

	pthread_mutex_unlock(&shareMutex);

	return res;
}


void shares_updateSpace(const char* name, long size) {
	Share* s;

	if (shareList == NULL)
		return;

	pthread_mutex_lock(&shareMutex);

	s = shares_get(name);
	if (s != NULL) {
		logDebug("space of %s increased by %li", name, size);
		s->spaceUsed += size;
	}

	pthread_mutex_unlock(&shareMutex);
}
