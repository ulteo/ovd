;; Copyright (C) 2011-2013 Ulteo SAS
;; http://www.ulteo.com
;; Author Julien LANGLOIS <julien@ulteo.com> 2011, 2013
;; Author David LECHEVALIER <david@ulteo.com> 2012
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

!define PRODUCT_NAME "Ulteo WebDAV FS driver"
!define PRODUCT_PUBLISHER "Ulteo"
!define PRODUCT_WEB_SITE "http://www.ulteo.com"
!define PRODUCT_FULL_NAME "${PRODUCT_PUBLISHER} ${PRODUCT_NAME}"

!define BASENAME "${PRODUCT_NAME}"
!define EXE_NAME "davfs.exe"
!define UNINSTALL_SHORTCUT "Uninstall - ${PRODUCT_NAME}.lnk"

!define UNINSTALL_REGKEY "Software\Microsoft\Windows\CurrentVersion\Uninstall\Ulteo\ovd-application-server"

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
  VIAddVersionKey FileVersion "${FILE_VERSION}"
  VIAddVersionKey FileDescription "${PRODUCT_NAME}"
  VIAddVersionKey LegalCopyright "Copyright (C) ${COPYRIGHT_YEAR} Ulteo SAS"
  VIProductVersion "${FILE_VERSION}"

;--------------------------------
;Interface Settings

;--------------------------------
;Pages

  ; Installer parameters and pages order
  !define MUI_WELCOMEPAGE_TITLE_3LINES
  !define MUI_LICENSEPAGE_RADIOBUTTONS
  ;!define MUI_FINISHPAGE_LINK "Visit our web site"
  ;!define MUI_FINISHPAGE_LINK_LOCATION ${PRODUCT_WEB_SITE}
  ;!define MUI_FINISHPAGE_TEXT "${PRODUCT_FULL_NAME} has been installed on your computer.\n\nClick Finish to close this wizard.\n\n\n"

  !insertmacro MUI_PAGE_WELCOME
  !insertmacro MUI_PAGE_LICENSE "media/LICENCE.txt"
  !insertmacro MUI_PAGE_DIRECTORY
  !insertmacro MUI_PAGE_INSTFILES
  !insertmacro MUI_PAGE_FINISH

  ; Uninstaller Parameters and pages order
  !define MUI_WELCOMEPAGE_TITLE_3LINES
  
  !insertmacro MUI_UNPAGE_WELCOME
  !insertmacro MUI_UNPAGE_CONFIRM
  !insertmacro MUI_UNPAGE_INSTFILES
  !insertmacro MUI_UNPAGE_FINISH

  !insertmacro MUI_LANGUAGE "English"

;--------------------------------
;Reserve Files
  
  ;If you are using solid compression, files that are required before
  ;the actual installation should be stored first in the data block,
  ;because this will make your installer start faster.
  
  !insertmacro MUI_RESERVEFILE_LANGDLL

Function .onInit
  ; to install for all user
  ; SetShellVarContext all
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
SectionEnd

Section "pre" PreCmd
  ;DetailPrint "Stopping service"
  ;nsExec::execToStack 'sc stop dokan'
SectionEnd

Section "un.pre" UnPostCmd
  ;DetailPrint "Stopping service"
  ;nsExec::execToStack 'sc stop dokan'

  DetailPrint "Removing Network Provider"
  nsExec::execToStack '$INSTDIR\dokanctl /r n'
  
  DetailPrint "Removing Driver"
  nsExec::execToStack '$INSTDIR\dokanctl /r a'
  
  Delete "$SYSDIR\drivers\dokan.sys"
  Delete "$SYSDIR\davfs.exe"
  Delete "$SYSDIR\dokan.dll"
  Delete "$SYSDIR\dokannp.dll" 
SectionEnd

  !include dist.nsh

Section "post" PostCmd
  Var /GLOBAL arch
  Var /GLOBAL sysdir32
  ClearErrors
  ReadEnvStr $0 "PROGRAMW6432"
  IfErrors x86 amd64

  x86:
    DetailPrint "Running on x86"
    StrCpy $arch "x86"
    StrCpy $sysdir32 "$SYSDIR"
    GOTO Done
  amd64:
    StrCpy $arch "amd64"
    System::Call "kernel32::GetSystemWow64Directory(t .r0, i ${NSIS_MAX_STRLEN})"
    StrCpy $sysdir32 "$0"
    GOTO Done
  Done:
    DetailPrint "Running on $arch"

  CopyFiles "$INSTDIR\davfs.exe" "$SYSDIR\"
  CopyFiles "$INSTDIR\dokan.dll" "$sysdir32\"
  CopyFiles "$INSTDIR\$arch\dokan.sys" "$SYSDIR\drivers\"
  CopyFiles "$INSTDIR\$arch\dokannp.dll" "$SYSDIR\"
  SetRebootFlag true
    
  SetOutPath "$INSTDIR"
    
  DetailPrint "Installing driver"
  nsExec::execToStack 'dokanctl.exe /i a'
    
  DetailPrint "Installing network provider"
  nsExec::execToStack 'dokanctl.exe /i n'
SectionEnd

Section "Shortcut Section" SecShortcut
  SetOutPath "$INSTDIR"
  CreateDirectory "$SMPROGRAMS\${PRODUCT_PUBLISHER}"
  WriteIniStr "$SMPROGRAMS\${PRODUCT_PUBLISHER}\Website.url" "InternetShortcut" "URL" "${PRODUCT_WEB_SITE}"

  CreateShortCut "$SMPROGRAMS\${PRODUCT_PUBLISHER}\${UNINSTALL_SHORTCUT}" "$INSTDIR\Uninstall.exe" 
SectionEnd

Section "un.Shortcut Section" SecUnShortcut
  Delete "$SMPROGRAMS\${PRODUCT_PUBLISHER}\${UNINSTALL_SHORTCUT}"
  Delete "$SMPROGRAMS\${PRODUCT_PUBLISHER}\Website.url"
  RMDir  "$SMPROGRAMS\${PRODUCT_PUBLISHER}"
SectionEnd

Section "Uninstall"
  RMDir /r "$INSTDIR"
  RMDir /r "$APPDATA\${PRODUCT_PUBLISHER}\${PRODUCT_NAME}"
  RMDir "$APPDATA\${PRODUCT_PUBLISHER}"

  Delete "$INSTDIR\Uninstall.exe"
  DeleteRegKey HKLM "${UNINSTALL_REGKEY}"

  DeleteRegValue HKLM "Software\${PRODUCT_PUBLISHER}" "${PRODUCT_NAME}"
  DeleteRegKey /ifempty HKLM "Software\${PRODUCT_PUBLISHER}"
SectionEnd
