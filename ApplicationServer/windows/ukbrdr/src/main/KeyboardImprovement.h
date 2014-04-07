#ifndef KEYBOARD_IMPROVEMENT_H_
#define KEYBOARD_IMPROVEMENT_H_


#include "proto.h"



class KeyboardImprovement {
private:
	int x;
	int y;

	bool sendMsg(ukb_msg* msg);
	KeyboardImprovement();

public:
	static KeyboardImprovement& getInstance();

	bool init();
	bool update();
	void processNextMessage();

	bool sendInit();
	bool sendIMEStatus(int status);
	bool sendCaretPosition();
};

#endif // KEYBOARD_IMPROVEMENT_H_





