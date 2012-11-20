// Copyright (C) 2012 
// Author Wei-Jen Chen 2012

#ifndef _Logger_H
#define _Logger_H

#include <string>

class Logger
{
public:
	static Logger& getSingleton();
	static Logger* getSingletonPtr();

	// set log file, defalut is "C:\\VirtSys.log"
	void setLogFile(std::wstring szLogFile);

	// NOTE: not working
	void debug(const char * format,...);
	
	// output log to log file
	void log(char *fmt,...);
	
	void enable(bool bEnable);
	bool isLogging(){return m_bIsLogging;}
	
private:
	Logger();
	~Logger();	

private:
	static Logger*	m_sInstance;
	std::wstring	m_szLogFile;

	bool m_bIsLogging;
	bool m_bEnable;
};

#endif