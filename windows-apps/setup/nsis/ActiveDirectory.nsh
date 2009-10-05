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
;;  ActiveDirectory.nsh
;;    : Verify if the system and the sessionManager are connected at the same domain
 
!ifndef ACTIVE_DIRECTORY_FUNCTION
!define ACTIVE_DIRECTORY_FUNCTION

!include "nsis\XML.nsh"

!macro .ErrorDisplay str

  MessageBox MB_OK|MB_TOPMOST|MB_ICONSTOP "Error :$\n${str}"
  Abort

!macroend

Function .ParseXML

  ${xml::LoadFile} "$TEMP\domain.xml" $0
  ${If} $0 != '0'
    Goto lbl_xmlError
  ${EndIf}

  ${xml::RootElement} $1 $0
  ${If} $0 != '0'
    Goto lbl_xmlError
  ${EndIf}

  ${xml::GetAttribute} "status" $1 $0
  ${If} $0 != '0'
    Goto lbl_xmlError
  ${EndIf}

  ${xml::GetText} $2 $0
  ${If} $0 != '0'
    Goto lbl_xmlError
  ${EndIf}

  ${Switch} $1
    ${Case} '0' ; ok
      ${Break}

    ${Case} '1' ; Error usage
      !insertmacro .ErrorDisplay "$2"
      ${Break}

    ${Case} '2' ; Not using Active Directory
      !insertmacro .ErrorDisplay "The session manager that you have specified does not use Active Directory.$\nUlteo does not support Windows without AD at the moment."
      ${Break}

    ${Case} '3' ; Not using the same Active Directory
      ${xml::GetAttribute} "domain" $3 $0
      ${If} $0 != '0'
        Goto lbl_xmlError
      ${EndIf}

      !insertmacro .ErrorDisplay "Your system and the session manager do not use the same Active Directory.$\nThe session manager uses $3."
      ${Break}

    ${Default}
      Goto lbl_xmlError
      ${Break}
  ${EndSwitch}
      
  Delete "$TEMP\domain.xml"
  Goto lbl_end

  lbl_xmlError:
    !insertmacro .ErrorDisplay "Parsing XML file failed."

  lbl_end:

FunctionEnd

Function .DomainVerification
  Pop $0

  Var /GLOBAL domain

  ReadRegStr $domain HKLM "SYSTEM\CurrentControlSet\Services\Tcpip\Parameters" Domain

  ${If} $domain == ""
    !insertmacro .ErrorDisplay "Your system is not connected to an Active Directory server."
  ${Else}
    ; get the domain by the session manager
    inetc::post /RESTORE "$0/webservices/domain.php?domain=$domain" "$TEMP\domain.xml" /END
    Pop $R0

    ${Switch} $R0
      ${Case} "OK"
        ; Success download. Parsing XML file
        Call .ParseXML
        ${Break}

      ${Default}
        !insertmacro .ErrorDisplay "The connection with the session manager ($0) failed : $R0"
        ${Break}
    ${EndSwitch}
  ${EndIf}

FunctionEnd

!endif
