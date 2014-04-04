#include <iostream>
#include <windows.h>
#include <vchannel/vchannel.h>
#include "KeyboardImprovement.h"
#include "win.h"

#define REFRESH_TIME 500

using namespace std;


int main(int argc, char** argv) {
	KeyboardImprovement ki;

	if (!ki.init()) {
		return 2;
	}

	while (true)
	{
		ki.update();
		ki.processNextMessage();

		Sleep(REFRESH_TIME);
	}

	return 0;
}

