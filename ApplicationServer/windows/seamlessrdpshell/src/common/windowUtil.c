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

#include "windowUtil.h"

#include <vchannel/vchannel.h>
#include "assert.h"
#include <CommCtrl.h>

// Screen size
static PSIZE g_screen_size = NULL;

PSIZE WindowUtil_getScreenSize() {
	if (! g_screen_size) {
		g_screen_size = malloc(sizeof(SIZE));
		if (! g_screen_size)
			return NULL;

		g_screen_size->cx = GetSystemMetrics(SM_CXSCREEN);
		g_screen_size->cy = GetSystemMetrics(SM_CYSCREEN);
	}

	return g_screen_size;
}

BOOL WindowUtil_isToplevel(HWND hwnd) {
	BOOL toplevel;
	HWND parent;
	parent = GetAncestor(hwnd, GA_PARENT);

	/* According to MS: "A window that has no parent, or whose
	   parent is the desktop window, is called a top-level
	   window." See http://msdn2.microsoft.com/en-us/library/ms632597(VS.85).aspx. */
	toplevel = (!parent || parent == GetDesktopWindow());
	return toplevel;
}

BOOL WindowUtil_isVisible(HWND hwnd) {
	return IsWindowVisible(hwnd);
}

BOOL WindowUtil_isGood(HWND hwnd) {
	char className[256];
	char *excludedClasses[] = {
		"TabThumbnailWindow",            /* IE11 tab preview */
		"Internet Explorer_Hidden",      /* IE11 (???) */
		"Alternate Owner",               /* IE11 (???) */
		NULL /* _MUST_ Be the last element */
	};

	/* Test if window is from an excluded class */
	if (GetClassName(hwnd, className, 256) != 0) {
		int i = 0;

		for (i=0 ; excludedClasses[i] != NULL ; ++i) {
			char *excluded = excludedClasses[i];

			if (strcmp(className, excluded) == 0) {
				return FALSE;
			}
		}
	}

	return TRUE;
}

int WindowUtil_getFlags(HWND hwnd) {
	int flags = 0;
	LONG style;
	LONG exstyle;
	TCHAR classname[256];

	style = GetWindowLong(hwnd, GWL_STYLE);
	exstyle = GetWindowLong(hwnd, GWL_EXSTYLE);

	if (style & DS_MODALFRAME || exstyle & WS_EX_DLGMODALFRAME)
		flags |= SEAMLESS_CREATE_MODAL;

	if (((style & WS_POPUP) || (exstyle & WS_EX_TOOLWINDOW))
		&& (style & WS_MINIMIZEBOX) == 0 && (style & WS_MAXIMIZEBOX) == 0) {
		flags |= SEAMLESS_CREATE_POPUP;

		if (GetClassName(hwnd, classname, 256)) {
			if ((strcmp(classname, TOOLTIPS_CLASS) == 0)
				|| (strcmp(classname, "Net UI Tool Window") == 0)
				|| (strcmp(classname, "OfficeTooltip") == 0)
				|| (strcmp(classname, "DUIListViewHost") == 0)) {
				flags |= SEAMLESS_CREATE_TOOLTIP;
			}
		}
	}
	if (! (style & WS_SIZEBOX))
		flags |= SEAMLESS_CREATE_FIXEDSIZE;

	// handle always on top
	if (exstyle & WS_EX_TOPMOST)
		flags |= SEAMLESS_CREATE_TOPMOST;

	return flags;
}

HWND WindowUtil_getParent(HWND hwnd) {
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

BOOL WindowUtil_isFocused(HWND hwnd) {
	HWND focused_hwnd;
	HWND child, parent;
	GUITHREADINFO thread_infos;
	char className[CONSOLE_WINDOW_CLASS_SIZE];

	thread_infos.cbSize = sizeof(GUITHREADINFO);
	if (! GetGUIThreadInfo(GetWindowThreadProcessId(hwnd, NULL), &thread_infos)) {
		return FALSE;
	}

	focused_hwnd = thread_infos.hwndFocus;

	if (GetClassName(hwnd, className, CONSOLE_WINDOW_CLASS_SIZE) == CONSOLE_WINDOW_CLASS_SIZE - 1)
		if (strncmp(CONSOLE_WINDOW_CLASS_NAME, className, CONSOLE_WINDOW_CLASS_SIZE) == 0)
			return (thread_infos.hwndCaret == hwnd);

	if (focused_hwnd == NULL)
		return FALSE;

	if (focused_hwnd == hwnd)
		return TRUE;

	parent = focused_hwnd;
	do {
		child = parent;

		parent = GetParent(child);
	} while(parent);

	return (child == hwnd);
}

BOOL WindowUtil_setFocus(HWND hwnd) {
	BOOL ret;

	// Attach foreground window thread
	AttachThreadInput(GetWindowThreadProcessId(GetForegroundWindow(), NULL), GetCurrentThreadId(), TRUE);

	ret = SetForegroundWindow(hwnd);
	SetFocus(hwnd);

	// Detach the attached thread
	AttachThreadInput(GetWindowThreadProcessId(GetForegroundWindow(), NULL), GetCurrentThreadId(), FALSE);

	return ret;
}

int WindowUtil_getState(HWND hwnd) {
	if (IsZoomed(hwnd))
		return 2;
	else if (IsIconic(hwnd))
		return 1;
	else
		return 0;
}

BOOL WindowUtil_setState(HWND hwnd, int state) {
	if (state == 0)
		ShowWindow(hwnd, SW_RESTORE);
	else if (state == 1)
		ShowWindow(hwnd, SW_MINIMIZE);
	else if (state == 2)
		ShowWindow(hwnd, SW_MAXIMIZE);
	else
		return FALSE;

	return TRUE;
}

HICON WindowUtil_getIcon(HWND hwnd, int large) {
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

	icon = (HICON) GetClassLong(hwnd, large ? GCLP_HICON : GCLP_HICONSM);

	if (icon)
		return icon;

	return NULL;
}

int WindowUtil_extractIcon(HICON icon, char *buffer, int maxlen) {
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
