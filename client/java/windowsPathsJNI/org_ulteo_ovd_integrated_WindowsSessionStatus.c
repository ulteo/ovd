/*
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Vincent ROULLIER <v.roullier@ulteo.com> 2013
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
#include "org_ulteo_ovd_integrated_WindowsSessionStatus.h"

#define WTS_CURRENT_SERVER_HANDLE 0
#define WTS_CURRENT_SESSION ((DWORD)-1)

typedef enum _WTS_INFO_CLASS {
  WTSInitialProgram         = 0,
  WTSApplicationName        = 1,
  WTSWorkingDirectory       = 2,
  WTSOEMId                  = 3,
  WTSSessionId              = 4,
  WTSUserName               = 5,
  WTSWinStationName         = 6,
  WTSDomainName             = 7,
  WTSConnectState           = 8,
  WTSClientBuildNumber      = 9,
  WTSClientName             = 10,
  WTSClientDirectory        = 11,
  WTSClientProductId        = 12,
  WTSClientHardwareId       = 13,
  WTSClientAddress          = 14,
  WTSClientDisplay          = 15,
  WTSClientProtocolType     = 16,
  WTSIdleTime               = 17,
  WTSLogonTime              = 18,
  WTSIncomingBytes          = 19,
  WTSOutgoingBytes          = 20,
  WTSIncomingFrames         = 21,
  WTSOutgoingFrames         = 22,
  WTSClientInfo             = 23,
  WTSSessionInfo            = 24,
  WTSSessionInfoEx          = 25,
  WTSConfigInfo             = 26,
  WTSValidationInfo         = 27,
  WTSSessionAddressV4       = 28,
  WTSIsRemoteSession        = 29
} WTS_INFO_CLASS;

typedef enum _WTS_CONNECTSTATE_CLASS {
  WTSActive,
  WTSConnected,
  WTSConnectQuery,
  WTSShadow,
  WTSDisconnected,
  WTSIdle,
  WTSListen,
  WTSReset,
  WTSDown,
  WTSInit
} WTS_CONNECTSTATE_CLASS;

const char* const WTS_CONNECTSTATE_STRING[] = {
    "WTSActive",
    "WTSConnected",
    "WTSConnectQuery",
    "WTSShadow",
    "WTSDisconnected",
    "WTSIdle",
    "WTSListen",
    "WTSReset",
    "WTSDown",
    "WTSInit"
};

JNIEXPORT jint JNICALL Java_org_ulteo_ovd_integrated_WindowsSessionStatus_nGetSessionStatus(JNIEnv *env, jclass class) {
  wchar_t* szConnectState;
  DWORD dwLen = 0;
  int i = 0;
  HMODULE wtsapi32DLL = LoadLibraryA("C:\\Windows\\System32\\wtsapi32.dll");
  if (!wtsapi32DLL) {
      fprintf(stderr, "LoadLibrary failed\n");
      return (*env)->NewStringUTF(env, "LoadLibrary failed");
  }
  typedef DWORD (WINAPI * WTSQuerySessionInformation_fn)(HANDLE, DWORD, WTS_INFO_CLASS, wchar_t*, DWORD *);
  WTSQuerySessionInformation_fn pfnWTSQuerySessionInformation = (WTSQuerySessionInformation_fn) GetProcAddress(wtsapi32DLL, "WTSQuerySessionInformationW" );

  typedef DWORD (WINAPI* WTSFreeMemory_fn)(DWORD);
  WTSFreeMemory_fn pfnWTSFreeMemory = (WTSFreeMemory_fn) GetProcAddress(wtsapi32DLL, "WTSFreeMemory");

  if (! pfnWTSQuerySessionInformation || ! pfnWTSFreeMemory ) {
    fprintf(stderr, "GetProcAddress Failed \n");
    return (*env)->NewStringUTF(env, "GetProcAddress Failed");
  }
  BOOL bStatus = pfnWTSQuerySessionInformation(WTS_CURRENT_SERVER_HANDLE,
                       WTS_CURRENT_SESSION,
                       WTSConnectState,
                       &szConnectState,
                       &dwLen);


  INT retvalue;
  if( dwLen == sizeof(INT) ) {
    retvalue = * (INT*) szConnectState;
    pfnWTSFreeMemory(retvalue);
  }
  return retvalue;//(*env)->NewStringUTF(env, WTS_CONNECTSTATE_STRING[retvalue]);

}
