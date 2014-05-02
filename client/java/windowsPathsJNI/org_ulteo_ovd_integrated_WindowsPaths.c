/*
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2011
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

#ifndef _WIN32_IE
#define _WIN32_IE 0x0500
#endif //WIN32_IE

#include <jni.h>
#include <stdio.h>
#include <windows.h>
#include <shlobj.h>
#include "org_ulteo_ovd_integrated_WindowsPaths.h"
#include "org_ulteo_utils_jni_WindowsTweaks.h"

// Used for EnumThreadWndProc
static int currentX;
static int currentY;


DWORD getPath(DWORD csidl, LPSTR path) {
    if (SHGetSpecialFolderPath(NULL, path, csidl, 0) == FALSE) {
        return GetLastError();
    }
    return -1;
}

JNIEXPORT jstring JNICALL Java_org_ulteo_ovd_integrated_WindowsPaths_nGetStartMenuPath(JNIEnv *env, jclass class) {
    TCHAR path[MAX_PATH];
    DWORD error = getPath(CSIDL_STARTMENU, path);
    if (error > -1) {
        printf("Failed to get StartMenu path: error %lu(0x%08lx)", error, error);
        return NULL;
    }

    printf("StartMenu path: %s\n", path);
    return (*env)->NewStringUTF(env, path);
}

JNIEXPORT jstring JNICALL Java_org_ulteo_ovd_integrated_WindowsPaths_nGetDesktopPath(JNIEnv *env, jclass class) {
    TCHAR path[MAX_PATH];
    DWORD error = getPath(CSIDL_DESKTOP, path);
    if (error > -1) {
        printf("Failed to get Desktop path: error %lu(0x%08lx)", error, error);
        return NULL;
    }

    printf("Desktop path: %s\n", path);
    return (*env)->NewStringUTF(env, path);
}

JNIEXPORT jstring JNICALL Java_org_ulteo_ovd_integrated_WindowsPaths_nGetAppDataPath(JNIEnv *env, jclass class) {
    TCHAR path[MAX_PATH];
    DWORD error = getPath(CSIDL_APPDATA, path);
    if (error > -1) {
        printf("Failed to get AppData path: error %lu(0x%08lx)", error, error);
        return NULL;
    }

    printf("AppData path: %s\n", path);
    return (*env)->NewStringUTF(env, path);
}

JNIEXPORT jstring JNICALL Java_org_ulteo_ovd_integrated_WindowsPaths_nGetPersonalDataPath(JNIEnv *env, jclass class) {
    TCHAR path[MAX_PATH];
    DWORD error = getPath(CSIDL_PERSONAL, path);
    if (error > -1) {
        printf("Failed to get PersonalData path: error %lu(0x%08lx)", error, error);
        return NULL;
    }

    printf("PersonalData path: %s\n", path);
    return (*env)->NewStringUTF(env, path);
}

JNIEXPORT void JNICALL Java_org_ulteo_utils_jni_WindowsTweaks_desktopRefresh(JNIEnv *env, jclass class) {
    SHChangeNotify(SHCNE_ASSOCCHANGED, SHCNF_IDLIST, 0, 0);
}



jboolean _setIMEPosition(HWND hwnd, int x, int y) {
	HWND defaultIMEWnd;

	if (hwnd == NULL) {
		printf("Invalid windows for setIMEPosition");
		return JNI_FALSE;
	}

	defaultIMEWnd = ImmGetDefaultIMEWnd(hwnd);

	if (defaultIMEWnd == NULL) {
		printf("Failed to find IME windows");
		return JNI_FALSE;
	}

	CANDIDATEFORM cf;
	cf.dwIndex = 0;
	cf.dwStyle = CFS_CANDIDATEPOS;

	cf.ptCurrentPos.x = x;
	cf.ptCurrentPos.y = y;

	SendMessage(defaultIMEWnd, WM_IME_CONTROL, IMC_SETCANDIDATEPOS, (LPARAM)&cf);

	return JNI_TRUE;;
}


BOOL CALLBACK EnumThreadWndProc( HWND hwnd, LPARAM lParam) {
	char className[255] = {0};

	if (GetClassName(hwnd, className, sizeof(className)) == 0) {
		printf("Failed to get className");
		return TRUE;
	}

	if (strstr(className, "Sun") == NULL) {
		return TRUE;
	}

	_setIMEPosition(hwnd, currentX, currentY);
	return FALSE;
}


JNIEXPORT jboolean JNICALL Java_org_ulteo_utils_jni_WindowsTweaks_setIMEPosition(JNIEnv *env, jclass class, jint x, jint y, jboolean useSeamless) {
	HWND hwnd = GetForegroundWindow();
	char className[255] = {0};
	POINT pt;

	// TODO check windows Class
	if (hwnd == NULL) {
		printf("Failed to find foreground Windows\n");
		return JNI_TRUE;
	}

	pt.x = x;
	pt.x = y;

	if (useSeamless == JNI_TRUE) {
		RECT rect;
		if (GetWindowRect(hwnd, &rect) == TRUE) {
			x -= rect.left;
			y -= rect.top;
		}
	}

	if (x < 0 || y < 0) {
		return JNI_TRUE;
	}

	if (GetClassName(hwnd, className, sizeof(className)) == 0) {
		printf("Failed to get className");
		return TRUE;
	}

	if (strstr(className, "Sun") == NULL) {
		currentX = x;
		currentY = y;

		EnumChildWindows(hwnd, EnumThreadWndProc, 0);

		return JNI_TRUE;
	}

	return _setIMEPosition(hwnd, x, y);
}
