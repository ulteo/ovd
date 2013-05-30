/*
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2013
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

#include "Process.h"
#include <common/Logger.h>


Process::Process(const std::wstring& programName): programName(programName) {}

Process::~Process() {
	CloseHandle(this->pi.hProcess);
	CloseHandle(this->pi.hThread);
}


bool Process::start(bool wait) {
	std::wstring commandLine;
	std::list<std::wstring>::iterator it;

	if (this->programName.empty()) {
		log_info(L"there is no processus to start");
		return false;
	}

	commandLine = this->programName;

	for(it = this->arguments.begin() ; it != this->arguments.end() ; it++) {
		commandLine.append(L" "+*(it));
	}

	ZeroMemory( &this->si, sizeof(STARTUPINFO));
	ZeroMemory( &this->pi, sizeof(PROCESS_INFORMATION));
	this->si.cb = sizeof(STARTUPINFO);
	this->si.wShowWindow = FALSE;

	if (! CreateProcess(NULL, (LPWSTR)commandLine.c_str(), NULL, NULL, FALSE, 0, NULL, NULL, &this->si, &this->pi)) {
		log_error(L"Failed to start command %s: %u", commandLine.c_str(), GetLastError());
		return false;
	}

	if (wait)
		this->wait(INFINITE);

	return true;
}


void Process::addArgs(std::wstring argument) {
	this->arguments.push_back(argument);
}


unsigned int Process::getStatus() {
	DWORD status;

	if (!GetExitCodeProcess(this->pi.hProcess, &status)) {
		log_error(L"Failed to get return code %u", GetLastError());
		return (DWORD)-1;
	}

	return status;
}


long Process::getPID() {
	return (long)this->pi.hProcess;
}


void Process::wait(unsigned int time) {
	if (WaitForSingleObject(this->pi.hProcess, time) == WAIT_FAILED)
		log_error(L"Failed to wait for processus %s", this->programName.c_str());
}


void Process::wait(std::list<Process*> processList, unsigned int time) {
	HANDLE* handles = new HANDLE[processList.size()];
	std::list<Process*>::iterator it;
	int index = 0;

	for (it = processList.begin() ; it != processList.end() ; it++)
		handles[index++] = (HANDLE)(*it)->getPID();

	if (WaitForMultipleObjects(processList.size(), handles, TRUE, time) == WAIT_FAILED)
		log_error(L"Failed to wait for processus list");

	delete[] handles;
}


