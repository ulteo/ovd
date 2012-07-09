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

#include "seamlessWindow.h"
#include "seamlessWindowHistory.h"
#include "seamlessChannel.h"
#include "windowUtil.h"

#include <stdio.h>
#include <stdlib.h>
#include <malloc.h>

void SeamlessWindow_create(HWND hwnd) {
		unsigned short *title;
		int state;
		DWORD pid;
		int flags;
		HICON icon;
		HWND parent;
		SeamlessWindow* window;

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
		window->state = -1;
		window->is_shown = TRUE;

		GetWindowThreadProcessId(hwnd, &pid);

		parent = WindowUtil_getParent(hwnd);
		if (getWindowFromHistory(parent) == NULL)
			parent = 0;

		flags = WindowUtil_getFlags(hwnd);
		if ((! parent && (flags & SEAMLESS_CREATE_POPUP) != 0)
			|| (flags & SEAMLESS_CREATE_TOOLTIP) != 0)
			parent = 0xffffffffL;

		SeamlessChannel_sendCreate(hwnd, pid, parent, flags);

		title = malloc(sizeof(unsigned short) * TITLE_SIZE);
		if (title != NULL) {
			GetWindowTextW(hwnd, title, TITLE_SIZE);

			SeamlessChannel_sendTitle(hwnd, title, 0);
		}
		if (window->title) {
			free(window->title);
			window->title = NULL;
		}
		window->title = title;

		icon = WindowUtil_getIcon(hwnd, 1);
		if (icon)
		{
			SeamlessWindow_updateIcon(window, icon, 1);
			DeleteObject(icon);
		}
		
		if (IsZoomed(hwnd)) {
			WINDOWPLACEMENT placement;

			placement.length = sizeof(WINDOWPLACEMENT);
			GetWindowPlacement(hwnd, &placement);
			SeamlessChannel_sendPosition(hwnd,
						placement.rcNormalPosition.left,
						placement.rcNormalPosition.top,
						placement.rcNormalPosition.right - placement.rcNormalPosition.left,
						placement.rcNormalPosition.bottom - placement.rcNormalPosition.top,
						1);
		}
		else {
			SeamlessWindow_updatePosition(window);
		}

		state = WindowUtil_getState(hwnd);
		SeamlessChannel_sendState(hwnd, state, 0);

		if (window->focus)
			SeamlessChannel_sendFocus(hwnd);
}

void SeamlessWindow_free(SeamlessWindow * n_) {
	if (n_ == NULL)
		return;

	if (n_->title != NULL)
		free(n_->title);

	if (n_->bounds != NULL)
		free(n_->bounds);

	free(n_);
}

void SeamlessWindow_updateZOrder(SeamlessWindow *sw) {
	HWND behind;
	SeamlessOrder_ZChange *lastOrder;

	if (! sw)
		return;

	lastOrder = (SeamlessOrder_ZChange *) SeamlessChannel_getLastOrder(SEAMLESSORDER_ZCHANGE);

	behind = GetNextWindow(sw->windows, GW_HWNDPREV);
	while (behind) {
		LONG style;

		style = GetWindowLong(behind, GWL_STYLE);

		if ((! (style & WS_CHILD) || (style & WS_POPUP)) && (style & WS_VISIBLE))
			break;

		behind = GetNextWindow(behind, GW_HWNDPREV);
	}

	if (lastOrder && (sw->windows == lastOrder->wnd) && (behind == lastOrder->behindWnd))
		SeamlessChannel_sendAck(lastOrder->serial);
	else
	{
		int flags = 0;
		LONG exstyle = GetWindowLong(sw->windows, GWL_EXSTYLE);
		// handle always on top
		if (exstyle & WS_EX_TOPMOST)
			flags |= SEAMLESS_CREATE_TOPMOST;
		SeamlessChannel_sendZChange(sw->windows, behind, flags);
	}
}

static void SeamlessWindow_sendPosition(SeamlessWindow *sw) {
	if (! sw || ! sw->bounds)
		return;

	SeamlessChannel_sendPosition(sw->windows, sw->bounds->left, sw->bounds->top, sw->bounds->right - sw->bounds->left, sw->bounds->bottom - sw->bounds->top, 0);
}

void SeamlessWindow_updatePosition(SeamlessWindow *sw) {
	RECT rect;
	SeamlessOrder_Position *lastOrder = NULL;
	PSIZE screenSize = NULL;

	if (! sw)
		return;

	lastOrder = (SeamlessOrder_Position *) SeamlessChannel_getLastOrder(SEAMLESSORDER_POSITION);

	if (IsZoomed(sw->windows) || IsIconic(sw->windows))
	{
		goto end;
	}
	else
	{
		if (! GetWindowRect(sw->windows, &rect))
		{
			SeamlessChannel_sendDebug("GetWindowRect failed!\n");
			goto end;
		}
	}

	if (lastOrder && (sw->windows == lastOrder->wnd) && (rect.left == lastOrder->bounds.left) && (rect.top == lastOrder->bounds.top)
	    && (rect.right == lastOrder->bounds.right) && (rect.bottom == lastOrder->bounds.bottom))
		goto end;

	screenSize = WindowUtil_getScreenSize();
	if (screenSize && (! IsZoomed(sw->windows)) && (rect.left < 0 || rect.top < 0 || rect.bottom > screenSize->cy || rect.right > screenSize->cx)) {
		int w = rect.right - rect.left;
		int h = rect.bottom - rect.top;
		int x = ((screenSize->cx - rect.left) < w) ? (screenSize->cx - w) : rect.left;
		int y = ((screenSize->cy - rect.top) < h) ? (screenSize->cy - h) : rect.top;
		x = (rect.left < 0) ? 0 : x;
		y = (rect.top < 0) ? 0 : y;

		SetWindowPos(sw->windows, NULL, x, y, w, h, SWP_NOACTIVATE | SWP_NOZORDER);

		goto end;
	}

	// Check if the window has been moved or resized
	if (sw->bounds && EqualRect(sw->bounds, &rect))
		goto end;

	// Store the new window bounds
	if (! sw->bounds)
		sw->bounds = malloc(sizeof(RECT));
	memcpy(sw->bounds, &rect, sizeof(RECT));

	SeamlessWindow_sendPosition(sw);

      end:
	;
}

#define ICON_CHUNK 400

void SeamlessWindow_updateIcon(SeamlessWindow *sw, HICON icon, int large) {
	int i, j, size, chunks;
	char buf[32 * 32 * 4];
	char asciibuf[ICON_CHUNK * 2 + 1];

	if (! sw)
		return;

	size = WindowUtil_extractIcon(icon, buf, sizeof(buf));
	if (size <= 0)
		return;

	if ((!large && size != 16 * 16 * 4) || (large && size != 32 * 32 * 4)) {
		SeamlessChannel_sendDebug("Unexpected icon size.");
		return;
	}

	chunks = (size + ICON_CHUNK - 1) / ICON_CHUNK;
	for (i = 0; i < chunks; i++) {
		for (j = 0; j < ICON_CHUNK; j++) {
			if (i * ICON_CHUNK + j >= size)
				break;

			sprintf(asciibuf + j * 2, "%02x", (int) (unsigned char) buf[i * ICON_CHUNK + j]);
		}

		SeamlessChannel_sendSetIcon(sw->windows, i, large ? 32 : 16, large ? 32 : 16, asciibuf);
	}
}

static void SeamlessWindow_sendTitle(SeamlessWindow *sw) {
	SeamlessChannel_sendTitle(sw->windows, sw->title, 0);
}

BOOL SeamlessWindow_updateTitle(SeamlessWindow *sw) {
	unsigned short *title;
	int i = 0;

	title = malloc(sizeof(unsigned short) * TITLE_SIZE);
	if (title == NULL)
		return FALSE;

	GetWindowTextW(sw->windows, title, TITLE_SIZE);

	if (sw->title != NULL && strcmp((const char*) sw->title, (const char*) title) == 0) {
		free(title);
		return FALSE;
	}

	if (sw->title) {
		free(sw->title);
	}
	sw->title = title;

	SeamlessWindow_sendTitle(sw);

	return TRUE;
}

BOOL SeamlessWindow_updateFocus(SeamlessWindow *sw) {
	BOOL isFocused = FALSE;

	isFocused = WindowUtil_isFocused(sw->windows);
	if (isFocused != sw->focus) {
		sw->focus = isFocused;

		if (sw->focus)
			SeamlessChannel_sendFocus(sw->windows);

		return TRUE;
	}

	return FALSE;
}

static void SeamlessWindow_sendState(SeamlessWindow *sw) {
	SeamlessChannel_sendState(sw->windows, sw->state, 0);
}

BOOL SeamlessWindow_updateState(SeamlessWindow *sw) {
	int newState = 0;

	newState = WindowUtil_getState(sw->windows);
	if (newState != sw->state) {
		sw->state = newState;
		SeamlessWindow_sendState(sw);

		return TRUE;
	}

	return FALSE;
}

void SeamlessWindow_synchronize(SeamlessWindow *sw)
{
	HWND parent;
	DWORD pid;
	int flags;
	HICON icon;

	if (sw == NULL)
		return;

	GetWindowThreadProcessId(sw->windows, &pid);
	
	parent = WindowUtil_getParent(sw->windows);
	if (getWindowFromHistory(parent) == NULL)
		parent = 0;

	flags = WindowUtil_getFlags(sw->windows);
	if ((! parent && (flags & SEAMLESS_CREATE_POPUP) != 0)
		|| (flags & SEAMLESS_CREATE_TOOLTIP) != 0)
		parent = 0xffffffffL;

	SeamlessChannel_sendCreate(sw->windows, pid, parent, flags);

	SeamlessWindow_sendTitle(sw);

	icon = WindowUtil_getIcon(sw->windows, 1);
	if (icon) {
		SeamlessWindow_updateIcon(sw, icon, 1);
		DeleteObject(icon);
	}

	SeamlessWindow_sendPosition(sw);

	SeamlessWindow_sendState(sw);
}

void SeamlessWindow_destroy(SeamlessWindow *sw) {
	if (! sw)
		return;

	SeamlessChannel_sendDestroy(sw->windows, 0);

	removeHWNDFromHistory(sw->windows);
}
