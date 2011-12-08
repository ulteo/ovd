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
#include <wtsapi32.h>

#include "HookDll/hookdll.h"
#include "internalWindow.h"
#include "windowUtil.h"
#include "seamlessChannel.h"
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

void CALLBACK InternalWindow_processCopyData(PCOPYDATASTRUCT data) {
	SeamlessWindow *sw = NULL;

	switch(data->dwData) {
		case HOOK_MSG_STATE:
			{
				HookMsg_State *msg = (HookMsg_State *)(data->lpData);
				SeamlessOrder_State *lastOrder = NULL;

				SeamlessChannel_sendDebug("HOOK_MSG_STATE: wnd: 0x%08lx state: %d", msg->wnd, msg->state);

				sw = getWindowFromHistory(msg->wnd);
				if (sw == NULL)
					break;

				SeamlessChannel_sendDebug("HOOK_MSG_STATE: wnd: 0x%08lx exists", msg->wnd);

				lastOrder = (SeamlessOrder_State *) SeamlessChannel_getLastOrder(SEAMLESSORDER_STATE);
				SeamlessChannel_sendDebug("HOOK_MSG_STATE: 1");

				if (lastOrder && (lastOrder->wnd == msg->wnd) && (lastOrder->state == msg->state)) {
					SeamlessChannel_sendDebug("HOOK_MSG_STATE: 2a");
					SeamlessChannel_sendAck(lastOrder->serial);
				}
				else {
					SeamlessChannel_sendDebug("HOOK_MSG_STATE: 2b");
					SeamlessChannel_sendState(msg->wnd, msg->state, 0);
				}
				SeamlessChannel_sendDebug("HOOK_MSG_STATE: end");
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

				if (msg->wnd == SeamlessChannel_getLastFocusedWindow())
					break;

				SeamlessChannel_setFocusedWindow(msg->wnd);

				SeamlessChannel_sendFocus(msg->wnd);
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
						SeamlessWindow_updateIcon(sw, msg->icon, 0);
						DeleteObject(msg->icon);
					}

					break;
				}

				if (msg->icon == NULL) {
					SeamlessChannel_sendDelIcon(msg->wnd, size, size);
					break;
				}
				
				SeamlessWindow_updateIcon(sw, msg->icon, msg->large);
			}
			break;
		case HOOK_MSG_TITLE:
			{
				HookMsg_Title *msg = (HookMsg_Title *)(data->lpData);

				sw = getWindowFromHistory(msg->wnd);
				
				if (sw == NULL){
					SeamlessWindow_create(msg->wnd);
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

					SeamlessChannel_sendTitle(sw->windows, title, 0);

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

				SeamlessChannel_sendDestroy(msg->wnd, 0);
			}
			break;
		case HOOK_MSG_POSITION:
			{
				HookMsg_Position *msg = (HookMsg_Position *)(data->lpData);

				sw = getWindowFromHistory(msg->wnd);
				if (! sw)
					break;

				SeamlessWindow_updatePosition(sw);
			}
			break;
		case HOOK_MSG_SHOW:
			{
				HookMsg_Show *msg = (HookMsg_Show *)(data->lpData);

				SeamlessWindow_create(msg->wnd);
			}
			break;
		case HOOK_MSG_DESTROYGRP:
			{
				HookMsg_DestroyGrp *msg = (HookMsg_DestroyGrp *)(data->lpData);

				SeamlessChannel_sendDestroyGrp(msg->pid, 0);
			}
			break;
		case HOOK_MSG_ZCHANGE:
			{
				HookMsg_ZChange *msg = (HookMsg_ZChange *)(data->lpData);

				sw = getWindowFromHistory(msg->wnd);
				if (! sw)
					break;

				SeamlessWindow_updateZOrder(sw);
			}
			break;
	}
}

static void
message(const char *text)
{
	MessageBox(GetDesktopWindow(), text, "SeamlessRDP Shell", MB_OK);
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

	ProcessIdToSessionId(GetCurrentProcessId(), &g_session_id);

	build_startup_procs();

	if (! SeamlessChannel_init())
		return -1;

	g_connected = is_connected();
	g_desktop_hidden = is_desktop_hidden();

	if (InternalWindow_create(instance, InternalWindow_processCopyData) == FALSE)
		SeamlessChannel_sendDebug("Failed to create seamless internal window");

	SeamlessChannel_sendHello(g_desktop_hidden ? SEAMLESS_HELLO_HIDDEN : 0);
	SeamlessChannel_sendDebug("PID: %lu", GetCurrentProcessId());

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
			SeamlessChannel_sendHello(flags);
		}

		g_connected = connected;

		if (check_counter < 0)
		{
			BOOL hidden;

			hidden = is_desktop_hidden();
			if (hidden && !g_desktop_hidden)
				SeamlessChannel_sendHide(0);
			else if (!hidden && g_desktop_hidden)
				SeamlessChannel_sendUnhide(0);

			g_desktop_hidden = hidden;

			check_counter = 5;
		}

		while (PeekMessage(&msg, NULL, 0, 0, PM_REMOVE))
		{
			TranslateMessage(&msg);
			DispatchMessage(&msg);
		}
		{
			char* line = NULL;
			int size = 0;

			while (SeamlessChannel_recv(&line) >= 0) {
				SeamlessChannel_sendDebug("Receive msg '%s'", line);
				SeamlessChannel_process(line);
			}
		}
		Sleep(100);
	}

	remove_hooks_fn();

	FreeLibrary(hookdll);

	SeamlessChannel_uninit();

	free_startup_procs();

	return 1;
}
