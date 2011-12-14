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

#ifndef _SEAMLESSWINDOW_H_
#define _SEAMLESSWINDOW_H_

#include <windows.h>

typedef struct SeamlessWindow_ {
	HWND windows;
	unsigned short *title;
	RECT *bounds;
	int state;
	BOOL focus;
	BOOL is_shown;
	struct SeamlessWindow_* next;
} SeamlessWindow;

void SeamlessWindow_create(HWND hwnd);
void SeamlessWindow_free(SeamlessWindow * n_);
void SeamlessWindow_updateZOrder(SeamlessWindow *sw);
void SeamlessWindow_updatePosition(SeamlessWindow *sw);
void SeamlessWindow_updateIcon(SeamlessWindow *sw, HICON icon, int large);
BOOL SeamlessWindow_updateTitle(SeamlessWindow *sw);
BOOL SeamlessWindow_updateFocus(SeamlessWindow *sw);
BOOL SeamlessWindow_updateState(SeamlessWindow *sw);
void SeamlessWindow_destroy(SeamlessWindow *sw);

#endif // _SEAMLESSWINDOW_H_
