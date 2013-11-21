/*
 * Copyright (C) 2012-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Yann Hodique <y.hodique@ulteo.com> 2012
 * Abraham Macías Paredes <amacias@solutia-it.es> 2013
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

#include "org_ulteo_pcsc_PCSC.h"
#include "org_ulteo_pcsc_PlatformPCSC.h"

#define MAX_STACK_BUFFER_SIZE 8192

// make the buffers larger than what should be necessary, just in case
#define ATR_BUFFER_SIZE 128
#define ATTRIB_BUFFER_SIZE 128
#define READERNAME_BUFFER_SIZE 128
#define RECEIVE_BUFFER_SIZE MAX_STACK_BUFFER_SIZE

#define PCSC_EXCEPTION_NAME "org/ulteo/pcsc/PCSCException"

void throwPCSCException(JNIEnv* env, LONG code) {
	jclass pcscClass;
	jmethodID constructor;
	jthrowable pcscException;

	pcscClass = (*env)->FindClass(env, PCSC_EXCEPTION_NAME);
	assert(pcscClass != NULL);
	constructor = (*env)->GetMethodID(env, pcscClass, "<init>", "(I)V");
	assert(constructor != NULL);
	pcscException = (jthrowable) (*env)->NewObject(env, pcscClass, constructor, (jint)code);
	(*env)->Throw(env, pcscException);
}

jboolean handleRV(JNIEnv* env, LONG code) {
	if (code == SCARD_S_SUCCESS) {
		return JNI_FALSE;
	} else {
		throwPCSCException(env, code);
		return JNI_TRUE;
	}
}

JNIEXPORT jint JNICALL JNI_OnLoad(JavaVM *vm, void *reserved) {
	return JNI_VERSION_1_4;
}

JNIEXPORT jlong JNICALL Java_org_ulteo_pcsc_PCSC_SCardEstablishContext
	(JNIEnv *env, jclass thisClass, jint dwScope)
{
	SCARDCONTEXT context;
	LONG rv;
	dprintf("-establishContext (%d)\n", (int)dwScope);
	rv = CALL_SCardEstablishContext(dwScope, NULL, NULL, &context);
	if (handleRV(env, rv)) {
		context = 0;
	}
	// note: SCARDCONTEXT is typedef'd as long, so this works
	dprintf("-context: %lx\n", (unsigned long)context);
	return (jlong)context;
}

JNIEXPORT void JNICALL Java_org_ulteo_pcsc_PCSC_SCardReleaseContext(
	JNIEnv *env, jclass thisClass, jlong hContext) {
	LONG rv;
	dprintf("-releaseContext: %lx\n", (unsigned long)hContext);
	rv = CALL_SCardReleaseContext(hContext);
	handleRV(env, rv);
}

JNIEXPORT jboolean JNICALL Java_org_ulteo_pcsc_PCSC_SCardIsValidContext(
	JNIEnv *env, jclass thisClass, jlong hContext) {
	LONG rv;
	dprintf("-isValidContext: %lx\n", (unsigned long)hContext);
	rv = CALL_SCardIsValidContext(hContext);
	handleRV(env, rv);
	return (jboolean)(rv == SCARD_S_SUCCESS);
}

/**
 * Convert a multi string to a java string array,
 */
jobjectArray pcsc_multi2jstring(JNIEnv *env, char *spec) {
	jobjectArray result;
	jclass stringClass;
	char *cp, **tab;
	jstring js;
	int cnt = 0;

	cp = spec;
	while (*cp != 0) {
		cp += (strlen(cp) + 1);
		++cnt;
	}

	tab = (char **)malloc(cnt * sizeof(char *));

	cnt = 0;
	cp = spec;
	while (*cp != 0) {
		tab[cnt++] = cp;
		cp += (strlen(cp) + 1);
	}

	stringClass = (*env)->FindClass(env, "java/lang/String");
	assert(stringClass != NULL);

	result = (*env)->NewObjectArray(env, cnt, stringClass, NULL);
	while (cnt-- > 0) {
		js = (*env)->NewStringUTF(env, tab[cnt]);
		(*env)->SetObjectArrayElement(env, result, cnt, js);
	}
	free(tab);
	return result;
}

#ifdef PCSC_DEBUG
jstring pcscLogPrefix(JNIEnv *env, jclass pcscClass) {
	return NULL;
	jstring prefix = NULL;
	jmethodID get_prefix_meth = NULL;

	get_prefix_meth = (*env)->GetStaticMethodID(env, pcscClass, "getDebugLogPrefix",
												"()Ljava/lang/String;");
	assert(get_prefix_meth != NULL);
	prefix = (jstring)((*env)->CallStaticObjectMethod(env, pcscClass, get_prefix_meth));
	return prefix;
}

const char* pcscLogPrefixGet(JNIEnv *env, jstring prefix) {
	return "SmartCard NativeCode";
	return (*env)->GetStringUTFChars(env, prefix, 0);
}

void pcscLogPrefixRelease(JNIEnv *env, jstring prefix, const char* buffer) {
	return;
	(*env)->ReleaseStringUTFChars(env, prefix, buffer);
}
#endif

JNIEXPORT jobjectArray JNICALL Java_org_ulteo_pcsc_PCSC_SCardListReaders
	(JNIEnv *env, jclass thisClass, jlong jContext)
{
	SCARDCONTEXT context = (SCARDCONTEXT)jContext;
	LONG rv;
	LPTSTR mszReaders;
	DWORD size;
	jobjectArray result;

	dprintf("-context: %lx\n", (unsigned long)context);
	rv = CALL_SCardListReaders(context, NULL, NULL, &size);
	if (handleRV(env, rv)) {
		return NULL;
	}
	dprintf("-size: %ld\n", size);

	mszReaders = malloc(size);
	rv = CALL_SCardListReaders(context, NULL, mszReaders, &size);
	if (handleRV(env, rv)) {
		free(mszReaders);
		return NULL;
	}
	dprintf("-String: %s\n", mszReaders);

	result = pcsc_multi2jstring(env, mszReaders);
	free(mszReaders);
	return result;
}

JNIEXPORT jobject JNICALL Java_org_ulteo_pcsc_PCSC_PrivateSCardConnect
	(JNIEnv *env, jclass thisClass, jlong jContext, jstring jReaderName,
	jint jShareMode, jint jPreferredProtocols)
{
	SCARDCONTEXT context = (SCARDCONTEXT)jContext;
	LONG rv;
	LPCTSTR readerName;
	SCARDHANDLE card;
	DWORD proto;
	jclass connectClass;
	jobject connectObject;
	jmethodID constructor;

	readerName = (*env)->GetStringUTFChars(env, jReaderName, NULL);
	dprintf("-PrivateSCardConnect(%lx, %s, %d, %d)\n", (unsigned long)context, readerName, (int)jShareMode, (int)jPreferredProtocols);
	rv = CALL_SCardConnect(context, readerName, jShareMode, jPreferredProtocols, &card, &proto);
	(*env)->ReleaseStringUTFChars(env, jReaderName, readerName);
	dprintf("-cardhandle: %lx\n", (unsigned long)card);
	dprintf("-protocol: %x\n", (int)proto);
	if (handleRV(env, rv)) {
		return NULL;
	}
	connectClass = (*env)->FindClass(env, "org/ulteo/pcsc/Connection");
	assert(connectClass != NULL);
	constructor = (*env)->GetMethodID(env, connectClass, "<init>", "(JJ)V");
	assert(constructor != NULL);

	connectObject = (jobject) (*env)->NewObject(
		env, connectClass, constructor, (jlong)card, (jlong)proto);
	return connectObject;
}

JNIEXPORT jobject JNICALL Java_org_ulteo_pcsc_PCSC_PrivateSCardReconnect
	(JNIEnv *env, jclass thisClass, jlong jCard,
	 jint jShareMode, jint jPreferredProtocols, jint jInitialization)
{
	SCARDHANDLE card = (SCARDHANDLE)jCard;
	LONG rv;
	DWORD proto;
	jclass connectClass;
	jobject connectObject;
	jmethodID constructor;

	dprintf("-PrivateSCardReconnect(%lx, %d, %d, %d)\n", (unsigned long)card, (int)jShareMode, (int)jPreferredProtocols, (int)jInitialization);
	rv = CALL_SCardReconnect(card, jShareMode, jPreferredProtocols, jInitialization, &proto);
	dprintf("-cardhandle: %lx\n", (unsigned long)card);
	dprintf("-protocol: %x\n", (int)proto);
	if (handleRV(env, rv)) {
		return NULL;
	}
	connectClass = (*env)->FindClass(env, "org/ulteo/pcsc/Connection");
	assert(connectClass != NULL);
	constructor = (*env)->GetMethodID(env, connectClass, "<init>", "(JJ)V");
	assert(constructor != NULL);

	connectObject = (jobject) (*env)->NewObject(
		env, connectClass, constructor, (jlong)card, (jlong)proto);
	return connectObject;
}

JNIEXPORT jbyteArray JNICALL Java_org_ulteo_pcsc_PCSC_SCardTransmit
	(JNIEnv *env, jclass thisClass, jlong jCard, jint protocol,
	jbyteArray jBuf, jint jOfs, jint jLen)
{
	SCARDHANDLE card = (SCARDHANDLE)jCard;
	LONG rv;
	SCARD_IO_REQUEST sendPci;
	unsigned char *sbuf;
	unsigned char rbuf[RECEIVE_BUFFER_SIZE];
	DWORD rlen = RECEIVE_BUFFER_SIZE;
	int ofs = (int)jOfs;
	int len = (int)jLen;
	jbyteArray jOut;

	sendPci.dwProtocol = protocol;
	sendPci.cbPciLength = sizeof(SCARD_IO_REQUEST);

	sbuf = (unsigned char *) ((*env)->GetByteArrayElements(env, jBuf, NULL));
	rv = CALL_SCardTransmit(card, &sendPci, sbuf + ofs, len, NULL, rbuf, &rlen);
	(*env)->ReleaseByteArrayElements(env, jBuf, (jbyte *)sbuf, JNI_ABORT);

	if (handleRV(env, rv)) {
		return NULL;
	}

	jOut = (*env)->NewByteArray(env, rlen);
	(*env)->SetByteArrayRegion(env, jOut, 0, rlen, (jbyte *)rbuf);
	return jOut;
}

JNIEXPORT jobject JNICALL Java_org_ulteo_pcsc_PCSC_PrivateSCardStatus
	(JNIEnv *env, jclass thisClass, jlong jCard)
{
	SCARDHANDLE card = (SCARDHANDLE)jCard;
	LONG rv;
	char readerName[READERNAME_BUFFER_SIZE];
	DWORD readerLen = READERNAME_BUFFER_SIZE;
	unsigned char atr[ATR_BUFFER_SIZE];
	DWORD atrLen = ATR_BUFFER_SIZE;
	DWORD state;
	DWORD protocol;
	jbyteArray jArray;
	jclass statusClass;
	jobject statusObject;
	jmethodID constructor;
	jobjectArray names;

	dprintf("-PrivateSCardStatus(%lx)\n", (unsigned long)card);
	rv = CALL_SCardStatus(card, readerName, &readerLen, &state, &protocol, atr, &atrLen);
	if (handleRV(env, rv)) {
		return NULL;
	}
	dprintf("-reader: %s\n", readerName);
	dprintf("-state: %ld\n", state);
	dprintf("-protocol: %ld\n", protocol);

	statusClass = (*env)->FindClass(env, "org/ulteo/pcsc/Status");
	assert(statusClass != NULL);
	constructor = (*env)->GetMethodID(env, statusClass, "<init>",\
									  "([Ljava/lang/String;[BII)V");
	assert(constructor != NULL);

	names = pcsc_multi2jstring(env, readerName);

	jArray = (*env)->NewByteArray(env, atrLen);
	(*env)->SetByteArrayRegion(env, jArray, 0, atrLen, (jbyte *)atr);

	statusObject = (jobject) (*env)->NewObject(
		env, statusClass, constructor, names, jArray, state, protocol);
	return statusObject;
}

JNIEXPORT void JNICALL Java_org_ulteo_pcsc_PCSC_SCardDisconnect
	(JNIEnv *env, jclass thisClass, jlong jCard, jint jDisposition)
{
	SCARDHANDLE card = (SCARDHANDLE)jCard;
	LONG rv;

	dprintf("-SCardDisconnect(%lx, %d)\n", (unsigned long)card, (int)jDisposition);
	rv = CALL_SCardDisconnect(card, jDisposition);
	dprintf("-disconnect: 0x%lX\n", rv);
	handleRV(env, rv);
	return;
}

#ifdef PCSC_DEBUG
#include <ctype.h>
static void hex_dump(JNIEnv *env, jclass thisClass, void *data, int size, const char* prefix)
{
	/* dumps size bytes of *data to stdout. Looks like:
	 * [0000] 75 6E 6B 6E 6F 77 6E 20
	 *				  30 FF 00 00 00 00 39 00 unknown 0.....9.
	 * (in a single line of course)
	 */

	unsigned char *p = data;
	unsigned char c;
	int n;
	char bytestr[4] = {0};
	char addrstr[10] = {0};
	char hexstr[ 16*3 + 5] = {0};
	char charstr[16*1 + 5] = {0};
	for(n=1;n<=size;n++) {
		if (n%16 == 1) {
			/* store address for this line */
			snprintf(addrstr, sizeof(addrstr), "%.4x",
					 (unsigned int)(p-(unsigned char*)data));
		}
			
		c = *p;
		if (isalnum(c) == 0) {
			c = '.';
		}

		/* store hex str (for left side) */
		snprintf(bytestr, sizeof(bytestr), "%02X ", *p);
		strncat(hexstr, bytestr, sizeof(hexstr)-strlen(hexstr)-1);

		/* store char str (for right side) */
		snprintf(bytestr, sizeof(bytestr), "%c", c);
		strncat(charstr, bytestr, sizeof(charstr)-strlen(charstr)-1);

		if(n%16 == 0) { 
			/* line completed */
			dprintf("%s[%4.4s]   %-50.50s  %s\n", prefix, addrstr, hexstr, charstr);
			hexstr[0] = 0;
			charstr[0] = 0;
		} else if(n%8 == 0) {
			/* half line: add whitespaces */
			strncat(hexstr, "  ", sizeof(hexstr)-strlen(hexstr)-1);
			strncat(charstr, " ", sizeof(charstr)-strlen(charstr)-1);
		}
		p++; /* next byte */
	}

	if (strlen(hexstr) > 0) {
		/* print rest of buffer if not empty */
		dprintf("%s[%4.4s]   %-50.50s  %s\n", prefix, addrstr, hexstr, charstr);
	}
}
#endif

JNIEXPORT void JNICALL Java_org_ulteo_pcsc_PCSC_PrivateSCardGetStatusChange
	(JNIEnv *env, jclass thisClass, jlong jContext, jlong jTimeout,
	jobjectArray jReaders)
{
	SCARDCONTEXT context = (SCARDCONTEXT)jContext;
	LONG rv;
	int readers = (*env)->GetArrayLength(env, jReaders);
	SCARD_READERSTATE *readerState = malloc(readers * sizeof(SCARD_READERSTATE));
	int i;
	jclass readerstate_cls = NULL;
	jmethodID setatr_meth = NULL;
	jmethodID setevent_meth = NULL;
	jmethodID setstate_meth = NULL;
	jmethodID getname_meth = NULL;
	jmethodID getstate_meth = NULL;
	jmethodID getevent_meth = NULL;
	jmethodID getatr_meth = NULL;

	jTimeout &= 0x00000000ffffffff;
	memset(readerState, 0, readers * sizeof(SCARD_READERSTATE));

	readerstate_cls = (*env)->FindClass(env, "org/ulteo/pcsc/ReaderState");
	assert(readerstate_cls != NULL);
	setatr_meth = (*env)->GetMethodID(env, readerstate_cls, "setAtr", "([B)V");
	assert(setatr_meth != NULL);
	setevent_meth = (*env)->GetMethodID(env, readerstate_cls, "setEvent",
										"(I)V");
	setstate_meth = (*env)->GetMethodID(env, readerstate_cls, "setState",
										"(I)V");
	assert(setevent_meth != NULL);
	getname_meth = (*env)->GetMethodID(env, readerstate_cls, "getName",
										"()Ljava/lang/String;");
	assert(getname_meth != NULL);
	getstate_meth = (*env)->GetMethodID(env, readerstate_cls, "getState",
										"()I");
	assert(getstate_meth != NULL);
	getevent_meth = (*env)->GetMethodID(env, readerstate_cls, "getEvent",
										"()I");
	assert(getevent_meth != NULL);
	getatr_meth = (*env)->GetMethodID(env, readerstate_cls, "getAtr",
										"()[B");
	assert(getevent_meth != NULL);

	dprintf("-get status change %lx %lx (%ld)\n", context, (DWORD) jTimeout, (long)sizeof(DWORD));
	for (i = 0; i < readers; i++) {
		jobject jReader = (*env)->GetObjectArrayElement(env, jReaders, i);
		jobject jReaderName = (*env)->CallObjectMethod(env, jReader, getname_meth);
		jint jReaderState = (*env)->CallIntMethod(env, jReader, getstate_meth);
		jint jReaderEvent = (*env)->CallIntMethod(env, jReader, getevent_meth);
		jbyteArray atr = (jbyteArray)((*env)->CallObjectMethod(env, jReader, getatr_meth));

		readerState[i].szReader = (*env)->GetStringUTFChars(env, jReaderName, NULL);
		readerState[i].pvUserData = NULL;
//		readerState[i].dwCurrentState = jReaderState & 0x0000ffff;
//		readerState[i].dwEventState = jReaderEvent & 0x0000ffff;
		readerState[i].dwCurrentState = jReaderState;
		readerState[i].dwEventState = jReaderEvent;
		readerState[i].cbAtr = (*env)->GetArrayLength(env, atr);
		(*env)->GetByteArrayRegion(env, atr, 0, readerState[i].cbAtr, (jbyte*)readerState[i].rgbAtr);
	}

	dprintf("--before call\n");
#ifdef PCSC_DEBUG
	{
		jstring pref = pcscLogPrefix(env, thisClass);
		const char* buffer = pcscLogPrefixGet(env, pref);
		hex_dump(env, thisClass, readerState, readers * sizeof(SCARD_READERSTATE), buffer);
#endif
		rv = CALL_SCardGetStatusChange(context, (DWORD)jTimeout, readerState, readers);
		dprintf("--after call\n");
#ifdef PCSC_DEBUG
		hex_dump(env, thisClass, readerState, readers * sizeof(SCARD_READERSTATE), buffer);
		pcscLogPrefixRelease(env, pref, buffer);
	}
#endif
	for (i = 0; i < readers; i++) {
		jint eventTmp, stateTmp;
		jbyteArray atr;
		jint jReaderState, jReaderEvent;
		jobject jReader = (*env)->GetObjectArrayElement(env, jReaders, i);

		dprintf("-reader status %s: 0x%lX, 0x%lX\n", readerState[i].szReader,
			readerState[i].dwCurrentState, readerState[i].dwEventState);

		jReaderState = (*env)->CallIntMethod(env, jReader, getstate_meth);
		jReaderEvent = (*env)->CallIntMethod(env, jReader, getevent_meth);

		eventTmp = (jReaderEvent & 0xffff0000) | (jint)readerState[i].dwEventState;
		stateTmp = (jReaderState & 0xffff0000) | (jint)readerState[i].dwCurrentState;

		(*env)->CallVoidMethod(env, jReader, setevent_meth, eventTmp);
		(*env)->CallVoidMethod(env, jReader, setstate_meth, stateTmp);

		atr = (*env)->NewByteArray(env, readerState[i].cbAtr);
		(*env)->SetByteArrayRegion(env, atr, 0, readerState[i].cbAtr,
								   (jbyte*)(readerState[i].rgbAtr));
		(*env)->CallVoidMethod(env, jReader, setatr_meth, atr);
	}
	free(readerState);

	handleRV(env, rv);
}

JNIEXPORT void JNICALL Java_org_ulteo_pcsc_PCSC_SCardCancel
	(JNIEnv *env, jclass thisClass, jlong hContext) {
	SCARDCONTEXT context = (SCARDCONTEXT)hContext;
	LONG rv;

	dprintf("-cancel 0x%lx\n", (unsigned long)context);
	rv = CALL_SCardCancel(context);
	handleRV(env, rv);
}

JNIEXPORT void JNICALL Java_org_ulteo_pcsc_PCSC_SCardBeginTransaction
	(JNIEnv *env, jclass thisClass, jlong jCard)
{
	SCARDHANDLE card = (SCARDHANDLE)jCard;
	LONG rv;

	rv = CALL_SCardBeginTransaction(card);
	dprintf("-beginTransaction: 0x%lx\n", (unsigned long)rv);
	handleRV(env, rv);
}

JNIEXPORT void JNICALL Java_org_ulteo_pcsc_PCSC_SCardEndTransaction
	(JNIEnv *env, jclass thisClass, jlong jCard, jint jDisposition)
{
	SCARDHANDLE card = (SCARDHANDLE)jCard;
	LONG rv;

	rv = CALL_SCardEndTransaction(card, jDisposition);
	dprintf("-endTransaction: 0x%lX\n", rv);
	handleRV(env, rv);
}

JNIEXPORT jbyteArray JNICALL Java_org_ulteo_pcsc_PCSC_SCardControl
	(JNIEnv *env, jclass thisClass, jlong jCard, jint jControlCode, jbyteArray jSendBuffer)
{
	SCARDHANDLE card = (SCARDHANDLE)jCard;
	LONG rv;
	jbyte* sendBuffer = NULL;
	jint sendBufferLength = 0;
	jbyte receiveBuffer[MAX_STACK_BUFFER_SIZE];
	jint receiveBufferLength = MAX_STACK_BUFFER_SIZE;
	ULONG returnedLength = 0;
	jbyteArray jReceiveBuffer;

	/* Be carefull with NULL values */
	if (jSendBuffer != NULL) {
		sendBuffer = (*env)->GetByteArrayElements(env, jSendBuffer, NULL);
		sendBufferLength = (*env)->GetArrayLength(env, jSendBuffer);
	}

#ifdef PCSC_DEBUG
{
	int k;
	dprintf("-control: 0x%x\n", (int)jControlCode);
	dprintf("-send: ");
	for (k = 0; k < sendBufferLength; k++) {
		dprintf("%02x ", sendBuffer[k]);
	}
	dprintf("\n");
}
#endif

	rv = CALL_SCardControl(card, jControlCode, sendBuffer, sendBufferLength,
		receiveBuffer, receiveBufferLength, &returnedLength);

	if (jSendBuffer != NULL) {
		(*env)->ReleaseByteArrayElements(env, jSendBuffer, sendBuffer, JNI_ABORT);
	}

	if (handleRV(env, rv)) {
		return NULL;
	}

#ifdef PCSC_DEBUG
{
	int k;
	dprintf("-recv:  ");
	for (k = 0; k < returnedLength; k++) {
		dprintf("%02x ", receiveBuffer[k]);
	}
	dprintf("\n");
}
#endif

	jReceiveBuffer = (*env)->NewByteArray(env, returnedLength);
	(*env)->SetByteArrayRegion(env, jReceiveBuffer, 0, returnedLength, receiveBuffer);
	return jReceiveBuffer;
}

JNIEXPORT jbyteArray JNICALL Java_org_ulteo_pcsc_PCSC_SCardGetAttrib
	(JNIEnv *env, jclass thisClass, jlong jCard, jlong attrId)
{
	SCARDHANDLE card = (SCARDHANDLE)jCard;
	DWORD dwAttrId = (DWORD)attrId;
	LONG rv;
	jbyteArray jReceiveBuffer;
	unsigned char attr[ATTRIB_BUFFER_SIZE];
	ULONG lReturn = ATTRIB_BUFFER_SIZE;

	rv = CALL_SCardGetAttrib(card, dwAttrId, attr, &lReturn);

	if (handleRV(env, rv)) {
		return NULL;
	}

	jReceiveBuffer = (*env)->NewByteArray(env, lReturn);
	(*env)->SetByteArrayRegion(env, jReceiveBuffer, 0, lReturn, (jbyte*)attr);
	return jReceiveBuffer;
}
