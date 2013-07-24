/**
 * Copyright (C) 2009-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010, 2012
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

#include <png.h>
#include <stdio.h>
#include <windows.h>
#include <Shlwapi.h>

void png_my_error(png_structp, png_const_charp);
void png_my_warning(png_structp, png_const_charp);
BOOL hicon2pngfile(HICON, LPCTSTR);
BOOL isARGBPixels(LPBYTE, long int);


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
    BOOL ret;
	BOOL isWow64 = FALSE;

    if (argc != 3) {
        fprintf(stderr, "usage: %s exe_file[,icon_index] out_png_file\n", argv[0]);
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
        fprintf(stderr, "extract icon failed %d\n", (int)GetLastError());
        return 2;
    }

    ret = hicon2pngfile(ico, argv[2]);
    if (ret != TRUE) {
        fprintf(stderr, "save png failed %d\n", (int)GetLastError());
        return 3;
    }

    ret = DestroyIcon(ico);
    if (ret != TRUE) {
        fprintf(stderr, "Unable to destroy icon (%d) but PNG transformation succeed\n", (int)GetLastError());
        return 2;
    }

    return 0;
}


BOOL hicon2pngfile(HICON ico, LPCTSTR png_filename) {
    HDC hdcScr, hdcScr2;
    HDC hdcMem, hdcMem2;
    HBITMAP hbmMem, hbmMem2;
    BITMAP bm;
    ICONINFO info;

    BITMAPINFO bmi;
    BITMAPINFOHEADER bmih;
    LPBYTE pixels;
	BOOL argbColors = FALSE;
  
    png_structp png_ptr;
    png_infop info_ptr;
    int x, y;

    if (!GetIconInfo(ico, &info)) {
        fprintf(stderr, "GetIconInfo error\n");
        return FALSE;
    }

    if (!GetObject(info.hbmColor, sizeof(BITMAP), &bm)) {
        fprintf(stderr, "GetObject error\n");
        return FALSE;
    }

#ifdef DEBUG
    printf("X: %ld\n", bm.bmWidth);
    printf("Y: %ld\n", bm.bmHeight);
#endif

    hdcScr = GetDC(NULL);
    hbmMem = CreateCompatibleBitmap(hdcScr, bm.bmWidth, bm.bmHeight);
    hdcMem = CreateCompatibleDC(hdcScr);
    SelectObject(hdcMem, info.hbmColor);

    if (bm.bmBitsPixel == 32) {
        memset(&bmi, 0, sizeof(BITMAPINFO));
        bmih.biSize = sizeof(BITMAPINFOHEADER);
        bmih.biWidth  = bm.bmWidth;
        bmih.biHeight = bm.bmHeight;
        bmih.biBitCount = 32;
        bmih.biCompression = BI_RGB;
        bmih.biPlanes = 1;
        bmi.bmiHeader.biBitCount = 32;
        bmi.bmiHeader = bmih;

        pixels = (LPBYTE) GlobalAlloc(GMEM_FIXED, bmih.biWidth * bmih.biHeight * (bmih.biBitCount / 8));
        if (pixels == NULL) {
            fprintf(stderr, "GlobalAlloc failed\n");
            return FALSE;
        }

        GetDIBits(hdcMem, info.hbmColor, 0, (WORD) bmih.biHeight, pixels, &bmi, DIB_RGB_COLORS);
        
        argbColors = isARGBPixels(pixels, bmih.biWidth * bmih.biHeight);
    }
    
    if (argbColors == TRUE) {
#ifdef DEBUG
        printf("XP Icons\n");
#endif
    }
    else {
#ifdef DEBUG
        printf("Old icons\n");
#endif
        hdcScr2 = GetDC(NULL);
        hbmMem2 = CreateCompatibleBitmap(hdcScr2, bm.bmWidth, bm.bmHeight);
        hdcMem2 = CreateCompatibleDC(hdcScr2);
        SelectObject(hdcMem2, info.hbmMask);
    }
 
    png_ptr = png_create_write_struct(PNG_LIBPNG_VER_STRING, NULL, png_my_error, png_my_warning);
    if (png_ptr == NULL) {
        fprintf(stderr, "Unable to initialize PNG image\n");
        return FALSE;
    }
  
    info_ptr = png_create_info_struct(png_ptr);
    if (info_ptr == NULL) {
        png_destroy_write_struct(&png_ptr, NULL);
        fprintf(stderr, "Unable to allocate memory for PNG\n");
        return FALSE;
    }

    if (setjmp(png_ptr->jmpbuf)) {
        png_destroy_write_struct(&png_ptr, &info_ptr);
        fprintf(stderr, "PNG jump, an error has occured\n");
        return FALSE;
    }
  
    FILE* fp = fopen(png_filename, "wb");
    if (fp == NULL) {
        fprintf(stderr, "Unable to open file '%s'\n", png_filename);
        return FALSE;
    }

    png_init_io(png_ptr, fp);

    png_set_compression_level(png_ptr, 6);
  
    png_set_compression_mem_level(png_ptr, MAX_MEM_LEVEL);

    png_set_IHDR(png_ptr, info_ptr,
                 bm.bmWidth, bm.bmHeight, 
                 8, PNG_COLOR_TYPE_RGB_ALPHA,
                 PNG_INTERLACE_NONE, PNG_COMPRESSION_TYPE_DEFAULT,
                 PNG_FILTER_TYPE_DEFAULT);

    png_write_info(png_ptr, info_ptr);
  
    png_bytep row = (png_bytep) malloc(4* bm.bmWidth * sizeof(png_byte));
  
    for (y=0 ; y<bm.bmHeight ; y++) {
        for (x=0 ; x<bm.bmWidth ; x++) {
            char r,g,b,a;
	
            if (argbColors == TRUE) {
                int pos = (bm.bmHeight - y) * bm.bmWidth + x;
	  
                a = pixels[pos * 4 + 3];
                r = pixels[pos * 4 + 2];
                g = pixels[pos * 4 + 1];
                b = pixels[pos * 4 + 0];
            }
            else { 
                COLORREF pixel, pixel_alpha;
      
                pixel = GetPixel(hdcMem, x, y);
                pixel_alpha = GetPixel(hdcMem2, x, y);
      
                r = GetRValue(pixel);
                g = GetGValue(pixel);
                b = GetBValue(pixel);
                a = 255 - GetRValue(pixel_alpha);
            }
#ifdef DEBUG
            printf("Value(%d, %d): R%x, G%x, B%x, A%x\n", x, y, r, g, b, a);
#endif
          
            row[x*4+0] = r;
            row[x*4+1] = g;
            row[x*4+2] = b;
            row[x*4+3] = a;
        }
        png_write_row(png_ptr, row);
    }
  
    free(row);
  
    png_write_end(png_ptr, info_ptr);
    png_destroy_write_struct(&png_ptr, &info_ptr);
    fclose(fp);
  
    if (bm.bmBitsPixel == 32)
        GlobalFree(pixels);

    return TRUE;
}


BOOL isARGBPixels(LPBYTE pixels, long int size) {
    int i;
    for (i=0; i<size; i++) {
        char a = pixels[i * 4 + 3];
        if (a!=0)
            return TRUE;
    }
    
    return FALSE;
}


void png_my_error(png_structp png_ptr, png_const_charp message) {
    fprintf(stderr, "ERROR(libpng): %s - %s\n", message,
            (char *)png_get_error_ptr(png_ptr));
    longjmp(png_ptr->jmpbuf, 1);
}


void png_my_warning(png_structp png_ptr, png_const_charp message) {
    fprintf(stderr, "WARNING(libpng): %s - %s\n", message,
            (char *)png_get_error_ptr(png_ptr));
}
