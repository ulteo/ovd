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

#include <stdio.h>
#include <stdarg.h>

#include "seamlessChannel.h"
#include "vchannel.h"
#include "windowUtil.h"
#include "internalWindow.h"
#include "seamlessWindow.h"
#include "seamlessWindowHistory.h"

static HANDLE g_mutex_orders = NULL;
static SeamlessOrder_State* g_last_state_order = NULL;
static SeamlessOrder_Position* g_last_position_order = NULL;
static SeamlessOrder_Destroy* g_last_destroy_order = NULL;
static SeamlessOrder_ZChange* g_last_zchange_order = NULL;
static SeamlessOrder_Focus* g_last_focus_order = NULL;

static HANDLE g_mutex_focus = NULL;
static HWND g_last_focused_window = NULL;
HWND SeamlessChannel_getLastFocusedWindow() {
	HWND last_focused_window;
	
	WaitForSingleObject(g_mutex_focus, INFINITE);

	last_focused_window = g_last_focused_window;
	
	ReleaseMutex(g_mutex_focus);

	return last_focused_window;
}
void SeamlessChannel_setFocusedWindow(HWND hwnd) {
	WaitForSingleObject(g_mutex_focus, INFINITE);

	g_last_focused_window = hwnd;
	
	ReleaseMutex(g_mutex_focus);
}

BOOL SeamlessChannel_init() {
	g_mutex_orders = CreateMutex(NULL, FALSE, NULL);
	if (! g_mutex_orders)
		return FALSE;
	
	g_mutex_focus = CreateMutex(NULL, FALSE, NULL);
	if (! g_mutex_orders)
		return FALSE;

	vchannel_open();

	return TRUE;
}

void SeamlessChannel_uninit() {
	vchannel_close();

	WaitForSingleObject(g_mutex_orders, INFINITE);

	if (g_last_state_order) {
		free(g_last_state_order);
		g_last_state_order = NULL;
	}

	if (g_last_position_order) {
		free(g_last_position_order);
		g_last_position_order = NULL;
	}

	if (g_last_destroy_order) {
		free(g_last_destroy_order);
		g_last_destroy_order = NULL;
	}

	if (g_last_zchange_order) {
		free(g_last_zchange_order);
		g_last_zchange_order = NULL;
	}

	if (g_last_focus_order) {
		free(g_last_focus_order);
		g_last_focus_order = NULL;
	}

	ReleaseMutex(g_mutex_orders);

	CloseHandle(g_mutex_orders);
	g_mutex_orders = NULL;

	WaitForSingleObject(g_mutex_focus, INFINITE);
	ReleaseMutex(g_mutex_focus);
	CloseHandle(g_mutex_focus);
	g_mutex_focus = NULL;
}

void* SeamlessChannel_getLastOrder(int order_type) {
	void *global_order = NULL;
	void *order = NULL;
	SIZE_T length = 0;

	WaitForSingleObject(g_mutex_orders, INFINITE);

	switch(order_type) {
		case SEAMLESSORDER_STATE:
			global_order = g_last_state_order;
			length = sizeof(SeamlessOrder_State);
			break;
		case SEAMLESSORDER_POSITION:
			global_order = g_last_position_order;
			length = sizeof(SeamlessOrder_Position);
			break;
		case SEAMLESSORDER_DESTROY:
			global_order = g_last_destroy_order;
			length = sizeof(SeamlessOrder_Destroy);
			break;
		case SEAMLESSORDER_ZCHANGE:
			global_order = g_last_zchange_order;
			length = sizeof(SeamlessOrder_ZChange);
			break;
		case SEAMLESSORDER_FOCUS:
			global_order = g_last_focus_order;
			length = sizeof(SeamlessOrder_Focus);
			break;
		default:
			return NULL;
	}

	if (! global_order)
		return NULL;

	order = malloc(length);
	if (order)
		memcpy(order, global_order, length);

	ReleaseMutex(g_mutex_orders);

	return order;
}

void SeamlessChannel_setLastOrder(int order_type, void* order) {
	void *global_order = NULL;
	SIZE_T length = 0;

	WaitForSingleObject(g_mutex_orders, INFINITE);

	switch(order_type) {
		case SEAMLESSORDER_STATE:
			global_order = g_last_state_order;
			length = sizeof(SeamlessOrder_State);
			break;
		case SEAMLESSORDER_POSITION:
			global_order = g_last_position_order;
			length = sizeof(SeamlessOrder_Position);
			break;
		case SEAMLESSORDER_DESTROY:
			global_order = g_last_destroy_order;
			length = sizeof(SeamlessOrder_Destroy);
			break;
		case SEAMLESSORDER_ZCHANGE:
			global_order = g_last_zchange_order;
			length = sizeof(SeamlessOrder_ZChange);
			break;
		case SEAMLESSORDER_FOCUS:
			global_order = g_last_focus_order;
			length = sizeof(SeamlessOrder_Focus);
			break;
		default:
			return;
	}

	if (! order) {
		global_order = NULL;
		return;
	}

	if (sizeof(order) != length)
		return;

	if (global_order)
		free(global_order);

	global_order = malloc(length);
	if (! global_order) {
		global_order = NULL;
		return;
	}

	memcpy(global_order, order, length);
}

static BOOL CALLBACK
enum_cb(HWND hwnd, LPARAM lparam)
{
	RECT rect;
	unsigned short title[150];
	LONG styles;
	int state;
	HWND parent;
	DWORD pid;
	int flags;

	if (hwnd == InternalWindow_getHandle())
		return TRUE;

	styles = GetWindowLong(hwnd, GWL_STYLE);

	if (!(styles & WS_VISIBLE))
		return TRUE;

	if (styles & WS_POPUP)
		parent = (HWND) GetWindowLong(hwnd, GWL_HWNDPARENT);
	else
		parent = NULL;

	GetWindowThreadProcessId(hwnd, &pid);

	flags = 0;
	if (styles & DS_MODALFRAME)
		flags |= SEAMLESS_CREATE_MODAL;

	SeamlessChannel_sendCreate(hwnd, pid, parent, flags);

	if (!GetWindowRect(hwnd, &rect))
	{
		SeamlessChannel_sendDebug("GetWindowRect failed!");
		return TRUE;
	}

	SeamlessChannel_sendPosition(hwnd, rect.left, rect.top, rect.right - rect.left, rect.bottom - rect.top, 0);

	GetWindowTextW(hwnd, title, sizeof(title) / sizeof(*title));

	SeamlessChannel_sendTitle(hwnd, title, 0);

	if (styles & WS_MAXIMIZE)
		state = 2;
	else if (styles & WS_MINIMIZE)
		state = 1;
	else
		state = 0;

	SeamlessChannel_sendState(hwnd, state, 0);

	return TRUE;
}

static void SeamlessChannel_process_sync(void) {
	//vchannel_block();

	SeamlessChannel_sendSyncBegin();

	EnumWindows(enum_cb, 0);

	SeamlessChannel_sendSyncEnd();

	//vchannel_unblock();
}

static void SeamlessChannel_process_state(unsigned int serial, HWND hwnd, int state) {
	int curstate;
	SeamlessOrder_State *order = NULL;

	//vchannel_block();

	curstate = WindowUtil_getState(hwnd);

	if (state == curstate)
	{
		SeamlessChannel_sendAck(serial);
		//vchannel_unblock();
		return;
	}

	order = malloc(sizeof(SeamlessOrder_State));
	order->wnd = hwnd;
	order->state = state;
	order->serial = serial;
	SeamlessChannel_setLastOrder(SEAMLESSORDER_STATE, order);

	//vchannel_unblock();

	if (! WindowUtil_setState(hwnd, state))
		SeamlessChannel_sendDebug("Invalid state %d sent.", state);

	SeamlessChannel_setLastOrder(SEAMLESSORDER_STATE, NULL);
}

static void SeamlessChannel_process_position(unsigned int serial, HWND hwnd, int x, int y, int width, int height) {
	RECT rect;
	SeamlessOrder_Position *order = NULL;
	SeamlessWindow *sw = NULL;
	
	sw = getWindowFromHistory(hwnd);
	if (! sw)
		return;

	if (IsZoomed(sw->windows))
		return;

	order = malloc(sizeof(SeamlessOrder_Position));
	order->wnd = sw->windows;
	order->serial = serial;
	order->bounds.left = x;
	order->bounds.top = y;
	order->bounds.right = x + width;
	order->bounds.bottom = y + height;
	SeamlessChannel_setLastOrder(SEAMLESSORDER_POSITION, order);

	SetWindowPos(sw->windows, NULL, x, y, width, height, SWP_NOACTIVATE | SWP_NOZORDER);

	SeamlessChannel_sendAck(serial);

	if (!GetWindowRect(sw->windows, &rect))
		SeamlessChannel_sendDebug("GetWindowRect failed!\n");
	else if ((rect.left != x) || (rect.top != y) || (rect.right != x + width)
		 || (rect.bottom != y + height))
		SeamlessWindow_updatePosition(sw);
	else if (! IsIconic(sw->windows))
		SeamlessChannel_sendPosition(sw->windows, rect.left, rect.top, rect.right - rect.left, rect.bottom - rect.top, 0);

	SeamlessChannel_setLastOrder(SEAMLESSORDER_POSITION, NULL);
}

static void SeamlessChannel_process_zchange(unsigned int serial, HWND hwnd, HWND behind) {
	SeamlessOrder_ZChange *order = NULL;

	order = malloc(sizeof(SeamlessOrder_ZChange));
	order->wnd = hwnd;
	order->behindWnd = behind;
	order->serial = serial;
	SeamlessChannel_setLastOrder(SEAMLESSORDER_ZCHANGE, order);

	if (behind == NULL)
		behind = HWND_TOP;

	SetWindowPos(hwnd, behind, 0, 0, 0, 0, SWP_NOACTIVATE | SWP_NOMOVE | SWP_NOSIZE);

	SeamlessChannel_setLastOrder(SEAMLESSORDER_ZCHANGE, NULL);
}

static void SeamlessChannel_process_focus(unsigned int serial, HWND hwnd, int action) {
	SeamlessOrder_Focus *order = NULL;

	order = malloc(sizeof(SeamlessOrder_Focus));
	order->wnd = hwnd;
	order->serial = serial;
	SeamlessChannel_setLastOrder(SEAMLESSORDER_FOCUS, order);

	if (action == SEAMLESS_FOCUS_RELEASE ) {
		WindowUtil_setFocus(InternalWindow_getHandle());
		return;
	}
	SendMessage(InternalWindow_getHandle(), WM_KILLFOCUS, (WPARAM)hwnd, (LPARAM)NULL);
}

/* No need for locking, since this is a request rather than a message
   that needs to indicate what has already happened. */
static void SeamlessChannel_process_destroy(unsigned int serial, HWND hwnd) {
	SeamlessWindow *sw = NULL;
	SeamlessOrder_Destroy *order = NULL;

	sw = getWindowFromHistory(hwnd);
	if (! sw)
		return;

	order = malloc(sizeof(SeamlessOrder_Destroy));
	order->wnd = hwnd;
	order->serial = serial;
	SeamlessChannel_setLastOrder(SEAMLESSORDER_DESTROY, order);

	PostMessage(sw->windows, WM_CLOSE, 0, 0);
}

static void SeamlessChannel_process_spawn(char *cmdline) {
	debug("Client-to-server SPAWN orders are disabled");
	/*PROCESS_INFORMATION proc_info;
	STARTUPINFO startup_info;
	BOOL pid;
	
	memset(&startup_info, 0, sizeof(STARTUPINFO));
	startup_info.cb = sizeof(STARTUPINFO);

	pid = CreateProcess(NULL, cmdline, NULL, NULL, FALSE, 0,
			       NULL, NULL, &startup_info, &proc_info);
	if (! pid)
	{
		// CreateProcess failed.
		char msg[256];
		_snprintf(msg, sizeof(msg),
			"Unable to launch the requested application:\n%s", cmdline);
		message(msg);
		return;
	}
	// Release handles
	CloseHandle(proc_info.hProcess);
	CloseHandle(proc_info.hThread);*/
}

static void SeamlessChannel_process_start_app(char *cmdline, unsigned int token) {
	debug("Client-to-server STARTAPP orders are disabled");
	/*PROCESS_INFORMATION proc_info;
	STARTUPINFO startup_info;
	BOOL pid;
	memset(&startup_info, 0, sizeof(STARTUPINFO));
	startup_info.cb = sizeof(STARTUPINFO);

	pid = CreateProcess(NULL, cmdline, NULL, NULL, FALSE, 0,
			       NULL, NULL, &startup_info, &proc_info);
	if (! pid)
	{
		vchannel_write("APP_ID", "0x%08x,0x%08x", token, -1);
		return;
	}

	vchannel_write("APP_ID", "0x%08x,0x%08x", token, proc_info.dwProcessId);
	// Release handles
	CloseHandle(proc_info.hProcess);
	CloseHandle(proc_info.hThread);*/
}

static char* get_token(char **s) {
	char *comma, *head;
	head = *s;

	if (!head)
		return NULL;

	comma = strchr(head, ',');
	if (comma) {
		*comma = '\0';
		*s = comma + 1;
	}
	else {
		*s = NULL;
	}

	return head;
}

void SeamlessChannel_process(char* line) {
	char *p, *tok1, *tok2, *tok3, *tok4, *tok5, *tok6, *tok7, *tok8;

	p = line;

	tok1 = get_token(&p);
	tok2 = get_token(&p);
	tok3 = get_token(&p);
	tok4 = get_token(&p);
	tok5 = get_token(&p);
	tok6 = get_token(&p);
	tok7 = get_token(&p);
	tok8 = get_token(&p);

	if (strcmp(tok1, "SYNC") == 0)
		SeamlessChannel_process_sync();
	else if (strcmp(tok1, "STATE") == 0)
		SeamlessChannel_process_state(strtoul(tok2, NULL, 0), (HWND) strtoul(tok3, NULL, 0),
			 strtol(tok4, NULL, 0));
	else if (strcmp(tok1, "POSITION") == 0)
		SeamlessChannel_process_position(strtoul(tok2, NULL, 0), (HWND) strtoul(tok3, NULL, 0),
			    strtol(tok4, NULL, 0), strtol(tok5, NULL, 0),
				strtol(tok6, NULL, 0), strtol(tok7, NULL, 0));
	else if (strcmp(tok1, "ZCHANGE") == 0)
		SeamlessChannel_process_zchange(strtoul(tok2, NULL, 0), (HWND) strtoul(tok3, NULL, 0),
				(HWND) strtoul(tok4, NULL, 0));
	else if (strcmp(tok1, "FOCUS") == 0)
		SeamlessChannel_process_focus(strtoul(tok2, NULL, 0), (HWND) strtoul(tok3, NULL, 0), (int) strtol(tok4, NULL, 0));
	else if (strcmp(tok1, "SPAWN") == 0)
		SeamlessChannel_process_spawn(tok3);
	else if (strcmp(tok1, "START_APP") == 0)
		SeamlessChannel_process_start_app(tok4, strtoul(tok3, NULL, 0));
	else if (strcmp(tok1, "DESTROY") == 0)
		SeamlessChannel_process_destroy(strtoul(tok2, NULL, 0), (HWND) strtoul(tok3, NULL, 0));
}

int SeamlessChannel_recv(char** line) {
	int size;

	if (*line)
		free(*line);

	*line = malloc(sizeof(char) * VCHANNEL_MAX_LINE);

	size = vchannel_read(*line, VCHANNEL_MAX_LINE);
	if (size < 0) {
		free(*line);
		*line = NULL;
	}

	return size;
}

void SeamlessChannel_sendAck(unsigned int serial) {
	vchannel_write("ACK", "%u", serial);
}

void SeamlessChannel_sendCreate(HWND hwnd, unsigned long group, HWND parent, int flags) {
	vchannel_write("CREATE", "0x%08lx,0x%08lx,0x%08lx,0x%08x",
					(long) hwnd, group, (long) parent, flags);
}

void SeamlessChannel_sendTitle(HWND hwnd, unsigned short *title, int flags) {
	vchannel_write("TITLE", "0x%08lx,%s,0x%08x", hwnd, vchannel_strfilter_unicode(title), flags);
}

void SeamlessChannel_sendState(HWND hwnd, int state, int flags) {
	vchannel_write("STATE", "0x%08lx,0x%08x,0x%08x", hwnd, state, flags);
}

void SeamlessChannel_sendFocus(HWND hwnd) {
	vchannel_write("FOCUS", "0x%08lx", hwnd);
}

void SeamlessChannel_sendZChange(HWND hwnd, HWND behind, int flags) {
	vchannel_write("ZCHANGE", "0x%08lx,0x%08lx,0x%08x", hwnd, behind, flags);
}

void SeamlessChannel_sendPosition(HWND hwnd, int x, int y, unsigned int width, unsigned int height, int flags) {
	vchannel_write("POSITION", "0x%08lx,%d,%d,%d,%d,0x%08x", hwnd, x, y, width, height, flags);
}

void SeamlessChannel_sendSetIcon(HWND hwnd, int chunk, unsigned int width, unsigned int height, char *buf) {
	vchannel_write("SETICON", "0x%08lx,%d,RGBA,%d,%d,%s", hwnd, chunk, width, height, buf);
}

void SeamlessChannel_sendDelIcon(HWND hwnd, unsigned int width, unsigned int height) {
	vchannel_write("DELICON", "0x%08lx,RGBA,%u,%u", hwnd, width, height);
}

void SeamlessChannel_sendDestroy(HWND hwnd, int flags) {
	vchannel_write("DESTROY", "0x%08lx,0x%08x", hwnd, flags);
}

void SeamlessChannel_sendDestroyGrp(unsigned long pid, int flags) {
	vchannel_write("DESTROYGRP", "0x%08lx, 0x%08lx", pid, flags);
}

void SeamlessChannel_sendDebug(char *format, ...) {
	va_list argp;
	char buf[256];

	va_start(argp, format);
	_vsnprintf(buf, sizeof(buf), format, argp);
	va_end(argp);

	vchannel_strfilter(buf);

	debug(buf);
}

void SeamlessChannel_sendSyncBegin() {
	vchannel_write("SYNCBEGIN", "0x0");
}
void SeamlessChannel_sendSyncEnd() {
	vchannel_write("SYNCEND", "0x0");
}

void SeamlessChannel_sendHello(int flags) {
	vchannel_write("HELLO", "0x%08x", flags);
}

void SeamlessChannel_sendHide(int flags) {
	vchannel_write("HIDE", "0x%08x", flags);
}
void SeamlessChannel_sendUnhide(int flags) {
	vchannel_write("UNHIDE", "0x%08x", flags);
}

void SeamlessChannel_sendAppId(unsigned int token, int pid) {
	vchannel_write("APP_ID", "0x%08x,0x%08x", token, pid);
}