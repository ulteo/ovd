//////////////////////////////////////////////////////////////////////
//
//  THIS CODE AND INFORMATION IS PROVIDED "AS IS" WITHOUT WARRANTY OF
//  ANY KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED
//  TO THE IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
//  PARTICULAR PURPOSE.
//
//  Copyright (C) 2003  Microsoft Corporation.  All rights reserved.
//
//  InsertHello.cpp
//
//          Insert Hello edit session
//
//////////////////////////////////////////////////////////////////////

#include "globals.h"
#include "TextService.h"
#include "EditSession.h"
#include <stdio.h>

//+---------------------------------------------------------------------------
//
// CInsertHelloEditSession
//
//----------------------------------------------------------------------------

class CInsertHelloEditSession2 : public CEditSessionBase
{
private:
	PVOID _pData;
	int _dwData;

public:
    CInsertHelloEditSession2(CTextService *pTextService, ITfContext *pContext, PVOID data, int len) : CEditSessionBase(pTextService, pContext)
    {
    	if (data == NULL) {
    		_pData = NULL;
    		_dwData = 0;
    	}

    	_pData = new char[len];
    	memcpy(_pData, (char*)data, len);
    	_dwData = len;
    }

    ~CInsertHelloEditSession2()
    {
    	if (_pData != NULL) {
    		delete[] _pData;
    	}
    }

    // ITfEditSession
    STDMETHODIMP DoEditSession(TfEditCookie ec);
};

//+---------------------------------------------------------------------------
//
// DoEditSession
//
//----------------------------------------------------------------------------

STDAPI CInsertHelloEditSession2::DoEditSession(TfEditCookie ec)
{
	_pTextService->_InsertComposition(ec, _pContext, _pData, _dwData);
    return S_OK;
}


BOOL IsRangeCovered2(TfEditCookie ec, ITfRange *pRangeTest, ITfRange *pRangeCover)
{
    LONG lResult;

    if (pRangeCover->CompareStart(ec, pRangeTest, TF_ANCHOR_START, &lResult) != S_OK ||
        lResult > 0)
    {
        return FALSE;
    }

    if (pRangeCover->CompareEnd(ec, pRangeTest, TF_ANCHOR_END, &lResult) != S_OK ||
        lResult < 0)
    {
        return FALSE;
    }

    return TRUE;
}


//+---------------------------------------------------------------------------
//
// InsertTextAtSelection
//
//----------------------------------------------------------------------------


HRESULT CTextService::_InsertComposition(TfEditCookie ec, ITfContext *pContext, PVOID data, int len)
{
    ITfRange *pRangeComposition;
    TF_SELECTION tfSelection;
    ULONG cFetched;
    BOOL fCovered;
    WCHAR* str = (WCHAR*)data;
    LONG result;

    // Start the new compositon if there is no composition.
    OutputDebugString("test compositing");
    if (!_IsComposing()) {
    	if (!this->_IsKeyboardOpen())
    		this->_SetKeyboardOpen(TRUE);

    	OutputDebugString("start compositing");
        _StartComposition(pContext);
    }

	// get current selection/insertion point
	if(pContext->GetSelection(ec, TF_DEFAULT_SELECTION, 1, &tfSelection, &cFetched) == S_OK) {
	    ITfRange *compositionRange;
		if(_pComposition->GetRange(&compositionRange) == S_OK) {
			bool selPosInComposition = true;
		    OutputDebugString("pouet1");

			// if current insertion point is not covered by composition, we cannot insert text here.
			if(selPosInComposition) {
				OutputDebugString("pouet2");
				// replace context of composion area with the new string.
				compositionRange->SetText(ec, TF_ST_CORRECTION, str, wcslen(str));

				// move the insertion point to end of the composition string
				tfSelection.range->Collapse(ec, TF_ANCHOR_END);
				pContext->SetSelection(ec, 1, &tfSelection);
			}

			// set display attribute to the composition range
			_SetCompositionDisplayAttributes(ec, pContext, _gaDisplayAttributeInput);
		}
		tfSelection.range->Release();
	}

    return S_OK;
}



//+---------------------------------------------------------------------------
//
// setComposition
//
// Set content of the preedit popup
//----------------------------------------------------------------------------

void CTextService::setComposition(PVOID data, int len)
{
    ITfDocumentMgr *pDocMgrFocus;
    ITfContext *pContext;
    CInsertHelloEditSession2 *pInsertHelloEditSession;
    HRESULT hr;

    // get the focus document
    if (_pThreadMgr->GetFocus(&pDocMgrFocus) != S_OK)
        return;

    // get the topmost context, since the main doc context could be
    // superseded by a modal tip context
    if (pDocMgrFocus->GetTop(&pContext) != S_OK)
    {
        pContext = NULL;
        goto Exit;
    }

    if (pInsertHelloEditSession = new CInsertHelloEditSession2(this, pContext, data, len))
    {
        //A document write lock is required to insert text
        // the CInsertHelloEditSession will do all the work when the
        // CInsertHelloEditSession::DoEditSession method is called by the context
        pContext->RequestEditSession(_tfClientId, pInsertHelloEditSession, TF_ES_READWRITE | TF_ES_ASYNCDONTCARE, &hr);

        pInsertHelloEditSession->Release();
    }

Exit:
    if (pContext)
        pContext->Release();

    pDocMgrFocus->Release();
}


//+---------------------------------------------------------------------------
//
// stopComposition
//
// Stop the current composition.
//----------------------------------------------------------------------------

void CTextService::stopComposition()
{
    ITfDocumentMgr *pDocMgrFocus;
    ITfContext *pContext;

    // get the focus document
    if (_pThreadMgr->GetFocus(&pDocMgrFocus) != S_OK)
        return;

    // get the topmost context, since the main doc context could be
    // superseded by a modal tip context
    if (pDocMgrFocus->GetTop(&pContext) != S_OK)
    {
        pContext = NULL;
        goto Exit;
    }

    _EndComposition(pContext);

    if (!this->_IsComposing()) {
    	if (!this->_IsKeyboardOpen())
    		this->_SetKeyboardOpen(FALSE);
    }

Exit:
    if (pContext)
        pContext->Release();

    pDocMgrFocus->Release();
}
