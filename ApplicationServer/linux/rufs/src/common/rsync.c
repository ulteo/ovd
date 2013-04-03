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

#include "rsync.h"
#include "list.h"
#include "fs.h"
#include "str.h"
#include "sys.h"



RSync* RSync_create() {
	return memory_new(RSync, true);
}


RSync* RSync_new(char* src, char* dst, char* filter) {
	RSync* rsync = memory_new(RSync, true);
	rsync->src = str_dup(src);
	rsync->dst = str_dup(dst);
	rsync->filter = str_dup(filter);

	return rsync;
}


void Rsync_free(RSync* rsync) {
	if (! rsync)
		return;

	memory_free(rsync->src);
	memory_free(rsync->dst);
	memory_free(rsync->filter);
	memory_free(rsync->statusMessage);
	memory_free(rsync);
}


bool Rsync_sync(RSync* rsync) {
	char src[PATH_MAX];
	char dst[PATH_MAX];
	List* args = list_new(true);
	bool res;

	list_add(args, (Any)str_dup(RSYNC_PATH));
	list_add(args, (Any)str_dup("-rltD"));
	list_add(args, (Any)str_dup("--safe-links"));

	if (rsync->filter && fs_exist(rsync->filter)) {
		char arg[PATH_MAX];
		str_sprintf(arg, "--include-from=%s", rsync->filter);
		list_add(args, (Any)str_dup(arg));
	}

	str_sprintf(src, "%s/", rsync->src);
	str_sprintf(dst, "%s/", rsync->dst);

	list_add(args, (Any)str_dup(src));
	list_add(args, (Any)str_dup(dst));

	res = sys_exec(args, &rsync->status, &rsync->statusMessage, true);

	list_delete(args);

	return res;
}


void Rsync_dumpStatus(RSync* rsync) {
	if (rsync->status == 0) {
		logInfo("Rsync return no error");
		return;
	}

	logInfo("Rsync exit with the following statement '%i': %s", rsync->status, rsync->statusMessage);
}



