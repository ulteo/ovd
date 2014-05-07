/* -*- c-basic-offset: 8 -*-
   rdesktop: A Remote Desktop Protocol client.
   Seamless windows - Virtual channel handling

   Copyright (C) Pierre Ossman <ossman@cendio.se> 2006

   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program; if not, write to the Free Software
   Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*/

#include <assert.h>
#include <stdio.h>
#include <stdarg.h>
#include <errno.h>

#include <windows.h>
#include <wtsapi32.h>
#include <cchannel.h>

#include "vchannel.h"

static HANDLE g_mutex = NULL;
static HANDLE g_vchannel = NULL;
static unsigned int g_opencount = 0;



DLL_EXPORT int
vchannel_open(const char* name)
{
	char mutexName[MAX_PATH];

	g_opencount++;
	if (g_opencount > 1)
		return 0;

	g_vchannel = WTSVirtualChannelOpen(WTS_CURRENT_SERVER_HANDLE, WTS_CURRENT_SESSION, (char*)name);

	if (g_vchannel == NULL)
		return -1;

	sprintf_s(mutexName, sizeof(mutexName), "Local\\%s", name);
	g_mutex = CreateMutex(NULL, FALSE, mutexName);
	if (!g_mutex)
	{
		WTSVirtualChannelClose(g_vchannel);
		g_vchannel = NULL;
		return -1;
	}

	return 0;
}

DLL_EXPORT void
vchannel_close()
{
	g_opencount--;
	if (g_opencount > 0)
		return;

	if (g_mutex)
		CloseHandle(g_mutex);

	if (g_vchannel)
		WTSVirtualChannelClose(g_vchannel);

	g_mutex = NULL;
	g_vchannel = NULL;
}

DLL_EXPORT int
vchannel_is_open()
{
	if (g_vchannel == NULL)
		return 0;
	else
		return 1;
}

DLL_EXPORT int
vchannel_read(char *line, size_t length)
{
	BOOL result;
	ULONG bytes_read;

	result = WTSVirtualChannelRead(g_vchannel, 0, line, length, &bytes_read);

	if (!result)
	{
		errno = EIO;
		return -1;
	}

	return bytes_read;
}

DLL_EXPORT int
vchannel_write(char *data, size_t length)
{
	BOOL result;
	ULONG bytes_written;

	assert(vchannel_is_open());

	WaitForSingleObject(g_mutex, INFINITE);

	result = WTSVirtualChannelWrite(g_vchannel, data, length, &bytes_written);
	printf("get last error %u\n", GetLastError());
	ReleaseMutex(g_mutex);

	if (!result)
		return -1;

	return bytes_written;
}

DLL_EXPORT void
vchannel_block()
{
	WaitForSingleObject(g_mutex, INFINITE);
}

DLL_EXPORT void
vchannel_unblock()
{
	ReleaseMutex(g_mutex);
}
