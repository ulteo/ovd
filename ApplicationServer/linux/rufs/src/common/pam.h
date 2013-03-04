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

#ifndef _PAM
#define _PAM

#include <stdio.h>
#include <security/pam_appl.h>


typedef struct _Credential
{
  char user[256];
  char pass[256];
} Credential;

typedef struct _AuthInfo
{
  Credential user_pass;
  int session_opened;
  int did_setcred;
  struct pam_conv pamc;
  pam_handle_t* ph;
} AuthInfo;


long pam_auth(const char* module, const char* user, const char* pass);
bool pam_startSession(long handle);
bool pam_endSession(long handle);
bool pam_setEnv(long handle);

#endif  // _PAM

