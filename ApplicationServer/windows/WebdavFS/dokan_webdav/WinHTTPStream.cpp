/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2010, 2012
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
#include "WinHTTPStream.h"


WinHTTPStream::WinHTTPStream(HINTERNET request) {
	m_refcount = 1;
	m_Request=request;
}

WinHTTPStream::~WinHTTPStream() {}

HRESULT STDMETHODCALLTYPE WinHTTPStream::QueryInterface(REFIID iid, void ** ppvObject) {
	if (iid == __uuidof(IUnknown) || iid == __uuidof(IStream) || iid == __uuidof(ISequentialStream)) {
		*ppvObject = static_cast<IStream*>(this);
		AddRef();
		return S_OK;
	}
	else
		return E_NOINTERFACE;
}

ULONG STDMETHODCALLTYPE WinHTTPStream::AddRef(void) { 
	return (ULONG)InterlockedIncrement(&m_refcount);
}

ULONG STDMETHODCALLTYPE WinHTTPStream::Release(void) {
	ULONG res = (ULONG) InterlockedDecrement(&m_refcount);
	if (res == 0)
		delete this;
	return res;
}

HRESULT STDMETHODCALLTYPE WinHTTPStream::Read(void* pv, ULONG cb, ULONG* pcbRead) {
	BOOL result = ::WinHttpReadData( m_Request, pv, cb, pcbRead);
	return (result) ? S_OK : HRESULT_FROM_WIN32(GetLastError());
}

HRESULT STDMETHODCALLTYPE WinHTTPStream::Write(void const* pv, ULONG cb, ULONG* pcbWritten) {
	BOOL result=::WinHttpWriteData( m_Request, (LPVOID)pv,cb , pcbWritten );
	return (result) ? S_OK : HRESULT_FROM_WIN32(GetLastError());
}

HRESULT STDMETHODCALLTYPE WinHTTPStream::SetSize(ULARGE_INTEGER) { 
	return E_NOTIMPL;
}
	
HRESULT STDMETHODCALLTYPE WinHTTPStream::CopyTo(IStream*, ULARGE_INTEGER, ULARGE_INTEGER*, ULARGE_INTEGER*) { 
	return E_NOTIMPL;
}
	
HRESULT STDMETHODCALLTYPE WinHTTPStream::Commit(DWORD) { 
	return E_NOTIMPL;
}
	
HRESULT STDMETHODCALLTYPE WinHTTPStream::Revert(void) { 
	return E_NOTIMPL;
}
	
HRESULT STDMETHODCALLTYPE WinHTTPStream::LockRegion(ULARGE_INTEGER, ULARGE_INTEGER, DWORD) { 
	return E_NOTIMPL;
}
	
HRESULT STDMETHODCALLTYPE WinHTTPStream::UnlockRegion(ULARGE_INTEGER, ULARGE_INTEGER, DWORD) { 
	return E_NOTIMPL;
}
	
HRESULT STDMETHODCALLTYPE WinHTTPStream::Clone(IStream **) { 
	return E_NOTIMPL;
}

HRESULT STDMETHODCALLTYPE WinHTTPStream::Seek(LARGE_INTEGER liDistanceToMove, DWORD dwOrigin, ULARGE_INTEGER* lpNewFilePointer) {
	UNREFERENCED_PARAMETER(dwOrigin);
	UNREFERENCED_PARAMETER(lpNewFilePointer);
	UNREFERENCED_PARAMETER(liDistanceToMove);
	return E_NOTIMPL;
}

HRESULT STDMETHODCALLTYPE WinHTTPStream::Stat(STATSTG* pStatstg, DWORD grfStatFlag) {
	UNREFERENCED_PARAMETER(pStatstg);
	UNREFERENCED_PARAMETER(grfStatFlag);
	return E_NOTIMPL;
}
