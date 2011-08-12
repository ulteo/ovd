/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
 * Author Samuel BOVEE <samuel@ulteo.com> 2010
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
 **/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <jni.h>
#include <X11/Xatom.h>
#include <X11/X.h>
#include <X11/Xlib.h>

typedef struct rect_ {
	int x;
	int y;
	int width;
	int height;
} Rectangle;

Rectangle* getWorkAreaRect();


int main() {
	Rectangle* rect = getWorkAreaRect();
	if (rect == NULL)
		return -1;

	printf("x_offset: %d\n", rect->x);
	printf("y_offset: %d\n", rect->y);
	printf("width: %d\n", rect->width);
	printf("height: %d\n", rect->height);

	free(rect);
	
	return 0;
}

Rectangle* getWorkAreaRect() {
	Display* dpy = XOpenDisplay("");
	Window rootWnd;

	Atom workarea_atom;
	int status;
	Atom actual_type;
	int actual_format;
	unsigned long nitems;
	unsigned long remaining_bytes;
	unsigned char* data;
	long* return_words;
	Rectangle* rect;

	if (dpy == NULL) {
		fprintf(stderr, "Cannot open the current display");
		return NULL;
	}
	rootWnd = XRootWindow(dpy, 0);

	workarea_atom = XInternAtom(dpy, "_NET_WORKAREA", True);
	if (workarea_atom == None) {
		fprintf(stderr, "The _NET_WORKAREA atom does not exists");
		return NULL;
	}

	status = XGetWindowProperty(dpy, rootWnd, workarea_atom, 0, ~0L, False, XA_CARDINAL, &actual_type, &actual_format, &nitems, &remaining_bytes, &data);
	if (status != Success) {
		fprintf(stderr, "Getting _NET_WORKAREA atom content failed");
		return NULL;
	}
	if (actual_type != XA_CARDINAL || actual_format != 32 || nitems < 4) {
		fprintf(stderr, "Getting _NET_WORKAREA atom content return bad content");
		return NULL;
	}

	rect = malloc(sizeof(Rectangle));
	if (rect == NULL) {
		fprintf(stderr, "Not enough memory: need %u bytes", (unsigned int)(sizeof(Rectangle)));
		return NULL;
	}

	return_words = (long *) data;

	rect->x = return_words[0];
	rect->y = return_words[1];
	rect->width = return_words[2];
	rect->height = return_words[3];

	XCloseDisplay(dpy);

	return rect;
}

JNIEXPORT jintArray JNICALL Java_org_ulteo_utils_jni_WorkArea_getWorkAreaSizeForX(JNIEnv *env, jclass class) {
	Rectangle* rect;
	jintArray area;
	int buff[4];

	rect = getWorkAreaRect();
	if (rect == NULL) {
		area = (*env)->NewIntArray(env,1);
		buff[0] = 0;
		(*env)->SetIntArrayRegion(env, area, 0, 1, (jint*)buff);
	}
	else {
		area = (*env)->NewIntArray(env,4);
		buff[0] = rect->x;
		buff[1] = rect->y;
		buff[2] = rect->width;
		buff[3] = rect->height;
		(*env)->SetIntArrayRegion(env, area, 0, 4, (jint*)buff);

		free(rect);
	}

	return area;
}

JNIEXPORT void JNICALL Java_org_ulteo_utils_jni_WorkArea_setFullscreenWindow(JNIEnv *env, jclass class, jlong window_id, jboolean enabled) {
	Display* dpy;
	Atom wmState;
	Atom wmStateFs;
	Window root, parent, *children = NULL;
	unsigned int numchildren;
	XEvent event;

	dpy = XOpenDisplay("");
	if (dpy == NULL) {
		fprintf(stderr, "Cannot open the current display");
		return;
	}
	
	wmState = XInternAtom(dpy, "_NET_WM_STATE", False);
	wmStateFs = XInternAtom(dpy, "_NET_WM_STATE_FULLSCREEN", False);

	if (wmState == None || wmStateFs == None) {
		fprintf(stderr, "State atoms not found\n");
		return;
	}

	if (!XQueryTree(dpy, window_id, &root, &parent, &children, &numchildren)) {
		fprintf(stderr, "XQueryTree failed\n");
		return;
	}

	if (children != NULL) {
		XFree(children);
	}

	memset(&event, 0, sizeof(event));
	event.xclient.type = ClientMessage;
	event.xclient.message_type = wmState;
	event.xclient.display = dpy;
	event.xclient.window = window_id;
	event.xclient.format = 32;
	event.xclient.data.l[0] = enabled ? 1 : 0; // 1==add, 0==remove
	event.xclient.data.l[1] = wmStateFs;

	XSendEvent(dpy, root, False, SubstructureRedirectMask | SubstructureNotifyMask, &event);
	XSync(dpy, False);

	XCloseDisplay(dpy);
}
