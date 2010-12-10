;; Copyright (C) 2009 - 2010 Ulteo SAS
;; http://www.ulteo.com
;; Author David LECHEVALIER <david@ulteo.com> 2010
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


!macro CreateUser SERVER_NAME USERNAME PASSWORD DESCRIPTION NAME SURNAME GROUP_ID
  # Create the user
  System::Call '*(w "${USERNAME}",w "${PASSWORD}",i n,i 1,w n,w "${DESCRIPTION}",i 513,w n)i.R0'
  System::Call 'netapi32::NetUserAdd(w "${SERVER_NAME}",i 1,i R0,*i.r0) i.r1'
  System::Free $R0
  # Set the user's name and surname
  System::Call '*(w "${NAME} ${SURNAME}")i.R0'
  System::Call 'netapi32::NetUserSetInfo(w "${SERVER_NAME}",w "${USERNAME}",i 1011, \
i R0,*i.r0)i.r1'
  System::Free $R0
  # Get the user's SID in $R8
  System::Call '*(&w${NSIS_MAX_STRLEN})i.R8'
  System::Call 'advapi32::LookupAccountNameW(w "${SERVER_NAME}",w "${USERNAME}",i R8, \
*i ${NSIS_MAX_STRLEN}, w .R1, *i ${NSIS_MAX_STRLEN}, *i .r0)i .r1'
  System::Call 'advapi32::ConvertSidToStringSid(i R8,*t .R1)i .r0'
  # Get the group name in $R9 using its SID
  System::Call '*(&i1 0,&i4 0,&i1 5)i.R0'
  System::Call 'advapi32::AllocateAndInitializeSid(i R0,i 2,i 32,i ${GROUP_ID},i 0, \
i 0,i 0,i 0,i 0,i 0,*i .r2)'
  System::Free $R0
  System::Call '*(&w${NSIS_MAX_STRLEN})i.R9'
  System::Call 'advapi32::LookupAccountSidW(i 0,i r2,i R9,*i ${NSIS_MAX_STRLEN},t .r3, \
*i ${NSIS_MAX_STRLEN},*i .r4)'
  System::Call 'advapi32::FreeSid(i r2)'
  # Add the user to the user's group
  System::Call 'netapi32::NetLocalGroupAddMembers(w "${SERVER_NAME}",i R9,i 0,*i R8,i 1)i .r0'
  System::Free $R8
  System::Free $R9
!macroend

