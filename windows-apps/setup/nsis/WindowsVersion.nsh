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

!macro WindowsVersionDetection un

  !ifndef ${un}WINVERSION_SET
    !define ${un}WINVERSION_SET

    Var /GLOBAL ${un}WinVersion

    ReadRegStr $${un}WinVersion HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion" CurrentVersion
  !endif

!macroend

;; changes setup
Function .WindowsInstall

  !ifndef WINVERSION_SET
    !insertmacro WindowsVersionDetection ""
  !endif

  StrCmp $WinVersion '5.1' lbl_winnt_XP
  StrCmp $WinVersion '5.2' lbl_winnt_2003
  StrCmp $WinVersion '6.0' lbl_winnt_vista
  
  Goto lbl_done

  lbl_winnt_XP:
    ;Change the default Shell for Windows XP
    DetailPrint "Change Default Shell"
    WriteRegStr HKLM "Software\Microsoft\Windows NT\CurrentVersion\WinLogon" "Shell" "seamlessrdpshell.exe"

    Goto lbl_done
 
  lbl_winnt_2003:
    Goto lbl_done
 
  lbl_winnt_vista:
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

  StrCmp $UNWinVersion '5.1' lbl_winnt_XP
  StrCmp $UNWinVersion '5.2' lbl_winnt_2003
  StrCmp $UNWinVersion '6.0' lbl_winnt_6.0
  
  Goto lbl_done
 
  lbl_winnt_6.0:
    ;Detecting the OS version between Vista and 2008
    Var /GLOBAL prodName
    Var /GLOBAL strSize

    ReadRegStr $prodName HKLM "Software\Microsoft\Windows NT\CurrentVersion" ProductName

    Push $prodName
    Push "2008"
    Call StrStr
    Pop $R0

    StrLen $strSize $R0
    IntCmp 0 $sizeStr lbl_winnt_vista
    Goto lbl_winnt_2008

  lbl_winnt_XP:
    ;Change the default Shell for Windows XP
    DetailPrint "Restore Default Shell"
    WriteRegStr HKLM "Software\Microsoft\Windows NT\CurrentVersion\WinLogon" "Shell" "explorer.exe"

    Goto lbl_done
 
  lbl_winnt_2003:
    Goto lbl_done
 
  lbl_winnt_vista:
    Goto lbl_done

  lbl_winnt_2008:
    Delete "$SYSDIR\seamlessrdpshell.exe"
    Delete "$SYSDIR\seamlessrdpshell.dll"
    Delete "$SYSDIR\vchannel.dll"

    Goto lbl_done
 
  lbl_done:

FunctionEnd

!endif