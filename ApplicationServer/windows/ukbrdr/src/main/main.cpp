#include <iostream>
#include <windows.h>
#include <vchannel/vchannel.h>
#include "KeyboardImprovement.h"
#include "InternalWin.h"
#include "win.h"

#define REFRESH_TIME 100


INT WINAPI WinMain(HINSTANCE hInstance, HINSTANCE hPrevInstance, LPSTR lpCmdLine, int nCmdShow) {
	InternalWin win(hInstance);
	KeyboardImprovement& ki = KeyboardImprovement::getInstance();
	MSG Msg;

	if (! ki.init()) {
		OutputDebugString("Failed to initialize Keyboard improvement object");
		return 2;
	}

	if (! win.init("OVDIMEChannelClass")) {
		OutputDebugString("Failed to initialize windows ");
		return 2;
	}

	if (! win.create()) {
		OutputDebugString("Failed to create windows ");
		return 3;
	}

	while(1) {
		ki.update();
		ki.processNextMessage();

		while (PeekMessage(&Msg, NULL, 0, 0, PM_REMOVE))
		{
			TranslateMessage(&Msg);
			DispatchMessage(&Msg);
		}

		Sleep(REFRESH_TIME);
	}

	return Msg.wParam;
}
