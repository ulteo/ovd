;; Copyright (C) 2009 - 2011 Ulteo SAS
;; http://www.ulteo.com
;; Author David LECHEVALIER <david@ulteo.com> 2011
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

!include "FileFunc.nsh"
!insertmacro GetDrives


Function VCRedistClean
  ; http://support.microsoft.com/kb/950683
  IfFileExists "$9\vc_red.msi" +3 0
	Push $0
    Return

  Delete "$9\install.exe"
  Delete "$9\install.res.1028.dll"
  Delete "$9\install.res.1031.dll"
  Delete "$9\install.res.1033.dll"
  Delete "$9\install.res.1036.dll"
  Delete "$9\install.res.1040.dll"
  Delete "$9\install.res.1041.dll"
  Delete "$9\install.res.1042.dll"
  Delete "$9\install.res.2052.dll"
  Delete "$9\install.res.3082.dll"
  Delete "$9\vcredist.bmp"
  Delete "$9\globdata.ini"
  Delete "$9\install.ini"
  Delete "$9\eula.1028.txt"
  Delete "$9\eula.1031.txt"
  Delete "$9\eula.1033.txt"
  Delete "$9\eula.1036.txt"
  Delete "$9\eula.1040.txt"
  Delete "$9\eula.1041.txt"
  Delete "$9\eula.1042.txt"
  Delete "$9\eula.2052.txt"
  Delete "$9\eula.3082.txt"
  Delete "$9\vc_red.msi"
  Delete "$9\vc_red.cab"
  Push $0
FunctionEnd

!macro VCRedistInstall
  nsExec::ExecToStack  '"msiexec" /i "$INSTDIR\vcredist\vc_red.msi"'
  ${GetDrives} "HDD" "VCRedistClean"
!macroend

