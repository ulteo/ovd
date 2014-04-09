//////////////////////////////////////////////////////////////////////
//
//  THIS CODE AND INFORMATION IS PROVIDED "AS IS" WITHOUT WARRANTY OF
//  ANY KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED
//  TO THE IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
//  PARTICULAR PURPOSE.
//
//  Copyright (C) 2003  Microsoft Corporation.  All rights reserved.
//
//  PopupWindow.h
//
//          CProeprtyPopupWindow
//
//////////////////////////////////////////////////////////////////////

#ifndef POPUPWINDOW_H
#define POPUPWINDOW_H

#include "TextService.h"

#define POPUP_CX 450
#define POPUP_CY 300

class CPropertyPopupWindow
{
public:
    CPropertyPopupWindow(CTextService *pService);
    ~CPropertyPopupWindow();

    HWND CreateWnd();
    void Destroy();
    void Show();
    void Hide();
    void OnPaint(HWND hwnd, HDC hdc);
    BOOL IsShown()
    {
        if (!IsWindow(_hwnd))
            return FALSE;

        return IsWindowVisible(_hwnd);
    }

    static BOOL StaticInit();

    static LRESULT CALLBACK _WndProc(HWND hwnd, UINT uMsg, WPARAM wParam, LPARAM lParam);

    static void _SetThis(HWND hwnd, LPARAM lParam)
    {
        SetWindowLongPtr(hwnd, GWLP_USERDATA, 
                         (LONG_PTR)((CREATESTRUCT *)lParam)->lpCreateParams);
    }

    static CPropertyPopupWindow *_GetThis(HWND hwnd)
    {
        return (CPropertyPopupWindow *)GetWindowLongPtr(hwnd, GWLP_USERDATA);
    }

    HWND _hwnd;
    CTextService *_pService;
    static TCHAR _szWndClass[];

    WCHAR *_psz;
};

#endif POPUPWINDOW_H
