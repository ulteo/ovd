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

#include <signal.h>
#include "signal.h"
#include "memory.h"



bool signal_installSIGHUPHandler(sighandler func) {
	return signal(SIGHUP, func) == SIG_ERR;
}


long signal_blockSIGHUP(long handle) {
	sigset_t* x = (sigset_t*)handle;

	if (handle == 0)
		x = memory_new(sigset_t, true);

	sigemptyset (x);
	sigaddset(x, SIGHUP);

	sigprocmask(SIG_BLOCK, x, NULL);

	return (long)x;
}


long signal_unblockSIGHUP(long handle) {
	sigset_t* x = (sigset_t*)handle;

	sigprocmask(SIG_UNBLOCK, x, NULL);

	return (long)x;
}
