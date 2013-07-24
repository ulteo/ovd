/**
 * Copyright (C) 2009-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009, 2012
 * based on code http://www.developpez.net/forums/d469482/general-developpement/programmation-windows/taille-resolution-screenshot/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


#include <windows.h>
#include <Shlwapi.h>
#include <stdio.h>

BOOL hicon2bmpfile(LPCTSTR pszFileName, HICON ico) {
  HDC              hdcMem;
  HDC              hdcScr;
  HBITMAP          hbmMem;
  HBITMAP          hbmOld;  
  HANDLE           hFile;
  BITMAPINFO       bmi;
  BITMAPFILEHEADER bfh;  
  BITMAPINFOHEADER bmih;
  LPBYTE           pPixels;
  DWORD            dwTmp;
  UINT             nScrX; //GetSystemMetrics(SM_CXSCREEN);
  UINT             nScrY; //GetSystemMetrics(SM_CYSCREEN);  

  ICONINFO info;
  BITMAP gg;
  
 
  if (!GetIconInfo(ico, &info)) {
    puts("1");
    return 1;
  }

  if (!GetObject(info.hbmMask, sizeof(BITMAP), &gg)) {
    puts("2");
    return 2;
  }

  nScrX = gg.bmWidth;
  nScrY = gg.bmHeight;

  // printf("X: %ld\n", gg.bmWidth);
  // printf("Y: %ld\n", gg.bmHeight);

  hdcScr = GetDC(NULL);
  hbmMem = CreateCompatibleBitmap(hdcScr, nScrX, nScrY);
  hdcMem = CreateCompatibleDC(hdcScr);

  hbmOld = (HBITMAP) SelectObject(hdcMem, hbmMem);

  // Set white background
  if (! Rectangle(hdcMem, -1, -1, nScrX+1, nScrY+1)) {
    puts("draw rect failed");
    return 3;
  }

  // Draw the icon in the DC
  DrawIcon(hdcMem, 0, 0, ico); 

  bmih.biSize          = sizeof(BITMAPINFOHEADER);
  bmih.biWidth         = nScrX;  // 512;
  bmih.biHeight        = nScrY;  // 384;
  bmih.biBitCount      = GetDeviceCaps(hdcMem, BITSPIXEL);
  bmih.biCompression   = BI_RGB;
  bmih.biPlanes        = 1;  
  bmih.biSizeImage     = 0;
  bmih.biXPelsPerMeter = 0;
  bmih.biYPelsPerMeter = 0;
  bmih.biClrUsed       = 0;
  bmih.biClrImportant  = 0;
  
  bmi.bmiHeader        = bmih;

  if(!(pPixels = (LPBYTE) GlobalAlloc(GMEM_FIXED, bmih.biWidth * bmih.biHeight * (bmih.biBitCount / 8))))
    goto error;                                  
                                 
  if(!GetDIBits(hdcMem, hbmMem, 0, (WORD) bmih.biHeight, pPixels, &bmi, DIB_RGB_COLORS))
    goto error;                 
  
  bfh.bfType      = 0x4d42;
  bfh.bfReserved1 = 0;
  bfh.bfReserved2 = 0; 
  bfh.bfOffBits   = (DWORD) sizeof(BITMAPFILEHEADER) + sizeof(BITMAPINFOHEADER);   
  bfh.bfSize      = (DWORD) sizeof(BITMAPFILEHEADER) + sizeof(BITMAPINFOHEADER) + 
    bmih.biWidth * bmih.biHeight * (bmih.biBitCount / 8);
                            
  hFile = CreateFile(pszFileName, GENERIC_WRITE, 0, NULL, CREATE_ALWAYS, FILE_ATTRIBUTE_NORMAL, NULL);

  if(hFile == INVALID_HANDLE_VALUE) 
    goto error;                            
  
  if(!WriteFile(hFile, (LPVOID) &bfh, sizeof(BITMAPFILEHEADER), (LPDWORD) &dwTmp, NULL))
    goto error;
    
  if(!WriteFile(hFile, (LPVOID) &bmih, sizeof(BITMAPINFOHEADER), (LPDWORD) &dwTmp, NULL))
    goto error;
    
  if(!WriteFile(hFile, (LPVOID) pPixels, bmih.biWidth * bmih.biHeight * (bmih.biBitCount / 8), (LPDWORD) &dwTmp, NULL))
    goto error;

  GlobalFree(pPixels);  
  CloseHandle(hFile);
  SelectObject(hdcMem, hbmOld);
  ReleaseDC(NULL, hdcScr);
  DeleteDC(hdcMem);
  return 0;
  
 error:
  if(pPixels) 
    GlobalFree(pPixels);  
  if(hFile) { 
    CloseHandle(hFile);
    DeleteFile(pszFileName);
  }
  SelectObject(hdcMem, hbmOld);
  ReleaseDC(NULL, hdcScr);
  DeleteDC(hdcMem); 
  return -1; 
}

typedef BOOL (WINAPI *LPFN_ISWOW64PROCESS) (HANDLE, PBOOL);
typedef BOOL (WINAPI *LPFN_Wow64DisableWow64FsRedirection) (PVOID);

LPFN_ISWOW64PROCESS fnIsWow64Process;

BOOL IsWow64()
{
    BOOL bIsWow64 = FALSE;

    //IsWow64Process is not available on all supported versions of Windows.
    //Use GetModuleHandle to get a handle to the DLL that contains the function
    //and GetProcAddress to get a pointer to the function if available.

    fnIsWow64Process = (LPFN_ISWOW64PROCESS) GetProcAddress(
        GetModuleHandle(TEXT("kernel32")),"IsWow64Process");

    if(NULL != fnIsWow64Process)
    {
        if (!fnIsWow64Process(GetCurrentProcess(),&bIsWow64))
        {
            //handle error
        }
    }
    return bIsWow64;
}

int main(int argc, char* argv[]){
  HICON ico;
  TCHAR iconPath[MAX_PATH];
  UINT iconIndex = 0;
  int r;

  if (argc != 3) {
    printf("usage: %s exe_file[,icon_index] out_bmp_file\n", argv[0]);
    return 1;
  }

    if (IsWow64() == TRUE) {
		LPFN_Wow64DisableWow64FsRedirection func = (LPFN_Wow64DisableWow64FsRedirection) GetProcAddress(GetModuleHandle(TEXT("kernel32")),"Wow64DisableWow64FsRedirection");
	    if(NULL != func) {
		    DWORD s;
			func(&s);
		}
    }

  strncpy(iconPath, argv[1], MAX_PATH);
  iconIndex = PathParseIconLocation(iconPath);

  ico = ExtractIcon(NULL, iconPath, iconIndex);
  if (ico == NULL){
    printf("extract icon failed %d\n", (int)GetLastError());
    return 2;
  }

  r = hicon2bmpfile(argv[2], ico);
  if (r!=0) {
    printf("save bmp failed %d\n", (int)GetLastError());
    return 2;
  }

  return 0;
}

