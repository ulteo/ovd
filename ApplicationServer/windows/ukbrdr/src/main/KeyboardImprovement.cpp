#include <iostream>
#include <windows.h>
#include <vchannel/vchannel.h>
#include "KeyboardImprovement.h"


using namespace std;


KeyboardImprovement& KeyboardImprovement::getInstance() {
	static KeyboardImprovement instance;
	return instance;
}

KeyboardImprovement::KeyboardImprovement() {
	this->x = 0;
	this->y = 0;
}

bool KeyboardImprovement::init() {
	if (vchannel_open("ukbrdr") != 0) {
		std::cerr<<"Failed to open channel"<<std::endl;
		return false;
	}

	this->sendInit();

	return true;
}


bool KeyboardImprovement::update() {
	std::cout<<"checking caret position"<<std::endl;

	HWND fg_win;
	POINT pt;

	fg_win = GetForegroundWindow();

	if(fg_win) {
		GUITHREADINFO guiThreadInfo;
		guiThreadInfo.cbSize = sizeof(GUITHREADINFO);
		DWORD OtherThreadID = GetWindowThreadProcessId(fg_win, NULL);

		if(GetGUIThreadInfo(OtherThreadID, &guiThreadInfo)) {
			pt.x = guiThreadInfo.rcCaret.left;
			pt.y = guiThreadInfo.rcCaret.top;

			ClientToScreen(fg_win, &pt);
		}
	}

	if (this->x != pt.x || this->y != pt.y) {
		this->x = pt.x;
		this->y = pt.y;
		std::cout<<"caret change ["<<this->x<<"-"<<this->x<<"]"<<std::endl;

		return this->sendCaretPosition(x, y);
	}

	return true;
}


bool KeyboardImprovement::receiveHeader(ukb_msg* msg) {
	int size;

	size = vchannel_read((char*)msg, sizeof(msg->header));

	if (size <= 0) {
		return false;
	}

	return true;
}


bool KeyboardImprovement::processCompositionMessage(ukb_msg* msg) {
	int size;
	char* data;
    COPYDATASTRUCT cds;
	HWND hwnd = FindWindow("OVDIMEClass", NULL);


	data = new char[msg->header.len];
	size = vchannel_read(data, msg->header.len);

	if (size < 0) {
		return false;
	}

	if (hwnd == NULL) {
		OutputDebugString("Failed to find windows with class OVDIMEClass");
		return false;
	}


    cds.dwData = 1;
    cds.cbData = msg->header.len;
    cds.lpData = data;

	// sends IMC_SETCANDIDATEPOS to IMM to move the candidate window.
    SendMessage(hwnd, WM_COPYDATA, 0, (LPARAM)&cds);

	delete data;
	return true;
}


bool KeyboardImprovement::processStopCompositionMessage(ukb_msg* msg) {
	int ime_stop_composition = RegisterWindowMessage("WM_OVD_STOP_COMPOSITION");
	HWND hwnd = FindWindow("OVDIMEClass", NULL);

	if (hwnd == NULL) {
		OutputDebugString("Failed to find windows with class OVDIMEClass");
		return false;
	}

	SendMessage(hwnd, ime_stop_composition, 0, 0);

	return true;
}


void KeyboardImprovement::processNextMessage() {
	ukb_msg msg;

	if (! this->receiveHeader(&msg))
		return;


	switch(msg.header.type) {
	case UKB_PUSH_COMPOSITION:
		OutputDebugString("new UKB_PUSH_COMPOSITION message");
		this->processCompositionMessage(&msg);
		break;

	case UKB_STOP_COMPOSITION:
		OutputDebugString("new UKB_STOP_COMPOSITION message");
		this->processStopCompositionMessage(&msg);
		break;

	default:
		OutputDebugString("Invalid message");
		break;
	}
}

bool KeyboardImprovement::sendMsg(ukb_msg* msg) {
	int result;
	if (msg == NULL)
		return false;

	long size = sizeof(msg->header);
	size += msg->header.len;

	// TODO manage partial write
	result = vchannel_write((char*)msg, size);

	return true;
}


bool KeyboardImprovement::sendInit() {
	ukb_msg msg;

	msg.header.type = UKB_INIT;
	msg.header.flags = 0;
	msg.header.len = sizeof(msg.u.init);

	msg.u.init.version = UKB_VERSION;

	return this->sendMsg(&msg);
}


bool KeyboardImprovement::sendIMEStatus(int status) {
	ukb_msg msg;

	msg.header.type = UKB_IME_STATUS;
	msg.header.flags = 0;
	msg.header.len = sizeof(msg.u.ime_status);

	msg.u.ime_status.state = status;

	return this->sendMsg(&msg);
}


bool KeyboardImprovement::sendCaretPosition(int x, int y) {
	ukb_msg msg;

	msg.header.type = UKB_CARET_POS;
	msg.header.flags = 0;
	msg.header.len = sizeof(msg.u.caret_pos);

	msg.u.caret_pos.x = x;
	msg.u.caret_pos.y = y;

	return this->sendMsg(&msg);
}
