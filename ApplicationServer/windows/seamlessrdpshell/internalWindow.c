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

#include "internalWindow.h"
#include "windowUtil.h"

static HWND hwnd_internal = NULL;
const char seamless_class[] = "InternalSeamlessClass";

static DATACOPYPROC lpDataCopyProcessFunc = NULL;

LRESULT CALLBACK WndProc(HWND hwnd, UINT msg, WPARAM wParam, LPARAM lParam) {
	HWND hwnd_to_focus = (HWND)wParam;

	switch(msg) {
	case WM_CLOSE:
		DestroyWindow(hwnd);
		break;
	case WM_DESTROY:
		PostQuitMessage(0);
		break;

	case WM_KILLFOCUS:
		if (hwnd_to_focus != InternalWindow_getHandle() && hwnd_to_focus != NULL)
			WindowUtil_setFocus(hwnd_to_focus);
		break;

	case WM_COPYDATA:
		if (! lpDataCopyProcessFunc)
			break;

		lpDataCopyProcessFunc((PCOPYDATASTRUCT) lParam);
		break;

	default:
		return DefWindowProc(hwnd, msg, wParam, lParam);
	}
	return 0;
}

BOOL InternalWindow_create(HINSTANCE instance, DATACOPYPROC lpDataCopyProcessFunc_) {
	WNDCLASSEX wc;

	wc.cbSize = sizeof(WNDCLASSEX);
	wc.style = 0;
	wc.lpfnWndProc = WndProc;
	wc.cbClsExtra = 0;
	wc.cbWndExtra = 0;
	wc.hInstance = instance;
	wc.hIcon = NULL;
	wc.hCursor = NULL;
	wc.hbrBackground = (HBRUSH)(COLOR_WINDOW+1);
	wc.lpszMenuName  = NULL;
	wc.lpszClassName = seamless_class;
	wc.hIconSm = NULL;

	if(!RegisterClassEx(&wc))
		return FALSE;

	hwnd_internal = CreateWindowEx(WS_EX_CLIENTEDGE, seamless_class, "", WS_POPUP|WS_CLIPCHILDREN|WS_VISIBLE, 0, 0, 0, 0, NULL, NULL, instance, NULL);

	if(hwnd_internal == NULL) {
		return FALSE;
	}

	lpDataCopyProcessFunc = lpDataCopyProcessFunc_;

	ShowWindow(hwnd_internal, SW_SHOW);
	UpdateWindow(hwnd_internal);

	return TRUE;
}

HWND InternalWindow_getHandle() {
	if (! hwnd_internal)
		hwnd_internal = FindWindow(seamless_class, NULL);

	return hwnd_internal;
}
