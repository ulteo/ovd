/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Vincent ROULLIER <v.roullier@ulteo.com> 2013
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

#include "org_ulteo_utils_jni_LinuxSessionStatus.h"
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <jni.h>
#include <sys/un.h>
#include <arpa/inet.h>
#include <sys/socket.h>
#include <netdb.h>
#include <errno.h>
#include <unistd.h>

struct stream {
	char* p;
	char* end;
	char* data;
	int size;
};

void* g_malloc(int size, int zero) {
	char* rv;

	rv = (char*) malloc(size);
	if (zero) {
		if (rv != 0) {
			memset(rv, 0, size);
		}
	}
	return rv;
}

#define make_stream(s) \
{ \
  (s) = (struct stream*) g_malloc(sizeof(struct stream), 1); \
}

#define init_stream(s, v) \
{ \
  if ((v) > (s)->size) \
  { \
    free((s)->data); \
    (s)->data = (char*)g_malloc((v), 1); \
    (s)->size = (v); \
  } \
  (s)->p = (s)->data; \
  (s)->end = (s)->data; \
}

#define in_uint32_be(s, v) \
{ \
  (v) = *((unsigned char*)((s)->p)); \
  (s)->p++; \
  (v) <<= 8; \
  (v) |= *((unsigned char*)((s)->p)); \
  (s)->p++; \
  (v) <<= 8; \
  (v) |= *((unsigned char*)((s)->p)); \
  (s)->p++; \
  (v) <<= 8; \
  (v) |= *((unsigned char*)((s)->p)); \
  (s)->p++; \
}

/******************************************************************************/
#define out_uint32_be(s, v) \
{ \
  *((s)->p) = (unsigned char)((v) >> 24); \
  s->p++; \
  *((s)->p) = (unsigned char)((v) >> 16); \
  s->p++; \
  *((s)->p) = (unsigned char)((v) >> 8); \
  s->p++; \
  *((s)->p) = (unsigned char)(v); \
  (s)->p++; \
}

/******************************************************************************/
#define out_uint8p(s, v, n) \
{ \
  memcpy((s)->p, (v), (n)); \
  (s)->p += (n); \
}

/*****************************************************************************/
/* connect to a socket unix */
int unix_connect(const char* socket_filename) {
	int sock, len;
	struct sockaddr_un saun;

	/* Create socket */
	if ((sock = socket(AF_UNIX, SOCK_STREAM, 0)) < 0) {
		return 0;
	}
	/* Connect to server */
	saun.sun_family = AF_UNIX;
	strcpy(saun.sun_path, socket_filename);
	len = sizeof(saun.sun_family) + strlen(saun.sun_path);
	if (connect(sock, (struct sockaddr *) &saun, len) < 0) {
		close(sock);
		return 0;
	}
	return sock;
}

/*****************************************************************************/
/* returns error, zero is good */
int tcp_local_connect(int sck, const char* port) {
	struct sockaddr_un s;

	memset(&s, 0, sizeof(struct sockaddr_un));
	s.sun_family = AF_UNIX;
	strcpy(s.sun_path, port);
	return connect(sck, (struct sockaddr*) &s, sizeof(struct sockaddr_un));
}

/*****************************************************************************/
int tcp_send(int sck, const void* ptr, int len, int flags) {
	int size_send = 0;
	int res = 0;

	do {
		res = send(sck, ptr + size_send, len - size_send, flags | MSG_NOSIGNAL);
		if (res <= 0) {
			return res;
		}
		size_send += res;
		if (res != len && errno != 0) {
			return size_send;
		}
	} while (size_send < len);

	return size_send;
}

int tcp_recv(int sck, void* ptr, int len, int flags) {
	int size_read = 0;
	int res = 0;

	do {
		res = recv(sck, ptr + size_read, len - size_read,
				flags | MSG_NOSIGNAL | MSG_WAITALL);

		if (res <= 0) {
			return res;
		}
		size_read += res;
		if (size_read != len && errno != 0) {
			return size_read;
		}
	} while (size_read < len);

	return size_read;
}

#define free_stream(s) \
{ \
  if ((s) != 0) \
  { \
    free((s)->data); \
  } \
  free((s)); \
} \

JNIEXPORT jstring JNICALL Java_org_ulteo_utils_jni_LinuxSessionStatus_nGetSessionStatus(
		JNIEnv *env, jclass class) {
	char * dis;
	if ((dis = getenv("DISPLAY")) == NULL)
		fprintf(stderr, ", 'DISPLAY' environment variable not set.\n");

	char f[50] = "/var/spool/xrdp/xrdp_management";
	int number;
	sscanf(dis, ":%i.*", &number);
	char dom[128];
	sprintf(dom,
			"<?xml version=\"1.0\" encoding=\"UTF-8\" ?> <request type=\"session\" id=\"%i\" action=\"status\" />",
			number);

	int msg_len = strlen(dom);
	int rv;
	int sck = unix_connect(f);
	struct stream* st;
	make_stream(st);
	init_stream(st, msg_len + 6);
	out_uint32_be(st, msg_len);
	out_uint8p(st, dom, msg_len);
	int size = st->p - st->data;
	if ((rv = tcp_send(sck, st->data, size, 0)) < 0) {
		fprintf(stderr, "Error in sending message : %s  \n", strerror(errno));
		close(sck);
		return NULL;
	}
	struct stream* in;
	make_stream(in);
	init_stream(in, 256);
	if ((rv = tcp_recv(sck, in->data, 4, 0)) < 0) {
		fprintf(stderr, "Error in receiving message : %s \n", strerror(errno));
		close(sck);
		return NULL;;
	}
	int in_len;
	in_uint32_be(in, in_len);
	if ((rv = tcp_recv(sck, in->p, in_len, 0)) < 0) {
		fprintf(stderr, "Error in receiving message : %s \n", strerror(errno));
		close(sck);
		return NULL;
	}

	char* buf = malloc(in_len + 1);
	memcpy(buf, in->data + 4, in_len);
	buf[in_len] = '\0';
	jstring jString = (*env)->NewStringUTF(env, buf);

	close(sck);
	free(buf);
	free_stream(in);
	free_stream(st);

	return jString;
}
