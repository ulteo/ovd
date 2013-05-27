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

#include "Configuration.h"
#include <common/fs/File.h>
#include <exception>



Configuration::Configuration() {
	this->logLevel = Logger::LOG_INFO;
	this->develOutput = false;
	this->stdoutOutput = false;
}


Configuration::~Configuration() { }


Configuration& Configuration::getInstance() {
	static Configuration instance;
	return instance;
}

Logger::Level Configuration::getLogLevel() {
	return this->logLevel;
}


bool Configuration::useDevelOutput() {
	return this->develOutput;
}


bool Configuration::useStdOut() {
	return this->stdoutOutput;
}


const std::string& Configuration::getLogFilename() {
	return this->logFilename;
}

bool Configuration::load() {
	return this->load(std::string(DEFAULT_CONF_FILENAME));
}

bool Configuration::load(const std::string& filename) {
	File f(filename);

	if (! f.expand()) {
		log_error("File %s is invalid", filename.c_str());
		return false;
	}

	INI ini(f.path());

	try {
		ini.parse();
	}
	catch (const std::exception& e) {
		log_error("Failed to parse configuration file: %s", e.what());
		return false;
	}

	// Parse configuration file
	Logger& logger = Logger::getSingleton();
	try {
		this->logLevel = logger.getFromString(ini.getString("log", "level"));
		logger.setLevel(this->logLevel);
	}
	catch (const std::exception& e) { }

	try {
		this->stdoutOutput = ini.getBool("log", "enableStdOutput");
		logger.setStdoutput(this->stdoutOutput);
	}
	catch (const std::exception& e) { }

	try {
		this->logFilename = ini.getString("log", "outputFilename");
		logger.setLogFile(this->logFilename);
	}
	catch (const std::exception& e) { }


	std::string unions =  ini.getString("main", "union");
	Section* rules =  ini.getSection("rules");


	return true;
}

