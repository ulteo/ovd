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

#include <windows.h>
#include <winnetwk.h>
#include <winsvc.h>
#include <winioctl.h>
#include <stdio.h>
#include <npapi.h>
#include <strsafe.h>
#include "dokannp.h"

static VOID
DokanDbgPrintW(LPCWSTR format, ...)
{
	WCHAR buffer[512];
	va_list argp;
	va_start(argp, format);
	StringCchVPrintfW(buffer, 127, format, argp);
    va_end(argp);
	OutputDebugStringW(buffer);
}

WCHAR 
getNextFreeLetter() {
	WCHAR drive[] = L"E:";
	UINT result = 0;
	int i = 0;

	for (i = (int)L'E' ; i <= (int)L'Z'; i++) {
		drive[0] = (WCHAR)i;

		result = GetDriveType((LPCTSTR)drive);

		if (result == DRIVE_NO_ROOT_DIR) {
			return drive[0];
		}
	}
	return L':';
}

BOOL
SendToDevice(
	LPCWSTR	DeviceName,
	DWORD	IoControlCode,
	PVOID	InputBuffer,
	ULONG	InputLength,
	PVOID	OutputBuffer,
	ULONG	OutputLength,
	PULONG	ReturnedLength)
{
	HANDLE	device;
	BOOL	status;
	ULONG	returnedLength;

	device = CreateFile(
				DeviceName,							// lpFileName
				GENERIC_READ | GENERIC_WRITE,       // dwDesiredAccess
                FILE_SHARE_READ | FILE_SHARE_WRITE, // dwShareMode
                NULL,                               // lpSecurityAttributes
                OPEN_EXISTING,                      // dwCreationDistribution
                0,                                  // dwFlagsAndAttributes
                NULL                                // hTemplateFile
			);

    if (device == INVALID_HANDLE_VALUE) {
		DWORD dwErrorCode = GetLastError();
		DbgPrintW(L"Dokan Error: Failed to open %ws with code %d\n", DeviceName, dwErrorCode);
        return FALSE;
    }

	status = DeviceIoControl(
				device,                 // Handle to device
				IoControlCode,			// IO Control code
				InputBuffer,		    // Input Buffer to driver.
				InputLength,			// Length of input buffer in bytes.
				OutputBuffer,           // Output Buffer from driver.
				OutputLength,			// Length of output buffer in bytes.
				ReturnedLength,		    // Bytes placed in buffer.
				NULL                    // synchronous call
			);

	CloseHandle(device);

	if (!status) {
		DbgPrintW(L"DokanError: Ioctl failed with code %d\n", GetLastError());
		return FALSE;
	}

	return TRUE;
}



LPCWSTR
GetRawDeviceName(LPCWSTR	DeviceName)
{
	static WCHAR rawDeviceName[MAX_PATH];
	StringCchCopyW(rawDeviceName, MAX_PATH, L"\\\\.");
	StringCchCatW(rawDeviceName, MAX_PATH, DeviceName);
	return rawDeviceName;
}

DWORD
SendReleaseIRP(LPCWSTR	DeviceName)
{
	ULONG	returnedLength;
	DbgPrintW(L"send release\n");

	if (!SendToDevice(
				GetRawDeviceName(DeviceName),
				IOCTL_EVENT_RELEASE,
				NULL,
				0,
				NULL,
				0,
				&returnedLength) ) {

		DbgPrintW(L"Failed to unmount device:%ws\n", DeviceName);
		return WN_BAD_VALUE;
	}
	return WN_SUCCESS;
}



BOOL
DokanMountControl(PDOKAN_CONTROL Control)
{
	HANDLE pipe;
	DWORD writtenBytes;
	DWORD readBytes;
	DWORD pipeMode;
	DWORD error;

	for (;;) {
		pipe = CreateFile(DOKAN_CONTROL_PIPE,  GENERIC_READ|GENERIC_WRITE,
						0, NULL, OPEN_EXISTING, 0, NULL);
		if (pipe != INVALID_HANDLE_VALUE) {
			break;
		}

		error = GetLastError();
		if (error == ERROR_PIPE_BUSY) {
			if (!WaitNamedPipe(DOKAN_CONTROL_PIPE, NMPWAIT_USE_DEFAULT_WAIT)) {
				DbgPrintW(L"DokanMounter service : ERROR_PIPE_BUSY\n");
				return FALSE;
			}
			continue;
		} else if (error == ERROR_ACCESS_DENIED) {
			DbgPrintW(L"failed to connect DokanMounter service: access denied\n");
			return FALSE;
		} else {
			DbgPrintW(L"failed to connect DokanMounter service: %d\n", GetLastError());
			return FALSE;
		}
	}

	pipeMode = PIPE_READMODE_MESSAGE|PIPE_WAIT;

	if(!SetNamedPipeHandleState(pipe, &pipeMode, NULL, NULL)) {
		DbgPrintW(L"failed to set named pipe state: %d\n", GetLastError());
		CloseHandle(pipe);
		return FALSE;
	}


	if(!TransactNamedPipe(pipe, Control, sizeof(DOKAN_CONTROL),
		Control, sizeof(DOKAN_CONTROL), &readBytes, NULL)) {
		DbgPrintW(L"failed to transact named pipe: %d\n", GetLastError());
	}

	CloseHandle(pipe);
	if(Control->Status != DOKAN_CONTROL_FAIL) {
		return TRUE;
	} else {
		return FALSE;
	}
}


int
Unmount(LPCWSTR DeviceName, LPCWSTR letter)
{
	int status = 0;
	DOKAN_CONTROL control;
	WCHAR* dev = NULL;

	ZeroMemory(&control, sizeof(DOKAN_CONTROL));

	if (! DefineDosDevice(DDD_REMOVE_DEFINITION, letter, NULL)) {
		DbgPrintW(L"Unable to unlink device %s and letter %s", DeviceName, letter);
	}

	control.Type = DOKAN_CONTROL_UNMOUNT;
	control.Option = DOKAN_CONTROL_OPTION_FORCE_UNMOUNT;
	dev = (WCHAR*)DeviceName;
	dev += lstrlen(L"\\Device");
	StringCchCatW(control.DeviceName, sizeof(control.DeviceName) / sizeof(WCHAR), dev);
	DokanMountControl(&control);

	if (control.Status == DOKAN_CONTROL_SUCCESS) {
		DbgPrintW(L"Unmount success: %s", DeviceName);
		status = SendReleaseIRP(dev);
	} else {
		DbgPrintW(L"Unmount failed: %s", DeviceName);
		status = WN_BAD_VALUE;
	}

	DbgPrintW(L"Unmount status = %d\n", status);
	return status;
}


static DWORD
StartDavModule(WCHAR* letter, WCHAR* remote, WCHAR* username, WCHAR* password)
{
	WCHAR cmdLine[MAX_PATH] = {0};
	BOOL result = 0;
	WCHAR l[] = L"E:";
	int timeout = WAIT_TIME;
	DWORD exitCode = 0;
	
	PROCESS_INFORMATION processInformation;
	STARTUPINFO startupInfo;
	memset(&processInformation, 0, sizeof(processInformation));
	memset(&startupInfo, 0, sizeof(startupInfo));
	
	startupInfo.cb = sizeof(startupInfo);
	startupInfo.dwFlags = STARTF_USESHOWWINDOW;
	startupInfo.wShowWindow = SW_HIDE;

	if (GetEnvironmentVariable(L"SYSTEMROOT", cmdLine, MAX_PATH) == 0) {
		DbgPrintW(L"ERROR: Unable to get SYSTEMROOT environment variable: %08x", GetLastError());
		return WN_WINDOWS_ERROR;
	}
	StringCchCatW(cmdLine, MAX_PATH, L"\\system32\\davfs.exe");
	StringCchCatW(cmdLine, MAX_PATH, L" ");
	
	if (letter == NULL) {
		l[0] = getNextFreeLetter();
		if (l[0] == L':') {
			return WN_BAD_LOCALNAME;
		}
	}
	else {
		l[0] = letter[0];
	}
	StringCchCatW(cmdLine, MAX_PATH, L" ");
	StringCchCatW(cmdLine, MAX_PATH, L"/l ");
	StringCchCatW(cmdLine, MAX_PATH, l);

	if (lstrlen(remote) > 0) {
		StringCchCatW(cmdLine, MAX_PATH, L" ");
		StringCchCatW(cmdLine, MAX_PATH, L"/u ");
		StringCchCatW(cmdLine, MAX_PATH, remote);
	}

	if (lstrlen(username) > 0) {
		StringCchCatW(cmdLine, MAX_PATH, L" ");
		StringCchCatW(cmdLine, MAX_PATH, L"/o ");
		StringCchCatW(cmdLine, MAX_PATH, username);
	}

	if (lstrlen(password) > 0) {
		StringCchCatW(cmdLine, MAX_PATH, L" ");
		StringCchCatW(cmdLine, MAX_PATH, L"/w ");
		StringCchCatW(cmdLine, MAX_PATH, password);
	}

	result = CreateProcess(NULL, cmdLine, NULL, NULL, FALSE, NORMAL_PRIORITY_CLASS, NULL, NULL, &startupInfo, &processInformation);

	if (result == 0)
	{
		DbgPrintW(L"ERROR: CreateProcess failed! %08x", GetLastError());
		return WN_WINDOWS_ERROR;
	}

	while (timeout > 0) {
		if (GetDriveType(l) != DRIVE_NO_ROOT_DIR) {
			DbgPrintW(L"Start: OK");
			return WN_SUCCESS;
		}

		WaitForSingleObject(processInformation.hProcess, 300);
		timeout -= 300;

		GetExitCodeProcess(processInformation.hProcess, &exitCode);
		DbgPrintW(L"Exit code: %d\n", exitCode);

		if (exitCode == ERROR_ACCESS_DENIED) {
			DbgPrintW(L"ERROR: Webdav failed to mount the drive, access denied");
			return WN_ACCESS_DENIED;
		}
		if (exitCode != STILL_ACTIVE)
			break;
	}

	DbgPrintW(L"ERROR: Webdav failed to mount the drive");
	return WN_BAD_NETNAME;
}	

DWORD APIENTRY
NPGetCaps(
	DWORD Index)
{
	DWORD rc = 0;
	DbgPrintW(L"NPGetCaps %d\n", Index);
  
    switch (Index) {
	case WNNC_SPEC_VERSION:
		DbgPrintW(L"  WNNC_SPEC_VERSION\n");
		rc = WNNC_SPEC_VERSION51;
		break;
 
	case WNNC_NET_TYPE:
		DbgPrintW(L"  WNNC_NET_TYPE\n");
		rc = WNNC_NET_RDR2SAMPLE;
		break;
  
	case WNNC_DRIVER_VERSION:
		DbgPrintW(L"  WNNC_DRIVER_VERSION\n");
		rc = 1;
		break;

	case WNNC_CONNECTION:
		DbgPrintW(L"  WNC_CONNECTION\n");
		rc = WNNC_CON_GETCONNECTIONS |
			WNNC_CON_CANCELCONNECTION |
			WNNC_CON_ADDCONNECTION |
			WNNC_CON_ADDCONNECTION3;
		break;

	case WNNC_ENUMERATION:
		DbgPrintW(L"  WNNC_ENUMERATION\n");
		rc = WNNC_ENUM_LOCAL;
		break;
		
	case WNNC_START:
		DbgPrintW(L"  WNNC_START\n");
		rc = 1;
		break;

	case WNNC_USER:
		DbgPrintW(L"  WNNC_USER\n");
		rc = 0;
		break;
	case WNNC_DIALOG:
		DbgPrintW(L"  WNNC_DIALOG\n");
		rc = 0;
		break;
	case WNNC_ADMIN:
		DbgPrintW(L"  WNNC_ADMIN\n");
		rc = 0;
		break;
	default:
		DbgPrintW(L"  default\n");
		rc = 0;
		break;
    }

	return rc;
}

DWORD APIENTRY
NPLogonNotify(
	__in PLUID		LogonId,
	__in PCWSTR		AuthentInfoType,
	__in PVOID		AuthentInfo,
	__in PCWSTR		PreviousAuthentInfoType,
	__in PVOID		PreviousAuthentInfo,
	__in PWSTR		StationName,
	__in PVOID		StationHandle,
	__out PWSTR		*LogonScript)
{
	DbgPrintW(L"NPLogonNotify\n");
	*LogonScript = NULL;
	return WN_SUCCESS;
}

DWORD APIENTRY
NPPasswordChangeNotify(
    __in LPCWSTR AuthentInfoType,
    __in LPVOID AuthentInfo,
	__in LPCWSTR PreviousAuthentInfoType,
	__in LPVOID RreviousAuthentInfo,
	__in LPWSTR StationName,
	__in PVOID StationHandle,
	__in DWORD ChangeInfo)
{
	DbgPrintW(L"NPPasswordChangeNotify\n");
	SetLastError(WN_NOT_SUPPORTED);
	return WN_NOT_SUPPORTED;
}


DWORD APIENTRY
NPAddConnection(
    __in LPNETRESOURCE NetResource,
	__in LPWSTR Password,
    __in LPWSTR UserName)
{
	DbgPrintW(L"NPAddConnection\n");
	return  NPAddConnection3(NULL, NetResource, Password, UserName, 0);
}

DWORD APIENTRY
NPAddConnection3(
    __in HWND WndOwner,
	__in LPNETRESOURCE NetResource,
	__in LPWSTR Password,
	__in LPWSTR UserName,
	__in DWORD Flags)
{
	WCHAR local[3] = {0};
	WCHAR remote[MAX_PATH*2] = {0};
	WCHAR* wpos = NULL;
	DWORD status = WN_BAD_VALUE;

	wpos = NetResource->lpRemoteName;

	DbgPrintW(L"NPAddConnection3\n");
	DbgPrintW(L"  LocalName: %s\n", NetResource->lpLocalName);
	DbgPrintW(L"  RemoteName: %s\n", NetResource->lpRemoteName);
	DbgPrintW(L"  UserName: %s\n", UserName);
	DbgPrintW(L"  Password: %s\n", Password);

	ZeroMemory(local, sizeof(local));
	if (_wcsnicmp(wpos, WEBDAVPREFFIX, lstrlen(WEBDAVPREFFIX)) != 0) {
		return WN_BAD_NETNAME;
	}
	wpos += lstrlen(WEBDAVPREFFIX);
	StringCchPrintfW(remote, MAX_PATH*2, L"%s%s", L"HTTP", wpos);

	if (NetResource->lpLocalName == NULL)
		local[0] = getNextFreeLetter();
	else
		local[0] = NetResource->lpLocalName[0];

	if (local[0] == ':')
		return WN_BAD_NETNAME;

	return StartDavModule(local, remote, UserName, Password);
}

DWORD APIENTRY
NPCancelConnection(
     __in LPWSTR Name,
	 __in BOOL Force)
{
	WCHAR device[MAX_PATH];
	WCHAR local[3] = {0};
	DWORD status = WN_BAD_VALUE;

	DbgPrintW(L"NpCancelConnection %s %d\n", Name, Force);

	if (lstrlen(Name) > 1 && Name[1] == L':') {
		local[0] = (WCHAR)toupper(Name[0]);
		local[1] = L':';
		local[2] = L'\0';
	}

	DbgPrintW(L"Drive %s\n", local);

	if (QueryDosDevice(local, device, MAX_PATH)) {
		DbgPrintW(L"Device %s\n", device);
		if (lstrlen(device) < lstrlen(DOKANREDIRECTOR)) {
			DbgPrintW(L"return WN_NOT_CONNECTED\n",);
			return WN_NOT_CONNECTED;
		}

	    if (_wcsnicmp(device, DOKANREDIRECTOR, lstrlen(DOKANREDIRECTOR)) == 0) {
	    	DbgPrintW(L"Try to unmount %s\n", device);
	    	status = Unmount(device, local);

	    }
	} else {
		DbgPrintW(L"NOT A WEBDAV DEVICE\n");
		status = WN_NOT_CONNECTED;
	}

	return status;
}


DWORD APIENTRY
NPGetConnection(
    __in LPWSTR LocalName,
	__out LPWSTR RemoteName,
	__inout LPDWORD BufferSize)
{
	WCHAR shareName[MAX_PATH] = {0};
	HKEY hkey = NULL;
	DWORD type = 0;
	DWORD len = 0;
	HRESULT err = 0;

	DbgPrintW(L"NpGetConnection %s, %d\n", LocalName, *BufferSize);

	err = RegOpenKeyEx(HKEY_CURRENT_USER, L"Software\\Ulteo\\WebdavFS", 0, KEY_READ, &hkey);
	if (err != ERROR_SUCCESS) {
		DbgPrintW(L"NpGetConnection: key (Software\\Ulteo\\WebdavFS) do not exist in HKEY_CURRENT_USER\n");
		return WN_SUCCESS;
	}

	len = MAX_PATH * sizeof(WCHAR);
	err = RegQueryValueEx(hkey, LocalName, NULL, &type, shareName, &len);

	if (err != ERROR_SUCCESS || len >= MAX_PATH) {
		DbgPrintW(L"NpGetConnection: key (%s) do not exist in HKEY_CURRENT_USER\\Software\\Ulteo\\WebdavFS \n", LocalName);
		RegCloseKey(hkey);
		return WN_SUCCESS;
	}
	DbgPrintW(L"NpGetConnection: key (%s) \n", shareName);
	RegCloseKey(hkey);

	if (*BufferSize < sizeof(WCHAR) * lstrlen(shareName)) {
		return WN_MORE_DATA;
	}

	StringCchCopyW(RemoteName, *BufferSize, shareName);
	*BufferSize = lstrlen(RemoteName) * sizeof(WCHAR);

	return WN_SUCCESS;
}


DWORD APIENTRY
NPOpenEnum(
     __in DWORD Scope,
	 __in DWORD Type,
	 __in DWORD Usage,
	 __in LPNETRESOURCE NetResource,
	 __in LPHANDLE Enum)
{
	DWORD status;
	DbgPrintW(L"NPOpenEnum\n");
    switch (Scope){
	case RESOURCE_CONNECTED:
        {
            *Enum = HeapAlloc(GetProcessHeap(),
							  HEAP_ZERO_MEMORY,
							  sizeof(ULONG));

            if (*Enum)
                status = WN_SUCCESS;
			else
                status = WN_OUT_OF_MEMORY;
        }
        break;
	case RESOURCE_CONTEXT:
	default:
		status  = WN_NOT_SUPPORTED;
		break;
    }
	return status;
}


DWORD APIENTRY
NPCloseEnum(
	__in HANDLE Enum)
{
	DbgPrintW(L"NpCloseEnum\n");
    HeapFree(GetProcessHeap(), 0, (PVOID)Enum);
	return WN_SUCCESS;
}


DWORD APIENTRY
NPGetResourceParent(
    __in LPNETRESOURCE NetResource,
	__in LPVOID Buffer,
	__in LPDWORD BufferSize)
{
	DbgPrintW(L"NPGetResourceParent\n");
	return WN_NOT_SUPPORTED;
}


DWORD APIENTRY
NPEnumResource(
     __in HANDLE Enum,
	 __in LPDWORD Count,
	 __in LPVOID Buffer,
	 __in LPDWORD BufferSize)
{
	DbgPrintW(L"NPEnumResource\n");
	*Count = 0;
	return WN_SUCCESS;
}

DWORD APIENTRY
NPGetResourceInformation(
    __in LPNETRESOURCE NetResource,
	__out LPVOID Buffer,
	__out LPDWORD BufferSize,
    __out LPWSTR *System)
{
	DbgPrintW(L"NPGetResourceInformation\n");
	return WN_NOT_SUPPORTED;
}


DWORD APIENTRY
NPGetUniversalName(
    __in LPCWSTR LocalPath,
	__in DWORD InfoLevel,
	__in LPVOID Buffer,
	__in LPDWORD BufferSize)
{
	DbgPrintW(L"NPGetUniversalName %s\n", LocalPath);
	return WN_NOT_SUPPORTED;
}

