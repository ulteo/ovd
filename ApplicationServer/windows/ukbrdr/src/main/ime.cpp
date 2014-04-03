#include <iostream>
#include <windows.h>
#include <vchannel/vchannel.h>


using namespace std;


bool ime_init() {
	if (vchannel_open("imer") != 0) {
		std::cerr<<"Failed to open channel"<<std::endl;
		return false;
	}

	return true;
}


