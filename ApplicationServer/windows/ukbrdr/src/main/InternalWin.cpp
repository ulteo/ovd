#include "InternalWin.h"
#include "KeyboardImprovement.h"
#include <iostream>
#include <sstream>


LRESULT CALLBACK InternalWin::wndProc(HWND hWnd, UINT msg, WPARAM wParam, LPARAM lParam) {
	int ime_status_message = RegisterWindowMessage("WM_OVD_IME_STATUS");

	if (msg == ime_status_message)
		KeyboardImprovement::getInstance().sendIMEStatus((int)lParam);

	switch(msg) {
	case WM_DESTROY:
		PostQuitMessage(WM_QUIT);
		break;

	default:
		return DefWindowProc(hWnd, msg, wParam, lParam);
	}

	return 0;
}


InternalWin::InternalWin(HINSTANCE hInstance) {
	this->hInstance = hInstance;
}

InternalWin::~InternalWin() {

}


bool InternalWin::init(std::string className) {
	WNDCLASS wndClass = {0};

	this->className = className;

	wndClass.lpfnWndProc   = InternalWin::wndProc;
	wndClass.hInstance     = hInstance;
	wndClass.lpszClassName = className.c_str();

	RegisterClass(&wndClass);

	return true;
}


bool InternalWin::create() {
	this->hwnd = CreateWindowEx(0, this->className.c_str(), "", 0, 0, 0, 0, 0, NULL, NULL, this->hInstance, NULL);
	SetWindowLong(this->hwnd, GWLP_USERDATA, (long)this);

	return true;

}
