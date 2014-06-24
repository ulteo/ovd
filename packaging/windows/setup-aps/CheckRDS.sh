;; Copyright (C) 2014 Ulteo SAS
;; http://www.ulteo.com
;; Author David LECHEVALIER <david@ulteo.com> 2014
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
 
 
!ifndef CHECK_RDS_FUNCTION
!define CHECK_RDS_FUNCTION

!include WindowsVersion.nsh
!include String.nsh
!include VersionCompare.nsh

!ifmacrondef _Trim
   !macro _Trim _UserVar _OriginalString
   !define Trim_UID ${__LINE__}
   
      Push $R1
      Push $R2
      Push `${_OriginalString}`
      Pop $R1
      
      Loop_${Trim_UID}:
         StrCpy $R2 "$R1" 1
         StrCmp "$R2" " " TrimLeft_${Trim_UID}
         StrCmp "$R2" "$\r" TrimLeft_${Trim_UID}
         StrCmp "$R2" "$\n" TrimLeft_${Trim_UID}
         StrCmp "$R2" "$\t" TrimLeft_${Trim_UID}
         GoTo Loop2_${Trim_UID}
      TrimLeft_${Trim_UID}:   
         StrCpy $R1 "$R1" "" 1
         Goto Loop_${Trim_UID}
 
      Loop2_${Trim_UID}:
         StrCpy $R2 "$R1" 1 -1
         StrCmp "$R2" " " TrimRight_${Trim_UID}
         StrCmp "$R2" "$\r" TrimRight_${Trim_UID}
         StrCmp "$R2" "$\n" TrimRight_${Trim_UID}
         StrCmp "$R2" "$\t" TrimRight_${Trim_UID}
         GoTo Done_${Trim_UID}
      TrimRight_${Trim_UID}:  
         StrCpy $R1 "$R1" -1
         Goto Loop2_${Trim_UID}
 
      Done_${Trim_UID}:
         Pop $R2
         Exch $R1
         Pop ${_UserVar}
      !undef Trim_UID
  !macroend
  !ifndef Trim
     !define Trim `!insertmacro _Trim`
  !endif
!endif
 

Function .CheckRDS
   Var /GLOBAL WinVersion
   ReadRegStr $WinVersion HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion" CurrentVersion

   ${VersionCompare} $WinVersion "5.2" $1
   IntCmp $1 1 win2008plus win2003 win2003

   win2003:
      nsExec::ExecToStack /OEM 'WMIC /NAMESPACE:\\root\CIMv2 path Win32_TerminalServiceSetting Get TerminalServerMode /Value'
      Pop $0
      StrCmp $0 0 0 failed
      Pop $0
      ${Trim} $0 $0
      Delete "tempwmicbatchfile.bat"
      StrCmp $0 "TerminalServerMode=1" good failed
   
   
   win2008plus:
      nsExec::ExecToStack /OEM 'WMIC /NAMESPACE:\\root\CIMv2 path Win32_ServerFeature where id=18 Get id /Value'
      Pop $0
      StrCmp $0 0 0 failed
      Pop $0
      ${Trim} $0 $0
      Delete "tempwmicbatchfile.bat"
      StrCmp $0 "ID=18" good failed
   
      
   failed:
      MessageBox MB_YESNO|MB_ICONEXCLAMATION "Remote Desktop role is not installed. Do you want to continue installation" IDYES good
      Abort
   
   good:
FunctionEnd


!endif
