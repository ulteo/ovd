//////////////////////////////////////////////////////////////////////
//
//  THIS CODE AND INFORMATION IS PROVIDED "AS IS" WITHOUT WARRANTY OF
//  ANY KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED
//  TO THE IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
//  PARTICULAR PURPOSE.
//
//  Copyright (C) 2003  Microsoft Corporation.  All rights reserved.
//
//  TextService.h
//
//          CTextService declaration.
//
//////////////////////////////////////////////////////////////////////

#ifndef TEXTSERVICE_H
#define TEXTSERVICE_H

class CPropertyPopupWindow;

class CTextService : public ITfTextInputProcessor,
                     public ITfThreadMgrEventSink,
                     public ITfTextEditSink,
                     public ITfCompositionSink,
                     public ITfThreadFocusSink,
                     public ITfDisplayAttributeProvider
{
public:
    CTextService();
    ~CTextService();

    // IUnknown
    STDMETHODIMP QueryInterface(REFIID riid, void **ppvObj);
    STDMETHODIMP_(ULONG) AddRef(void);
    STDMETHODIMP_(ULONG) Release(void);

    // ITfTextInputProcessor
    STDMETHODIMP Activate(ITfThreadMgr *pThreadMgr, TfClientId tfClientId);
    STDMETHODIMP Deactivate();

    // ITfThreadMgrEventSink
    STDMETHODIMP OnInitDocumentMgr(ITfDocumentMgr *pDocMgr);
    STDMETHODIMP OnUninitDocumentMgr(ITfDocumentMgr *pDocMgr);
    STDMETHODIMP OnSetFocus(ITfDocumentMgr *pDocMgrFocus, ITfDocumentMgr *pDocMgrPrevFocus);
    STDMETHODIMP OnPushContext(ITfContext *pContext);
    STDMETHODIMP OnPopContext(ITfContext *pContext);

    // ITfTextEditSink
    STDMETHODIMP OnEndEdit(ITfContext *pContext, TfEditCookie ecReadOnly, ITfEditRecord *pEditRecord);

    // ITfThreadFocusSink
    STDMETHODIMP OnSetThreadFocus();
    STDMETHODIMP OnKillThreadFocus();

//////

    // ITfCompositionSink
    STDMETHODIMP OnCompositionTerminated(TfEditCookie ecWrite, ITfComposition *pComposition);

    // ITfDisplayAttributeProvider
    STDMETHODIMP EnumDisplayAttributeInfo(IEnumTfDisplayAttributeInfo **ppEnum);
    STDMETHODIMP GetDisplayAttributeInfo(REFGUID guidInfo, ITfDisplayAttributeInfo **ppInfo);

//////

    // CClassFactory factory callback
    static HRESULT CreateInstance(IUnknown *pUnkOuter, REFIID riid, void **ppvObj);

    ITfThreadMgr *_GetThreadMgr() { return _pThreadMgr; }
    void setComposition(PVOID data, int len);

    // utility function for compartment
    BOOL _IsKeyboardDisabled();
    BOOL _IsKeyboardOpen();
    HRESULT _SetKeyboardOpen(BOOL fOpen);

    // functions for the composition object.
    void _StartComposition(ITfContext *pContext);
    void _EndComposition(ITfContext *pContext);
    void _TerminateComposition(TfEditCookie ec, ITfContext *pContext);
    BOOL _IsComposing();
    void _SetComposition(ITfComposition *pComposition);
    void _ShowPopupWindow();

    BOOL _InitThreadFocusSink();
    void _UninitThreadFocusSink();

    // key event handlers.
    HRESULT _HandleCharacterKey(TfEditCookie ec, ITfContext *pContext, WPARAM wParam);
    HRESULT _HandleArrowKey(TfEditCookie ec, ITfContext *pContext, WPARAM wParam);
    HRESULT _HandleReturnKey(TfEditCookie ec, ITfContext *pContext);
    HRESULT _HandleSpaceKey(TfEditCookie ec, ITfContext *pContext);
    HRESULT _InvokeKeyHandler(ITfContext *pContext, WPARAM wParam, LPARAM lParam);
    HRESULT _InsertComposition(TfEditCookie ec, ITfContext *pContext, PVOID data, int len);

    void _ClearCompositionDisplayAttributes(TfEditCookie ec, ITfContext *pContext);
    BOOL _SetCompositionDisplayAttributes(TfEditCookie ec, ITfContext *pContext, TfGuidAtom gaDisplayAttribute);
    BOOL _InitDisplayAttributeGuidAtom();

private:
    // initialize and uninitialize ThreadMgrEventSink.
    BOOL _InitThreadMgrEventSink();
    void _UninitThreadMgrEventSink();

    // initialize TextEditSink.
    BOOL _InitTextEditSink(ITfDocumentMgr *pDocMgr);

    BOOL _InitLanguageBar();
    void _UninitLanguageBar();

    // initialize and uninitialize KeyEventSink.
    BOOL _InitKeyEventSink();
    void _UninitKeyEventSink();

    // utility function for KeyEventSink
    BOOL _IsKeyEaten(WPARAM wParam);

    //
    // state
    //
    ITfThreadMgr *_pThreadMgr;
    TfClientId   _tfClientId;

    // The cookie of ThreadMgrEventSink
    DWORD _dwThreadMgrEventSinkCookie;

    DWORD _dwThreadFocusCookie;
    int commit_len;

    //
    // private variables for TextEditSink
    //
    ITfContext   *_pTextEditSinkContext;
    DWORD _dwTextEditSinkCookie;

    // the current composition object.
    ITfComposition *_pComposition;

    // guidatom for the display attibute.
    TfGuidAtom _gaDisplayAttributeInput;
    TfGuidAtom _gaDisplayAttributeConverted;

    CPropertyPopupWindow *_pPopupWindow;

    LONG _cRef;     // COM ref count
};


#endif // TEXTSERVICE_H
