/* -*- c-basic-offset: 8 -*-
   rdesktop: A Remote Desktop Protocol client.
   Seamless windows - Remote server hook DLL

   Based on code copyright (C) 2004-2005 Martin Wickett

   Copyright 2005-2008 Peter Åstrand <astrand@cendio.se> for Cendio AB
   Copyright 2006-2008 Pierre Ossman <ossman@cendio.se> for Cendio AB

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
/*
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2012
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

#include <windows.h>

#include "hookdll.h"
#include "../windowUtil.h"
#include "../internalWindow.h"

#define DLL_EXPORT __declspec(dllexport)

#ifdef __GNUC__
#define SHARED __attribute__((section ("SHAREDDATA"), shared))
#else
#define SHARED
#endif

// Shared DATA
#pragma data_seg ( "SHAREDDATA" )

// this is the total number of processes this dll is currently attached to
int g_instance_count SHARED = 0;
static HANDLE g_mutex = NULL;

static HINSTANCE g_instance = NULL;

static HHOOK g_cbt_hook = NULL;
static HHOOK g_wndproc_hook = NULL;
static HHOOK g_wndprocret_hook = NULL;

#pragma data_seg ()

#pragma comment(linker, "/section:SHAREDDATA,rws")

// Internal Window
static HWND g_internal_window = NULL;

static BOOL is_seamless_internal_windows(HWND hwnd) {
	return (hwnd == g_internal_window);
}

// Hook messages
static void sendCopyDataMessage(HWND hwnd_src_, int msg_id_, LPVOID data_, SIZE_T data_len_) {
	COPYDATASTRUCT copydata;

	copydata.dwData = msg_id_;		// function identifier
	copydata.cbData = (DWORD) data_len_;	// size of data
	copydata.lpData = data_;		// data structure
	
	SendMessage(g_internal_window, WM_COPYDATA, (WPARAM)(HWND) hwnd_src_, (LPARAM) (LPVOID) &copydata);
}
static void sendHookMsg_State(HWND hwnd_, int state_) {
	HookMsg_State msg;
	msg.wnd = PtrToUlong(hwnd_);
	msg.state = state_;
	sendCopyDataMessage(hwnd_, HOOK_MSG_STATE, &msg, sizeof(HookMsg_State));
}
static void sendHookMsg_Focus(HWND hwnd_) {
	HookMsg_Focus msg;
	msg.wnd = PtrToUlong(hwnd_);
	sendCopyDataMessage(hwnd_, HOOK_MSG_FOCUS, &msg, sizeof(HookMsg_Focus));
}
static void sendHookMsg_Icon(HWND hwnd_, HICON icon_, BOOL large_, BOOL haveToGetIcon_) {
	HookMsg_Icon msg;
	msg.wnd = PtrToUlong(hwnd_);
	msg.icon = icon_;
	msg.large = large_;
	msg.haveToGetIcon = haveToGetIcon_;
	sendCopyDataMessage(hwnd_, HOOK_MSG_ICON, &msg, sizeof(HookMsg_Icon));
}
static void sendHookMsg_Title(HWND hwnd_, unsigned short * title_) {
	HookMsg_Title msg;
	msg.wnd = PtrToUlong(hwnd_);
	wcscpy(msg.title, title_);
	sendCopyDataMessage(hwnd_, HOOK_MSG_TITLE, &msg, sizeof(HookMsg_Title));
}
static void sendHookMsg_Destroy(HWND hwnd_) {
	HookMsg_Destroy msg;
	msg.wnd = PtrToUlong(hwnd_);
	sendCopyDataMessage(hwnd_, HOOK_MSG_DESTROY, &msg, sizeof(HookMsg_Destroy));
}
static void sendHookMsg_Position(HWND hwnd_) {
	HookMsg_Position msg;
	msg.wnd = PtrToUlong(hwnd_);
	sendCopyDataMessage(hwnd_, HOOK_MSG_POSITION, &msg, sizeof(HookMsg_Position));
}
static void sendHookMsg_Show(HWND hwnd_) {
	HookMsg_Show msg;
	msg.wnd = PtrToUlong(hwnd_);
	sendCopyDataMessage(hwnd_, HOOK_MSG_SHOW, &msg, sizeof(HookMsg_Show));
}
static void sendHookMsg_DestroyGrp(DWORD pid_) {
	HookMsg_DestroyGrp msg;
	msg.pid = pid_;
	sendCopyDataMessage(NULL, HOOK_MSG_DESTROYGRP, &msg, sizeof(HookMsg_DestroyGrp));
}
static void sendHookMsg_ZChange(HWND hwnd_) {
	HookMsg_ZChange msg;
	msg.wnd = PtrToUlong(hwnd_);
	sendCopyDataMessage(hwnd_, HOOK_MSG_ZCHANGE, &msg, sizeof(HookMsg_ZChange));
}

static LRESULT CALLBACK
wndproc_hook_proc(int code, WPARAM cur_thread, LPARAM details)
{
	HWND hwnd;
	UINT msg;
	WPARAM wparam;
	LPARAM lparam;

	LONG style;

	if (code < 0)
		goto end;

	hwnd = ((CWPSTRUCT *) details)->hwnd;
	msg = ((CWPSTRUCT *) details)->message;
	wparam = ((CWPSTRUCT *) details)->wParam;
	lparam = ((CWPSTRUCT *) details)->lParam;

	if (! WindowUtil_isToplevel(hwnd) || is_seamless_internal_windows(hwnd))
	{
		goto end;
	}

	style = GetWindowLong(hwnd, GWL_STYLE);

	switch (msg)
	{
		case WM_SHOWWINDOW:
			{
				sendHookMsg_Show(hwnd);
				break;
			}
		case WM_WINDOWPOSCHANGED:
			{
				WINDOWPOS *wp = (WINDOWPOS *) lparam;

				if (wp->flags & SWP_SHOWWINDOW) {
					sendHookMsg_Show(hwnd);
				}

				if (wp->flags & SWP_HIDEWINDOW) {
					sendHookMsg_Destroy(hwnd);
					break;
				}

				if (!(style & WS_VISIBLE) || (style & WS_MINIMIZE))
					break;

				if (!(wp->flags & SWP_NOMOVE && wp->flags & SWP_NOSIZE))
					sendHookMsg_Position(hwnd);

				break;
			}

		case WM_SETICON:
			{
				HICON icon = NULL;
				BOOL large = FALSE;

				if (!(style & WS_VISIBLE))
					break;

				switch (wparam)
				{
					case ICON_BIG:
						large = TRUE;
						break;
					case ICON_SMALL:
					case 2:
						large = FALSE;
						break;
					default:
						// Weird icon size
						goto end;
				}

				if (lparam)
					icon = (HICON) lparam;

				sendHookMsg_Icon(hwnd, icon, large, FALSE);
				break;
			}

		case WM_SIZE:
			switch (wparam) {
				case SIZE_MAXIMIZED:
					sendHookMsg_State(hwnd, 2);
					break;
				case SIZE_MINIMIZED:
					sendHookMsg_State(hwnd, 1);
					break;
				case SIZE_RESTORED:
					sendHookMsg_State(hwnd, 0);
					break;
			}
			break;

		case WM_MOVE:
			if (!(style & WS_VISIBLE) || (style & WS_MINIMIZE))
				break;
			
			sendHookMsg_Position(hwnd);
			break;

		case WM_DESTROY:
			if (!(style & WS_VISIBLE))
				break;

			sendHookMsg_Destroy(hwnd);
			break;

		default:
			break;
	}

      end:
	return CallNextHookEx(g_wndproc_hook, code, cur_thread, details);
}

static LRESULT CALLBACK
wndprocret_hook_proc(int code, WPARAM cur_thread, LPARAM details)
{
	HWND hwnd;
	UINT msg;
	WPARAM wparam;
	LPARAM lparam;

	LONG style;

	if (code < 0)
		goto end;

	hwnd = ((CWPRETSTRUCT *) details)->hwnd;
	msg = ((CWPRETSTRUCT *) details)->message;
	wparam = ((CWPRETSTRUCT *) details)->wParam;
	lparam = ((CWPRETSTRUCT *) details)->lParam;

	if (! WindowUtil_isToplevel(hwnd) || is_seamless_internal_windows(hwnd))
	{
		goto end;
	}

	style = GetWindowLong(hwnd, GWL_STYLE);

	switch (msg)
	{
		case WM_WINDOWPOSCHANGED:
			{
				WINDOWPOS *wp = (WINDOWPOS *) lparam;

				if (!(style & WS_VISIBLE) || (style & WS_MINIMIZE))
					break;

				if (!(wp->flags & SWP_NOZORDER))
					//sendHookMsg_ZChange(hwnd); --> WinDev applications bring if we do that
					break;

				break;
			}


		case WM_SETTEXT:
			{
				unsigned short title[TITLE_SIZE] = {0};

				if (!(style & WS_VISIBLE))
					break;

				/* We cannot use the string in lparam because
				   we need unicode. */
				GetWindowTextW(hwnd, title, TITLE_SIZE);

				sendHookMsg_Title(hwnd, title);
				break;
			}

		case WM_SETICON:
			{
				sendHookMsg_Icon(hwnd, NULL, FALSE, TRUE);
				break;
			}

		case WM_ACTIVATE:
			// http://msdn.microsoft.com/en-us/library/ms646274(VS.85).aspx
			if (wparam == 0)  // WA_INACTIVE
				break;
		case WM_SETFOCUS: // Focus gained
			sendHookMsg_Focus(hwnd);
			break;

		default:
			break;
	}

      end:
	return CallNextHookEx(g_wndprocret_hook, code, cur_thread, details);
}

static LRESULT CALLBACK
cbt_hook_proc(int code, WPARAM wparam, LPARAM lparam)
{
	HWND hwnd;
	
	if (code < 0)
		goto end;

	hwnd = (HWND) wparam;

	switch (code)
	{
		case HCBT_MINMAX:
			{
				int show, state;
				LONG style;

				style = GetWindowLong(hwnd, GWL_STYLE);

				if (!(style & WS_VISIBLE))
					break;

				show = LOWORD(lparam);

				if ((show == SW_NORMAL) || (show == SW_SHOWNORMAL)
				    || (show == SW_RESTORE))
					state = 0;
				else if ((show == SW_MINIMIZE) || (show == SW_SHOWMINIMIZED))
					state = 1;
				else if ((show == SW_MAXIMIZE) || (show == SW_SHOWMAXIMIZED))
					state = 2;
				else
				{
					// Unexpected show
					break;
				}

				sendHookMsg_State(hwnd, state);
				break;
			}

		default:
			break;
	}

      end:
	return CallNextHookEx(g_cbt_hook, code, wparam, lparam);
}

DLL_EXPORT void
SetHooks(void)
{
	if (!g_cbt_hook)
		g_cbt_hook = SetWindowsHookEx(WH_CBT, cbt_hook_proc, g_instance, 0);

	if (!g_wndproc_hook)
		g_wndproc_hook = SetWindowsHookEx(WH_CALLWNDPROC, wndproc_hook_proc, g_instance, 0);

	if (!g_wndprocret_hook)
		g_wndprocret_hook =
			SetWindowsHookEx(WH_CALLWNDPROCRET, wndprocret_hook_proc, g_instance, 0);
}

DLL_EXPORT void
RemoveHooks(void)
{
	if (g_cbt_hook)
		UnhookWindowsHookEx(g_cbt_hook);

	if (g_wndproc_hook)
		UnhookWindowsHookEx(g_wndproc_hook);

	if (g_wndprocret_hook)
		UnhookWindowsHookEx(g_wndprocret_hook);
}

DLL_EXPORT int
GetInstanceCount()
{
	return g_instance_count;
}

BOOL APIENTRY
DllMain(HINSTANCE hinstDLL, DWORD ul_reason_for_call, LPVOID lpReserved)
{
	switch (ul_reason_for_call)
	{
		case DLL_PROCESS_ATTACH:
			// remember our instance handle
			g_instance = hinstDLL;

			g_mutex = CreateMutex(NULL, FALSE, "Local\\SeamlessDLL");
			if (!g_mutex)
				return FALSE;

			WaitForSingleObject(g_mutex, INFINITE);
			++g_instance_count;
			ReleaseMutex(g_mutex);

			g_internal_window = InternalWindow_getHandle();

			break;

		case DLL_THREAD_ATTACH:
			break;

		case DLL_THREAD_DETACH:
			break;

		case DLL_PROCESS_DETACH:
			sendHookMsg_DestroyGrp(GetCurrentProcessId());

			WaitForSingleObject(g_mutex, INFINITE);
			--g_instance_count;
			ReleaseMutex(g_mutex);

			CloseHandle(g_mutex);

			break;
	}

	return TRUE;
}
