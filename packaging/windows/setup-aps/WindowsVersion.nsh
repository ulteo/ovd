;; Copyright (C) 2009-2011 Ulteo SAS
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
!include VersionCompare.nsh

!macro setRegViewFromArch
   ; Set the registry view.
   ; On x64 systems, the registry view is in HKLM\SOFTWARE\WOW64 for x86 programs
   ClearErrors
   ReadEnvStr $0 "PROGRAMW6432"
   IfErrors x86 amd64

   x86:
      SetRegView 32
      goto end
   amd64:
      SetRegView 64
   
   end:
!macroend

Function .WindowsInstall
   Var /GLOBAL WinVersionNum
   ReadRegStr $WinVersionNum HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion" CurrentVersion

   ${VersionCompare} $WinVersionNum "6.0" $1
   IntCmp $1 2 done remoteapps done

   remoteapps:
      CopyFiles "$INSTDIR\wrapper\*.exe" "$SYSDIR"

      ; If you apply local modification on the user environment variable "path", 
      ; this modification can't be applied in seamless mode.
      ; When you try to connect in seamless mode you end up getting a full-screen 
      ; display of the entire windows desktop.
      ; It's not possible to modify the path environment variable for 
      ; remote application users even if you use Group policy object.
      ; This solution helps also to keep the control on the explorer for administrative tasks.

      !define REG_DESKTOP "Software\Microsoft\Windows NT\CurrentVersion\Terminal Server\TsAppAllowList\Applications\OvdDesktop"
      WriteRegDWORD HKLM "${REG_DESKTOP}" "CommandLineSetting" "1"
      WriteRegDWORD HKLM "${REG_DESKTOP}" "IconIndex" "0"
      WriteRegStr HKLM "${REG_DESKTOP}" "IconPath" "$SYSDIR\OvdDesktop.exe"
      WriteRegStr HKLM "${REG_DESKTOP}" "Name" "OvdDesktop.exe"
      WriteRegStr HKLM "${REG_DESKTOP}" "Path" "$SYSDIR\OvdDesktop.exe"
      WriteRegStr HKLM "${REG_DESKTOP}" "RequiredCommandLine" ""
      WriteRegStr HKLM "${REG_DESKTOP}" "ShortPath" ""
      WriteRegDWORD HKLM "${REG_DESKTOP}" "ShowInTSWA" "0"
      WriteRegStr HKLM "${REG_DESKTOP}" "VPath" ""

      !define REG_REMOTEAPP "Software\Microsoft\Windows NT\CurrentVersion\Terminal Server\TsAppAllowList\Applications\OvdRemoteApps"
      WriteRegDWORD HKLM "${REG_REMOTEAPP}" "CommandLineSetting" "1"
      WriteRegDWORD HKLM "${REG_REMOTEAPP}" "IconIndex" "0"
      WriteRegStr HKLM "${REG_REMOTEAPP}" "IconPath" "$SYSDIR\OvdRemoteApps.exe"
      WriteRegStr HKLM "${REG_REMOTEAPP}" "Name" "OvdRemoteApps.exe"
      WriteRegStr HKLM "${REG_REMOTEAPP}" "Path" "$SYSDIR\OvdRemoteApps.exe"
      WriteRegStr HKLM "${REG_REMOTEAPP}" "RequiredCommandLine" ""
      WriteRegStr HKLM "${REG_REMOTEAPP}" "ShortPath" ""
      WriteRegDWORD HKLM "${REG_REMOTEAPP}" "ShowInTSWA" "0"
      WriteRegStr HKLM "${REG_REMOTEAPP}" "VPath" ""
   done:
      SetRebootFlag true
FunctionEnd


Function un.WindowsInstall
   Var /GLOBAL unWinVersionNum
   ReadRegStr $unWinVersionNum HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion" CurrentVersion

   ${un.VersionCompare} $unWinVersionNum "6.0" $1
   IntCmp $1 2 done remoteapps done

   remoteapps:
      Delete "$SYSDIR\OvdDesktop.exe"
      Delete "$SYSDIR\OvdRemoteApps.dll"
  done:
      SetRebootFlag true
FunctionEnd

!endif
