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

#include <windows.h>

#include "internalWindow.h"
#include "seamlessWindow.h"
#include "seamlessWindowHistory.h"
#include "windowUtil.h"

static BOOL g_continue_monitoring = FALSE;
static HANDLE g_monitoring_thread = NULL;

// Return FALSE will stop windows enumeration
static BOOL CALLBACK EnumWindowsProc(HWND hwnd, LPARAM lparam) {
	SeamlessWindow *sw = NULL;

	if (! IsWindowVisible(hwnd))
		return TRUE;

	sw = getWindowFromHistory(hwnd);

	// Return if the window is already registered
	if (sw != NULL)
		return TRUE;

	if (hwnd == InternalWindow_getHandle())
		return TRUE;

	SeamlessWindow_create(hwnd);

	return TRUE;
}

static void checkRegisteredWindows() {
	SeamlessWindow* sw = getHistory();
	if (sw == NULL)
		return;

	while(sw){
		unsigned short *title = NULL;
		RECT *bounds = NULL;

		// Check window visibility
		if (! WindowUtil_isVisible(sw->windows)) {
			SeamlessWindow_destroy(sw);

			sw = sw->next;
			continue;
		}

		// Check window focus
		SeamlessWindow_updateFocus(sw);

		// Check window title
		SeamlessWindow_updateTitle(sw);

		// Check window state
		SeamlessWindow_updateState(sw);

		// Check window position
		SeamlessWindow_updatePosition(sw);

		sw = sw->next;
	}
}

static DWORD WINAPI windows_monitoring(LPVOID lpParam) {
	int i = 0;
	while (g_continue_monitoring) {
		Sleep(100);

		checkRegisteredWindows();

		EnumWindows((WNDENUMPROC)EnumWindowsProc,(LPARAM) 0);

		i++;
	}

	return 0;
}

int start_windows_monitoring() {
	DWORD dwThreadId;

	if (g_monitoring_thread)
		return 1;

	g_continue_monitoring = TRUE;

	// Create the thread to begin execution on its own.
	g_monitoring_thread = CreateThread(
					NULL,			// default security attributes
					0,			// use default stack size
					windows_monitoring,	// thread function name
					NULL,			// argument to thread function
					0,			// use default creation flags
					&dwThreadId		// returns the thread identifier
	);

	// Check the return value for success.
	// If CreateThread fails, terminate execution.
	// This will automatically clean up threads and memory.

	if (g_monitoring_thread == NULL)
		return 2;

	return 0;
}

void stop_windows_monitoring() {
	if (! g_monitoring_thread)
		return;

	g_continue_monitoring = FALSE;

	// Wait until the thread have terminated.
	WaitForSingleObject(g_monitoring_thread, INFINITE);

	// Close the thread handle and free memory allocation.
	CloseHandle(g_monitoring_thread);

	return;
}
