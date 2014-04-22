//////////////////////////////////////////////////////////////////////
//
//  THIS CODE AND INFORMATION IS PROVIDED "AS IS" WITHOUT WARRANTY OF
//  ANY KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED
//  TO THE IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
//  PARTICULAR PURPOSE.
//
//  Copyright (C) 2003  Microsoft Corporation.  All rights reserved.
//
//  PopupWindow.cpp
//
//          CProeprtyPopupWindow
//
//////////////////////////////////////////////////////////////////////

#include "Globals.h"
#include "TextService.h"
#include "PopupWindow.h"
#include <stdio.h>

TCHAR CPropertyPopupWindow::_szWndClass[] = TEXT("OVDIMEClass");

//+---------------------------------------------------------------------------
//
// CPropertyPopupWindow
//
//----------------------------------------------------------------------------

//+---------------------------------------------------------------------------
//
// ctor
//
//----------------------------------------------------------------------------

CPropertyPopupWindow::CPropertyPopupWindow(CTextService *pService)
{
    _hwnd = NULL;
    _pService = pService;
}

//+---------------------------------------------------------------------------
//
// dtor
//
//----------------------------------------------------------------------------

CPropertyPopupWindow::~CPropertyPopupWindow()
{
    if (IsWindow(_hwnd))
        DestroyWindow(_hwnd);
}


void CPropertyPopupWindow::Destroy()
{
    if (IsWindow(_hwnd))
        DestroyWindow(_hwnd);

    _hwnd = NULL;
}


//+---------------------------------------------------------------------------
//
// StaticInit
//
//----------------------------------------------------------------------------

BOOL CPropertyPopupWindow::StaticInit()
{

    WNDCLASSEX wcex;

    memset(&wcex, 0, sizeof(wcex));
    wcex.cbSize        = sizeof(wcex);
    wcex.style         = CS_HREDRAW | CS_VREDRAW ;
    wcex.hInstance     = g_hInst;
    wcex.hCursor       = LoadCursor(NULL, IDC_ARROW);
    wcex.hbrBackground    = (HBRUSH)(COLOR_WINDOW+1);

    wcex.lpfnWndProc   = _WndProc;
    wcex.lpszClassName = _szWndClass;
    RegisterClassEx(&wcex);

    return TRUE;
}

//+---------------------------------------------------------------------------
//
// CreateWnd
//
//----------------------------------------------------------------------------

HWND CPropertyPopupWindow::CreateWnd()
{
    if (_hwnd)
        return _hwnd;

    _hwnd = CreateWindowEx(0, _szWndClass, TEXT(""),
                           WS_POPUP | WS_THICKFRAME | WS_DISABLED,
                           0, 0, 0, 0,
                           NULL, 0, g_hInst, this);

    return _hwnd;
}

//+---------------------------------------------------------------------------
//
// _OwnerWndProc
//
//----------------------------------------------------------------------------

LRESULT CALLBACK CPropertyPopupWindow::_WndProc(HWND hwnd, UINT uMsg, WPARAM wParam, LPARAM lParam)
{
    CPropertyPopupWindow *_this;
    COPYDATASTRUCT* cds;
  
    _this = _GetThis(hwnd);
	int ime_stop_composition = RegisterWindowMessage("WM_OVD_STOP_COMPOSITION");

	if (uMsg == ime_stop_composition) {
		OutputDebugString("new WM_OVD_STOP_COMPOSITION message");
		_this->_pService->stopComposition();
		return 0;
	}

    switch (uMsg)
    {
        case WM_CREATE:
            _SetThis(hwnd, lParam);
            return 0;

        case WM_COPYDATA:
            cds = (COPYDATASTRUCT*) lParam;
            OutputDebugString("New data");
            _this->_pService->setComposition(cds->lpData, cds->cbData);
            break;

        default:
            return DefWindowProc(hwnd, uMsg, wParam, lParam);
    }
    return 0;
}


//+---------------------------------------------------------------------------
//
// _ShowPopupWindow
//
//----------------------------------------------------------------------------

void CTextService::_ShowPopupWindow()
{
    if (!_pPopupWindow)
        _pPopupWindow = new CPropertyPopupWindow(this);

    if (_pPopupWindow)
    {
        _pPopupWindow->CreateWnd();
    }
}
