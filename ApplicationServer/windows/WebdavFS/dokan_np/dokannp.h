/*
 * Copyright (C) 2011-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011, 2012
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


#ifndef DOKANNP_H_
#define DOKANNP_H_

#define DEBUG 1
#define WAIT_TIME               10000
#define DOKAN_CONTROL_PIPE			L"\\\\.\\pipe\\DokanMounter"
#define REMOTE_NAME                             L"Webdav Drive"

#define DOKAN_CONTROL_MOUNT		1
#define DOKAN_CONTROL_UNMOUNT	2
#define DOKAN_CONTROL_CHECK		3
#define DOKAN_CONTROL_FIND		4
#define DOKAN_CONTROL_LIST		5

#define DOKAN_CONTROL_OPTION_FORCE_UNMOUNT 1

#define DOKAN_CONTROL_SUCCESS	1
#define DOKAN_CONTROL_FAIL		0

#define IOCTL_EVENT_RELEASE \
	CTL_CODE(FILE_DEVICE_UNKNOWN, 0x804, METHOD_BUFFERED, FILE_ANY_ACCESS)

#define WEBDAVPREFFIX   L"WEBDAV"
#define DOKANREDIRECTOR   L"\\Device\\DokanRedirector"

#ifdef DEBUG
	#define DbgPrintW(format, ...) \
		DokanDbgPrintW(format, __VA_ARGS__)
#else
	#define DbgPrintW(format, ...)
#endif


typedef struct _DOKAN_CONTROL {
	ULONG	Type;
	WCHAR	MountPoint[MAX_PATH];
	WCHAR	DeviceName[64];
	ULONG	Option;
	ULONG	Status;

} DOKAN_CONTROL, *PDOKAN_CONTROL;


#endif /* DOKANNP_H_ */
