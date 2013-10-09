/*
* Copyright (C) 2009 Ulteo SAS
* http://www.ulteo.com
* Author Vincent ROULLIER <v.roullier@ulteo.com> 2013
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; version 2
* of the License.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

package org.ulteo.ovd.integrated;

public class WindowsSessionStatus {
	public static String getSessionStatus() {
		int session_status = WindowsSessionStatus.nGetSessionStatus();
		String status = "UNKNOWN";
		switch (session_status)
		{
		case 0:
			status = "ACTIVE";
			break;
		case 1:
			status = "CONNECTED";
			break;
			
		case 4 :
			status = "DISCONNECT";
			break;
		}
		return status;
	}
	
	protected static native int nGetSessionStatus();
}
