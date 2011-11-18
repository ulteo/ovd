/* -*- c-basic-offset: 8 -*-
   rdesktop: A Remote Desktop Protocol client.
   Seamless windows - Remote server executable

   Based on code copyright (C) 2004-2005 Martin Wickett

   Copyright (C) Peter Åstrand <astrand@cendio.se> 2005-2006
   Copyright (C) Pierre Ossman <ossman@cendio.se> 2006

   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program; if not, write to the Free Software
   Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*/

#define WINVER 0x0501

#include <windows.h>
#include <stdio.h>
#include <wtsapi32.h>
#include <cchannel.h>
#include <CommCtrl.h>

#include "HookDll/hookdll.h"
#include "internalWindow.h"
#include "vchannel.h"
#include "windowUtil.h"
#include "seamlessWindow.h"
#include "seamlessWindowHistory.h"

#include "resource.h"

#define APP_NAME "SeamlessRDP Shell"

/* Global data */
static HINSTANCE g_instance;

static DWORD g_session_id;
static DWORD *g_startup_procs;
static int g_startup_num_procs;

static BOOL g_connected;
static BOOL g_desktop_hidden;

typedef void (*set_hooks_proc_t) ();
typedef void (*remove_hooks_proc_t) ();
typedef int (*get_instance_count_proc_t) ();

static HWND hwnd_internal;

// blocks for locally generated events
HWND g_block_move_hwnd = NULL;
unsigned int g_block_move_serial = 0;
RECT g_block_move = { 0, 0, 0, 0 };

unsigned int g_blocked_zchange_serial = 0;
HWND g_blocked_zchange[2] = { NULL, NULL };

unsigned int g_blocked_focus_serial = 0;
HWND g_blocked_focus = NULL;

unsigned int g_blocked_focus_lost_serial = 0;
HWND g_blocked_focus_lost = NULL;

unsigned int g_blocked_state_serial = 0;
HWND g_blocked_state_hwnd = NULL;
int g_blocked_state = -1;

static HWND g_last_focused_window = NULL;

static HANDLE g_last_changes_mutex = NULL;

// Screen size
static int g_screen_width = 0;
static int g_screen_height = 0;
static void getScreenSize() {
	g_screen_width = GetSystemMetrics(SM_CXSCREEN);
	g_screen_height = GetSystemMetrics(SM_CYSCREEN);
}

static void
update_zorder(HWND hwnd)
{
	HWND behind;
	HWND block_hwnd, block_behind;
	unsigned int serial;

	WaitForSingleObject(g_last_changes_mutex, INFINITE);
	serial = g_blocked_zchange_serial;
	block_hwnd = g_blocked_zchange[0];
	block_behind = g_blocked_zchange[1];
	ReleaseMutex(g_last_changes_mutex);

	vchannel_block();

	behind = GetNextWindow(hwnd, GW_HWNDPREV);
	while (behind)
	{
		LONG style;

		style = GetWindowLong(behind, GWL_STYLE);

		if ((!(style & WS_CHILD) || (style & WS_POPUP)) && (style & WS_VISIBLE))
			break;

		behind = GetNextWindow(behind, GW_HWNDPREV);
	}

	if ((hwnd == block_hwnd) && (behind == block_behind))
		vchannel_write("ACK", "%u", serial);
	else
	{
		int flags = 0;
		LONG exstyle = GetWindowLong(hwnd, GWL_EXSTYLE);
		// handle always on top
		if (exstyle & WS_EX_TOPMOST)
			flags |= SEAMLESS_CREATE_TOPMOST;
		vchannel_write("ZCHANGE", "0x%08lx,0x%08lx,0x%08x", hwnd, behind, flags);
	}

	vchannel_unblock();
}

static void update_position(HWND hwnd) {
	RECT rect, blocked;
	HWND blocked_hwnd;
	unsigned int serial;
	SeamlessWindow *sw = NULL;

	// Check if the window exists
	sw = getWindowFromHistory(hwnd);
	if (! sw)
		goto end;

	WaitForSingleObject(g_last_changes_mutex, INFINITE);
	blocked_hwnd = g_block_move_hwnd;
	serial = g_block_move_serial;
	memcpy(&blocked, &g_block_move, sizeof(RECT));
	ReleaseMutex(g_last_changes_mutex);

	vchannel_block();

	if (IsZoomed(hwnd) || IsIconic(hwnd))
	{
		goto end;
	}
	else
	{
		if (!GetWindowRect(hwnd, &rect))
		{
			debug("GetWindowRect failed!\n");
			goto end;
		}
	}

	if ((hwnd == blocked_hwnd) && (rect.left == blocked.left) && (rect.top == blocked.top)
	    && (rect.right == blocked.right) && (rect.bottom == blocked.bottom))
		goto end;

	if ((! IsZoomed(hwnd)) && (rect.left < 0 || rect.top < 0 || rect.bottom > g_screen_height || rect.right > g_screen_width)) {
		int w = rect.right - rect.left;
		int h = rect.bottom - rect.top;
		int x = ((g_screen_width - rect.left) < w) ? (g_screen_width - w) : rect.left;
		int y = ((g_screen_height - rect.top) < h) ? (g_screen_height - h) : rect.top;
		x = (rect.left < 0) ? 0 : x;
		y = (rect.top < 0) ? 0 : y;

		SetWindowPos(hwnd, NULL, x, y, w, h, SWP_NOACTIVATE | SWP_NOZORDER);

		goto end;
	}

	// Check if the window has been moved or resized
	if (sw->bounds && EqualRect(sw->bounds, &rect))
		goto end;

	// Store the new window bounds
	if (! sw->bounds)
		sw->bounds = malloc(sizeof(RECT));
	memcpy(sw->bounds, &rect, sizeof(RECT));

	vchannel_write("POSITION", "0x%08lx,%d,%d,%d,%d,0x%08x",
		       hwnd,
		       rect.left, rect.top, rect.right - rect.left, rect.bottom - rect.top, 0);

      end:
	vchannel_unblock();
}

#define ICON_CHUNK 400

static void update_icon(HWND hwnd, HICON icon, int large) {
	int i, j, size, chunks;
	char buf[32 * 32 * 4];
	char asciibuf[ICON_CHUNK * 2 + 1];

	debug("update_icon");

	size = WindowUtil_extractIcon(icon, buf, sizeof(buf));
	if (size <= 0) {
		debug("update_icon return 1");
		return;
	}

	if ((!large && size != 16 * 16 * 4) || (large && size != 32 * 32 * 4))
	{
		debug("Unexpected icon size.");
		return;
	}

	chunks = (size + ICON_CHUNK - 1) / ICON_CHUNK;
	for (i = 0; i < chunks; i++)
	{
		for (j = 0; j < ICON_CHUNK; j++)
		{
			if (i * ICON_CHUNK + j >= size)
				break;
			sprintf(asciibuf + j * 2, "%02x",
				(int) (unsigned char) buf[i * ICON_CHUNK + j]);
		}

		vchannel_write("SETICON", "0x%08lx,%d,RGBA,%d,%d,%s", hwnd, i,
			       large ? 32 : 16, large ? 32 : 16, asciibuf);
	}
}

static void create_window(HWND hwnd){
		unsigned short *title;
		int state;
		DWORD pid;
		int flags;
		HICON icon;
		LONG exstyle;
		LONG style;
		HWND parent;
		SeamlessWindow* window;
		TCHAR classname[256];

		window = getWindowFromHistory(hwnd);
		if (window != NULL) {
			if (window->is_shown)
				return;
		}
		else {
			window = addHWDNToHistory(hwnd);
			if (window == NULL)
				return;
		}

		window->bounds = NULL;
		window->is_shown = TRUE;

		style = GetWindowLong(hwnd, GWL_STYLE);
		vchannel_write("DEBUG","NEW WINDOWS");

		exstyle = GetWindowLong(hwnd, GWL_EXSTYLE);
		GetWindowThreadProcessId(hwnd, &pid);

		parent = WindowUtil_getParent(hwnd);
		if (getWindowFromHistory(parent) == NULL)
			parent = 0;

		flags = 0;
		if (style & DS_MODALFRAME || exstyle & WS_EX_DLGMODALFRAME)
			flags |= SEAMLESS_CREATE_MODAL;

		if (((style & WS_POPUP) || (exstyle & WS_EX_TOOLWINDOW))
			&& (style & WS_MINIMIZEBOX) == 0 && (style & WS_MAXIMIZEBOX) == 0) {
			flags |= SEAMLESS_CREATE_POPUP;
			if (! parent)
				parent = 0xffffffffL;

			if (GetClassName(hwnd, classname, 256)) {
				if ((strcmp(classname, TOOLTIPS_CLASS) == 0)
					|| (strcmp(classname, "Net UI Tool Window") == 0)
					|| (strcmp(classname, "OfficeTooltip") == 0)
					|| (strcmp(classname, "DUIListViewHost") == 0)) {
					debug("%s", classname);
					flags |= SEAMLESS_CREATE_TOOLTIP;
					parent = 0xffffffffL;
				}
				else
					debug("Unknown classname: %s style: 0x%08lx exstyle: 0x%08lx", classname, style, exstyle);
			}
		}
		if (! (style & WS_SIZEBOX))
			flags |= SEAMLESS_CREATE_FIXEDSIZE;

		// handle always on top
		if (exstyle & WS_EX_TOPMOST)
			flags |= SEAMLESS_CREATE_TOPMOST;

		vchannel_write("CREATE", "0x%08lx,0x%08lx,0x%08lx,0x%08x",
					   (long) hwnd, (long) pid, (long) parent, flags);

		title = malloc(sizeof(unsigned short) * TITLE_SIZE);
		if (title != NULL) {
			GetWindowTextW(hwnd, title, TITLE_SIZE);

			vchannel_write("TITLE", "0x%08lx,%s,0x%08x", hwnd, vchannel_strfilter_unicode(title), 0);
		}
		if (window->title) {
			free(window->title);
			window->title = NULL;
		}
		window->title = title;

		icon = WindowUtil_getIcon(hwnd, 1);
		if (icon)
		{
			update_icon(hwnd, icon, 1);
			DeleteObject(icon);
		}

		state = WindowUtil_getState(hwnd);

		update_position(hwnd);
		vchannel_write("STATE", "0x%08lx,0x%08x,0x%08x", hwnd, state, 0);

		if (window->focus)
			vchannel_write("FOCUS", "0x%08lx", hwnd);
}

void CALLBACK InternalWindow_processCopyData(PCOPYDATASTRUCT data) {
	SeamlessWindow *sw = NULL;

	switch(data->dwData) {
		case HOOK_MSG_STATE:
			{
				HookMsg_State *msg = (HookMsg_State *)(data->lpData);
				int blocked;
				HWND blocked_hwnd;
				unsigned int serial;

				sw = getWindowFromHistory(msg->wnd);
				if (sw == NULL)
					break;

				WaitForSingleObject(g_last_changes_mutex, INFINITE);
				blocked_hwnd = g_blocked_state_hwnd;
				serial = g_blocked_state_serial;
				blocked = g_blocked_state;
				ReleaseMutex(g_last_changes_mutex);

				if ((blocked_hwnd == msg->wnd) && (blocked == msg->state))
					vchannel_write("ACK", "%u", serial);
				else
					vchannel_write("STATE", "0x%08lx,0x%08x,0x%08x", msg->wnd, msg->state, 0);
			}
			break;
		case HOOK_MSG_FOCUS:
			{
				HookMsg_Focus *msg = (HookMsg_Focus *)(data->lpData);

				sw = getWindowFromHistory(msg->wnd);
				if (sw == NULL) {
					sw = addHWDNToHistory(msg->wnd);
					if (sw == NULL)
						break;

					sw->focus = TRUE;
					break;
				}

				if (msg->wnd == g_last_focused_window)
					break;

				WaitForSingleObject(g_last_changes_mutex, INFINITE);
				g_last_focused_window = msg->wnd;
				ReleaseMutex(g_last_changes_mutex);

				vchannel_write("FOCUS", "0x%08lx", msg->wnd);
			}
			break;
		case HOOK_MSG_ICON:
			{
				HookMsg_Icon *msg = (HookMsg_Icon *)(data->lpData);
				int size;

				sw = getWindowFromHistory(msg->wnd);
				if (sw == NULL)
					break;

				size = (msg->large) ? 32 : 16;

				if (msg->haveToGetIcon) {
					/*
					 * Somehow, we never get WM_SETICON for the small icon.
					 * So trigger a read of it every time the large one is
					 * changed.
					 */
					msg->icon = WindowUtil_getIcon(msg->wnd, 0);
					if (msg->icon)
					{
						update_icon(msg->wnd, msg->icon, 0);
						DeleteObject(msg->icon);
					}

					break;
				}

				if (msg->icon == NULL) {
					vchannel_write("DELICON", "0x%08lx,RGBA,%i,%i", msg->wnd, size, size);
					break;
				}
				
				update_icon(msg->wnd, msg->icon, msg->large);
			}
			break;
		case HOOK_MSG_TITLE:
			{
				HookMsg_Title *msg = (HookMsg_Title *)(data->lpData);

				sw = getWindowFromHistory(msg->wnd);
				
				if (sw == NULL){
					create_window(msg->wnd);
				}
				else{
					unsigned short *title;
					BOOLEAN titleIsTheSame = TRUE;
					int i = 0;
					
					title = malloc(sizeof(unsigned short) * TITLE_SIZE);
					if (title == NULL)
						break;

					GetWindowTextW(sw->windows, title, TITLE_SIZE);

					if (sw->title != NULL) {
						for (i = 0; i < TITLE_SIZE; i++) {
							if (title[i] != sw->title[i]) {
								titleIsTheSame = FALSE;
								break;
							}
						}
					}
					else {
						titleIsTheSame = FALSE;
					}

					if (titleIsTheSame) {
						free(title);
						break;
					}

					vchannel_write("TITLE", "0x%08lx,%s,0x%08x", msg->wnd, vchannel_strfilter_unicode(title), 0);

					if (sw->title) {
						free(sw->title);
						sw->title;
					}
					sw->title = title;
				}
			}
			break;
		case HOOK_MSG_DESTROY:
			{
				HookMsg_Destroy *msg = (HookMsg_Destroy *)(data->lpData);
				
				if (! removeHWNDFromHistory(msg->wnd))
					break;

				vchannel_write("DESTROY", "0x%08lx,0x%08x", msg->wnd, 0);
			}
			break;
		case HOOK_MSG_POSITION:
			{
				HookMsg_Position *msg = (HookMsg_Position *)(data->lpData);

				update_position(msg->wnd);
			}
			break;
		case HOOK_MSG_SHOW:
			{
				HookMsg_Show *msg = (HookMsg_Show *)(data->lpData);

				create_window(msg->wnd);
			}
			break;
		case HOOK_MSG_DESTROYGRP:
			{
				HookMsg_DestroyGrp *msg = (HookMsg_DestroyGrp *)(data->lpData);

				vchannel_write("DESTROYGRP", "0x%08lx, 0x%08lx", msg->pid, 0);
			}
			break;
		case HOOK_MSG_ZCHANGE:
			{
				HookMsg_ZChange *msg = (HookMsg_ZChange *)(data->lpData);

				update_zorder(msg->wnd);
			}
			break;
	}
}

static void
message(const char *text)
{
	MessageBox(GetDesktopWindow(), text, "SeamlessRDP Shell", MB_OK);
}

static char *
get_token(char **s)
{
	char *comma, *head;
	head = *s;

	if (!head)
		return NULL;

	comma = strchr(head, ',');
	if (comma)
	{
		*comma = '\0';
		*s = comma + 1;
	}
	else
	{
		*s = NULL;
	}

	return head;
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

	if (hwnd == hwnd_internal)
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

	vchannel_write("CREATE", "0x%08lx,0x%08lx,0x%08lx,0x%08x", (long) hwnd, (long) pid,
		       (long) parent, flags);

	if (!GetWindowRect(hwnd, &rect))
	{
		debug("GetWindowRect failed!");
		return TRUE;
	}

	vchannel_write("POSITION", "0x%08lx,%d,%d,%d,%d,0x%08x",
		       hwnd,
		       rect.left, rect.top, rect.right - rect.left, rect.bottom - rect.top, 0);

	GetWindowTextW(hwnd, title, sizeof(title) / sizeof(*title));

	vchannel_write("TITLE", "0x%x,%s,0x%x", hwnd, vchannel_strfilter_unicode(title), 0);

	if (styles & WS_MAXIMIZE)
		state = 2;
	else if (styles & WS_MINIMIZE)
		state = 1;
	else
		state = 0;

	vchannel_write("STATE", "0x%08lx,0x%08x,0x%08x", hwnd, state, 0);

	return TRUE;
}

static void
do_sync(void)
{
	vchannel_block();

	vchannel_write("SYNCBEGIN", "0x0");

	EnumWindows(enum_cb, 0);

	vchannel_write("SYNCEND", "0x0");

	vchannel_unblock();
}

static void
do_state(unsigned int serial, HWND hwnd, int state)
{
	int curstate;

	vchannel_block();

	curstate = WindowUtil_getState(hwnd);

	if (state == curstate)
	{
		vchannel_write("ACK", "%u", serial);
		vchannel_unblock();
		return;
	}

	WaitForSingleObject(g_last_changes_mutex, INFINITE);
	g_blocked_state_hwnd = hwnd;
	g_blocked_state_serial = serial;
	g_blocked_state = state;
	ReleaseMutex(g_last_changes_mutex);

	vchannel_unblock();

	if (! WindowUtil_setState(hwnd, state))
		debug("Invalid state %d sent.", state);

	WaitForSingleObject(g_last_changes_mutex, INFINITE);
	g_blocked_state_hwnd = NULL;
	g_blocked_state = -1;
	ReleaseMutex(g_last_changes_mutex);
}

static void
do_position(unsigned int serial, HWND hwnd, int x, int y, int width, int height)
{
	RECT rect;

	if (IsZoomed(hwnd))
		return;

	WaitForSingleObject(g_last_changes_mutex, INFINITE);
	g_block_move_hwnd = hwnd;
	g_block_move_serial = serial;
	g_block_move.left = x;
	g_block_move.top = y;
	g_block_move.right = x + width;
	g_block_move.bottom = y + height;
	ReleaseMutex(g_last_changes_mutex);

	SetWindowPos(hwnd, NULL, x, y, width, height, SWP_NOACTIVATE | SWP_NOZORDER);

	vchannel_write("ACK", "%u", serial);

	if (!GetWindowRect(hwnd, &rect))
		debug("GetWindowRect failed!\n");
	else if ((rect.left != x) || (rect.top != y) || (rect.right != x + width)
		 || (rect.bottom != y + height))
		update_position(hwnd);
	else if (! IsIconic(hwnd))
		vchannel_write("POSITION", "0x%08lx,%d,%d,%d,%d,0x%08x", hwnd, rect.left, rect.top, rect.right - rect.left, rect.bottom - rect.top, 0);

	WaitForSingleObject(g_last_changes_mutex, INFINITE);
	g_block_move_hwnd = NULL;
	memset(&g_block_move, 0, sizeof(RECT));
	ReleaseMutex(g_last_changes_mutex);
}

static void
do_zchange(unsigned int serial, HWND hwnd, HWND behind)
{
	WaitForSingleObject(g_last_changes_mutex, INFINITE);
	g_blocked_zchange_serial = serial;
	g_blocked_zchange[0] = hwnd;
	g_blocked_zchange[1] = behind;
	ReleaseMutex(g_last_changes_mutex);

	if (behind == NULL)
		behind = HWND_TOP;

	SetWindowPos(hwnd, behind, 0, 0, 0, 0, SWP_NOACTIVATE | SWP_NOMOVE | SWP_NOSIZE);

	WaitForSingleObject(g_last_changes_mutex, INFINITE);
	g_blocked_zchange[0] = NULL;
	g_blocked_zchange[1] = NULL;
	ReleaseMutex(g_last_changes_mutex);
}

static void
do_focus(unsigned int serial, HWND hwnd, int action)
{
	if (action == SEAMLESS_FOCUS_RELEASE ) {
		WindowUtil_setFocus(hwnd_internal);
		return;
	}
	SendMessage(hwnd_internal, WM_KILLFOCUS, (WPARAM)hwnd, (LPARAM)NULL);
}

/* No need for locking, since this is a request rather than a message
   that needs to indicate what has already happened. */
static void
do_destroy(HWND hwnd)
{
	PostMessage(hwnd, WM_CLOSE, 0, 0);
}

static void
do_spawn(char *cmdline)
{
	PROCESS_INFORMATION proc_info;
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
	CloseHandle(proc_info.hThread);
}

static void
do_start_app(char *cmdline, unsigned int token)
{
	PROCESS_INFORMATION proc_info;
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
	CloseHandle(proc_info.hThread);
}

static void
process_cmds(void)
{
	char line[VCHANNEL_MAX_LINE];
	int size;

	char *p, *tok1, *tok2, *tok3, *tok4, *tok5, *tok6, *tok7, *tok8;

	while ((size = vchannel_read(line, sizeof(line))) >= 0)
	{
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
			do_sync();
		else if (strcmp(tok1, "STATE") == 0)
			do_state(strtoul(tok2, NULL, 0), (HWND) strtoul(tok3, NULL, 0),
				 strtol(tok4, NULL, 0));
		else if (strcmp(tok1, "POSITION") == 0)
			do_position(strtoul(tok2, NULL, 0), (HWND) strtoul(tok3, NULL, 0),
				    strtol(tok4, NULL, 0), strtol(tok5, NULL, 0), strtol(tok6, NULL,
											 0),
				    strtol(tok7, NULL, 0));
		else if (strcmp(tok1, "ZCHANGE") == 0)
			do_zchange(strtoul(tok2, NULL, 0), (HWND) strtoul(tok3, NULL, 0),
				   (HWND) strtoul(tok4, NULL, 0));
		else if (strcmp(tok1, "FOCUS") == 0)
			do_focus(strtoul(tok2, NULL, 0), (HWND) strtoul(tok3, NULL, 0), (int) strtol(tok4, NULL, 0));
		else if (strcmp(tok1, "SPAWN") == 0)
			do_spawn(tok3);
		else if (strcmp(tok1, "START_APP") == 0)
			do_start_app(tok4, strtoul(tok3, NULL, 0));
		else if (strcmp(tok1, "DESTROY") == 0)
			do_destroy((HWND) strtoul(tok3, NULL, 0));
	}
}

static BOOL
build_startup_procs(void)
{
	PWTS_PROCESS_INFO pinfo;
	DWORD i, j, count;

	if (!WTSEnumerateProcesses(WTS_CURRENT_SERVER_HANDLE, 0, 1, &pinfo, &count))
		return FALSE;

	g_startup_num_procs = 0;

	for (i = 0; i < count; i++)
	{
		if (pinfo[i].SessionId != g_session_id)
			continue;

		g_startup_num_procs++;
	}

	g_startup_procs = malloc(sizeof(DWORD) * g_startup_num_procs);

	j = 0;
	for (i = 0; i < count; i++)
	{
		if (pinfo[i].SessionId != g_session_id)
			continue;

		g_startup_procs[j] = pinfo[i].ProcessId;
		j++;
	}

	WTSFreeMemory(pinfo);

	return TRUE;
}

static void
free_startup_procs(void)
{
	free(g_startup_procs);

	g_startup_procs = NULL;
	g_startup_num_procs = 0;
}

static BOOL
should_terminate(void)
{
	PWTS_PROCESS_INFO pinfo;
	DWORD i, j, count;

	if (!WTSEnumerateProcesses(WTS_CURRENT_SERVER_HANDLE, 0, 1, &pinfo, &count))
		return TRUE;

	for (i = 0; i < count; i++)
	{
		if (pinfo[i].SessionId != g_session_id)
			continue;

		for (j = 0; j < g_startup_num_procs; j++)
		{
			if (pinfo[i].ProcessId == g_startup_procs[j])
				break;
		}

		if (j == g_startup_num_procs)
		{
			WTSFreeMemory(pinfo);
			return FALSE;
		}
	}

	WTSFreeMemory(pinfo);

	return TRUE;
}

static BOOL
is_connected(void)
{
	BOOL res;
	INT *state;
	DWORD size;

	res = WTSQuerySessionInformation(WTS_CURRENT_SERVER_HANDLE,
					 WTS_CURRENT_SESSION, WTSConnectState, (LPTSTR *) & state,
					 &size);
	if (!res)
		return TRUE;

	res = *state == WTSActive;

	WTSFreeMemory(state);

	return res;
}

static BOOL
is_desktop_hidden(void)
{
	HDESK desk;

	/* We cannot get current desktop. But we can try to open the current
	   desktop, which will most likely be a secure desktop (if it isn't
	   ours), and will thus fail. */
	desk = OpenInputDesktop(0, FALSE, GENERIC_READ);
	if (desk)
		CloseDesktop(desk);

	return desk == NULL;
}

int WINAPI
WinMain(HINSTANCE instance, HINSTANCE prev_instance, LPSTR cmdline, int cmdshow)
{
	HMODULE hookdll;
	MSG msg;
	int check_counter;

	set_hooks_proc_t set_hooks_fn;
	remove_hooks_proc_t remove_hooks_fn;
	get_instance_count_proc_t instance_count_fn;

	g_instance = instance;

	hookdll = LoadLibrary("seamlessrdp.dll");
	if (!hookdll)
	{
		message("Could not load hook DLL. Unable to continue.");
		return -1;
	}

	set_hooks_fn = (set_hooks_proc_t) GetProcAddress(hookdll, "SetHooks");
	remove_hooks_fn = (remove_hooks_proc_t) GetProcAddress(hookdll, "RemoveHooks");
	instance_count_fn = (get_instance_count_proc_t) GetProcAddress(hookdll, "GetInstanceCount");

	if (!set_hooks_fn || !remove_hooks_fn || !instance_count_fn)
	{
		FreeLibrary(hookdll);
		message("Hook DLL doesn't contain the correct functions. Unable to continue.");
		return -1;
	}

	/* Check if the DLL is already loaded */
	if (instance_count_fn() != 1)
	{
		FreeLibrary(hookdll);
		message("Another running instance of Seamless RDP detected.");
		return -1;
	}

	getScreenSize();

	// Init mutex
	g_last_changes_mutex = CreateMutex(NULL, FALSE, "Local\\SeamlessDLL");

	ProcessIdToSessionId(GetCurrentProcessId(), &g_session_id);

	build_startup_procs();

	vchannel_open();

	g_connected = is_connected();
	g_desktop_hidden = is_desktop_hidden();

	if (InternalWindow_create(instance, InternalWindow_processCopyData) == FALSE)
		debug("Failed to create seamless internal window");
	hwnd_internal = InternalWindow_getHandle();

	vchannel_write("HELLO", "0x%08x", g_desktop_hidden ? SEAMLESS_HELLO_HIDDEN : 0);
	debug("PID: %lu", GetCurrentProcessId());

	set_hooks_fn();

	/* Since we don't see the entire desktop we must resize windows
	   immediatly. */
	SystemParametersInfo(SPI_SETDRAGFULLWINDOWS, TRUE, NULL, 0);

	/* Disable screen saver since we cannot catch its windows. */
	SystemParametersInfo(SPI_SETSCREENSAVEACTIVE, FALSE, NULL, 0);

	/* We don't want windows denying requests to activate windows. */
	SystemParametersInfo(SPI_SETFOREGROUNDLOCKTIMEOUT, 0, 0, 0);


	check_counter = 5;
	while (1)
	{
		BOOL connected;

		connected = is_connected();
		if (connected && !g_connected)
		{
			int flags;
			/* These get reset on each reconnect */
			SystemParametersInfo(SPI_SETDRAGFULLWINDOWS, TRUE, NULL, 0);
			SystemParametersInfo(SPI_SETSCREENSAVEACTIVE, FALSE, NULL,
					     0);
			SystemParametersInfo(SPI_SETFOREGROUNDLOCKTIMEOUT, 0, 0, 0);

			flags = SEAMLESS_HELLO_RECONNECT;
			if (g_desktop_hidden)
				flags |= SEAMLESS_HELLO_HIDDEN;
			vchannel_write("HELLO", "0x%08x", flags);
		}

		g_connected = connected;

		if (check_counter < 0)
		{
			BOOL hidden;

			hidden = is_desktop_hidden();
			if (hidden && !g_desktop_hidden)
				vchannel_write("HIDE", "0x%08x", 0);
			else if (!hidden && g_desktop_hidden)
				vchannel_write("UNHIDE", "0x%08x", 0);

			g_desktop_hidden = hidden;

			check_counter = 5;
		}

		while (PeekMessage(&msg, NULL, 0, 0, PM_REMOVE))
		{
			TranslateMessage(&msg);
			DispatchMessage(&msg);
		}
		process_cmds();
		Sleep(100);
	}

	remove_hooks_fn();

	FreeLibrary(hookdll);

	vchannel_close();

	free_startup_procs();

	// Close mutex
	CloseHandle(g_last_changes_mutex);

	return 1;
}
