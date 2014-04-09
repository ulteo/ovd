//////////////////////////////////////////////////////////////////////
//
//  THIS CODE AND INFORMATION IS PROVIDED "AS IS" WITHOUT WARRANTY OF
//  ANY KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED
//  TO THE IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
//  PARTICULAR PURPOSE.
//
//  Copyright (C) 2003  Microsoft Corporation.  All rights reserved.
//
//  ThreadFocusSink.cpp
//
//          ITfThreadFocusSink interface
//
//////////////////////////////////////////////////////////////////////

#include "Globals.h"
#include "TextService.h"
#include "PopupWindow.h"

//+---------------------------------------------------------------------------
//
// OnSetThreadFocus
//
//----------------------------------------------------------------------------

STDAPI CTextService::OnSetThreadFocus()
{
	OutputDebugString("OnSetThreadFocus");
	_ShowPopupWindow();

    if (_pPopupWindow)
    {
    	OutputDebugString("Show");
       _pPopupWindow->Show();
    }
    return S_OK;
}

//+---------------------------------------------------------------------------
//
// OnKillThreadFocus
//
//----------------------------------------------------------------------------

STDAPI CTextService::OnKillThreadFocus()
{
    if (_pPopupWindow)
    {
       //_pPopupWindow->Hide();
       _pPopupWindow->Destroy();
    }

    return S_OK;
}

//+---------------------------------------------------------------------------
//
// _InitThreadFocusSink
//
//----------------------------------------------------------------------------

BOOL CTextService::_InitThreadFocusSink()
{
    ITfSource *pSource;

    if (_pThreadMgr->QueryInterface(IID_ITfSource, (void **)&pSource) == S_OK)
    {
        pSource->AdviseSink(IID_ITfThreadFocusSink, (ITfThreadFocusSink *)this, &_dwThreadFocusCookie);
        pSource->Release();
    }

    return TRUE;
}
 
//+---------------------------------------------------------------------------
//
// _UninitThreadFocusSink
//
//----------------------------------------------------------------------------

void CTextService::_UninitThreadFocusSink()
{
    ITfSource *pSource;

    if (_pThreadMgr->QueryInterface(IID_ITfSource, (void **)&pSource) == S_OK)
    {
        pSource->UnadviseSink(_dwThreadFocusCookie);
        pSource->Release();
    }
}
 
 


