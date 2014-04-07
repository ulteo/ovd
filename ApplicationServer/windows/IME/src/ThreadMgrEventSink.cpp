//////////////////////////////////////////////////////////////////////
//
//  THIS CODE AND INFORMATION IS PROVIDED "AS IS" WITHOUT WARRANTY OF
//  ANY KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED
//  TO THE IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
//  PARTICULAR PURPOSE.
//
//  Copyright (C) 2003  Microsoft Corporation.  All rights reserved.
//
//  ThreadMgrEventSink.cpp
//
//          ITfThreadMgrEventSink implementation.
//
//////////////////////////////////////////////////////////////////////

#include "Globals.h"
#include "TextService.h"
#include <stdio.h>

//+---------------------------------------------------------------------------
//
// OnInitDocumentMgr
//
// Sink called by the framework just before the first context is pushed onto
// a document.
//----------------------------------------------------------------------------

STDAPI CTextService::OnInitDocumentMgr(ITfDocumentMgr *pDocMgr)
{
	OutputDebugString("OnInitDocumentMgr");

    return S_OK;
}

//+---------------------------------------------------------------------------
//
// OnUninitDocumentMgr
//
// Sink called by the framework just after the last context is popped off a
// document.
//----------------------------------------------------------------------------

STDAPI CTextService::OnUninitDocumentMgr(ITfDocumentMgr *pDocMgr)
{
	OutputDebugString("OnUninitDocumentMgr");
    return S_OK;
}



BOOL CTextService::_IsKeyboardDisabled(ITfDocumentMgr *pDocMgrFocus)
{
    ITfCompartmentMgr *pCompMgr = NULL;
    ITfContext *pContext = NULL;
    BOOL fDisabled = FALSE;

    if (pDocMgrFocus == NULL)
    {
        // if there is no focus document manager object, the keyboard
        // is disabled.
        fDisabled = TRUE;
        goto Exit;
    }

    if ((pDocMgrFocus->GetTop(&pContext) != S_OK) ||
        (pContext == NULL))
    {
        // if there is no context object, the keyboard is disabled.
        fDisabled = TRUE;
        goto Exit;
    }

    if (pContext->QueryInterface(IID_ITfCompartmentMgr, (void **)&pCompMgr) == S_OK)
    {
        ITfCompartment *pCompartmentDisabled;
        ITfCompartment *pCompartmentEmptyContext;

        // Check GUID_COMPARTMENT_KEYBOARD_DISABLED.
        if (pCompMgr->GetCompartment(GUID_COMPARTMENT_KEYBOARD_DISABLED, &pCompartmentDisabled) == S_OK)
        {
            VARIANT var;
            if (S_OK == pCompartmentDisabled->GetValue(&var))
            {
                if (var.vt == VT_I4) // Even VT_EMPTY, GetValue() can succeed
                {
                    fDisabled = (BOOL)var.lVal;
                }
            }
            pCompartmentDisabled->Release();
        }

        // Check GUID_COMPARTMENT_EMPTYCONTEXT.
        if (pCompMgr->GetCompartment(GUID_COMPARTMENT_EMPTYCONTEXT, &pCompartmentEmptyContext) == S_OK)
        {
            VARIANT var;
            if (S_OK == pCompartmentEmptyContext->GetValue(&var))
            {
                if (var.vt == VT_I4) // Even VT_EMPTY, GetValue() can succeed
                {
                    fDisabled = (BOOL)var.lVal;
                }
            }
            pCompartmentEmptyContext->Release();
        }

        pCompMgr->Release();
    }

Exit:
    if (pContext)
        pContext->Release();

    return fDisabled;
}



//+---------------------------------------------------------------------------
//
// OnSetFocus
//
// Sink called by the framework when focus changes from one document to
// another.  Either document may be NULL, meaning previously there was no
// focus document, or now no document holds the input focus.
//----------------------------------------------------------------------------

STDAPI CTextService::OnSetFocus(ITfDocumentMgr *pDocMgrFocus, ITfDocumentMgr *pDocMgrPrevFocus)
{
	char buffer[1024];
	BOOL status;

    status = this->_IsKeyboardDisabled(pDocMgrFocus);

    sprintf_s(buffer, sizeof(buffer), "IME status %i", status);
    OutputDebugString(buffer);
    //
    // Whenever focus is changed, initialize the TextEditSink.
    //
    _InitTextEditSink(pDocMgrFocus);

	OutputDebugString("OnSetFocus");

    return S_OK;
}

//+---------------------------------------------------------------------------
//
// OnPushContext
//
// Sink called by the framework when a context is pushed.
//----------------------------------------------------------------------------

STDAPI CTextService::OnPushContext(ITfContext *pContext)
{

	OutputDebugString("OnPushContext");

    return S_OK;
}

//+---------------------------------------------------------------------------
//
// OnPopContext
//
// Sink called by the framework when a context is popped.
//----------------------------------------------------------------------------

STDAPI CTextService::OnPopContext(ITfContext *pContext)
{
	OutputDebugString("OnPopContext");

    return S_OK;
}

//+---------------------------------------------------------------------------
//
// _InitThreadMgrEventSink
//
// Advise our sink.
//----------------------------------------------------------------------------

BOOL CTextService::_InitThreadMgrEventSink()
{
    ITfSource *pSource;
    BOOL fRet;

    if (_pThreadMgr->QueryInterface(IID_ITfSource, (void **)&pSource) != S_OK)
        return FALSE;

    fRet = FALSE;

    if (pSource->AdviseSink(IID_ITfThreadMgrEventSink, (ITfThreadMgrEventSink *)this, &_dwThreadMgrEventSinkCookie) != S_OK)
    {
        // Don't try to Unadvise _dwThreadMgrEventSinkCookie later
        _dwThreadMgrEventSinkCookie = TF_INVALID_COOKIE;
        goto Exit;
    }

    fRet = TRUE;

Exit:
    pSource->Release();
    return fRet;
}

//+---------------------------------------------------------------------------
//
// _UninitThreadMgrEventSink
//
// Unadvise our sink.
//----------------------------------------------------------------------------

void CTextService::_UninitThreadMgrEventSink()
{
    ITfSource *pSource;

    if (_dwThreadMgrEventSinkCookie == TF_INVALID_COOKIE)
        return; // never Advised

    if (_pThreadMgr->QueryInterface(IID_ITfSource, (void **)&pSource) == S_OK)
    {
        pSource->UnadviseSink(_dwThreadMgrEventSinkCookie);
        pSource->Release();
    }

    _dwThreadMgrEventSinkCookie = TF_INVALID_COOKIE;
}
