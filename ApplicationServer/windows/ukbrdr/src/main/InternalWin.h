/*
 * InternalWin.h
 *
 *  Created on: 4 avr. 2014
 *      Author: david
 */

#ifndef INTERNALWIN_H_
#define INTERNALWIN_H_

#include <windows.h>
#include <string>


typedef BOOL (STDAPICALLTYPE *PtrChangeWindowMessageFilter) (UINT,DWORD);


class InternalWin {
private:
	HINSTANCE hInstance;
	HWND hwnd;
	std::string className;

public:
	InternalWin(HINSTANCE hInstance);
	virtual ~InternalWin();

	bool init(std::string className);
	bool create();



	static LRESULT CALLBACK wndProc(HWND hWnd, UINT Msg, WPARAM wParam, LPARAM lParam);
};

#endif /* INTERNALWIN_H_ */
