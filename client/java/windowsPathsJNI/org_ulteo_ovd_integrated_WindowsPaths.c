/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

BOOL getPath(DWORD csidl, LPSTR path) {
    if (SHGetSpecialFolderPath(NULL, path, csidl, 0) == FALSE)
        return FALSE;
    return TRUE;
}

JNIEXPORT jstring JNICALL Java_org_ulteo_ovd_integrated_WindowsPaths_nGetStartMenuPath(JNIEnv *env, jclass class) {
    jstring ret = NULL;
    TCHAR path[MAX_PATH];

    if (getPath(CSIDL_STARTMENU, path) == TRUE)
        ret = (*env)->NewStringUTF(env, path);
    printf("StartMenu path: %s\n", path);
    return ret;
}

JNIEXPORT jstring JNICALL Java_org_ulteo_ovd_integrated_WindowsPaths_nGetDesktopPath(JNIEnv *env, jclass class) {
    jstring ret = NULL;
    TCHAR path[MAX_PATH];

    if (getPath(CSIDL_DESKTOP, path) == TRUE)
        ret = (*env)->NewStringUTF(env, path);
    printf("Desktop path: %s\n", path);
    return ret;
}

JNIEXPORT jstring JNICALL Java_org_ulteo_ovd_integrated_WindowsPaths_nGetAppDataPath(JNIEnv *env, jclass class) {
    jstring ret = NULL;
    TCHAR path[MAX_PATH];

    if (getPath(CSIDL_APPDATA, path) == TRUE)
        ret = (*env)->NewStringUTF(env, path);
    printf("AppData path: %s\n", path);
    return ret;
}

JNIEXPORT jstring JNICALL Java_org_ulteo_ovd_integrated_WindowsPaths_nGetPersonalDataPath(JNIEnv *env, jclass class) {
    jstring ret = NULL;
    TCHAR path[MAX_PATH];

    if (getPath(CSIDL_PERSONAL, path) == TRUE)
        ret = (*env)->NewStringUTF(env, path);
    printf("PersonalData path: %s\n", path);
    return ret;
}
