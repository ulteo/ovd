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

#ifndef _HOOKDLL_H_
#define _HOOKDLL_H_

#include <common/windowUtil.h>

#define HOOK_MSG_STATE			0
#define HOOK_MSG_FOCUS			1
#define HOOK_MSG_ICON			2
#define HOOK_MSG_TITLE			3
#define HOOK_MSG_DESTROY		4
#define HOOK_MSG_POSITION		5
#define HOOK_MSG_SHOW			6
#define HOOK_MSG_DESTROYGRP		7
#define HOOK_MSG_ZCHANGE		8

typedef struct HookMsg_State_ {
	ULONG32 wnd;
	int state;
} HookMsg_State;
typedef struct HookMsg_Focus_ {
	ULONG32 wnd;
} HookMsg_Focus;
typedef struct HookMsg_Icon_ {
	ULONG32 wnd;
	HICON icon;
	BOOL large;
	BOOL haveToGetIcon;
} HookMsg_Icon;
typedef struct HookMsg_Title_ {
	ULONG32 wnd;
	unsigned short title[TITLE_SIZE];
} HookMsg_Title;
typedef struct HookMsg_Destroy_ {
	ULONG32 wnd;
} HookMsg_Destroy;
typedef struct HookMsg_Position_ {
	ULONG32 wnd;
} HookMsg_Position;
typedef struct HookMsg_Show_ {
	ULONG32 wnd;
} HookMsg_Show;
typedef struct HookMsg_DestroyGrp_ {
	DWORD pid;
} HookMsg_DestroyGrp;
typedef struct HookMsg_ZChange_ {
	ULONG32 wnd;
} HookMsg_ZChange;

#endif // _HOOKDLL_H_
