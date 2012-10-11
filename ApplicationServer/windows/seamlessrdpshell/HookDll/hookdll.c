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

#include <assert.h>
#include <stdio.h>
#include <stdarg.h>

#include <windows.h>
#include <winuser.h>
#include <CommCtrl.h>

#include "../vchannel.h"

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

// blocks for locally generated events
HWND g_block_move_hwnd SHARED = NULL;
unsigned int g_block_move_serial SHARED = 0;
RECT g_block_move SHARED = { 0, 0, 0, 0 };

unsigned int g_blocked_zchange_serial SHARED = 0;
HWND g_blocked_zchange[2] SHARED = { NULL, NULL };

unsigned int g_blocked_focus_serial SHARED = 0;
HWND g_blocked_focus SHARED = NULL;

unsigned int g_blocked_focus_lost_serial = 0;
HWND g_blocked_focus_lost = NULL;

unsigned int g_blocked_state_serial SHARED = 0;
HWND g_blocked_state_hwnd SHARED = NULL;
int g_blocked_state SHARED = -1;

HWND g_last_focused_window = NULL;

#pragma data_seg ()

#pragma comment(linker, "/section:SHAREDDATA,rws")

#define FOCUS_REQUEST_MSG_NAME	"WM_SEAMLESS_FOCUS_REQUEST"
#define FOCUS_RELEASE_MSG_NAME	"WM_SEAMLESS_FOCUS_RELEASE"
static UINT g_wm_seamless_focus_request;
static UINT g_wm_seamless_focus_release;

static HWND g_internal_window = NULL;

static HHOOK g_cbt_hook = NULL;
static HHOOK g_wndproc_hook = NULL;
static HHOOK g_wndprocret_hook = NULL;

static HINSTANCE g_instance = NULL;

static HANDLE g_mutex = NULL;

const char seamless_class[] = "InternalSeamlessClass";

#define TITLE_SIZE 150

typedef struct node_{
	HWND windows;
	unsigned short *title;
	boolean focus;
	boolean is_shown;
	int state;
	struct node_* next;
}node;

static void free_node(node * n_) {
	if (n_ == NULL)
		return;

	if (n_->title != NULL)
		free(n_->title);

	free(n_);
}

static node* hwdHistory = NULL;

static node* getWindowFromHistory(HWND hwnd){
	node* currentNode = hwdHistory;
	if (currentNode == NULL){
		return (node*)NULL;
	}
	while(currentNode){
		if( currentNode->windows == hwnd)
			return currentNode;
		currentNode = currentNode->next;
	}
	return (node*)NULL;
}

static node* addHWDNToHistory(HWND hwnd){
	node* currentNode = NULL;
	node* newNode = hwdHistory;
	
	currentNode = getWindowFromHistory(hwnd);
	if(currentNode != NULL && currentNode->windows == hwnd){
		return (node*) NULL;
	}

	if (hwdHistory == NULL){
		hwdHistory = malloc(sizeof(node));
		hwdHistory->windows = hwnd;
		hwdHistory->title = NULL;
		hwdHistory->focus = FALSE;
		hwdHistory->is_shown = FALSE;
		hwdHistory->next = NULL;
		return hwdHistory;
	}
	
	currentNode = hwdHistory;
	while(currentNode->next){
		currentNode = currentNode->next;
	}
	newNode = malloc(sizeof(node));
	newNode->windows = hwnd;
	newNode->title = NULL;
	newNode->next = NULL;
	newNode->focus = FALSE;
	newNode->is_shown = FALSE;
	currentNode->next = newNode;
	return newNode;
}

static BOOL removeHWNDFromHistory(HWND hwnd){
	node* currentNode = hwdHistory;
	node* previousNode = NULL;

	while(currentNode && currentNode->windows != hwnd){
		previousNode = currentNode;
		currentNode = currentNode->next;
	}
	if (! currentNode)
		return FALSE;
	if (previousNode == NULL)
		hwdHistory = currentNode->next;
	else
		previousNode->next = currentNode->next;

	free_node(currentNode);

	return TRUE;
}

static int g_screen_width = 0;
static int g_screen_height = 0;


static BOOL is_seamless_internal_windows(HWND hwnd) {
	return (hwnd == g_internal_window);
}

static HWND get_internal_window() {
	return FindWindow(seamless_class, NULL);
}

static BOOL
is_toplevel(HWND hwnd)
{
	BOOL toplevel;
	HWND parent;
	parent = GetAncestor(hwnd, GA_PARENT);

	/* According to MS: "A window that has no parent, or whose
	   parent is the desktop window, is called a top-level
	   window." See http://msdn2.microsoft.com/en-us/library/ms632597(VS.85).aspx. */
	toplevel = (!parent || parent == GetDesktopWindow());
	return toplevel;
}

/* Determine the "parent" field for the CREATE response. */
static HWND
get_parent(HWND hwnd)
{
	HWND result;
	HWND owner;
	LONG exstyle;

	/* Use the same logic to determine if the window should be
	   "transient" (ie have no task icon) as MS uses. This is documented at 
	   http://msdn2.microsoft.com/en-us/library/bb776822.aspx */
	owner = GetWindow(hwnd, GW_OWNER);
	exstyle = GetWindowLong(hwnd, GWL_EXSTYLE);
	if (!owner && !(exstyle & WS_EX_TOOLWINDOW))
	{
		/* display taskbar icon */
		result = NULL;
	}
	else
	{
		/* no taskbar icon */
		if (owner)
			result = owner;
		else
			result = (HWND) - 1;
	}

	return result;
}

static void
getScreenSize() {
	g_screen_width = GetSystemMetrics(SM_CXSCREEN);
	g_screen_height = GetSystemMetrics(SM_CYSCREEN);
}

static void
update_position(HWND hwnd)
{
	RECT rect, blocked;
	HWND blocked_hwnd;
	unsigned int serial;

	WaitForSingleObject(g_mutex, INFINITE);
	blocked_hwnd = g_block_move_hwnd;
	serial = g_block_move_serial;
	memcpy(&blocked, &g_block_move, sizeof(RECT));
	ReleaseMutex(g_mutex);

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

	vchannel_write("POSITION", "0x%08lx,%d,%d,%d,%d,0x%08x",
		       hwnd,
		       rect.left, rect.top, rect.right - rect.left, rect.bottom - rect.top, 0);

      end:
	vchannel_unblock();
}

static void
update_zorder(HWND hwnd)
{
	HWND behind;
	HWND block_hwnd, block_behind;
	unsigned int serial;

	WaitForSingleObject(g_mutex, INFINITE);
	serial = g_blocked_zchange_serial;
	block_hwnd = g_blocked_zchange[0];
	block_behind = g_blocked_zchange[1];
	ReleaseMutex(g_mutex);

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

static HICON
get_icon(HWND hwnd, int large)
{
	HICON icon;

	if (!SendMessageTimeout(hwnd, WM_GETICON, large ? ICON_BIG : ICON_SMALL,
				0, SMTO_ABORTIFHUNG, 1000, (PDWORD_PTR) & icon))
		return NULL;

	if (icon)
		return icon;

	/*
	 * Modern versions of Windows uses the voodoo value of 2 instead of 0
	 * for the small icons.
	 */
	if (!large)
	{
		if (!SendMessageTimeout(hwnd, WM_GETICON, 2,
					0, SMTO_ABORTIFHUNG, 1000, (PDWORD_PTR) & icon))
			return NULL;
	}

	if (icon)
		return icon;

	icon = (HICON) GetClassLong(hwnd, large ? GCL_HICON : GCL_HICONSM);

	if (icon)
		return icon;

	return NULL;
}

static int
extract_icon(HICON icon, char *buffer, int maxlen)
{
	ICONINFO info;
	HDC hdc;
	BITMAP mask_bmp, color_bmp;
	BITMAPINFO bmi;
	int size, i;
	char *mask_buf, *color_buf;
	char *o, *m, *c;
	int ret = -1;

	assert(buffer);
	assert(maxlen > 0);

	if (!GetIconInfo(icon, &info))
		goto fail;

	if (!GetObject(info.hbmMask, sizeof(BITMAP), &mask_bmp))
		goto free_bmps;
	if (!GetObject(info.hbmColor, sizeof(BITMAP), &color_bmp))
		goto free_bmps;

	if (mask_bmp.bmWidth != color_bmp.bmWidth)
		goto free_bmps;
	if (mask_bmp.bmHeight != color_bmp.bmHeight)
		goto free_bmps;

	if ((mask_bmp.bmWidth * mask_bmp.bmHeight * 4) > maxlen)
		goto free_bmps;

	size = (mask_bmp.bmWidth + 3) / 4 * 4;
	size *= mask_bmp.bmHeight;
	size *= 4;

	mask_buf = malloc(size);
	if (!mask_buf)
		goto free_bmps;
	color_buf = malloc(size);
	if (!color_buf)
		goto free_mbuf;

	memset(&bmi, 0, sizeof(BITMAPINFO));

	bmi.bmiHeader.biSize = sizeof(BITMAPINFO);
	bmi.bmiHeader.biWidth = mask_bmp.bmWidth;
	bmi.bmiHeader.biHeight = -mask_bmp.bmHeight;
	bmi.bmiHeader.biPlanes = 1;
	bmi.bmiHeader.biBitCount = 32;
	bmi.bmiHeader.biCompression = BI_RGB;
	bmi.bmiHeader.biSizeImage = size;

	hdc = CreateCompatibleDC(NULL);
	if (!hdc)
		goto free_cbuf;

	if (!GetDIBits(hdc, info.hbmMask, 0, mask_bmp.bmHeight, mask_buf, &bmi, DIB_RGB_COLORS))
		goto del_dc;
	if (!GetDIBits(hdc, info.hbmColor, 0, color_bmp.bmHeight, color_buf, &bmi, DIB_RGB_COLORS))
		goto del_dc;

	o = buffer;
	m = mask_buf;
	c = color_buf;
	for (i = 0; i < size / 4; i++)
	{
		o[0] = c[2];
		o[1] = c[1];
		o[2] = c[0];

		o[3] = ((int) (unsigned char) m[0] + (unsigned char) m[1] +
			(unsigned char) m[2]) / 3;
		o[3] = 0xff - o[3];

		o += 4;
		m += 4;
		c += 4;
	}

	ret = size;

      del_dc:
	DeleteDC(hdc);

      free_cbuf:
	free(color_buf);
      free_mbuf:
	free(mask_buf);

      free_bmps:
	DeleteObject(info.hbmMask);
	DeleteObject(info.hbmColor);

      fail:
	return ret;
}

#define ICON_CHUNK 400

static void
update_icon(HWND hwnd, HICON icon, int large)
{
	int i, j, size, chunks;
	char buf[32 * 32 * 4];
	char asciibuf[ICON_CHUNK * 2 + 1];

	size = extract_icon(icon, buf, sizeof(buf));
	if (size <= 0)
		return;

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
		DWORD pid;
		int flags;
		HICON icon;
		LONG exstyle;
		LONG style;
		HWND parent;
		node* window;
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

		window->is_shown = TRUE;

		style = GetWindowLong(hwnd, GWL_STYLE);
		vchannel_write("DEBUG","NEW WINDOWS");

		exstyle = GetWindowLong(hwnd, GWL_EXSTYLE);
		GetWindowThreadProcessId(hwnd, &pid);

		parent = get_parent(hwnd);
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

		icon = get_icon(hwnd, 1);
		if (icon)
		{
			update_icon(hwnd, icon, 1);
			DeleteObject(icon);
		}

		icon = get_icon(hwnd, 0);
		if (icon)
		{
			update_icon(hwnd, icon, 0);
			DeleteObject(icon);
		}

		if (style & WS_MAXIMIZE)
			window->state = 2;
		else if (style & WS_MINIMIZE)
			window->state = 1;
		else
			window->state = 0;

		update_position(hwnd);
		vchannel_write("STATE", "0x%08lx,0x%08x,0x%08x", hwnd,
				   window->state, 0);

		if (window->focus)
			vchannel_write("FOCUS", "0x%08lx", hwnd);
}

static void destroy_window(HWND hwnd)
{
	if (! removeHWNDFromHistory(hwnd))
		return;

	vchannel_write("DESTROY", "0x%08lx,0x%08x", hwnd, 0);
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
	
	if (!is_toplevel(hwnd) || is_seamless_internal_windows(hwnd))
	{
		goto end;
	}

	style = GetWindowLong(hwnd, GWL_STYLE);

	switch (msg)
	{
		case WM_SHOWWINDOW:
			{
				create_window(hwnd);
				break;
			}
		case WM_WINDOWPOSCHANGED:
			{
				WINDOWPOS *wp = (WINDOWPOS *) lparam;

				if (wp->flags & SWP_SHOWWINDOW)
				{
					create_window(hwnd);
				}

				if (wp->flags & SWP_HIDEWINDOW)
					destroy_window(hwnd);

				if (!(style & WS_VISIBLE) || (style & WS_MINIMIZE))
					break;

				if (!(wp->flags & SWP_NOMOVE && wp->flags & SWP_NOSIZE))
					update_position(hwnd);

				break;
			}

		case WM_SETICON:
			if (!(style & WS_VISIBLE))
				break;

			switch (wparam)
			{
				case ICON_BIG:
					if (lparam)
						update_icon(hwnd, (HICON) lparam, 1);
					else
						vchannel_write("DELICON", "0x%08lx,RGBA,32,32",
							       hwnd);
					break;
				case ICON_SMALL:
				case 2:
					if (lparam)
						update_icon(hwnd, (HICON) lparam, 0);
					else
						vchannel_write("DELICON", "0x%08lx,RGBA,16,16",
							       hwnd);
					break;
				default:
					debug("Weird icon size %d", (int) wparam);
			}

			break;

		case WM_SIZE:
			{
				HWND blocked_hwnd;
				int blocked;
				unsigned int serial;
				node* sw;
				int state;
				
				sw = getWindowFromHistory(hwnd);
				if (! sw)
					break;

				WaitForSingleObject(g_mutex, INFINITE);
				blocked_hwnd = g_blocked_state_hwnd;
				serial = g_blocked_state_serial;
				blocked = g_blocked_state;
				ReleaseMutex(g_mutex);

				switch (wparam) {
					case SIZE_MAXIMIZED:
						state = 2;
						break;
					case SIZE_MINIMIZED:
						state = 1;
						break;
					case SIZE_RESTORED:
						state = 0;
						break;
				}

				if (sw->state == state) {
					break;
				}

				sw->state = state;
				if ((blocked_hwnd == hwnd) && (blocked == state)) {
					vchannel_write("ACK", "%u", serial);
				}
				else {
					vchannel_write("STATE", "0x%08lx,0x%08x,0x%08x",
						  hwnd, sw->state, 0);
				}
			}

			if (wparam == SIZE_MAXIMIZED || !(style & WS_VISIBLE) || (style & WS_MINIMIZE))
				break;
			update_position(hwnd);
			break;

		case WM_MOVE:
			if (!(style & WS_VISIBLE) || (style & WS_MINIMIZE))
				break;
			update_position(hwnd);
			break;

		case WM_DESTROY:
			if (!(style & WS_VISIBLE))
				break;
			destroy_window(hwnd);
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

	if (!is_toplevel(hwnd) || is_seamless_internal_windows(hwnd))
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
					//update_zorder(hwnd) --> WinDev applications bring if we do that;
					break;

				break;
			}


		case WM_SETTEXT:
			{
				if (!(style & WS_VISIBLE))
					break;
				/* We cannot use the string in lparam because
				   we need unicode. */
				if (getWindowFromHistory(hwnd) == NULL){
					create_window(hwnd);
				}
				else{
					BOOLEAN titleIsTheSame = TRUE;
					int i = 0;
					unsigned short *title;
					node* window = getWindowFromHistory(hwnd);
					if (window == NULL) {
						break;
					}

					title = malloc(sizeof(unsigned short) * TITLE_SIZE);
					if (title == NULL)
						break;

					GetWindowTextW(hwnd, title, TITLE_SIZE);

					if (window->title != NULL) {
						for (i = 0; i < TITLE_SIZE; i++) {
							if (title[i] != window->title[i]) {
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

					vchannel_write("TITLE", "0x%08lx,%s,0x%08x", hwnd, vchannel_strfilter_unicode(title), 0);

					if (window->title) {
						free(window->title);
						window->title;
					}
					window->title = title;
				}
				break;
			}

		case WM_SETICON:
			{
				HICON icon;

				/*
				 * Somehow, we never get WM_SETICON for the small icon.
				 * So trigger a read of it every time the large one is
				 * changed.
				 */
				icon = get_icon(hwnd, 0);
				if (icon)
				{
					update_icon(hwnd, icon, 0);
					DeleteObject(icon);
				}
				break;
			}

		case WM_ACTIVATE:
			// http://msdn.microsoft.com/en-us/library/ms646274(VS.85).aspx
			if (wparam == 0)  // WA_INACTIVE
				break;

		case WM_SETFOCUS: // Focus gained
			if (hwnd == g_last_focused_window)
				break;

			WaitForSingleObject(g_mutex, INFINITE);
			g_last_focused_window = hwnd;
			ReleaseMutex(g_mutex);

			{
				node* window;

				window = getWindowFromHistory(hwnd);
				if (window == NULL) {
					window = addHWDNToHistory(hwnd);
					if (window == NULL)
						goto end;

					window->focus = TRUE;
					goto end;
				}
			}

			vchannel_block();
			vchannel_write("FOCUS", "0x%08lx", hwnd);
			vchannel_unblock();

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
	node* sw;
	int show;
	LONG style;
	
	if (code < 0)
		goto end;

	hwnd = (HWND) wparam;
				
	sw = getWindowFromHistory(hwnd);
	if (! sw)
		goto end;
	
	show = LOWORD(lparam);
	
	style = GetWindowLong(hwnd, GWL_STYLE);

	switch (code)
	{
		case HCBT_MINMAX:
			{
				int state, blocked;
				HWND blocked_hwnd;
				unsigned int serial;

				WaitForSingleObject(g_mutex, INFINITE);
				blocked_hwnd = g_blocked_state_hwnd;
				serial = g_blocked_state_serial;
				blocked = g_blocked_state;
				ReleaseMutex(g_mutex);

				if (!(style & WS_VISIBLE))
					break;

				if ((show == SW_NORMAL) || (show == SW_SHOWNORMAL)
				    || (show == SW_RESTORE))
					state = 0;
				else if ((show == SW_MINIMIZE) || (show == SW_SHOWMINIMIZED))
					state = 1;
				else if ((show == SW_MAXIMIZE) || (show == SW_SHOWMAXIMIZED))
					state = 2;
				else
				{
					debug("Unexpected show: %d", show);
					break;
				}

				if ((blocked_hwnd == hwnd) && (blocked == state))
					vchannel_write("ACK", "%u", serial);
				else if (sw->state != state) {
					sw->state = state;
					vchannel_write("STATE", "0x%08lx,0x%08x,0x%08x",
						       hwnd, state, 0);
				}

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

DLL_EXPORT void
SafeMoveWindow(unsigned int serial, HWND hwnd, int x, int y, int width, int height)
{
	RECT rect;

	if (IsZoomed(hwnd))
		return;

	WaitForSingleObject(g_mutex, INFINITE);
	g_block_move_hwnd = hwnd;
	g_block_move_serial = serial;
	g_block_move.left = x;
	g_block_move.top = y;
	g_block_move.right = x + width;
	g_block_move.bottom = y + height;
	ReleaseMutex(g_mutex);

	SetWindowPos(hwnd, NULL, x, y, width, height, SWP_NOACTIVATE | SWP_NOZORDER);

	vchannel_write("ACK", "%u", serial);

	if (!GetWindowRect(hwnd, &rect))
		debug("GetWindowRect failed!\n");
	else if ((rect.left != x) || (rect.top != y) || (rect.right != x + width)
		 || (rect.bottom != y + height))
		update_position(hwnd);
	else if (! IsIconic(hwnd))
		vchannel_write("POSITION", "0x%08lx,%d,%d,%d,%d,0x%08x", hwnd, rect.left, rect.top, rect.right - rect.left, rect.bottom - rect.top, 0);

	WaitForSingleObject(g_mutex, INFINITE);
	g_block_move_hwnd = NULL;
	memset(&g_block_move, 0, sizeof(RECT));
	ReleaseMutex(g_mutex);
}

DLL_EXPORT void
SafeZChange(unsigned int serial, HWND hwnd, HWND behind)
{
	WaitForSingleObject(g_mutex, INFINITE);
	g_blocked_zchange_serial = serial;
	g_blocked_zchange[0] = hwnd;
	g_blocked_zchange[1] = behind;
	ReleaseMutex(g_mutex);

	if (behind == NULL)
		behind = HWND_TOP;

	SetWindowPos(hwnd, behind, 0, 0, 0, 0, SWP_NOACTIVATE | SWP_NOMOVE | SWP_NOSIZE);

	WaitForSingleObject(g_mutex, INFINITE);
	g_blocked_zchange[0] = NULL;
	g_blocked_zchange[1] = NULL;
	ReleaseMutex(g_mutex);
}

DLL_EXPORT void
SafeFocus(unsigned int serial, HWND hwnd, int action)
{
	UINT msg_type;
	UINT *blocked_focus_serial;

	switch (action) {
		case SEAMLESS_FOCUS_REQUEST:
			msg_type = g_wm_seamless_focus_request;
			blocked_focus_serial = &g_blocked_focus_serial;
			break;
		case SEAMLESS_FOCUS_RELEASE:
			msg_type = g_wm_seamless_focus_release;
			blocked_focus_serial = &g_blocked_focus_lost_serial;
			break;
		default:
			return;
	}
	
	WaitForSingleObject(g_mutex, INFINITE);
	*blocked_focus_serial = serial;
	g_blocked_focus = hwnd;
	ReleaseMutex(g_mutex);

	SendMessage(hwnd, msg_type, 0, 0);

	WaitForSingleObject(g_mutex, INFINITE);
	g_blocked_focus = NULL;
	ReleaseMutex(g_mutex);
}

DLL_EXPORT void
SafeSetState(unsigned int serial, HWND hwnd, int state)
{
	LONG style;
	int curstate;

	vchannel_block();

	style = GetWindowLong(hwnd, GWL_STYLE);

	if (style & WS_MAXIMIZE)
		curstate = 2;
	else if (style & WS_MINIMIZE)
		curstate = 1;
	else
		curstate = 0;

	if (state == curstate)
	{
		vchannel_write("ACK", "%u", serial);
		vchannel_unblock();
		return;
	}

	WaitForSingleObject(g_mutex, INFINITE);
	g_blocked_state_hwnd = hwnd;
	g_blocked_state_serial = serial;
	g_blocked_state = state;
	ReleaseMutex(g_mutex);

	vchannel_unblock();

	if (state == 0)
		ShowWindow(hwnd, SW_RESTORE);
	else if (state == 1)
		ShowWindow(hwnd, SW_MINIMIZE);
	else if (state == 2)
		ShowWindow(hwnd, SW_MAXIMIZE);
	else
		debug("Invalid state %d sent.", state);

	WaitForSingleObject(g_mutex, INFINITE);
	g_blocked_state_hwnd = NULL;
	g_blocked_state = -1;
	ReleaseMutex(g_mutex);
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

			g_wm_seamless_focus_request = RegisterWindowMessage(FOCUS_REQUEST_MSG_NAME);
			g_wm_seamless_focus_release = RegisterWindowMessage(FOCUS_RELEASE_MSG_NAME);

			g_internal_window = get_internal_window();

			vchannel_open();
			getScreenSize();

			break;

		case DLL_THREAD_ATTACH:
			break;

		case DLL_THREAD_DETACH:
			break;

		case DLL_PROCESS_DETACH:
			vchannel_write("DESTROYGRP", "0x%08lx, 0x%08lx", GetCurrentProcessId(), 0);

			WaitForSingleObject(g_mutex, INFINITE);
			--g_instance_count;
			ReleaseMutex(g_mutex);

			vchannel_close();

			CloseHandle(g_mutex);

			break;
	}

	return TRUE;
}
