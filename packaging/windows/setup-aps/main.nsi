;; Copyright (C) 2009 - 2011 Ulteo SAS
;; http://www.ulteo.com
;; Author David LECHEVALIER <david@ulteo.com> 2011
;; Author Laurent CLOUET <laurent@ulteo.com> 2011
;; Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010, 2011
;; Author Thomas MOUTON <thomas@ulteo.com> 2009, 2010, 2011
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

!define PRODUCT_NAME "OVD Application Server"
!define PRODUCT_PUBLISHER "Ulteo"
!define PRODUCT_WEB_SITE "http://www.ulteo.com"
!define PRODUCT_FULL_NAME "${PRODUCT_PUBLISHER} ${PRODUCT_NAME}"

!define BASENAME "${PRODUCT_NAME}"
!define EXE_NAME "ulteo-ovd-slaveserver.exe"
!define CONFIG_FILE "$APPDATA\ulteo\ovd\slaveserver.conf"
!define SHORTCUT "${BASENAME}.lnk"

!define UNINSTALL_REGKEY "Software\Microsoft\Windows\CurrentVersion\Uninstall\${PRODUCT_FULL_NAME}"

;Include Modern UI
  !include "MUI.nsh"

  !define MUI_HEADERIMAGE
  !define MUI_HEADERIMAGE_BITMAP "media\header.bmp"
  !define MUI_HEADERIMAGE_UNBITMAP  "media\header.bmp"
  !define MUI_WELCOMEFINISHPAGE_BITMAP "media\startlogo.bmp"
  !define MUI_UNWELCOMEFINISHPAGE_BITMAP "media\startlogo.bmp"
  !define MUI_ICON "media\ulteo-package.ico"
  !define MUI_UNICON ${MUI_ICON}

  !define MUI_ABORTWARNING
  !define MUI_UNABORTWARNING

;General

  ;Name and file
  Name "${PRODUCT_FULL_NAME}"
  OutFile "${OUT_DIR}\${SETUP_NAME}.exe"

  BrandingText "Copyright (C) ${COPYRIGHT_YEAR} Ulteo SAS"
  ;Default installation folder
  InstallDir "$PROGRAMFILES\${PRODUCT_PUBLISHER}\${PRODUCT_NAME}"

  ;Get installation folder from registry if available
  InstallDirRegKey HKLM "Software\${PRODUCT_PUBLISHER}" "${PRODUCT_NAME}"

  VIAddVersionKey ProductName "${PRODUCT_NAME}"
  VIAddVersionKey CompanyName "${PRODUCT_PUBLISHER}"
  VIAddVersionKey ProductVersion "${PRODUCT_VERSION}"
  VIAddVersionKey FileVersion "${PRODUCT_VERSION}"
  VIAddVersionKey FileDescription "Ulteo OVD Application Server service"
  VIAddVersionKey LegalCopyright "Copyright (C) ${COPYRIGHT_YEAR} Ulteo SAS"
  VIProductVersion "${PRODUCT_VERSION}"

;--------------------------------
;Interface Settings

;--------------------------------
;Pages

  ; Installer parameters and pages order
  !define MUI_WELCOMEPAGE_TITLE_3LINES
  !define MUI_LICENSEPAGE_RADIOBUTTONS
  ;!define MUI_FINISHPAGE_LINK "Visit our web site"
  ;!define MUI_FINISHPAGE_LINK_LOCATION ${PRODUCT_WEB_SITE}
  ;!define MUI_FINISHPAGE_TEXT "${PRODUCT_FULL_NAME} has been installed on your computer.\n\nClick Finish to close this wizard.\n\n\nThe software is installed as a service and is automatically started at computer boot."

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

;Include String Functions
  !include String.nsh
  
;Include User Functions
  !include User.nsh

;Include Visual C++ redistribuable deployment Functions
  !include "VCredist.nsh"
  
;Include Service library
  !include servicelib.nsh

## First Dialog
Function InputBoxPageShow
  Var /GLOBAL sm_address

  ReadINIStr $sm_address ${CONFIG_FILE} "main" "session_manager"
  Push $sm_address
  Call Trim
  Pop $sm_address
  
  again:
    !insertmacro MUI_HEADER_TEXT "Configuration" "Give the Session Manager address."

    PassDialog::InitDialog /NOUNLOAD InputBox \
                           /HEADINGTEXT "Caution: give full name or ip address" \
                           /GROUPTEXT "Session Manager host/address" \
                           /BOX "Example: sm.ulteo.com" $sm_address 0
    PassDialog::Show
    Pop $0
  
    Pop $sm_address
    ${IF} $sm_address == ""
      MessageBox MB_OK|MB_TOPMOST|MB_ICONEXCLAMATION "Session Manager host/address required"
      Goto again
    ${ENDIF}
FunctionEnd

Function InputBoxPageLeave
  ;WriteRegStr HKLM "Software\${PRODUCT_PUBLISHER}\${PRODUCT_NAME}" "sm_address" $sm_address

FunctionEnd

!include WindowsVersion.nsh

Function .onInit
  !insertmacro setRegViewFromArch

  ; to install for all user
    SetShellVarContext all
FunctionEnd

Function un.onInit
  !insertmacro setRegViewFromArch
  
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

Section "pre" PreCmd
  Push "installed"
  Push "OVD"
  Push ""
  Call Service
  Pop $0
  ${If} $0 == "false"
    Goto no_service
  ${EndIf}
  
  DetailPrint "Stopping service"
  nsExec::execToStack 'sc stop OVD'
  
  Push "OVD"
  Push "stopped"
  Call WaitServiceStatus
  Pop $0
  
  no_service:

SectionEnd

Section "un.pre" UnPostCmd
  Push "installed"
  Push "OVD"
  Push ""
  Call un.Service
  Pop $0
  ${If} $0 == "false"
    Goto no_service
  ${EndIf}
  
  DetailPrint "Stopping service"
  nsExec::execToStack 'sc stop OVD'
  
  Push "OVD"
  Push "stopped"
  Call un.WaitServiceStatus
  Pop $0

  DetailPrint "Removing Service"
  nsExec::execToStack 'sc delete OVD'
  
  no_service:

  DetailPrint "Removing OVDAdmin user"
  UserMgr::DeleteAccount "OVDAdmin"
  
  DetailPrint "Remove PDF printer driver"
  nsExec::execToStack 'rundll32 printui.dll,PrintUIEntry /dd /m "Ulteo TS Printer Driver"'
  DeleteRegKey HKLM "SOFTWARE\GPL Ghostscript\8.71"

  DetailPrint "Remove PATH Environment variable"
  ${un.EnvVarUpdate} $0 "PATH" "D" "HKLM" "$INSTDIR\plus"

  Detailprint "Removing Configuration file"
  RMDir /r "$APPDATA\ulteo\ovd\spool"
  Delete "$APPDATA\ulteo\ovd\slaveserver.conf"
  RMDir  "$APPDATA\ulteo\ovd\log"
  RMDir  "$APPDATA\ulteo\ovd"
  RMDir  "$APPDATA\ulteo"
SectionEnd

  !include dist.nsh

Section "post" PostCmd
  SetOutPath "$APPDATA\ulteo\ovd"

  DetailPrint "Generating Config file"
  WriteINIStr "${CONFIG_FILE}" "main" "session_manager" "$sm_address"
  WriteINIStr "${CONFIG_FILE}" "main" "roles" "aps"
  WriteINIStr "${CONFIG_FILE}" "log" "level" "*"

  SetOutPath "$APPDATA\ulteo\ovd\log"
  SetOverwrite ifnewer
  SetOutPath "$APPDATA\ulteo\ovd\spool"
  SetOverwrite ifnewer
  SetOutPath "$APPDATA\ulteo\ovd"

  DetailPrint "Change PATH Environment variable"
  ${EnvVarUpdate} $0 "PATH" "A" "HKLM" "$INSTDIR\plus"

  Call .WindowsInstall

  !insertmacro VCRedistInstall
  
  Var /GLOBAL printerDir
  ClearErrors
  ReadEnvStr $0 "PROGRAMW6432"
  IfErrors x86 amd64

  x86:
    DetailPrint "Running on x86"
    StrCpy $printerDir "$INSTDIR\printer\i386"
    GOTO Done
  amd64:
    DetailPrint "Running on amd64"
    StrCpy $printerDir "$INSTDIR\printer\amd64"
    GOTO Done
  Done:
    WriteRegStr HKLM "SOFTWARE\GPL Ghostscript\8.71" "GS_DLL" "$printerDir\gsdll32.dll"
    WriteRegStr HKLM "SOFTWARE\GPL Ghostscript\8.71" "GS_LIB" "$printerDir\lib"
    SetOutPath "$printerDir"
    DetailPrint "Installing PDF printer driver"
    nsExec::execToStack 'rundll32 printui.dll,PrintUIEntry /ia /m "Ulteo TS Printer Driver" /f ulteodll.inf'
 
  DetailPrint "Check if the service is already installed"
  ClearErrors
  UserMgr::GetUserInfo "OVDAdmin" "EXISTS"
  Pop $0
  StrCmp $0 "OK" exist
  
  DetailPrint "Generating random password"
  Var /GLOBAL pass
  pwgen::GeneratePassword 20
  Pop $pass

  !insertmacro CreateUser "" "OVDAdmin" "$pass" "OVDAdmin" "OVDAdmin" "" 544
  UserMgr::SetUserInfo "OVDAdmin" "PASSWD_NEVER_EXPIRES" "YES"
  UserMgr::AddPrivilege "OVDAdmin" "SeServiceLogonRight" 
	
  nsExec::execToStack 'sc create OVD BinPath= "$INSTDIR\OVDWin32Service.exe" DisplayName= "Ulteo Open Virtual Desktop agent" obj= ".\OVDAdmin" password= "$pass" depend= EventLog/TermService/winmgmt start= auto'

  exist:
    DetailPrint "Starting service"
    nsExec::execToStack 'sc start OVD'
SectionEnd

Section "Shortcut Section" SecShortcut
  SetOutPath "$INSTDIR"
  CreateDirectory "$SMPROGRAMS\${PRODUCT_PUBLISHER}"
  WriteIniStr "$SMPROGRAMS\${PRODUCT_PUBLISHER}\Website.url" "InternetShortcut" "URL" "${PRODUCT_WEB_SITE}"
SectionEnd

Section "un.Shortcut Section" SecUnShortcut
  ;Delete "$SMPROGRAMS\${PRODUCT_PUBLISHER}\Website.url"
  ;RMDir  "$SMPROGRAMS\${PRODUCT_PUBLISHER}"
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
