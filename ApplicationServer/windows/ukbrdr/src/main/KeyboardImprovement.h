#ifndef KEYBOARD_IMPROVEMENT_H_
#define KEYBOARD_IMPROVEMENT_H_


#include "proto.h"



class KeyboardImprovement {
private:
	int x;
	int y;

	bool sendMsg(ukb_msg* msg);

public:
	KeyboardImprovement();
	bool init();
	bool update();
	void processNextMessage();

	bool sendInit();
	bool sendCaretPosition();
};

#endif // KEYBOARD_IMPROVEMENT_H_





