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

!include String.nsh

!macro WindowsVersionDetection un

  !ifndef ${un}WINVERSION_SET
    !define ${un}WINVERSION_SET

    Var /GLOBAL ${un}WinVersionNum
    Var /GLOBAL ${un}WinVersionLbl

    ReadRegStr $${un}WinVersionNum HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion" CurrentVersion

    ${Switch} $${un}WinVersionNum
      ;Windows XP
      ${Case} '5.1'
        StrCpy $${un}WinVersionLbl "XP"
        ${Break}

      ;Windows 2003
      ${Case} '5.2'
        StrCpy $${un}WinVersionLbl "2003"
        ${Break}

      ${Case} 6.0
        !insertmacro DifferenciateVistaAnd2008 "${un}"
        Pop $R0
        ${If} $R0 == '0'
          ;Windows Vista
          StrCpy $${un}WinVersionLbl "Vista"
        ${Else}
          ;Windows 2008
          StrCpy $${un}WinVersionLbl "2008"
        ${EndIf}
        ${Break}

      ${Default}
        ${Break}
    ${EndSwitch}

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

  SetRebootFlag true

  !ifndef WINVERSION_SET
    !insertmacro WindowsVersionDetection ""
  !endif

  ${Switch} $WinVersionLbl
    ${Case} "XP"
      ;Change the default Shell for Windows XP
      DetailPrint "Change Default Shell"
      WriteRegStr HKLM "Software\Microsoft\Windows NT\CurrentVersion\WinLogon" "Shell" "seamlessrdpshell.exe"
      ${Break}

    ${Case} "2008"
      ; If you apply local modification on the user environment variable "path", 
      ; this modification can't be applied in seamless mode.
      ; When you try to connect in seamless mode you end up getting a full-screen 
      ; display of the entire windows desktop.
      ; It's not possible to modify the path environment variable for 
      ; remote application users even if you use Group policy object.
      ; This solution helps also to keep the control on the explorer for administrative tasks.
      !define REG_REMOTEAPP "Software\Microsoft\Windows NT\CurrentVersion\Terminal Server\TsAppAllowList\Applications\seamlessrdpshell"

      CopyFiles $INSTDIR\rdp\* $SYSDIR

      WriteRegDWORD HKLM "${REG_REMOTEAPP}" "CommandLineSetting" "1"
      WriteRegDWORD HKLM "${REG_REMOTEAPP}" "IconIndex" "0"
      WriteRegStr HKLM "${REG_REMOTEAPP}" "IconPath" "$SYSDIR\seamlessrdpshell.exe"
      WriteRegStr HKLM "${REG_REMOTEAPP}" "Name" "seamlessrdpshell.exe"
      WriteRegStr HKLM "${REG_REMOTEAPP}" "Path" "$SYSDIR\seamlessrdpshell.exe"
      WriteRegStr HKLM "${REG_REMOTEAPP}" "RequiredCommandLine" ""
      WriteRegStr HKLM "${REG_REMOTEAPP}" "ShortPath" ""
      WriteRegDWORD HKLM "${REG_REMOTEAPP}" "ShowInTSWA" "0"
      WriteRegStr HKLM "${REG_REMOTEAPP}" "VPath" ""

      ${Break}

    ${Default}
      ${Break}
  ${EndSwitch}

FunctionEnd

;; changes uninstall
Function un.WindowsInstall

  SetRebootFlag true

  !ifndef UNWINVERSION_SET
    !insertmacro WindowsVersionDetection "UN"
  !endif

  ${Switch} $UNWinVersionLbl
    ${Case} "XP"
      ;Change the default Shell for Windows XP
      DetailPrint "Restore Default Shell"
      WriteRegStr HKLM "Software\Microsoft\Windows NT\CurrentVersion\WinLogon" "Shell" "explorer.exe"
      ${Break}

    ${Case} "2008"
      Delete "$SYSDIR\seamlessrdpshell.exe"
      Delete "$SYSDIR\seamlessrdpshell.dll"
      Delete "$SYSDIR\vchannel.dll"
      ${Break}

    ${Default}
      ${Break}
  ${EndSwitch}

FunctionEnd

!endif
