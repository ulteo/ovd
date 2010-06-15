;; Copyright (C) 2009 - 2010 Ulteo SAS
;; http://www.ulteo.com
;; Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010
;; Author Thomas MOUTON <thomas@ulteo.com> 2009, 2010
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

!define PRODUCT_NAME "Open Virtual Desktop"
!define PRODUCT_LONGVERSION "${PRODUCT_VERSION}.0.0"
!define PRODUCT_PUBLISHER "Ulteo"
!define PRODUCT_WEB_SITE "http://www.ulteo.com"
!define PRODUCT_FULL_NAME "${PRODUCT_PUBLISHER} ${PRODUCT_NAME}"

!define BASENAME "${PRODUCT_NAME}"
!define EXE_NAME "ovdSlaveServer.exe"
!define SHORTCUT "${BASENAME}.lnk"

!define UNINSTALL_REGKEY "Software\Microsoft\Windows\CurrentVersion\Uninstall\Ulteo"

;Include Modern UI
  !include "MUI.nsh"

  !define MUI_HEADERIMAGE
  !define MUI_HEADERIMAGE_BITMAP "media\header.bmp"
  !define MUI_HEADERIMAGE_UNBITMAP  "media\header.bmp"
  !define MUI_WELCOMEFINISHPAGE_BITMAP "media\startlogo.bmp"
  !define MUI_UNWELCOMEFINISHPAGE_BITMAP "media\startlogo.bmp"
  !define MUI_ICON "media\ulteo.ico"
  !define MUI_UNICON "media\ulteo.ico"

  !define MUI_ABORTWARNING
  !define MUI_UNABORTWARNING

;General

  ;Name and file
  Name "${PRODUCT_NAME}"
  OutFile "${OUT_DIR}\${SETUP_NAME}.exe"

  BrandingText "Copyright Ulteo"
  ;Default installation folder
  InstallDir "$PROGRAMFILES\${PRODUCT_PUBLISHER}\${PRODUCT_NAME}"

  ;Get installation folder from registry if available
  InstallDirRegKey HKLM "Software\${PRODUCT_PUBLISHER}" "${PRODUCT_NAME}"

  VIAddVersionKey ProductName "${PRODUCT_NAME}"
  VIAddVersionKey CompanyName "${PRODUCT_PUBLISHER}"
  VIAddVersionKey ProductVersion "${PRODUCT_VERSION}"
  VIAddVersionKey FileVersion "${PRODUCT_VERSION}"
  VIAddVersionKey FileDescription "An agent to plug a Windows TS on an Ulteo OVD architecture"
  VIAddVersionKey LegalCopyright "Copyright @ 2010 ${PRODUCT_PUBLISHER}"
  VIProductVersion "${PRODUCT_LONGVERSION}"

;--------------------------------
;Interface Settings

;--------------------------------
;Pages

  ; Installer parameters and pages order
  !define MUI_WELCOMEPAGE_TITLE_3LINES
  !define MUI_LICENSEPAGE_RADIOBUTTONS
  !define MUI_FINISHPAGE_LINK "Visit our web site"
  !define MUI_FINISHPAGE_LINK_LOCATION ${PRODUCT_WEB_SITE}

  !insertmacro MUI_PAGE_WELCOME
  !insertmacro MUI_PAGE_LICENSE "media/LICENCE.txt"
  !insertmacro MUI_PAGE_DIRECTORY
  Page Custom InputBoxPageShow InputBoxPageLeave

  !insertmacro MUI_PAGE_INSTFILES
  !insertmacro MUI_PAGE_FINISH

  ; Uninstaller Parameters and pages order
  !define MUI_WELCOMEPAGE_TITLE_3LINES
  
  !insertmacro MUI_UNPAGE_WELCOME
  !insertmacro MUI_UNPAGE_CONFIRM
  !insertmacro MUI_UNPAGE_INSTFILES
  !insertmacro MUI_UNPAGE_FINISH

  !insertmacro MUI_LANGUAGE "English"
  ; Env var managment
  ; http://nsis.sourceforge.net/Environmental_Variables:_append%2C_prepend%2C_and_remove_entries
  !include EnvVarUpdate.nsh


;--------------------------------
;Reserve Files
  
  ;If you are using solid compression, files that are required before
  ;the actual installation should be stored first in the data block,
  ;because this will make your installer start faster.
  
  !insertmacro MUI_RESERVEFILE_LANGDLL

## First Dialog
Function InputBoxPageShow
  ReadRegStr $R1 HKLM "Software\${PRODUCT_PUBLISHER}\${PRODUCT_NAME}" "sm_address"

  ${IF} $R0 == ""
    StrCpy $R0 "sm.ulteo.com"
  ${ENDIF}

 !insertmacro MUI_HEADER_TEXT "Configuration" "Give the Session Manager address."

 PassDialog::InitDialog /NOUNLOAD InputBox \
            /HEADINGTEXT "Caution: give full name or ip address" \
            /BOX "Session Manager host/address:" $R0 0
 PassDialog::Show
FunctionEnd

Function InputBoxPageLeave
  Var /GLOBAL sm_address

  Pop $sm_address

  WriteRegStr HKLM "Software\${PRODUCT_PUBLISHER}\${PRODUCT_NAME}" "sm_address" $sm_address

FunctionEnd

!include WindowsVersion.nsh

Function .onInit
  ; to install for all user
    SetShellVarContext all
FunctionEnd

Function un.onInit
  ; to uninstall for all user
    SetShellVarContext all
FunctionEnd

Section "Main Section" SecMain
  SetOutPath "$INSTDIR"

  ;Store installation folder
  WriteRegStr HKLM "Software\${PRODUCT_PUBLISHER}" "${PRODUCT_NAME}" $INSTDIR

  ;Create uninstaller
  WriteUninstaller "$INSTDIR\Uninstall.exe"

  WriteRegStr HKLM "${UNINSTALL_REGKEY}" "Comments"        "${PRODUCT_FULL_NAME}"
  WriteRegStr HKLM "${UNINSTALL_REGKEY}" "DisplayIcon"     "$INSTDIR\${EXE_NAME},0"
  WriteRegStr HKLM "${UNINSTALL_REGKEY}" "DisplayName"     "${PRODUCT_FULL_NAME} (${PRODUCT_VERSION})"
  WriteRegStr HKLM "${UNINSTALL_REGKEY}" "DisplayVersion"  "${PRODUCT_VERSION}"
  WriteRegStr HKLM "${UNINSTALL_REGKEY}" "InstallLocation" "$INSTDIR"
  WriteRegStr HKLM "${UNINSTALL_REGKEY}" "Publisher"       "${PRODUCT_PUBLISHER}"
  WriteRegStr HKLM "${UNINSTALL_REGKEY}" "UninstallString" '"$INSTDIR\Uninstall.exe"' 
  WriteRegStr HKLM "${UNINSTALL_REGKEY}" "URLInfoUbout"    "${PRODUCT_WEB_SITE}"
  WriteRegDWORD HKLM "${UNINSTALL_REGKEY}" "NoModify" "1"
  WriteRegDWORD HKLM "${UNINSTALL_REGKEY}" "NoRepair" "1"

;  SetOutPath "$APPDATA\${PRODUCT_PUBLISHER}\${PRODUCT_NAME}"
;  SetOverwrite ifnewer
SectionEnd

Section "un.pre" UnPostCmd
  DetailPrint "Stopping service"
  nsExec::execToStack 'sc stop OVD'

  DetailPrint "Removing Service"
  nsExec::execToStack 'sc delete OVD'

  DetailPrint "Remove PATH Environment variable"
  ${un.EnvVarUpdate} $0 "PATH" "D" "HKLM" "$INSTDIR\plus"

  Detailprint "Removing Configuration file"
  Delete "$APPDATA\ovd\ovd-slaveserver.conf"
  RMDir  "$APPDATA\ovd\log"
  RMDir  "$APPDATA\ovd"
SectionEnd

  !include dist.nsh

Section "post" PostCmd
  SetOutPath "$APPDATA\ovd"

  DetailPrint "Generating Config file"
  FileOpen $4 "$APPDATA\ovd\ovd-slaveserver.conf" w
  FileWrite $4 "session_manager = $sm_address"
  FileWrite $4 "$\r$\n" 
  FileWrite $4 "ROLES = aps"
  FileWrite $4 "$\r$\n" 
  FileWrite $4 'LOG_LEVEL = *'
  FileWrite $4 "$\r$\n"
  FileClose $4

  SetOutPath "$APPDATA\ovd\log"
  SetOverwrite ifnewer
  SetOutPath "$APPDATA\ovd"

  DetailPrint "Change PATH Environment variable"
  ${EnvVarUpdate} $0 "PATH" "A" "HKLM" "$INSTDIR\plus"

  Call .WindowsInstall

  DetailPrint "Creating Service"
  nsExec::execToStack 'sc create OVD BinPath= "$INSTDIR\OVDWin32Service.exe" DisplayName= "Ulteo Open Virtual Desktop agent" depend= EventLog/winmgmt start= auto'

  DetailPrint "Launch service"
  nsExec::execToStack 'sc start OVD'
SectionEnd

Section "Shortcut Section" SecShortcut
  SetOutPath "$INSTDIR"
  CreateDirectory "$SMPROGRAMS\${PRODUCT_PUBLISHER}"
  WriteIniStr "$SMPROGRAMS\${PRODUCT_PUBLISHER}\Website.url" "InternetShortcut" "URL" "${PRODUCT_WEB_SITE}"

  CreateShortCut "$SMPROGRAMS\${PRODUCT_PUBLISHER}\Uninstall.lnk" "$INSTDIR\Uninstall.exe" 
SectionEnd

Section "un.Shortcut Section" SecUnShortcut
  Delete "$SMPROGRAMS\${PRODUCT_PUBLISHER}\Uninstall.lnk"
  Delete "$SMPROGRAMS\${PRODUCT_PUBLISHER}\Website.url"
  RMDir  "$SMPROGRAMS\${PRODUCT_PUBLISHER}"
SectionEnd

Section "Uninstall"
  Call un.WindowsInstall

  RMDir /r "$INSTDIR"
  RMDir /r "$APPDATA\${PRODUCT_PUBLISHER}\${PRODUCT_NAME}"
  RMDir "$APPDATA\${PRODUCT_PUBLISHER}"

  Delete "$INSTDIR\Uninstall.exe"
  DeleteRegKey HKLM "${UNINSTALL_REGKEY}"

  DeleteRegValue HKLM "Software\${PRODUCT_PUBLISHER}" "${PRODUCT_NAME}"
  DeleteRegKey /ifempty HKLM "Software\${PRODUCT_PUBLISHER}"
SectionEnd
