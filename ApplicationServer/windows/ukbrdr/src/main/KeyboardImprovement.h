#ifndef KEYBOARD_IMPROVEMENT_H_
#define KEYBOARD_IMPROVEMENT_H_


#include "proto.h"



class KeyboardImprovement {
private:
	int x;
	int y;
	int imeStatus;
	int lastImeStatus;
	int currentTSFStatus;
	int currentTSFProcessID;
	int lastTSFProcessID;

	bool sendMsg(ukb_msg* msg);
	KeyboardImprovement();

public:
	static KeyboardImprovement& getInstance();

	bool init();
	bool update();
	void setIMEStatus(int processID, int status);
	void processNextMessage();
	bool processStopCompositionMessage(ukb_msg* msg);
	bool processCompositionMessage(ukb_msg* msg);
	bool receiveHeader(ukb_msg* msg);

	bool sendInit();
	bool sendIMEStatus(int status);
	bool sendCaretPosition(int x, int y);
};

#endif // KEYBOARD_IMPROVEMENT_H_





