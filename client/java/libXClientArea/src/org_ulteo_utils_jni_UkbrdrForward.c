/**
 * Copyright (C) 2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Alexandre CONFIANT-LATOUT <a.confiant@ulteo.com> 2014
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

#include "org_ulteo_utils_jni_UkbrdrForward.h"
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <jni.h>
#include <arpa/inet.h>
#include <sys/socket.h>
#include <sys/un.h>
#include <netdb.h>
#include <errno.h>
#include <unistd.h>
#include <stdint.h>

/* ukbrdr protocol */

#ifndef _WIN32
	#define PACK( __Declaration__ )  __Declaration__ __attribute__((__packed__))
#else
	#define PACK( __Declaration__ ) __pragma( pack(push, 1)) __Declaration__ __pragma( pack(pop)    )
#endif

#define UKB_VERSION 1
typedef unsigned char  __u8;
typedef unsigned short __u16;
typedef unsigned int   __u32;


enum message_type {
 UKB_INIT = 0,
 UKB_CARET_POS,
 UKB_IME_STATUS,
 UKB_PUSH_TEXT,
 UKB_PUSH_COMPOSITION,
};

PACK(
struct ukb_header {
 __u16 type;
 __u16 flags;
 __u32 len;
};)

PACK(
struct ukb_init {
 __u16 version;
};)

PACK(
struct ukb_caret_pos {
 __u32 x;
 __u32 y;
};)

PACK(
struct ukb_ime_status {
 __u8 state;
};)

PACK(
struct ukb_push_text {
 __u16 text_len;
 // data
};)

PACK(
struct ukb_update_composition {
 __u16 text_len;
 // data
};)

PACK(
struct ukb_msg {
 struct ukb_header header;

 union {
 struct ukb_init init;
 struct ukb_caret_pos caret_pos;
 struct ukb_ime_status ime_status;
 struct ukb_push_text push_text;
 struct ukb_update_composition update_composition;
 } u;
};)


int tcp_send(int sck, const void* ptr, int len, int flags);
int unix_connect(const char* socket_filename);

/*****************************************************************************/
JNIEXPORT void JNICALL Java_org_ulteo_utils_jni_UkbrdrForward_setIMEPosition(JNIEnv *env, jclass class, jint x, jint y) {
	static int sck = 0;
	char *display;
	char buffer[250];
	int num;
	int rv;
	struct ukb_msg message;

	if ((display = getenv("DISPLAY")) == NULL) {
		fprintf(stderr, ", 'DISPLAY' environment variable not set.\n");
  }

	sscanf(display, ":%d", &num);
	snprintf(buffer, 250, "/var/spool/xrdp/%d/ukbrdr_internal_client", num);

	if(sck == 0) {
		sck = unix_connect(buffer);
		if(sck != 0) {
			printf("Connected to ukbrdr_internal_client socket\n");
		} else {
			return;
		}
  }

	message.header.type = UKB_CARET_POS;
	message.header.flags = 0;
	message.header.len = sizeof(struct ukb_caret_pos);
	message.u.caret_pos.x = x;
	message.u.caret_pos.y = y;

	printf("New position : %d, %d\n", x, y);

	if ((rv = tcp_send(sck, (char*) &message, sizeof(struct ukb_header) + message.header.len, 0)) < 0) {
		fprintf(stderr, "Error in sending ukbrdr imeCaretPosition. (%s)\n", strerror(errno));
		close(sck);
		return;
	}

	close(sck);
}
