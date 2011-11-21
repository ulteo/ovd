/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2011
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

#ifndef _SEAMLESS_CHANNEL_H_
#define _SEAMLESS_CHANNEL_H_

#include <windows.h>

#define SEAMLESS_CREATE_MODAL		0x0001
#define SEAMLESS_CREATE_TOPMOST		0x0002
#define SEAMLESS_CREATE_POPUP		0x0004
#define SEAMLESS_CREATE_FIXEDSIZE	0x0008
#define SEAMLESS_CREATE_TOOLTIP		0x0010

#define SEAMLESS_HELLO_RECONNECT	0x0001
#define SEAMLESS_HELLO_HIDDEN		0x0002

#define SEAMLESS_FOCUS_REQUEST      0X0000
#define SEAMLESS_FOCUS_RELEASE      0X0001

typedef struct SeamlessOrder_State_ {
	HWND wnd;
	unsigned int serial;
	int state;
} SeamlessOrder_State;

typedef struct SeamlessOrder_Position_ {
	HWND wnd;
	unsigned int serial;
	RECT bounds;
} SeamlessOrder_Position;

typedef struct SeamlessOrder_Destroy_ {
	HWND wnd;
	unsigned int serial;
} SeamlessOrder_Destroy;

typedef struct SeamlessOrder_ZChange_ {
	HWND wnd;
	HWND behindWnd;
	unsigned int serial;
} SeamlessOrder_ZChange;

typedef struct SeamlessOrder_Focus_ {
	HWND wnd;
	unsigned int serial;
} SeamlessOrder_Focus;

#define SEAMLESSORDER_STATE				0
#define SEAMLESSORDER_POSITION			1
#define SEAMLESSORDER_DESTROY			2
#define SEAMLESSORDER_ZCHANGE			3
#define SEAMLESSORDER_FOCUS				4

BOOL SeamlessChannel_init();
void SeamlessChannel_uninit();

HWND SeamlessChannel_getLastFocusedWindow();
void SeamlessChannel_setFocusedWindow(HWND hwnd);

void* SeamlessChannel_getLastOrder(int order_type);
void SeamlessChannel_setLastOrder(int order_type, void* order);

int SeamlessChannel_recv(char** line);
void SeamlessChannel_process(char* line);

void SeamlessChannel_sendAck(unsigned int serial);
void SeamlessChannel_sendCreate(HWND hwnd, unsigned long group, HWND parent, int flags);
void SeamlessChannel_sendTitle(HWND hwnd, unsigned short *title, int flags);
void SeamlessChannel_sendState(HWND hwnd, int state, int flags);
void SeamlessChannel_sendFocus(HWND hwnd);
void SeamlessChannel_sendZChange(HWND hwnd, HWND behind, int flags);
void SeamlessChannel_sendPosition(HWND hwnd, int x, int y, unsigned int width, unsigned int height, int flags);
void SeamlessChannel_sendSetIcon(HWND hwnd, int chunk, unsigned int width, unsigned int height, char *buf);
void SeamlessChannel_sendDelIcon(HWND hwnd, unsigned int width, unsigned int height);
void SeamlessChannel_sendDestroy(HWND hwnd, int flags);
void SeamlessChannel_sendDestroyGrp(unsigned long pid, int flags);
void SeamlessChannel_sendDebug(char *format, ...);
void SeamlessChannel_sendSyncBegin();
void SeamlessChannel_sendSyncEnd();
void SeamlessChannel_sendHello(int flags);
void SeamlessChannel_sendHide(int flags);
void SeamlessChannel_sendUnhide(int flags);
void SeamlessChannel_sendAppId(unsigned int token, int pid);

#endif // _SEAMLESS_CHANNEL_H_