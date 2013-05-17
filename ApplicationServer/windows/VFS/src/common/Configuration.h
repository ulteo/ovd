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
#ifndef CONFIGURATION_H_
#define CONFIGURATION_H_

#include <string>
#include "Logger.h"

#define DEFAULT_FILENAME    L"%CSIDL_COMMON_APPDATA\\ulteo\\profile\\default.conf"


class Configuration {
private:
	Configuration();
	virtual ~Configuration();

	Logger::level logLevel;
	bool develOutput;
	bool stdoutOutput;
	std::string logFilename;


public:
	static Configuration& getInstance();

	bool load(const std::string& filename);

	Logger::level getLogLevel();
	bool useDevelOutput();
	bool useStdOut();
	const std::string& getLogFilename();
};

#endif /* CONFIGURATION_H_ */
