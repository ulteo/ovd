/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2010
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
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

public enum LoadingStatus {
	
	LOADING_START (0, 20),
	SM_CONNECTION (20, 40),
	SM_START (40, 60),
	SM_GET_APPLICATION (60, 80),
	CLIENT_INSTALL_APPLICATION (80, 100),
	CLIENT_WAITING_SERVER (100, 100);
	
	private int minValue;
	private int maxValue;

	LoadingStatus(int minValue_, int maxValue_) {
		this.minValue = minValue_;
		this.maxValue = maxValue_;
	}

	public static int getIncrement(LoadingStatus status, int subStatus) {
		if (subStatus > 100)
			return -1;
		return status.minValue + (status.maxValue - status.minValue) * subStatus / 100;
	}
	
	public static String getMsg(LoadingStatus status) {
		switch (status) {
			case LOADING_START:
				return I18n._("Start loading session");
			case SM_CONNECTION:
				return  I18n._("Connecting to the session manager");
			case SM_START:
				return I18n._("Get session information from session manager");
			case SM_GET_APPLICATION:
				return I18n._("Get application data from session manager");
			case CLIENT_INSTALL_APPLICATION:
				return I18n._("Installing application data on client");
			case CLIENT_WAITING_SERVER:
				return I18n._("Waiting server for session");
			default:
				return I18n._("Invalid status");
			}
	}
}
