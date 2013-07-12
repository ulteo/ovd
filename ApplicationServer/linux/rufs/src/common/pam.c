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
#include "log.h"
#include "memory.h"
#include "pam.h"
#include "string.h"



static int verify_pam_conv(int num_msg, const struct pam_message** msg, struct pam_response** resp, void* appdata_ptr) {
	struct pam_response* reply;
	Credential* cred;
	int i;

	reply = memory_alloc(sizeof(struct pam_response) * num_msg, true);
	for (i = 0; i < num_msg; i++) {
		cred = appdata_ptr;
		reply[i].resp_retcode = PAM_SUCCESS;

		switch (msg[i]->msg_style) {

		case PAM_PROMPT_ECHO_ON:
			*(reply[i].resp) = str_dup(cred->user);
			break;

		case PAM_PROMPT_ECHO_OFF:
			*(reply[i].resp) = str_dup(cred->pass);
			break;

		default:
			logWarn("Invalid pam msg style");
			memory_free(reply);
			return PAM_CONV_ERR;
		}
	}

	*resp = reply;
	return PAM_SUCCESS;
}


long pam_auth(const char* module, const char* user, const char* pass) {
	AuthInfo* authInfo;
	int res;

	authInfo = memory_new(AuthInfo, true);
	if (pass)
		str_ncpy(authInfo->user_pass.pass, pass, 255);

	str_ncpy(authInfo->user_pass.user, user, 255);
	authInfo->pamc.conv = &verify_pam_conv;
	authInfo->pamc.appdata_ptr = &(authInfo->user_pass);

	res = pam_start(module, user, &(authInfo->pamc), &(authInfo->ph));
	if (res != PAM_SUCCESS) {
		logWarn("Failed to start pam: %s", pam_strerror(authInfo->ph, res));
		memory_free(authInfo);
		return 0;
	}

	res = pam_authenticate(authInfo->ph, 0);
	if (res != PAM_SUCCESS) {
		logWarn("Authentication failed: %s", pam_strerror(authInfo->ph, res));
		memory_free(authInfo);
		return 0;
	}

	res = pam_acct_mgmt(authInfo->ph, 0);
	if (res != PAM_SUCCESS) {
		logWarn("Failed to check account: %s", pam_strerror(authInfo->ph, res));
		memory_free(authInfo);
		return 0;
	}

	return (long)authInfo;
}


bool pam_startSession(long handle) {
	AuthInfo* authInfo = (AuthInfo*)handle;
	int res;

	res = pam_setcred(authInfo->ph, PAM_ESTABLISH_CRED);
	if (res != PAM_SUCCESS) {
		logWarn("Failed to set credential: %s", pam_strerror(authInfo->ph, res));
		return false;
	}

	authInfo->did_setcred = 1;
	res = pam_open_session(authInfo->ph, 0);
	if (res != PAM_SUCCESS) {
		logWarn("Failed to start pam session: %s", pam_strerror(authInfo->ph, res));
		return false;
	}

	authInfo->session_opened = 1;
	return true;
}




bool pam_endSession(long handle) {
	AuthInfo* authInfo = (AuthInfo*)handle;

	if (authInfo && authInfo->ph) {
		if (authInfo->session_opened)
			pam_close_session(authInfo->ph, 0);

		if (authInfo->did_setcred)
			pam_setcred(authInfo->ph, PAM_DELETE_CRED);

		pam_end(authInfo->ph, PAM_SUCCESS);
		authInfo->ph = 0;
	}

	memory_free(authInfo);
	return true;
}


bool pam_setEnv(long handle) {
	AuthInfo* authInfo = (AuthInfo*)handle;
	char** pam_envList;
	char** pam_env;

	if (authInfo == 0)
		return false;

	pam_envList = pam_getenvlist(authInfo->ph);
	if (pam_envList == NULL)
		return false;

	for (pam_env = pam_envList; *pam_env != NULL; ++pam_env) {
		putenv(*pam_env);
		memory_free(*pam_env);
	}

	memory_free(pam_envList);

	return true;
}

