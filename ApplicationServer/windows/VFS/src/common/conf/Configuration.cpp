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


const std::wstring& Configuration::getLogFilename() {
	return this->logFilename;
}


std::list<Rule>& Configuration::getRules() {
	return this->rules;
}


std::list<Union>& Configuration::getUnions() {
	return this->unions;
}


bool Configuration::load() {
	return this->load(std::wstring(DEFAULT_CONF_FILENAME));
}


void Configuration::parseUnions(INI& ini) {
	std::wstring unions =  ini.getString(L"main", L"union");
	std::list<std::wstring>::iterator it;
	std::list<std::wstring> unionsList;
	StringUtil::split(unionsList, unions, ',');

	if (unionsList.empty())
		throw std::exception("there is no unions");


	for (it = unionsList.begin() ; it != unionsList.end() ; it++) {
		std::wstring unionName = (*it);
		StringUtil::atrim(unionName);
		Union unionNode(unionName);

		std::wstring path = L"";
		std::wstring rsyncSrc = L"";
		std::wstring rsyncFilter = L"";
		bool deleteOnClose = false;

		Section* sec = ini.getSection(unionName);

		try {
			File path(sec->getString(L"path"));
			path.expand(this->srcPath);
			unionNode.setPath(path.path());

			try {
				File rsyncSrc(sec->getString(L"rsync"));
				rsyncSrc.expand(this->srcPath);
				unionNode.setRsyncSrc(rsyncSrc.path());

				rsyncFilter = sec->getString(L"rsync_filter");
				File f(rsyncFilter);
				f.expand();
				unionNode.setRsyncFilter(f.path());
			}
			catch (const std::exception&) { }

			try {
				deleteOnClose = sec->getBool(L"deleteOnEnd");
				unionNode.setDeleteOnClose(deleteOnClose);
			}
			catch (const std::exception&) { }
		}
		catch (std::exception& e) {
			log_error(L"failed to parse union '%s': %s", (*it), e.what());
			throw;
		}

		this->unions.push_front(unionNode);
	}
}


void Configuration::parseRules(INI& ini) {
	Section* rules =  ini.getSection(L"rules");
	std::vector<std::wstring>& keys = rules->getKeys();
	std::vector<std::wstring>& values = rules->getValues();

	for (unsigned int i = 0 ; i < keys.size() ; i++) {
		File f(values[i]);
		f.expand();
		this->rules.push_back(Rule(keys[i], f.path()));
	}
}

void Configuration::parseLog(INI& ini) {
	Logger& logger = Logger::getSingleton();

	try {
		this->logLevel = logger.getFromString(ini.getString(L"log", L"level"));
		logger.setLevel(this->logLevel);
	}
	catch (const std::exception&) { }

	try {
		this->stdoutOutput = ini.getBool(L"log", L"enableStdOutput");
		logger.setStdoutput(this->stdoutOutput);
	}
	catch (const std::exception&) { }

	try {
		this->logFilename = ini.getString(L"log", L"outputFilename");
		logger.setLogFile(this->logFilename);
	}
	catch (const std::exception&) {}
}


void Configuration::parseTranslations(INI& ini) {
	Section* rules =  ini.getSection(L"translation");
	std::vector<std::wstring>& keys = rules->getKeys();
	std::vector<std::wstring>& values = rules->getValues();

	for (unsigned int i = 0 ; i < keys.size() ; i++) {
		File f(values[i]);
		f.expand();

		this->trans.add(keys[i], f.path());
	}
}


bool Configuration::load(const std::wstring& filename) {
	File f(filename);

	if (! f.expand()) {
		log_error(L"File %s is invalid", filename.c_str());
		return false;
	}

	if (this->srcPath.empty()) {
		log_error(L"missing source path");
		return false;
	}

	INI ini(f.path());

	try {
		ini.parse();
	}
	catch (const std::exception& e) {
		log_error(L"Failed to parse configuration file: %s", e.what());
		return false;
	}

	this->parseLog(ini);

	this->parseTranslations(ini);

	try {
		this->parseUnions(ini);
	}
	catch (const std::exception& e) {
		log_error(L"failed to parse unions list: %s", e.what());
		return false;
	}

	try {
		this->parseRules(ini);
	}
	catch (const std::exception& e) {
		log_error(L"failed to parse rules list: %s", e.what());
		return false;
	}

	return true;
}


void Configuration::setSrcPath(const std::wstring& path) {
	this->srcPath = path;
}


std::wstring& Configuration::getSrcPath() {
	return this->srcPath;
}


void Configuration::dump() {
	std::wstring logLevel;
	Logger& logger =  Logger::getSingleton();
	std::list<Rule>::iterator itRules;
	std::list<Union>::iterator itUnions;
	logger.getLevelString(logLevel);
	std::vector<std::wstring>& transKeys = this->trans.getKeys();
	std::vector<std::wstring>& transValues = this->trans.getValues();

	log_info(L"Configuration dump");
	log_info(L"  Log configuration:");
	log_info(L"    - log level: %s", logLevel.c_str());
	log_info(L"    - use stdout: %s", this->stdoutOutput? "yes": "no");
	log_info(L"    - output file: %s", this->logFilename.c_str());

	log_info(L"  Translations:");
	for(unsigned int i = 0 ; i < transKeys.size() ; i++) {
		log_info(L"    - translation: %s => %s", transKeys[i].c_str(), transValues[i].c_str());
	}

	log_info(L"  Rules:");
	for(itRules = this->rules.begin() ; itRules != this->rules.end() ; itRules++)
		log_info(L"    - rules: %s => %s", (*itRules).getUnion().c_str(), (*itRules).getPattern().c_str());

	log_info(L"  Unions:");
	for(itUnions = this->unions.begin() ; itUnions != this->unions.end() ; itUnions++) {
		Union& u = (*itUnions);
		log_info(L"    - union: %s", u.getName().c_str());

		log_info(L"      - path: %s", u.getPath().c_str());
		log_info(L"      - delete content on session end: %s", u.isDeleteOnClose()? "yes":"no");

		if (! u.getRsyncSrc().empty())
			log_info(L"      - rsync src: %s", u.getRsyncSrc().c_str());

		if (! u.getRsyncFilter().empty())
			log_info(L"      - rsync filter: %s", u.getRsyncFilter().c_str());
	}
}
