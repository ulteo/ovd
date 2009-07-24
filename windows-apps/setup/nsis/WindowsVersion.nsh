;; Copyright (C) 2009 Ulteo SAS
;; http://www.ulteo.com
;; Author Thomas MOUTON <thomas@ulteo.com>
;;
;; This program is free software; you can redistribute it and/or 
;; modify it under the terms of the GNU General Public License
;; as published by the Free Software Foundation; version 2
;; of the License
;;
;; This program is distributed in the hope that it will be useful,
;; but WITHOUT ANY WARRANTY; without even the implied warranty of
;; MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
;; GNU General Public License for more details.
;;
;; You should have received a copy of the GNU General Public License
;; along with this program; if not, write to the Free Software
;; Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
;;
;;  WindowsVersion.nsh
;;    : Apply changes depending on Microsoft Windows version
;;
;;  Usage:
;;    .WindowsInstall for changes setup
;;    un.WindowsInstall for changes uninstall

 
 
!ifndef WINDOWSVERSION_FUNCTION
!define WINDOWSVERSION_FUNCTION

!include nsis\String.nsh

!macro WindowsVersionDetection un

  !ifndef ${un}WINVERSION_SET
    !define ${un}WINVERSION_SET

    Var /GLOBAL ${un}WinVersionNum
    Var /GLOBAL ${un}WinVersionLbl

    ReadRegStr $${un}WinVersionNum HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion" CurrentVersion

    StrCmp $${un}WinVersionNum '5.1' lbl_winnt_XP
    StrCmp $${un}WinVersionNum '5.2' lbl_winnt_2003
    StrCmp $${un}WinVersionNum '6.0' lbl_winnt_6.0

    Goto lbl_winDetect_done

    lbl_winnt_XP:
      StrCpy $${un}WinVersionLbl "XP"

      Goto lbl_winDetect_done

    lbl_winnt_2003:
      StrCpy $${un}WinVersionLbl "2003"

      Goto lbl_winDetect_done

    lbl_winnt_6.0:
      !insertmacro DifferenciateVistaAnd2008 "${un}"
      Pop $R0
      IntCmp 0 $R0 lbl_winnt_vista
      
      Goto lbl_winnt_2008

    lbl_winnt_vista:
      StrCpy $${un}WinVersionLbl "Vista"

      Goto lbl_winDetect_done

    lbl_winnt_2008:
      StrCpy $${un}WinVersionLbl "2008"

      Goto lbl_winDetect_done

    lbl_winDetect_done:
  
  !endif

!macroend

;Detecting the OS version between Vista and 2008
!macro DifferenciateVistaAnd2008 un

    Var /GLOBAL ${un}prodName
    Var /GLOBAL ${un}strSize

    ReadRegStr $${un}prodName HKLM "Software\Microsoft\Windows NT\CurrentVersion" ProductName

    Push $${un}prodName
    Push "2008"
    !insertmacro SubStr
    Pop $R0
    StrLen $${un}strSize $R0
    Push $${un}strSize

!macroend

;; changes setup
Function .WindowsInstall

  !ifndef WINVERSION_SET
    !insertmacro WindowsVersionDetection ""
  !endif

  StrCmp $WinVersionLbl "XP" lbl_XP
  StrCmp $WinVersionLbl "2008" lbl_2008
  
  Goto lbl_done

  lbl_XP:
    ;Change the default Shell for Windows XP
    DetailPrint "Change Default Shell"
    WriteRegStr HKLM "Software\Microsoft\Windows NT\CurrentVersion\WinLogon" "Shell" "seamlessrdpshell.exe"
    
    Goto lbl_done
 
  lbl_2008:
    ; If you apply local modification on the user environment variable "path", 
    ; this modification can't be applied in seamless mode.
    ; When you try to connect in seamless mode you end up getting a full-screen 
    ; display of the entire windows desktop.
    ; It's not possible to modify the path environment variable for 
    ; remote application users even if you use Group policy object.
    ; This solution helps also to keep the control on the explorer for administrative tasks.
    CopyFiles $INSTDIR\rdp\* $SYSDIR

    Goto lbl_done
 
  lbl_done:

FunctionEnd

;; changes uninstall
Function un.WindowsInstall

  !ifndef UNWINVERSION_SET
    !insertmacro WindowsVersionDetection "UN"
  !endif

  StrCmp $UNWinVersionLbl "XP" lbl_XP
  StrCmp $UNWinVersionLbl "2008" lbl_2008
  
  Goto lbl_done
 
  lbl_XP:
    ;Change the default Shell for Windows XP
    DetailPrint "Restore Default Shell"
    WriteRegStr HKLM "Software\Microsoft\Windows NT\CurrentVersion\WinLogon" "Shell" "explorer.exe"

    Goto lbl_done
 
  lbl_2008:
    Delete "$SYSDIR\seamlessrdpshell.exe"
    Delete "$SYSDIR\seamlessrdpshell.dll"
    Delete "$SYSDIR\vchannel.dll"

    Goto lbl_done
 
  lbl_done:

FunctionEnd

!endif
