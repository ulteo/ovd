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

#include "seamlessWindowHistory.h"

#include <stdlib.h>
#include <malloc.h>

static SeamlessWindow* hwdHistory = NULL;

SeamlessWindow* getHistory() {
	return hwdHistory;
}

SeamlessWindow* getWindowFromHistory(HWND hwnd) {
	SeamlessWindow* currentNode = hwdHistory;
	if (currentNode == NULL) {
		return (SeamlessWindow*)NULL;
	}
	while(currentNode) {
		if( currentNode->windows == hwnd)
			return currentNode;
		currentNode = currentNode->next;
	}
	return (SeamlessWindow*)NULL;
}

SeamlessWindow* addHWDNToHistory(HWND hwnd) {
	SeamlessWindow* currentNode = NULL;
	SeamlessWindow* newNode = hwdHistory;
	
	currentNode = getWindowFromHistory(hwnd);
	if(currentNode != NULL && currentNode->windows == hwnd) {
		return (SeamlessWindow*) NULL;
	}

	if (hwdHistory == NULL){
		hwdHistory = malloc(sizeof(SeamlessWindow));
		hwdHistory->windows = hwnd;
		hwdHistory->title = NULL;
		hwdHistory->focus = FALSE;
		hwdHistory->is_shown = FALSE;
		hwdHistory->next = NULL;
		return hwdHistory;
	}
	
	currentNode = hwdHistory;
	while(currentNode->next) {
		currentNode = currentNode->next;
	}
	newNode = malloc(sizeof(SeamlessWindow));
	newNode->windows = hwnd;
	newNode->title = NULL;
	newNode->next = NULL;
	newNode->focus = FALSE;
	newNode->is_shown = FALSE;
	currentNode->next = newNode;
	return newNode;
}

BOOL removeHWNDFromHistory(HWND hwnd) {
	SeamlessWindow* currentNode = hwdHistory;
	SeamlessWindow* previousNode = NULL;

	while(currentNode && currentNode->windows != hwnd) {
		previousNode = currentNode;
		currentNode = currentNode->next;
	}
	if (! currentNode)
		return FALSE;
	if (previousNode == NULL)
		hwdHistory = currentNode->next;
	else
		previousNode->next = currentNode->next;

	SeamlessWindow_free(currentNode);

	return TRUE;
}
