// Copyright (C) 2012 
// Author Wei-Jen Chen 2012

#ifndef _Logger_H
#define _Logger_H

#include <string>


class Logger
{
public:
	enum Level {LOG_DEBUG, LOG_INFO, LOG_WARN, LOG_ERROR};

	static Logger& getSingleton();
	static Logger* getSingletonPtr();
	Level getFromString(std::string& level);
	void getLevelString(std::string& level);


	// set log file, defalut is "C:\\VirtSys.log"
	void setLogFile(std::string szLogFile);
	void setLevel(Level lvl);
	void setStdoutput(bool value);

	// NOTE: not working
	void debug(const wchar_t * format,...);
	
	// output log to log file
	void log(Level lvl, char *fmt,...);


private:
	Logger();
	~Logger();	

private:
	static Logger*	m_sInstance;
	std::string	m_szLogFile;
	Level logLevel;
	bool useStdOut;
};



#define log_debug(format, ...) Logger::getSingleton().log(Logger::LOG_DEBUG, format, __VA_ARGS__)
#define log_error(format, ...) Logger::getSingleton().log(Logger::LOG_ERROR, format, __VA_ARGS__)
#define log_warn(format, ...) Logger::getSingleton().log(Logger::LOG_WARN, format, __VA_ARGS__)
#define log_info(format, ...) Logger::getSingleton().log(Logger::LOG_INFO, format, __VA_ARGS__)

#endif
