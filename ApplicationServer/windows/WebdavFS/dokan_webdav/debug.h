/*
 * debug.h
 *
 *  Created on: 18 avr. 2011
 *      Author: david
 */

#ifndef DEBUG_H_
#define DEBUG_H_


#define  DbgPrint(format, ...) \
{ \
	if (g_DebugMode) { \
		WCHAR buffer[512]; \
		va_list argp; \
		va_start(argp, format); \
		vswprintf_s(buffer, sizeof(buffer)/sizeof(WCHAR), format, argp); \
		va_end(argp); \
		if (g_UseStdErr) { \
			fwprintf(stderr, buffer); \
		} else { \
			OutputDebugStringW(buffer); \
		} \
	} \
}

#endif /* DEBUG_H_ */
