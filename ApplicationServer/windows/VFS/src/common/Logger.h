// Copyright (C) 2012 
// Author Wei-Jen Chen 2012

#ifndef _Logger_H
#define _Logger_H

#include <string>
#include <common/UException.h>

#define MAX_DBG_MSG_LEN (4096)


class Logger
{
public:
	enum Level {LOG_DEBUG, LOG_INFO, LOG_WARN, LOG_ERROR};

	static Logger& getSingleton();
	static Logger* getSingletonPtr();
	Level getFromString(std::wstring& level);
	void getLevelString(std::wstring& level);


	// set log file, defalut is "C:\\VirtSys.log"
	void setLogFile(std::wstring szLogFile);
	void setLevel(Level lvl);
	void setStdoutput(bool value);
	void setDevelOutput(bool value);

	// NOTE: not working
	void debug(const wchar_t * format,...);
	
	// output log to log file
	void log(Level lvl, wchar_t *fmt,...);
	
	bool isLogging(){return m_bIsLogging;}
	


private:
	Logger();
	~Logger();	

private:
	static Logger*	m_sInstance;
	std::wstring	m_szLogFile;
	std::wstring module;
	Level logLevel;
	bool useStdOut;
	bool useDevelStdOut;

	bool m_bIsLogging;
};



#define log_debug(format, ...) Logger::getSingleton().log(Logger::LOG_DEBUG, format, __VA_ARGS__)
#define log_error(format, ...) Logger::getSingleton().log(Logger::LOG_ERROR, format, __VA_ARGS__)
#define log_warn(format, ...) Logger::getSingleton().log(Logger::LOG_WARN, format, __VA_ARGS__)
#define log_info(format, ...) Logger::getSingleton().log(Logger::LOG_INFO, format, __VA_ARGS__)

#endif
