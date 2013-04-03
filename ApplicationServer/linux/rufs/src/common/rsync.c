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
#include "sys.h"



RSync* RSync_create() {
	return memory_new(RSync, true);
}


void Rsync_delete(RSync* rsync) {
	if (! rsync)
		return;

	memory_free(rsync->src);
	memory_free(rsync->dst);
	memory_free(rsync->includeFilter);
	memory_free(rsync->excludeFilter);
	memory_free(rsync->statusMessage);
	memory_free(rsync);
}


bool Rsync_INSync(RSync* rsync) {
	char src[PATH_MAX];
	char dst[PATH_MAX];
	List* args = list_new(true);

	list_add(args, str_dup(RSYNC_PATH));
	list_add(args, str_dup("-rltD"));
	list_add(args, str_dup("--safe-links"));

	if (rsync->includeFilter && fs_exist(rsync->includeFilter)) {
		const char arg[PATH_MAX];
		str_sprintf("--include-from=%s", rsync->includeFilter);
		list_add(args, str_dup(arg));
	}

	if (rsync->excludeFilter && fs_exist(rsync->excludeFilter)) {
		const char arg[PATH_MAX];
		str_sprintf("--exclude-from=%s", rsync->excludeFilter);
		list_add(args, str_dup(arg));
	}

	str_sprintf(src, "\"%s\"", rsync->src);
	str_sprintf(dst, "\"%s\"", rsync->dst);

	list_add(args, str_dup(src));
	list_add(args, str_dup(dst));

	if (sys_exec(args, &rsync->status, &rsync->statusMessage, true))
		return false;

	Rsync_dumpStatus(rsync);
	memory_free(rsync->statusMessage);

	return true;
}


bool Rsync_OUTSync(RSync* rsync) {
	return true;
}


void Rsync_dumpStatus(RSync* rsync) {
	if (rsync->status == 0) {
		logInfo("Rsync return no error");
		return;
	}

	logInfo("Rsync exit with the following statement '%i': %s", rsync->status, rsync->statusMessage);
}



