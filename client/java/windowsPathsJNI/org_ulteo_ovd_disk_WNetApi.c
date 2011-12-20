/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
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
#include "org_ulteo_ovd_disk_WNetApi.h"

#define WNNC_NET_TERMSRV 0x360000


DWORD WNetManageError(DWORD dwResult, LPSTR function) {
	switch (dwResult) {
		case ERROR_NOT_CONTAINER:
			printf("[%s]: not a container\n", function);
			return E_FAIL;

		case ERROR_INVALID_PARAMETER:
			printf("[%s]: Invalid handle\n", function);
			return E_FAIL;

		case ERROR_INVALID_HANDLE:
			printf("[%s]: Invalid handle\n", function);
			return E_FAIL;

		case ERROR_NO_NETWORK:
			printf("[%s]: No network\n", function);
			return E_FAIL;

		case ERROR_INVALID_ADDRESS:
			printf("[%s]: No network\n", function);
			return E_FAIL;

		case ERROR_EXTENDED_ERROR:
			printf("[%s]: Extended error %lu\n", function, GetLastError());
			return E_FAIL;

		case NO_ERROR:
		case ERROR_MORE_DATA:
		case ERROR_NO_MORE_ITEMS:
			// OK, continue
			return S_OK;

		default:
			printf("[WNetEnumResource]: Unknow error\n");
			return E_FAIL;
	}
}

/*
 * Class:     org_ulteo_ovd_disk_WNetApi
 * Method:    nWNetGetProviderName
 * Signature: ()Ljava/lang/String;
 */
JNIEXPORT jstring JNICALL Java_org_ulteo_ovd_disk_WNetApi_nGetProviderName(JNIEnv *env, jclass class, jint provider) {
	DWORD dwResult;
	TCHAR networkProvider[MAX_PATH];
	DWORD size = sizeof(networkProvider);

	dwResult = WNetGetProviderName(provider, networkProvider, &size);
	if (WNetManageError(dwResult, "WNetGetProviderName") == E_FAIL)
		return NULL;

	return (*env)->NewStringUTF(env, networkProvider);
}

/*
 * Class:     org_ulteo_ovd_disk_WNetApi
 * Method:    nOpenEnum
 * Signature: ()J
 */
JNIEXPORT jlong JNICALL Java_org_ulteo_ovd_disk_WNetApi_nOpenEnum(JNIEnv *env, jclass class) {
	HANDLE hEnum = 0;
	DWORD dwResult;

	dwResult = WNetOpenEnum(RESOURCE_CONNECTED, RESOURCETYPE_ANY, 0, NULL, &hEnum);
	if (WNetManageError(dwResult, "WNetOpenEnum") == E_FAIL) {
		return -1;
	}
	return (jlong)hEnum;    // HANDLE is smaller than jlong, however jint is signed
}

/*
 * Class:     org_ulteo_ovd_disk_WNetApi
 * Method:    nGetNext
 * Signature: (JLjava/lang/String;)Ljava/lang/String;
 */
JNIEXPORT jstring JNICALL Java_org_ulteo_ovd_disk_WNetApi_nGetNext(JNIEnv *env, jclass class, jlong handle, jstring networkProvider) {
	DWORD dwResult = 0;
	DWORD dwBuffer = 16384; // 16K
	DWORD cEntries = 1;
	BYTE bBuffer[dwBuffer];
	HANDLE hEnum = (HANDLE)handle;   // HANDLE is smaller than jlong, however jint is signed
	LPNETRESOURCE lpRessource = NULL;
	jstring shareName = NULL;
	const char *providerName = NULL;

	providerName = (*env)->GetStringUTFChars(env, networkProvider, 0);
	if (providerName == NULL) {
		printf("GetStringUTFChars return NULL");
		return NULL;
	}
	do {
		dwResult = WNetEnumResource(hEnum, &cEntries, bBuffer, &dwBuffer);
		if (WNetManageError(dwResult, "WNetEnumResource") == E_FAIL)
			break;

		if (cEntries == 0)
			break;

		lpRessource = (LPNETRESOURCE)bBuffer;
		if (lpRessource[0].lpProvider == NULL)
			continue;

		if (strcmp(lpRessource[0].lpProvider, providerName) == 0) {
			shareName = (*env)->NewStringUTF(env, lpRessource[0].lpRemoteName);
			break;
		}

	} while(dwResult != ERROR_NO_MORE_ITEMS);
	(*env)->ReleaseStringUTFChars(env, networkProvider, providerName);

	return shareName;
}

/*
 * Class:     org_ulteo_ovd_disk_WNetApi
 * Method:    nCloseEnum
 * Signature: (J)V
 */
JNIEXPORT void JNICALL Java_org_ulteo_ovd_disk_WNetApi_nCloseEnum(JNIEnv *env, jclass class, jlong handle) {
	HANDLE hEnum = (HANDLE)handle;   // HANDLE is smaller than jlong, however jint is signed
	DWORD dwResult;

	dwResult = WNetCloseEnum(hEnum);
	WNetManageError(dwResult, "WNetCloseEnum");
}
