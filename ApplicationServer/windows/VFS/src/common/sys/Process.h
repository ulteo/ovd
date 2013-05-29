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

#ifndef PROCESS_H_
#define PROCESS_H_

#include <windows.h>
#include <string>
#include <list>

// TODO manage stdout

class Process {
private:
	STARTUPINFOA si;
	PROCESS_INFORMATION pi;

	std::string programName;
	std::list<std::string> arguments;

public:
	Process(const std::string& programName);
	virtual ~Process();

	void addArgs(std::string argument);
	unsigned int getStatus();
	long getPID();
	bool start(bool wait);

	void wait(unsigned int time);
	static void wait(std::list<Process*> processList, unsigned int time);
};

#endif /* PROCESS_H_ */
