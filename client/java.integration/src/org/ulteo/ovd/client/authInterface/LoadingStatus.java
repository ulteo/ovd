/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2010
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

package org.ulteo.ovd.client.authInterface;

import org.ulteo.utils.I18n;

public class LoadingStatus {
	public static final int STATUS_SM_CONNECTION = 10;
	public static final int STATUS_SM_START = 20;
	public static final int STATUS_SM_GET_APPLICATION = 40;
	public static final int STATUS_CLIENT_INSTALL_APPLICATION = 80;
	public static final int STATUS_CLIENT_WAITING_SERVER = 100;
	

	
	public static int getIncrement(int status, int subStatus) {
		int range;
		int increment = 0;

		switch (status) {
		case STATUS_SM_CONNECTION:
			increment = subStatus;
			break;
		case STATUS_SM_START:
			increment = subStatus;
			break;
			
		case STATUS_SM_GET_APPLICATION:
			range  = (STATUS_CLIENT_INSTALL_APPLICATION - STATUS_SM_GET_APPLICATION);
			increment =  range * subStatus /100;
			break;
		case STATUS_CLIENT_INSTALL_APPLICATION:
			range  = (STATUS_CLIENT_WAITING_SERVER - STATUS_CLIENT_INSTALL_APPLICATION);
			increment =  range * subStatus /100;
			break;
		case STATUS_CLIENT_WAITING_SERVER:
			System.out.println("STATUS_CLIENT_WAITING_SERVER");
			increment = subStatus;
			break;
			
		}
		return status + increment;
	}
	
	public static String getMsg(int status) {
		switch (status) {
		case STATUS_SM_CONNECTION:
			return  I18n._("Connecting to the session manager");
		case STATUS_SM_START:
			return I18n._("Get session information from session manager");
			
		case STATUS_SM_GET_APPLICATION:
			return I18n._("Get application data from session manager");

		case STATUS_CLIENT_INSTALL_APPLICATION:
			return I18n._("Installing application data on client");

		case STATUS_CLIENT_WAITING_SERVER:
			return I18n._("Waiting server for session");
			
		default:
			return I18n._("Invalid status");
		}
	}
}
