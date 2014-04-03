#include <iostream>
#include <windows.h>
#include <vchannel/vchannel.h>
#include "ime.h"


using namespace std;


int main(int argc, char** argv) {

	if (!ime_init()) {
		return 2;
	}

	while(true) {
		std::cout<<"pouet"<<std::endl;
		vchannel_write("TEST ", "");

		Sleep(1000);
	}
	
	return 0;
}

