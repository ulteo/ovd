/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author David Lechevalier <david@ulteo.com>
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
 **/
# define GSDLLEXPORT __declspec(dllimport)

#include "precomp.h"

#include "debug.h"

#include "devmode.h"
#include "oemps.h"
#include "intrface.h"
#include <winspool.h>
#include <Shlwapi.h>
#include <Rpc.h>
#include <string.h>
#include <winreg.h>
//for gs library
#include "ierrors.h"
#include "iapi.h"

const CHAR CONVERTIONTAG[]                   = "%%EOF";

// This indicates to Prefast that this is a usermode driver file.
__user_driver;


////////////////////////////////////////////////////////
//      Internal Globals
////////////////////////////////////////////////////////

static long g_cComponents;     // Count of active components
static long g_cServerLocks;    // Count of locks

////////////////////////////////////////////////////////
//      Internal Constants
////////////////////////////////////////////////////////

///////////////////////////////////////////////////////
// Warning: the following array order must match the
//          order in enum ENUMHOOKS.
///////////////////////////////////////////////////////
static const DRVFN OEMHookFuncs[] =
{
    { INDEX_DrvRealizeBrush,                (PFN) OEMRealizeBrush               },
    { INDEX_DrvCopyBits,                    (PFN) OEMCopyBits                   },
    { INDEX_DrvBitBlt,                      (PFN) OEMBitBlt                     },
    { INDEX_DrvStretchBlt,                  (PFN) OEMStretchBlt                 },
    { INDEX_DrvTextOut,                     (PFN) OEMTextOut                    },
    { INDEX_DrvStrokePath,                  (PFN) OEMStrokePath                 },
    { INDEX_DrvFillPath,                    (PFN) OEMFillPath                   },
    { INDEX_DrvStrokeAndFillPath,           (PFN) OEMStrokeAndFillPath          },
    { INDEX_DrvStartPage,                   (PFN) OEMStartPage                  },
    { INDEX_DrvSendPage,                    (PFN) OEMSendPage                   },
    { INDEX_DrvEscape,                      (PFN) OEMEscape                     },
    { INDEX_DrvStartDoc,                    (PFN) OEMStartDoc                   },
    { INDEX_DrvEndDoc,                      (PFN) OEMEndDoc                     },
    { INDEX_DrvQueryFont,                   (PFN) OEMQueryFont                  },
    { INDEX_DrvQueryFontTree,               (PFN) OEMQueryFontTree              },
    { INDEX_DrvQueryFontData,               (PFN) OEMQueryFontData              },
    { INDEX_DrvQueryAdvanceWidths,          (PFN) OEMQueryAdvanceWidths         },
    { INDEX_DrvFontManagement,              (PFN) OEMFontManagement             },
    { INDEX_DrvGetGlyphMode,                (PFN) OEMGetGlyphMode               },
    { INDEX_DrvStretchBltROP,               (PFN) OEMStretchBltROP              },
    { INDEX_DrvPlgBlt,                      (PFN) OEMPlgBlt                     },
    { INDEX_DrvTransparentBlt,              (PFN) OEMTransparentBlt             },
    { INDEX_DrvAlphaBlend,                  (PFN) OEMAlphaBlend                 },
    { INDEX_DrvGradientFill,                (PFN) OEMGradientFill               },
    { INDEX_DrvIcmCreateColorTransform,     (PFN) OEMIcmCreateColorTransform    },
    { INDEX_DrvIcmDeleteColorTransform,     (PFN) OEMIcmDeleteColorTransform    },
    { INDEX_DrvQueryDeviceSupport,          (PFN) OEMQueryDeviceSupport         },
};


////////////////////////////////////////////////////////////////////////////////
//
// IOemPS body
//
IOemPS::IOemPS() : m_cRef(1)
{
    VERBOSE("IOemPS::IOemPS() entered.\r\n");

    // Increment COM component count.
    InterlockedIncrement(&g_cComponents);

    m_pOEMHelp = NULL;

    VERBOSE("IOemPS::IOemPS() leaving.\r\n");
}


IOemPS::~IOemPS()
{
    // Make sure that helper interface is released.
    if(NULL != m_pOEMHelp)
    {
        m_pOEMHelp->Release();
        m_pOEMHelp = NULL;
    }

    // If this instance of the object is being deleted, then the reference
    // count should be zero.
    assert(0 == m_cRef);

    // Decrement COM compontent count.
    InterlockedDecrement(&g_cComponents);
}


BOOL IOemPS::isEOF(char* pBuf, int size){
     int indexBuf = 0;
     int indexMotif = 0;
     int motif_len = (int)strlen(CONVERTIONTAG);
     while (size != indexBuf){
           if( indexBuf+motif_len > size)
                return FALSE;
           while (indexMotif != motif_len){
                if(pBuf[indexBuf+indexMotif] != CONVERTIONTAG[indexMotif]){
                     indexMotif = 0;
                     break;
                }
                indexMotif++;
           }
           indexBuf++;
           if(indexMotif == motif_len)
                return TRUE;
     }
     return FALSE;   
}

int IOemPS::DoConvertion(wchar_t* wPSFile, wchar_t* wPDFFile )
{
    int code, code1;
    void *minst;
    const char * gsargv[10];
    int gsargc;
    char psfile[256];
    char pdffile[256];
    char temp[300];

    wcstombs(psfile, wPSFile, 256 );
    wcstombs(pdffile, wPDFFile, 256 );
    
    gsargv[0] = "ps2pdf";	/* actual value doesn't matter */
    gsargv[1] = "-dNOPAUSE";
    gsargv[2] = "-dBATCH";
    gsargv[3] = "-dSAFER";
    gsargv[4] = "-sDEVICE=pdfwrite";
    sprintf_s(temp, 300, "-sOutputFile=%s", pdffile);
    gsargv[5] = temp;
    gsargv[6] = "-c";
    gsargv[7] = ".setpdfwrite";
    gsargv[8] = "-f";
    gsargv[9] = psfile;
    gsargc=10;

    code = gsapi_new_instance(&minst, NULL);
    if (code < 0)
	   return 1;
    code = gsapi_init_with_args(minst, gsargc, (char**)gsargv);
    code1 = gsapi_exit(minst);
    if ((code == 0) || (code == e_Quit))
	   code = code1;

    gsapi_delete_instance(minst);

    if ((code == 0) || (code == e_Quit))
	   return 0;
    return 1;
}

BOOL IOemPS::isTSPrinter(HANDLE hPrinter){
    DWORD dwBytesReturned, dwBytesNeeded;
    GetPrinter(hPrinter, 2, NULL, NULL, &dwBytesNeeded);
    PRINTER_INFO_2* p2 = (PRINTER_INFO_2*)GlobalAlloc(GPTR, dwBytesNeeded);

    if ( ! ::GetPrinter(hPrinter, 2, (LPBYTE)p2, dwBytesNeeded, &dwBytesReturned)){
       GlobalFree(p2);
       return FALSE;
    }
  
    if (p2->Attributes & PRINTER_ATTRIBUTE_TS){
       GlobalFree(p2);
       return TRUE;
    }
    /* on Windows XP, no support for PRINTER_ATTRIBUTE_TS attribute*/
    if ( wcsncmp(p2->pPortName, L"TS", 2) == 0){
       GlobalFree(p2);
       return TRUE;
    }

    GlobalFree(p2);
    return FALSE;     
}


wchar_t* IOemPS::GetNewSpoolJobName()
{
  BYTE tempDir[MAX_PATH + 1];
  UUID uuid;
  WCHAR* wszUuid = NULL;

  wchar_t* spoolFileName = new wchar_t[MAX_PATH];
  DWORD res = 0;

  res = GetEnvironmentVariable(L"TEMP", (LPWSTR)tempDir, MAX_PATH+1);

  if (res == 0)
  {
    ERR("IOemPS::GetNewSpoolJobName() Unable to get environment variable TEMP.\r\n");
    return NULL;
  }
  if (res > 0 &&  tempDir[0] == NULL)
  {
    ERR("IOemPS::GetNewSpoolJobName() Environment variable TEMP is more larger than a common path.\r\n");
    return NULL;
  }

  //uuid generation
  ::ZeroMemory(&uuid, sizeof(UUID));
  ::UuidCreate(&uuid);

  ::UuidToStringW(&uuid, &wszUuid);

  swprintf_s(spoolFileName, MAX_PATH, L"%s\\%s", tempDir, wszUuid);

  ::RpcStringFree(&wszUuid);
  wszUuid = NULL;

  return spoolFileName;
}

BOOL IOemPS::SendPDF(HANDLE hPrinter, wchar_t* PDFFile)
{
  DWORD reallyRead = 0;
  DWORD dwBytesReturned = 0;

  if (hPrinter == INVALID_HANDLE_VALUE) {
        return FALSE;
     }

     HANDLE hHandle = CreateFile(PDFFile, FILE_GENERIC_READ, FILE_SHARE_READ, 0, OPEN_EXISTING, FILE_ATTRIBUTE_NORMAL, 0);
     if (hHandle == INVALID_HANDLE_VALUE){
        return FALSE;
     }
     LPBYTE lpBuffer = new BYTE[1000];
     reallyRead = 1;

     while( reallyRead > 0) {
    	 ReadFile(hHandle, lpBuffer, 1000, &reallyRead, NULL);
    	 if (::WritePrinter(hPrinter, lpBuffer, reallyRead,  &dwBytesReturned) <= 0) {
    		 return FALSE;
    	 }
     }
     CloseHandle(hHandle);
     ::ClosePrinter(hPrinter);
     delete lpBuffer;
     return TRUE;
}


HRESULT __stdcall IOemPS::QueryInterface(const IID& iid, void** ppv)
{
    if (iid == IID_IUnknown)
    {
        *ppv = static_cast<IUnknown*>(this);
        VERBOSE("IOemPS::QueryInterface IUnknown.\r\n");
    }
    else if (iid == IID_IPrintOemPS2)
    {
        *ppv = static_cast<IPrintOemPS2*>(this);
        VERBOSE("IOemPS::QueryInterface IPrintOemPs2.\r\n");
    }
    else
    {
        VERBOSE("IOemPS::QueryInterface (interface not supported).\r\n");
        *ppv = NULL;
        return E_NOINTERFACE;
    }
    reinterpret_cast<IUnknown*>(*ppv)->AddRef();
    return S_OK;
}

ULONG __stdcall IOemPS::AddRef()
{
    VERBOSE("IOemPS::AddRef() entry.\r\n");
    return InterlockedIncrement(&m_cRef);
}

__drv_at(this, __drv_freesMem(object)) 

ULONG __stdcall IOemPS::Release()
{
   VERBOSE("IOemPS::Release() entry.\r\n");
   ASSERT( 0 != m_cRef);
   ULONG cRef = InterlockedDecrement(&m_cRef);
   if (0 == cRef)
   {
      delete this;

   }
   return cRef;
}

//
//IPrintOemPS method
//
HRESULT __stdcall IOemPS::GetInfo (
    DWORD   dwMode,
    PVOID   pBuffer,
    DWORD   cbSize,
    PDWORD  pcbNeeded)
{
    VERBOSE("IOemPS::GetInfo(%d) entry.\r\n");

    // Validate parameters.
    if( (NULL == pcbNeeded)
        ||
        ( (OEMGI_GETSIGNATURE != dwMode)
          &&
          (OEMGI_GETVERSION != dwMode)
          &&
          (OEMGI_GETPUBLISHERINFO != dwMode)
        )
      )
    {
        ERR("IOemPS::GetInfo() exit pcbNeeded is NULL!\r\n");
        SetLastError(ERROR_INVALID_PARAMETER);
        return E_FAIL;
    }

    // Set expected buffer size.
    if(OEMGI_GETPUBLISHERINFO != dwMode)
    {
        *pcbNeeded = sizeof(DWORD);
    }
    else
    {
        *pcbNeeded = sizeof(PUBLISHERINFO);
        return E_FAIL;
    }

    // Check buffer size is sufficient.
    if((cbSize < *pcbNeeded) || (NULL == pBuffer))
    {
        ERR("IOemPS::GetInfo() exit insufficient buffer!\r\n");
        SetLastError(ERROR_INSUFFICIENT_BUFFER);
        return E_FAIL;
    }

    switch(dwMode)
    {
        // OEM DLL Signature
        case OEMGI_GETSIGNATURE:
            *(PDWORD)pBuffer = OEM_SIGNATURE;
            break;

        // OEM DLL version
        case OEMGI_GETVERSION:
            *(PDWORD)pBuffer = OEM_VERSION;
            break;

        // dwMode not supported.
        case OEMGI_GETPUBLISHERINFO:
        case OEMGI_GETREQUESTEDHELPERINTERFACES:
        default:
            // Set written bytes to zero since nothing was written.
            ERR("IOemPS::GetInfo() exit, mode not supported.\r\n");
            *pcbNeeded = 0;
            SetLastError(ERROR_NOT_SUPPORTED);
            return E_FAIL;
    }

    return S_OK;
}

HRESULT __stdcall IOemPS::PublishDriverInterface(
    IUnknown *pIUnknown)
{
    VERBOSE("IOemPS::PublishDriverInterface() entry.\r\n");

    // Need to store pointer to Driver Helper functions, if we already haven't.
    if (this->m_pOEMHelp == NULL)
    {
        HRESULT hResult;


        // Get Interface to Helper Functions.
        hResult = pIUnknown->QueryInterface(IID_IPrintOemDriverPS, (void** ) &(this->m_pOEMHelp));

        if(!SUCCEEDED(hResult))
        {
            // Make sure that interface pointer reflects interface query failure.
            this->m_pOEMHelp = NULL;

            return E_FAIL;
        }
    }

    return S_OK;
}


HRESULT __stdcall IOemPS::EnableDriver(DWORD          dwDriverVersion,
                                    DWORD          cbSize,
                                    PDRVENABLEDATA pded)
{
    VERBOSE("IOemPS::EnableDriver() entry.\r\n");

    UNREFERENCED_PARAMETER(dwDriverVersion);
    UNREFERENCED_PARAMETER(cbSize);

    // List DDI functions that are hooked.
    pded->iDriverVersion =  PRINTER_OEMINTF_VERSION;
    pded->c = sizeof(OEMHookFuncs) / sizeof(DRVFN);
    pded->pdrvfn = (DRVFN *) OEMHookFuncs;

    // Even if nothing is done, need to return S_OK so
    // that DisableDriver() will be called, which releases
    // the reference to the Printer Driver's interface.
    // If error occurs, return E_FAIL.
    return S_OK;
}

HRESULT __stdcall IOemPS::DisableDriver(VOID)
{
    VERBOSE("IOemPS::DisaleDriver() entry.\r\n");
    // Release reference to Printer Driver's interface.
    if (this->m_pOEMHelp)
    {
        this->m_pOEMHelp->Release();
        this->m_pOEMHelp = NULL;
    }

    return S_OK;
};

HRESULT __stdcall IOemPS::DisablePDEV(
    PDEVOBJ         pdevobj)
{
    VERBOSE("IOemPS::DisablePDEV() entry.\r\n");
    POEMPDEV    poempdev;
    //
    // Free memory for OEMPDEV and any memory block that hangs off OEMPDEV.
    //
    if (NULL != pdevobj->pdevOEM) {
        poempdev = (POEMPDEV)pdevobj->pdevOEM;
        if (poempdev != NULL ) {
            delete pdevobj->pdevOEM;
        }
    }
    return S_OK;
};

HRESULT __stdcall IOemPS::EnablePDEV(
    PDEVOBJ         pdevobj,
    __in PWSTR      pPrinterName,
    ULONG           cPatterns,
    HSURF          *phsurfPatterns,
    ULONG           cjGdiInfo,
    GDIINFO        *pGdiInfo,
    ULONG           cjDevInfo,
    DEVINFO        *pDevInfo,
    DRVENABLEDATA  *pded,
    OUT PDEVOEM    *pDevOem)
{
    UNREFERENCED_PARAMETER(pdevobj);
    UNREFERENCED_PARAMETER(pPrinterName);
    UNREFERENCED_PARAMETER(cPatterns);
    UNREFERENCED_PARAMETER(phsurfPatterns);
    UNREFERENCED_PARAMETER(cjGdiInfo);
    UNREFERENCED_PARAMETER(pGdiInfo);
    UNREFERENCED_PARAMETER(cjDevInfo);
    UNREFERENCED_PARAMETER(pDevInfo);

    VERBOSE("IOemPS::EnablePDEV() entry.\r\n");

    POEMPDEV    poempdev;
    INT         i, j;
    DWORD       dwDDIIndex;
    PDRVFN      pdrvfn;

    //
    // Allocate the OEMDev
    //
    poempdev = new OEMPDEV;
    if (NULL == poempdev)
    {
        return E_FAIL;
    }

    //
    // Fill in OEMDEV
    //

    for (i = 0; i < MAX_DDI_HOOKS; i++)
    {
        //
        // search through PS's hooks and locate the function ptr
        //
        dwDDIIndex = OEMHookFuncs[i].iFunc;
        for (j = pded->c, pdrvfn = pded->pdrvfn; j > 0; j--, pdrvfn++)
        {
            if (dwDDIIndex == pdrvfn->iFunc)
            {
                poempdev->pfnPS[i] = pdrvfn->pfn;
                break;
            }
        }
        if (j == 0)
        {
            //
            // Didn't find the hook. This could mean PS doesn't hook this DDI but allows OEMs to hook it out.
            //
            poempdev->pfnPS[i] = NULL;
        }

    }

    poempdev->spoolPSFileName = NULL;
    *pDevOem = (POEMPDEV) poempdev;

    return S_OK;
}


HRESULT __stdcall IOemPS::ResetPDEV(
    PDEVOBJ         pdevobjOld,
    PDEVOBJ        pdevobjNew)
{

    VERBOSE("IOemPS::ResetPDEV() entry.\r\n");
    POEMPDEV    poempdevOld;
    POEMPDEV    poempdevNew;

    VERBOSE("OEMEndDoc() entry.\r\n");

    poempdevOld = (POEMPDEV)pdevobjOld->pdevOEM;
    poempdevNew = (POEMPDEV)pdevobjNew->pdevOEM;
    poempdevNew-> spoolPSFileName = poempdevOld->spoolPSFileName;
    poempdevNew-> spoolPDFFileName = poempdevOld->spoolPDFFileName;
    //
    // If any information from the previous PDEV needs to be preserved,
    // copy it in this function.
    //

    return TRUE;
}


HRESULT __stdcall IOemPS::DevMode(
    DWORD  dwMode,
    POEMDMPARAM pOemDMParam)
{
    VERBOSE("IOemPS:DevMode entry.\n");
    return hrOEMDevMode(dwMode, pOemDMParam);
}


HRESULT __stdcall IOemPS::Command(
    PDEVOBJ     pdevobj,
    DWORD       dwIndex,
    PVOID       pData,
    DWORD       cbSize,
    OUT DWORD   *pdwResult)
{
    UNREFERENCED_PARAMETER(pdevobj);
    UNREFERENCED_PARAMETER(pData);
    UNREFERENCED_PARAMETER(cbSize);
    UNREFERENCED_PARAMETER(pdwResult);
    UNREFERENCED_PARAMETER(dwIndex);
    
    return E_NOTIMPL;
}









//
// IPrintOemPS2 method
//

HRESULT __stdcall IOemPS::GetPDEVAdjustment(
        PDEVOBJ    pdevobj,
        DWORD      dwAdjustType,
        PVOID      pBuf,
        DWORD      cbBuffer,
        OUT BOOL  *pbAdjustmentDone)
{
    UNREFERENCED_PARAMETER(pdevobj);
    UNREFERENCED_PARAMETER(dwAdjustType);
    UNREFERENCED_PARAMETER(pBuf);
    UNREFERENCED_PARAMETER(cbBuffer);
    UNREFERENCED_PARAMETER(pbAdjustmentDone);
    
    return E_NOTIMPL;
}


HRESULT __stdcall IOemPS::WritePrinter(
        PDEVOBJ     pdevobj,
        PVOID       pBuf,
        DWORD       cbBuffer,
        PDWORD     pcbWritten)
{
    if( cbBuffer == 0){
        *pcbWritten = 0;
        return S_OK;
    }

    if( ! isTSPrinter(pdevobj->hPrinter)){
       UNREFERENCED_PARAMETER(pdevobj);
       UNREFERENCED_PARAMETER(pBuf);
       UNREFERENCED_PARAMETER(cbBuffer);
       UNREFERENCED_PARAMETER(pcbWritten);
       return E_NOTIMPL;
    }
    //get spool tempfile
    POEMPDEV    poempdev;

    poempdev = (POEMPDEV)pdevobj->pdevOEM;
    if (poempdev->spoolPSFileName == NULL) {
      wchar_t* spoolFileName = NULL;
      spoolFileName = GetNewSpoolJobName();
      if (spoolFileName == NULL) {
        return E_NOTIMPL;
      }
      else {
        poempdev->spoolPSFileName = new wchar_t[256];
        poempdev->spoolPDFFileName = new wchar_t[256];
        swprintf_s(poempdev->spoolPDFFileName, MAX_PATH, L"%s.pdf", spoolFileName);
        swprintf_s(poempdev->spoolPSFileName, MAX_PATH,  L"%s.ps", spoolFileName);
      }
    }

    HANDLE hHandle = CreateFile(poempdev->spoolPSFileName, FILE_APPEND_DATA, FILE_SHARE_READ, 0, OPEN_EXISTING, FILE_ATTRIBUTE_NORMAL, 0);
    if (hHandle == INVALID_HANDLE_VALUE){
        hHandle = CreateFile(poempdev->spoolPSFileName, GENERIC_ALL, FILE_SHARE_WRITE, 0, CREATE_ALWAYS, FILE_ATTRIBUTE_NORMAL, 0);
    }
    if (hHandle == INVALID_HANDLE_VALUE){
        return E_NOTIMPL;
    }
    WriteFile(hHandle, pBuf, cbBuffer, pcbWritten, 0);
    CloseHandle(hHandle);

    if(isEOF((char*)pBuf, cbBuffer)){
        DoConvertion(poempdev->spoolPSFileName, poempdev->spoolPDFFileName);
        SendPDF(pdevobj->hPrinter, poempdev->spoolPDFFileName);
        DeleteFile(poempdev->spoolPSFileName);
        DeleteFile(poempdev->spoolPDFFileName);
        //free memory
        if (poempdev->spoolPSFileName != NULL) {
            delete poempdev->spoolPSFileName;
            delete poempdev->spoolPDFFileName;
            poempdev->spoolPSFileName = NULL;
            poempdev->spoolPDFFileName = NULL;

        }
        *pcbWritten = cbBuffer;
        return S_OK;
    }
    //UNREFERENCED_PARAMETER(pdevobj);
    UNREFERENCED_PARAMETER(pBuf);
    //UNREFERENCED_PARAMETER(cbBuffer);
    //UNREFERENCED_PARAMETER(pcbWritten);

    *pcbWritten = cbBuffer;
    return S_OK;
}























////////////////////////////////////////////////////////////////////////////////
//
// oem class factory
//
class IOemCF : public IClassFactory
{
public:
    // *** IUnknown methods ***
    
    STDMETHOD(QueryInterface) (THIS_ REFIID riid, LPVOID FAR* ppvObj);
    
    STDMETHOD_(ULONG,AddRef)  (THIS);
    
    // the __drv_at tag here tells prefast that once release 
    // is called, the memory should not be considered leaked
    __drv_at(this, __drv_freesMem(object)) 
    STDMETHOD_(ULONG,Release) (THIS);

    // *** IClassFactory methods ***
    STDMETHOD(CreateInstance) (THIS_
                               LPUNKNOWN pUnkOuter,
                               REFIID riid,
                               LPVOID FAR* ppvObject);
    STDMETHOD(LockServer)     (THIS_ BOOL bLock);


    // Constructor
    IOemCF();
    ~IOemCF();

protected:
    long    m_cRef;
};


///////////////////////////////////////////////////////////
//
// Class factory body
//
IOemCF::IOemCF() : m_cRef(1)
{
    VERBOSE("IOemCF::IOemCF() entered.\r\n");
}

IOemCF::~IOemCF()
{
    VERBOSE("IOemCF::~IOemCF() entered.\r\n");

    // If this instance of the object is being deleted, then the reference
    // count should be zero.
    assert(0 == m_cRef);
}

HRESULT __stdcall IOemCF::QueryInterface(const IID& iid, void** ppv)
{
    VERBOSE("IOemCF::QueryInterface entered.\r\n");

    if ((iid == IID_IUnknown) || (iid == IID_IClassFactory))
    {
        *ppv = static_cast<IOemCF*>(this);
    }
    else
    {
        WARNING("IOemCF::QueryInterface (interface not supported).\r\n");
        *ppv = NULL;
        return E_NOINTERFACE;
    }

    reinterpret_cast<IUnknown*>(*ppv)->AddRef();

    VERBOSE("IOemCF::QueryInterface leaving.\r\n");

    return S_OK;
}

ULONG __stdcall IOemCF::AddRef()
{
    VERBOSE("IOemCF::AddRef() called.\r\n");
    return InterlockedIncrement(&m_cRef);
}

__drv_at(this, __drv_freesMem(object)) 
ULONG __stdcall IOemCF::Release()
{
   VERBOSE("IOemCF::Release() called.\r\n");

   ASSERT( 0 != m_cRef);
   ULONG cRef = InterlockedDecrement(&m_cRef);
   if (0 == cRef)
   {
      delete this;

   }
   return cRef;
}

// IClassFactory implementation
HRESULT __stdcall IOemCF::CreateInstance(IUnknown* pUnknownOuter,
                                           const IID& iid,
                                           void** ppv)
{
    VERBOSE("Class factory:  Create component.\r\n");

    if (ppv == NULL)
    {
        return E_POINTER;
    }
    *ppv = NULL;

    // Cannot aggregate.
    if (pUnknownOuter != NULL)
    {
        WARNING("Class factory:  non-Null pUnknownOuter.\r\n");

        return CLASS_E_NOAGGREGATION;
    }

    // Create component.
    IOemPS* pOemCP = new IOemPS;
    if (pOemCP == NULL)
    {
        ERR("Class factory:  failed to allocate IOemPS.\r\n");

        return E_OUTOFMEMORY;
    }

    // Get the requested interface.
    HRESULT hr = pOemCP->QueryInterface(iid, ppv);

    // Release the IUnknown pointer.
    // (If QueryInterface failed, component will delete itself.)
    pOemCP->Release();
    return hr;
}

// LockServer
HRESULT __stdcall IOemCF::LockServer(BOOL bLock)
{
    VERBOSE("IOemCF::LockServer entered.\r\n");

    if (bLock)
    {
        InterlockedIncrement(&g_cServerLocks);
    }
    else
    {
        InterlockedDecrement(&g_cServerLocks);
    }

    VERBOSE("IOemCF::LockServer() leaving.\r\n");
    return S_OK;
}


//
// Registration functions
//

//
// Can DLL unload now?
//
STDAPI DllCanUnloadNow()
{
    //
    // To avoid leaving OEM DLL still in memory when Unidrv or Pscript drivers
    // are unloaded, Unidrv and Pscript driver ignore the return value of
    // DllCanUnloadNow of the OEM DLL, and always call FreeLibrary on the OEMDLL.
    //
    // If OEM DLL spins off a working thread that also uses the OEM DLL, the
    // thread needs to call LoadLibrary and FreeLibraryAndExitThread, otherwise
    // it may crash after Unidrv or Pscript calls FreeLibrary.
    //

    VERBOSE("DllCanUnloadNow entered.\r\n");

    if ((g_cComponents == 0) && (g_cServerLocks == 0))
    {
        return S_OK;
    }
    else
    {
        return S_FALSE;
    }
}

//
// Get class factory
//
STDAPI  DllGetClassObject(
    __in REFCLSID clsid, 
    __in REFIID iid, 
    __deref_out LPVOID* ppv)
{
    VERBOSE("DllGetClassObject:  Create class factory entered.\r\n");

    if (ppv == NULL)
    {
        return E_POINTER;
    }
    *ppv = NULL;

    // Can we create this component?
    if (clsid != CLSID_OEMRENDER)
    {
        WARNING("DllGetClassObject:  doesn't support clsid!\r\n");
        return CLASS_E_CLASSNOTAVAILABLE;
    }

    // Create class factory.
    IOemCF* pFontCF = new IOemCF;  // Reference count set to 1
                                         // in constructor
    if (pFontCF == NULL)
    {
        WARNING("DllGetClassObject:  memory allocation failed!\r\n");
        return E_OUTOFMEMORY;
    }

    // Get requested interface.
    HRESULT hr = pFontCF->QueryInterface(iid, ppv);
    pFontCF->Release();

    return hr;
}

