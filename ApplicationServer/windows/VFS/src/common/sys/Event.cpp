/*
 * Copyright (C) 2014 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2014
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

#include <common/Logger.h>
#include "Event.h"


Event::Event(std::wstring eventName, bool useArch) {
	HANDLE event = 0;

	if (useArch) {
		this->eventName = eventName + ARCH_STR;
	}
	else {
		this->eventName = eventName;
	}
}

Event::~Event() {
	CloseHandle(this->handle);
}


bool Event::create() {
	if (this->eventName.empty()) {
		log_error(L"Failed to create event '%s' : empty event", this->eventName.c_str());
		return false;
	}

	this->handle = CreateEvent(NULL, FALSE, FALSE, this->eventName.c_str());

	if (this->handle == NULL) {
		log_error(L"Failed to create event '%s' : %u", this->eventName.c_str(), GetLastError());
		return false;
	}

	return true;
}

bool Event::fire() {
	if (this->handle == NULL) {
		log_error(L"Failed to fire event %s : event is not initialized", this->eventName.c_str());
		return false;
	}

	if (! SetEvent(this->handle)) {
		log_error(L"Failed to fire event '%s' : %u", this->eventName.c_str(), GetLastError());
		return false;
	}

	return true;
}


bool Event::wait(int time) {
	if (this->handle == NULL) {
		log_error(L"Failed to wait event %s : event is not initialized", this->eventName.c_str());
		return false;
	}

	if (WaitForSingleObject(this->handle, time) == WAIT_FAILED) {
		log_error(L"Failed to wait for processus list %x", GetLastError());
		return false;
	}

	return true;
}
