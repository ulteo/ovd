/**
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
 **/
#include "sys.h"
#include <stdlib.h>
#include "memory.h"
#include "log.h"
#include <sys/types.h>
#include <sys/wait.h>
#include <errno.h>


void sys_exit(int status) {
	exit(status);
}

char* sys_getEnv(const char* name) {
	if (name == NULL)
		return NULL;

	return getenv(name);
}

pid_t sys_getPID() {
	return getpid();
}

bool sys_exec(List* args, int* status, char** message, bool wait) {
	pid_t pid = 0;
	int out[2];
	int dataLen;
	char* commandLine;
	int bufferSize = 1024;
	int totalLength = 0;


	logDebug("creating pipe");
	if (pipe(out) == -1) {
		logError("Failed to create pipe %s", str_geterror());
		return false;
	}

	commandLine = list_dumpStr(args, " ");
	logDebug("start the following command line %s", commandLine);
	memory_free(commandLine);

	pid = fork();
	if (pid == -1) {
		logWarn("Failed to create rsync processus: %s", error_str());
		list_clear(args);

		return false;
	}
	else if (pid == 0) {
		close(1);
		close(2);
		close(out[0]);
		dup2(out[1], 1);
		dup2(out[1], 2);

		execvp((char*)args->values[0], ((char**)args->values));

		logError("failed to exec command '%s': %s", (char*)args->values[0], str_geterror());
		sys_exit(0);
	}

	close(out[1]);

	if (! wait) {
		close(out[0]);
		list_clear(args);

		return true;
	}


	*message = memory_alloc(bufferSize, false);
	*status = 0;

	while (waitpid(pid, status, WNOHANG) <= 0 || (errno == EINTR))
		usleep(1000);

	while((dataLen = read(out[0], *message, (bufferSize - totalLength))) > 0) {
		totalLength += dataLen;

		if (totalLength == bufferSize) {
			*message = memory_realloc(*message, bufferSize + 1024);
			bufferSize += 1024;
		}
	}

	close(out[0]);
	list_clear(args);

	return true;
}
