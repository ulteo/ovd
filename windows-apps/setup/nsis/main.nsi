;; Copyright (C) 2009 Ulteo SAS
;; http://www.ulteo.com
;; Author Julien LANGLOIS <julien@ulteo.com>
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
!define PRODUCT_VERSION "1.0-rc2"
!define PRODUCT_LONGVERSION "1.0.0.2"
!define PRODUCT_PUBLISHER "Ulteo"
!define PRODUCT_WEB_SITE "http://www.ulteo.com"
!define PRODUCT_FULL_NAME "${PRODUCT_PUBLISHER} ${PRODUCT_NAME}"

!define BASENAME "${PRODUCT_NAME}"
!define EXE_NAME "ovd.exe"
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
  Name "${PRODUCT_NAME}"INSTDIR
  OutFile "${SETUP_NAME}.exe"

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
  VIAddVersionKey LegalCopyright "Copyright @ 2009 ${PRODUCT_PUBLISHER}"
  VIProductVersion "${PRODUCT_LONGVERSION}"

;--------------------------------
;Interface Settings

;--------------------------------
;Language Selection Dialog Settings

  ;Remember the installer language
  !define MUI_LANGDLL_REGISTRY_ROOT "HKLM" 
  !define MUI_LANGDLL_REGISTRY_KEY "Software\${PRODUCT_PUBLISHER}" 
  !define MUI_LANGDLL_REGISTRY_VALUENAME "Installer Language"

;--------------------------------
;Pages

  ; Installer parameters and pages order
  !define MUI_WELCOMEPAGE_TITLE_3LINES
  !define MUI_LICENSEPAGE_RADIOBUTTONS
  !define MUI_FINISHPAGE_LINK $(VisitOurWebSite)
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

  ;Multi Language configuration
  !include nsis\i18n.nsh
  ; Env var managment
  ; http://nsis.sourceforge.net/Environmental_Variables:_append%2C_prepend%2C_and_remove_entries
  !include nsis\EnvVarUpdate.nsh


;--------------------------------
;Reserve Files
  
  ;If you are using solid compression, files that are required before
  ;the actual installation should be stored first in the data block,
  ;because this will make your installer start faster.
  
  !insertmacro MUI_RESERVEFILE_LANGDLL


## First Dialog
Function InputBoxPageShow
 !insertmacro MUI_HEADER_TEXT "Configuration" "Give your server name and the Session Manager url."

 PassDialog::InitDialog /NOUNLOAD InputBox \
            /HEADINGTEXT "Caution: give full name or ip adress" \
            /BOX "Servername:" "aps.ulteo.com" 0 \
            /BOX "Session Manager URL:" "http://sm.ulteo.com/sessionmanager" 0
 PassDialog::Show
FunctionEnd

Function InputBoxPageLeave
 Pop $R0
 !define OVD_SERVERNAME $R0
 Pop $R1
 !define OVD_SMURL $R1
FunctionEnd


Function .onInit
  ; to install for all user
    SetShellVarContext all
    !insertmacro MUI_LANGDLL_DISPLAY
FunctionEnd

Function un.onInit
  ; to uninstall for all user
  SetShellVarContext all
  !insertmacro MUI_UNGETLANGUAGE
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
  ${un.EnvVarUpdate} $0 "PATH" "D" "HKLM" "$INSTDIR\rdp"

  Detailprint "Removing Configuration file"
  Delete "$INSTDIR\ulteo-ovd.conf"
  ;RMDir  "$APPDATA\${PRODUCT_PUBLISHER}\ovd\log"
  RMDir  "$APPDATA\${PRODUCT_PUBLISHER}\ovd"
  RMDir  "$APPDATA\${PRODUCT_PUBLISHER}"
SectionEnd

  !include dist.nsh

Section "post" PostCmd
  SetOutPath "$APPDATA\${PRODUCT_PUBLISHER}\ovd"

  DetailPrint "Generating Config file"
  FileOpen $4 "$INSTDIR\ulteo-ovd.conf" w
  FileWrite $4 "SERVERNAME=${OVD_SERVERNAME}"
  FileWrite $4 "$\r$\n" 
  FileWrite $4 "SESSION_MANAGER_URL=${OVD_SMURL}"
  FileWrite $4 "$\r$\n" 
  FileWrite $4 'LOG_FLAGS="info warn error"'
  FileWrite $4 "$\r$\n" 
  FileWrite $4 "WEBPORT=8082"
  FileClose $4

  ;Windows Version
  Push $R0
  Push $R1
 
  ClearErrors
 
  ReadRegStr $R0 HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion" CurrentVersion
 
  IfErrors 0 lbl_winnt
 
  ; we are not NT
  ReadRegStr $R0 HKLM "SOFTWARE\Microsoft\Windows\CurrentVersion" VersionNumber
 
  StrCpy $R1 $R0 1
  StrCmp $R1 '4' 0 lbl_error
 
  StrCpy $R1 $R0 3
 
  ;StrCmp $R1 '4.0' lbl_win32_95
  ;StrCmp $R1 '4.9' lbl_win32_ME lbl_win32_98
 
  ;lbl_win32_95:
    ;StrCpy $R0 '95'
    ;MessageBox MB_OK|MB_ICONQUESTION "Windows 95" IDOK 

    ;Goto lbl_done
 
  ;lbl_win32_98:
    ;StrCpy $R0 '98'
    ;MessageBox MB_OK|MB_ICONQUESTION "Windows 98" IDOK 

    ;Goto lbl_done
 
  ;lbl_win32_ME:
    ;StrCpy $R0 'ME'
    ;MessageBox MB_OK|MB_ICONQUESTION "Windows ME" IDOK 

    ;Goto lbl_done
 
  lbl_winnt:
 
    StrCpy $R1 $R0 1
    ;StrCmp $R1 '3' lbl_winnt_x
    ;StrCmp $R1 '4' lbl_winnt_x

    StrCpy $R1 $R0 3
    ;StrCmp $R1 '5.0' lbl_winnt_2000
    StrCmp $R1 '5.1' lbl_winnt_XP
    StrCmp $R1 '5.2' lbl_winnt_2003
    StrCmp $R1 '6.0' lbl_winnt_vista
    ;StrCmp $R1 '6.1' lbl_winnt_7 lbl_error
 
  ;lbl_winnt_x:
    ;StrCpy $R0 "NT $R0" 6

    ;Goto lbl_done
 
  ;lbl_winnt_2000:
    ;Strcpy $R0 '2000'
    ;MessageBox MB_OK|MB_ICONQUESTION "Windows 2000" IDOK 

    ;Goto lbl_done
 
  lbl_winnt_XP:
    ;Strcpy $R0 'XP'
    ;MessageBox MB_OK|MB_ICONQUESTION "Windows XP" IDOK 
    ;Change the default Shell for Windows XP
    DetailPrint "Change Default Shell"
    WriteRegStr HKLM "Software\Microsoft\Windows NT\CurrentVersion\WinLogon" "Shell" "seamlessrdpshell.exe"

    Goto lbl_done
 
  lbl_winnt_2003:
    ;Strcpy $R0 '2003'
    ;MessageBox MB_OK|MB_ICONQUESTION "Windows 2003" IDOK 

    Goto lbl_done
 
  lbl_winnt_vista:
    ;Strcpy $R0 'Vista OR Windows Server 2008' 
    CopyFiles $INSTDIR\rdp\* $SYSDIR

    Goto lbl_done
 
  ;lbl_winnt_7:
    ;Strcpy $R0 '7'
    ;MessageBox MB_OK|MB_ICONQUESTION "Windows 7" IDOK 

    ;Goto lbl_done
 
  lbl_error:
    Strcpy $R0 ''
  
  lbl_done:
    Pop $R1
    Exch $R0

  DetailPrint "Change PATH Environment variable"
  ${EnvVarUpdate} $0 "PATH" "A" "HKLM" "$INSTDIR\rdp"

  DetailPrint "Creating Service"
  nsExec::execToStack 'sc create OVD BinPath= "$INSTDIR\OVD.exe" DisplayName= "Ulteo Open Virtual Desktop agent" depend= EventLog/winmgmt start= auto'

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
  ;Windows Version
  Push $R0
  Push $R1
 
  ClearErrors
 
  ReadRegStr $R0 HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion" CurrentVersion
 
  IfErrors 0 lbl_winnt
 
  ; we are not NT
  ReadRegStr $R0 HKLM "SOFTWARE\Microsoft\Windows\CurrentVersion" VersionNumber
 
  StrCpy $R1 $R0 1
  StrCmp $R1 '4' 0 lbl_error
 
  StrCpy $R1 $R0 3
 
  ;StrCmp $R1 '4.0' lbl_win32_95
  ;StrCmp $R1 '4.9' lbl_win32_ME lbl_win32_98
 
  ;lbl_win32_95:
    ;StrCpy $R0 '95'
    ;MessageBox MB_OK|MB_ICONQUESTION "Windows 95" IDOK 

   ; Goto lbl_done
 
  ;lbl_win32_98:
    ;StrCpy $R0 '98'
    ;MessageBox MB_OK|MB_ICONQUESTION "Windows 98" IDOK 

   ; Goto lbl_done
 
  ;lbl_win32_ME:
    ;StrCpy $R0 'ME'
    ;MessageBox MB_OK|MB_ICONQUESTION "Windows ME" IDOK 

   ; Goto lbl_done
 
  lbl_winnt:
 
    StrCpy $R1 $R0 1
    ;StrCmp $R1 '3' lbl_winnt_x
    ;StrCmp $R1 '4' lbl_winnt_x

    StrCpy $R1 $R0 3
    ;StrCmp $R1 '5.0' lbl_winnt_2000
    StrCmp $R1 '5.1' lbl_winnt_XP
    StrCmp $R1 '5.2' lbl_winnt_2003
    StrCmp $R1 '6.0' lbl_winnt_vista
    ;StrCmp $R1 '6.1' lbl_winnt_7 lbl_error
 
  ;lbl_winnt_x:
   ; StrCpy $R0 "NT $R0" 6

    ;Goto lbl_done
 
  ;lbl_winnt_2000:
    ;Strcpy $R0 '2000'
    ;MessageBox MB_OK|MB_ICONQUESTION "Windows 2000" IDOK 

   ; Goto lbl_done
 
  lbl_winnt_XP:
    ;Strcpy $R0 'XP'
    ;MessageBox MB_OK|MB_ICONQUESTION "Windows XP" IDOK 
    ;Change the default Shell for Windows XP
    DetailPrint "Restore Default Shell"
    WriteRegStr HKLM "Software\Microsoft\Windows NT\CurrentVersion\WinLogon" "Shell" "explorer.exe"

    Goto lbl_done
 
  lbl_winnt_2003:
    ;Strcpy $R0 '2003'
    ;MessageBox MB_OK|MB_ICONQUESTION "Windows 2003" IDOK 

    Goto lbl_done
 
  lbl_winnt_vista:
    ;Strcpy $R0 'Vista OR Windows Server 2008'
    Delete "$SYSDIR\seamlessrdpshell.exe"
    Delete "$SYSDIR\seamlessrdpshell.dll"
    Delete "$SYSDIR\vchannel.dll"

    Goto lbl_done
 
  ;lbl_winnt_7:
   ; Strcpy $R0 '7'
    ;MessageBox MB_OK|MB_ICONQUESTION "Windows 7" IDOK 

    ;Goto lbl_done
 
  lbl_error:
    Strcpy $R0 ''
  
  lbl_done:
    Pop $R1
    Exch $R0

  RMDir /r "$INSTDIR"
  RMDir /r "$APPDATA\${PRODUCT_PUBLISHER}\${PRODUCT_NAME}"
  RMDir "$APPDATA\${PRODUCT_PUBLISHER}"

  Delete "$INSTDIR\Uninstall.exe"
  DeleteRegKey HKLM "${UNINSTALL_REGKEY}"

  DeleteRegValue HKLM "Software\${PRODUCT_PUBLISHER}" "${PRODUCT_NAME}"
  DeleteRegKey /ifempty HKLM "Software\${PRODUCT_PUBLISHER}"
SectionEnd





