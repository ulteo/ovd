#include <iostream>
#include <windows.h>
#include <vchannel/vchannel.h>
#include "KeyboardImprovement.h"
#include "InternalWin.h"
#include "win.h"

#define REFRESH_TIME 200


INT WINAPI WinMain(HINSTANCE hInstance, HINSTANCE hPrevInstance, LPSTR lpCmdLine, int nCmdShow) {
	InternalWin win(hInstance);
	KeyboardImprovement& ki = KeyboardImprovement::getInstance();
	bool connected = false;
	MSG Msg;

	if (! win.init("OVDIMEChannelClass")) {
		OutputDebugString("Failed to initialize windows ");
		return 2;
	}

	if (! win.create()) {
		OutputDebugString("Failed to create windows ");
		return 3;
	}

	while(true) {
		// Test if the channel is now opened
		if (!connected) {
			if (ki.isConnected()) {

				OutputDebugString("status switch to connected");
				connected = true;

				ki.uninit();
				ki.init();
			}
			else {
				Sleep(1000);
				continue;
			}
		}

		while(ki.isConnected()) {
			ki.update();
			ki.processNextMessage();

			while (PeekMessage(&Msg, NULL, 0, 0, PM_REMOVE))
			{
				TranslateMessage(&Msg);
				DispatchMessage(&Msg);
			}

			Sleep(REFRESH_TIME);
		}

		OutputDebugString("session disconnected");
		connected = false;
	}

	return Msg.wParam;
}
