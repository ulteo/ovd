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
