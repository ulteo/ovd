 /*
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2012, 2013
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
#include "user.h"
#include "str.h"
#include "pam.h"
#include "log.h"
#include <pwd.h>



bool user_getINFO(const char* user, int* gid, int* uid, char* shell, char* dir) {
	struct passwd* pwd = getpwnam(user);

	if (pwd == 0)
		return false;

	if (gid != 0)
		*gid = pwd->pw_gid;

	if (uid != 0)
		*uid = pwd->pw_uid;

	if (dir != 0)
		str_cpy(dir, pwd->pw_dir);

	if (shell != 0)
		str_cpy(shell, pwd->pw_shell);

	return true;
}


bool user_setGroup(const char* user, gid_t gid) {
	if (initgroups(user, gid) < 0) {
		logError("Failed to set groups for user %s: %s", user, str_geterror());
		return false;
	}

	return true;
}


bool user_setGID(gid_t gid) {
	if (setgid(gid) < 0) {
		logError("Failed to set gid: %s", str_geterror());
		return false;
	}

	return true;
}


bool user_setUID(uid_t uid) {
	if (setuid(uid) < 0) {
		logError("Failed to set uid: %s", str_geterror());
		return false;
	}

	return true;
}


bool user_switch(const char* user, const char* pass) {
	char shell[256];
	char homedir[256];
	long handle;
	gid_t gid;
	uid_t uid;

	if (user == NULL) {
		logError("Unable to start a pam session with a null user");
		return false;
	}

	handle = pam_auth("su", user, pass);
	if (handle == 0) {
		logWarn("Authentication failed for user '%s'", user);
		return false;
	}

	if (!pam_startSession(handle)) {
		logWarn("Failed to start a session with the user '%s'", user);
		return false;
	}

	if (! pam_setEnv(handle)) {
		logWarn("Failed to customize user environment for the user '%s'", user);
		return false;
	}

	if (! user_getINFO(user, &gid, &uid, shell, homedir)) {
		logError("Unable to get user information");
		return false;
	}

	setenv("SHELL", shell);
	setenv("PATH", "/bin:/usr/bin:/usr/X11R6/bin:/usr/local/bin");
	setenv("USER", user);
	setenv("HOME", homedir);

	return (user_setGID(gid) && user_setGroup(user, gid) && fs_setCurrentDir(homedir) && user_setUID(uid));
}

