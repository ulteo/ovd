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
#include <list>
#include "INI.h"
#include <common/Logger.h>
#include <common/StringUtil.h>
#include "Rule.h"
#include "Union.h"
#include "Translation.h"


#define DEFAULT_CONF_FILENAME    L"%{CSIDL_COMMON_APPDATA}\\ulteo\\profile\\default.conf"
#define REGISTRY_PATH_KEY        L"HKEY_CURRENT_USER\\Software\\ulteo"


class Configuration {
private:
	Configuration();
	virtual ~Configuration();

	bool hookRegistry;

	// Log configuration
	Logger::Level logLevel;
	bool develOutput;
	bool stdoutOutput;
	std::wstring logFilename;

	// union configuration
	std::wstring srcPath;
	std::list<Rule> rules;
	std::list<Union> unions;
	Translation trans;


public:
	static Configuration& getInstance();

	bool load();
	bool load(const std::wstring& filename);
	void parseUnions(INI& ini);
	void parseRules(INI& ini);
	void parseLog(INI& ini);
	void parseTranslations(INI& ini);

	bool supportHookRegistry();

	Logger::Level getLogLevel();
	bool useDevelOutput();
	bool useStdOut();
	const std::wstring& getLogFilename();

	std::list<Rule>& getRules();
	std::list<Union>& getUnions();
	void setSrcPath(const std::wstring& path);
	std::wstring& getSrcPath();

	void dump();
};

#endif /* CONFIGURATION_H_ */
