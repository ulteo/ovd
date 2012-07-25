/*
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Yann Hodique <y.hodique@ulteo.com> 2012
 *
 * Copyright (c) 2005, 2006, Oracle and/or its affiliates. All rights reserved.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This code is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License version 2 only, as
 * published by the Free Software Foundation.  Oracle designates this
 * particular file as subject to the "Classpath" exception as provided
 * by Oracle in the LICENSE file that accompanied this code.
 *
 * This code is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License
 * version 2 for more details (a copy is included in the LICENSE file that
 * accompanied this code).
 *
 * You should have received a copy of the GNU General Public License version
 * 2 along with this work; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * Please contact Oracle, 500 Oracle Parkway, Redwood Shores, CA 94065 USA
 * or visit www.oracle.com if you need additional information or have any
 * questions.
 */

#ifndef _ORG_ULTEO_PCSC_PCSC_H_
#define _ORG_ULTEO_PCSC_PCSC_H_

#include <jni.h>

/* disable asserts in product mode */
#ifndef DEBUG
  #ifndef NDEBUG
    #define NDEBUG
  #endif
#else
  #define PCSC_DEBUG
#endif

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <assert.h>

#include <winscard.h>

#define STDOUT_FLUSH() do { fflush(stdout); } while(0)

#ifdef PCSC_DEBUG
jstring pcscLogPrefix(JNIEnv *env, jclass pcscClass);
const char* pcscLogPrefixGet(JNIEnv *env, jstring prefix);
void pcscLogPrefixRelease(JNIEnv *env, jstring prefix, const char* buffer);
#define dprintf(s, ...) do { \
        jstring js = pcscLogPrefix(env, thisClass);                     \
        const char* buffer = pcscLogPrefixGet(env, js);                 \
        printf("%s (native) ", buffer);                                  \
        pcscLogPrefixRelease(env, js, buffer);                          \
        printf(s, ##__VA_ARGS__); fflush(stdout); } while(0)
#else
#define dprintf(s, ...)
#endif

#endif /* _ORG_ULTEO_PCSC_PCSC_H_ */
