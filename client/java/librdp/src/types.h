/**
 * FreeRDP: A Remote Desktop Protocol Implementation
 * Type Definitions
 *
 * Copyright 2009-2011 Jay Sorg
 * Copyright 2011 Marc-Andre Moreau <marcandre.moreau@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

#ifndef FREERDP_TYPES_H
#define FREERDP_TYPES_H

#ifdef _WIN32

#include <windows.h>

#else

#include <inttypes.h>

#define TRUE 1
#define FALSE 0
typedef unsigned char BOOL;
typedef unsigned char BYTE;
typedef unsigned int UINT;
typedef unsigned char UINT8;
typedef unsigned short UINT16;
typedef unsigned int UINT32;
#define uint64 UINT64
typedef unsigned long ULONG, *PULONG;

#define ZeroMemory(ptr, size) memset(ptr, 0, size)

#endif // _WIN32

#ifndef MIN
#define MIN(x,y)	(((x) < (y)) ? (x) : (y))
#endif

#ifndef MAX
#define MAX(x,y)	(((x) > (y)) ? (x) : (y))
#endif


#endif /* __RDP_TYPES_H */
