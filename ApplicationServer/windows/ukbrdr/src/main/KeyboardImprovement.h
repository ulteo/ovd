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
	bool processStopCompositionMessage(ukb_msg* msg);
	bool processCompositionMessage(ukb_msg* msg);
	bool receiveHeader(ukb_msg* msg);

	bool sendInit();
	bool sendIMEStatus(int status);
	bool sendCaretPosition(int x, int y);
};

#endif // KEYBOARD_IMPROVEMENT_H_





