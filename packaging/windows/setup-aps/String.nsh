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
;;  String.nsh
;;    : addeds functions which work on strings
;;
;;  Usage:
;;    - .SubStr :
;;      'Push "My string in which I seek a substring."'
;;      'Push "which"' ; The substring which I seek
;;      '!insertmacro SubStr'
;;      'Pop $R0' ; $R0 = "which I seek a substring."

 
 
!ifndef STRING_FUNCTION
!define STRING_FUNCTION

!macro SubStr

  Exch $R1
  Exch
  Exch $R2
  Push $R3
  Push $R4
  Push $R5
  StrLen $R3 $R1
  StrCpy $R4 0

  loop:
    StrCpy $R5 $R2 $R3 $R4
    StrCmp $R5 $R1 done
    StrCmp $R5 "" done
    IntOp $R4 $R4 + 1
    Goto loop
  done:
    StrCpy $R1 $R2 "" $R4
    Pop $R5
    Pop $R4
    Pop $R3
    Pop $R2
    Exch $R1

!macroend

; Trim
;   Removes leading & trailing whitespace from a string
; Usage:
;   Push 
;   Call Trim
;   Pop 
Function Trim
  Exch $R1 ; Original string
  Push $R2

  Loop:
    StrCpy $R2 "$R1" 1
    StrCmp "$R2" " " TrimLeft
    StrCmp "$R2" "$\r" TrimLeft
    StrCmp "$R2" "$\n" TrimLeft
    StrCmp "$R2" "$\t" TrimLeft
    GoTo Loop2

  TrimLeft:
    StrCpy $R1 "$R1" "" 1
    Goto Loop

  Loop2:
    StrCpy $R2 "$R1" 1 -1
    StrCmp "$R2" " " TrimRight
    StrCmp "$R2" "$\r" TrimRight
    StrCmp "$R2" "$\n" TrimRight
    StrCmp "$R2" "$\t" TrimRight
    GoTo Done

  TrimRight:
    StrCpy $R1 "$R1" -1
    Goto Loop2

  Done:
    Pop $R2
    Exch $R1
FunctionEnd

!endif
