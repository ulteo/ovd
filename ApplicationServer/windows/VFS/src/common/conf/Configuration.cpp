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
#include <common/UException.h>



Configuration::Configuration() {
	this->logLevel = Logger::LOG_INFO;
	this->develOutput = false;
	this->stdoutOutput = false;
	this->hookRegistry = false;
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


bool Configuration::supportHookRegistry() {
	return this->hookRegistry;
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


Union& Configuration::getUnions(const std::wstring& name) {
	std::list<Union>::iterator it;
	for(it = this->unions.begin() ; it != this->unions.end() ; it++) {
		if ((*it).getName().compare(name) == 0)
			return (*it);
	}

	throw UException(L"invalid union");
}



Translation& Configuration::getTranslation() {
	return this->trans;
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
		throw UException(L"there is no unions");


	for (it = unionsList.begin() ; it != unionsList.end() ; it++) {
		std::wstring unionName = (*it);
		StringUtil::atrim(unionName);
		Union unionNode(unionName);

		std::wstring path = L"";
		std::wstring rsyncSrc = L"";
		std::wstring rsyncFilter = L"";
		bool deleteOnClose = false;
		std::wstring predefinedDirectory;

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
			catch (const UException&) { }

			try {
				deleteOnClose = sec->getBool(L"deleteOnEnd");
				unionNode.setDeleteOnClose(deleteOnClose);
			}
			catch (const UException&) { }

			try {
				std::list<std::wstring> l;
				std::list<std::wstring>::iterator it;
				std::wstring s = sec->getString(L"populate");
				StringUtil::unquote(s);
				StringUtil::split(l, s, L';');

				for( it = l.begin() ; it != l.end() ; it++) {
					std::wstring v = (*it);
					StringUtil::atrim(v);
					unionNode.addPredefinedDirectory(v);
				}
			}
			catch (const UException&) { }
		}
		catch (UException& e) {
			log_error(L"failed to parse union '%s': %s", (*it), e.wwhat());
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
	catch (const UException&) { }

	try {
		this->stdoutOutput = ini.getBool(L"log", L"enableStdOutput");
		logger.setStdoutput(this->stdoutOutput);
	}
	catch (const UException&) { }

	try {
		this->logFilename = ini.getString(L"log", L"outputFilename");
		logger.setLogFile(this->logFilename);
	}
	catch (const UException&) {}
}


void Configuration::parseTranslations(INI& ini) {
	File profile(L"%{CSIDL_PROFILE}");
	Section* rules =  ini.getSection(L"translation");
	std::vector<std::wstring>& keys = rules->getKeys();
	std::vector<std::wstring>& values = rules->getValues();
	profile.expand();

	for (unsigned int i = 0 ; i < keys.size() ; i++) {
		File f(values[i]);
		std::wstring path;
		f.expand();

		path = f.path();
		if (path.find(profile.path()) == 0)
			path.erase(0, profile.path().length() + 1);

		this->trans.add(keys[i], path);
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
	catch (const UException& e) {
		log_error(L"Failed to parse configuration file: %s", e.wwhat());
		return false;
	}

	try {
		this->hookRegistry = ini.getBool(L"main", L"hookRegistry");
	}
	catch (const UException&) { }

	this->parseLog(ini);

	this->parseTranslations(ini);

	try {
		this->parseUnions(ini);
	}
	catch (const UException& e) {
		log_error(L"failed to parse unions list: %s", e.wwhat());
		return false;
	}

	try {
		this->parseRules(ini);
	}
	catch (const UException& e) {
		log_error(L"failed to parse rules list: %s", e.wwhat());
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
	log_info(L"    - use stdout: %s", this->stdoutOutput? L"yes": L"no");
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
		std::list<std::wstring> l;
		std::list<std::wstring>::iterator it;

		log_info(L"    - union: %s", u.getName().c_str());

		log_info(L"      - path: %s", u.getPath().c_str());
		log_info(L"      - delete content on session end: %s", u.isDeleteOnClose()? "yes":"no");

		if (! u.getRsyncSrc().empty())
			log_info(L"      - rsync src: %s", u.getRsyncSrc().c_str());

		if (! u.getRsyncFilter().empty())
			log_info(L"      - rsync filter: %s", u.getRsyncFilter().c_str());

		l = u.getpredefinedDirectoryList();
		if (! l.empty())
			log_info(L"      - predefined directories:");

		for(it = l.begin() ; it != l.end() ; it++)
			log_info(L"        - %s", (*it));


	}
}
