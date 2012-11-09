#ifndef _Logger_H
#define _Logger_H

class Logger
{
public:
	static Logger& getSingleton();
	static Logger* getSingletonPtr();

	void debug(const char * format,...);
	void log(char *fmt,...);
	void enable(bool bEnable);
	bool isLogging(){return m_bIsLogging;}
	
private:
	Logger();
	~Logger();	

private:
	static Logger* m_sInstance;

	bool m_bIsLogging;
	bool m_bEnable;
};

#endif