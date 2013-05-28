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


std::list<Rule>& Configuration::getRules() {
	return this->rules;
}


std::list<Union>& Configuration::getUnions() {
	return this->unions;
}


bool Configuration::load() {
	return this->load(std::string(DEFAULT_CONF_FILENAME));
}


void Configuration::parseUnions(INI& ini) {
	std::string unions =  ini.getString("main", "union");
	std::list<std::string>::iterator it;
	std::list<std::string> unionsList;
	StringUtil::split(unionsList, unions, ',');

	if (unionsList.empty())
		throw std::exception("there is no unions");


	for (it = unionsList.begin() ; it != unionsList.end() ; it++) {
		std::string unionName = (*it);
		StringUtil::atrim(unionName);
		Union unionNode(unionName);

		std::string path = "";
		std::string rsyncSrc = "";
		std::string rsyncFilter = "";
		bool deleteOnClose = false;

		Section* sec = ini.getSection(unionName);

		try {
			path = sec->getString("path");
			File f(path);
			std::cout<<"before expand "<<f.path()<<std::endl;
			f.expand();
			std::cout<<"after expand "<<f.path()<<std::endl;

			// manage local path
			if (! f.isAbsolute()) {
				File src(this->srcPath);
				src.join(f.path());
				unionNode.setPath(src.path());
			}
			else {
				unionNode.setPath(f.path());
			}

			try {
				rsyncSrc = sec->getString("rsync");
				unionNode.setRsyncSrc(rsyncSrc);

				rsyncFilter = sec->getString("rsync_filter");
				File f(rsyncFilter);
				f.expand();
				unionNode.setRsyncFilter(f.path());
			}
			catch (const std::exception&) { }

			try {
				deleteOnClose = sec->getBool("deleteOnEnd");
				unionNode.setDeleteOnClose(deleteOnClose);
			}
			catch (const std::exception&) { }
		}
		catch (std::exception& e) {
			log_error("failed to parse union '%s': %s", (*it), e.what());
			throw;
		}

		this->unions.push_front(unionNode);
	}
}


void Configuration::parseRules(INI& ini) {
	Section* rules =  ini.getSection("rules");
	std::vector<std::string>& keys = rules->getKeys();
	std::vector<std::string>& values = rules->getValues();

	for (unsigned int i = 0 ; i < keys.size() ; i++) {
		File f(values[i]);
		f.expand();
		this->rules.push_back(Rule(keys[i], f.path()));
	}
}

void Configuration::parseLog(INI& ini) {
	Logger& logger = Logger::getSingleton();

	try {
		this->logLevel = logger.getFromString(ini.getString("log", "level"));
		logger.setLevel(this->logLevel);
	}
	catch (const std::exception&) { }

	try {
		this->stdoutOutput = ini.getBool("log", "enableStdOutput");
		logger.setStdoutput(this->stdoutOutput);
	}
	catch (const std::exception&) { }

	try {
		this->logFilename = ini.getString("log", "outputFilename");
		logger.setLogFile(this->logFilename);
	}
	catch (const std::exception&) {}
}


void Configuration::parseTranslations(INI& ini) {
	Section* rules =  ini.getSection("translation");
	std::vector<std::string>& keys = rules->getKeys();
	std::vector<std::string>& values = rules->getValues();

	for (unsigned int i = 0 ; i < keys.size() ; i++) {
		File f(values[i]);
		f.expand();

		this->trans.add(keys[i], f.path());
	}
}


bool Configuration::load(const std::string& filename) {
	File f(filename);

	if (! f.expand()) {
		log_error("File %s is invalid", filename.c_str());
		return false;
	}

	if (this->srcPath.empty()) {
		log_error("missing source path");
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

	this->parseLog(ini);

	this->parseTranslations(ini);

	try {
		this->parseUnions(ini);
	}
	catch (const std::exception& e) {
		log_error("failed to parse unions list: %s", e.what());
		return false;
	}

	try {
		this->parseRules(ini);
	}
	catch (const std::exception& e) {
		log_error("failed to parse rules list: %s", e.what());
		return false;
	}

	return true;
}


void Configuration::setSrcPath(const std::string& path) {
	this->srcPath = path;
}


void Configuration::dump() {
	std::string logLevel;
	Logger& logger =  Logger::getSingleton();
	std::list<Rule>::iterator itRules;
	std::list<Union>::iterator itUnions;
	logger.getLevelString(logLevel);
	std::vector<std::string>& transKeys = this->trans.getKeys();
	std::vector<std::string>& transValues = this->trans.getValues();

	log_info("Configuration dump");
	log_info("  Log configuration:");
	log_info("    - log level: %s", logLevel.c_str());
	log_info("    - use stdout: %s", this->stdoutOutput? "yes": "no");
	log_info("    - output file: %s", this->logFilename.c_str());

	log_info("  Translations:");
	for(unsigned int i = 0 ; i < transKeys.size() ; i++) {
		log_info("    - translation: %s => %s", transKeys[i].c_str(), transValues[i].c_str());
	}

	log_info("  Rules:");
	for(itRules = this->rules.begin() ; itRules != this->rules.end() ; itRules++)
		log_info("    - rules: %s => %s", (*itRules).getUnion().c_str(), (*itRules).getPattern().c_str());

	log_info("  Unions:");
	for(itUnions = this->unions.begin() ; itUnions != this->unions.end() ; itUnions++) {
		Union& u = (*itUnions);
		log_info("    - union: %s", u.getName().c_str());

		log_info("      - path: %s", u.getPath().c_str());
		log_info("      - delete content on session end: %s", u.isDeleteOnClose()? "yes":"no");

		if (! u.getRsyncSrc().empty())
			log_info("      - rsync src: %s", u.getRsyncSrc().c_str());

		if (! u.getRsyncFilter().empty())
			log_info("      - rsync filter: %s", u.getRsyncFilter().c_str());
	}
}
