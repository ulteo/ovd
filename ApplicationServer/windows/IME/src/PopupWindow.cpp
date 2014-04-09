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
    _psz = NULL;
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

    if (_psz)
        LocalFree(_psz);
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

    _hwnd = CreateWindowEx(WS_EX_TOPMOST, _szWndClass, TEXT(""),
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
    HDC hdc;
    PAINTSTRUCT ps;
    COPYDATASTRUCT* cds;
  
    _this = _GetThis(hwnd);

    switch (uMsg)
    {
        case WM_CREATE:
            _SetThis(hwnd, lParam);
            return 0;

        case WM_PAINT:
            hdc = BeginPaint(hwnd, &ps);
            if (_this)
                _this->OnPaint(hwnd, hdc);
            EndPaint(hwnd, &ps);
            break;

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
// Show
//
//----------------------------------------------------------------------------

void CPropertyPopupWindow::Show()
{
    if (!IsWindow(_hwnd))
        return;

    RECT rcWork;
    SystemParametersInfo(SPI_GETWORKAREA,  0, &rcWork, FALSE);

    InvalidateRect(_hwnd, NULL, TRUE);
    SetWindowPos(_hwnd, HWND_TOPMOST, 0, 0, 100, 100, SWP_SHOWWINDOW | SWP_NOACTIVATE);
}


//+---------------------------------------------------------------------------
//
// Hide
//
//----------------------------------------------------------------------------

void CPropertyPopupWindow::Hide()
{
    if (!IsWindow(_hwnd))
        return;

    ShowWindow(_hwnd, SW_HIDE);
}


//+---------------------------------------------------------------------------
//
// OnPaint
//
//----------------------------------------------------------------------------

void CPropertyPopupWindow::OnPaint(HWND hwnd, HDC hdc)
{
    HFONT hfont = (HFONT)GetStockObject(DEFAULT_GUI_FONT);
    HFONT hfontOld = (HFONT)SelectObject(hdc, hfont);

    RECT rc;
    GetClientRect(hwnd, &rc);
    DrawTextW(hdc, L"Hello world", lstrlenW(L"Hello world"), &rc, DT_EXPANDTABS);

    SelectObject(hdc, hfontOld);
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
        _pPopupWindow->Show();
    }
}
