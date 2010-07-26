/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com>
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

package org.ulteo.ovd.applet;

import java.applet.Applet;
import netscape.javascript.JSObject;

public class CheckJava extends Applet {
	private String jsCallBack_onSuccess = null;
	private String jsCallBack_onFailure = null;
	private boolean hasCallbacks = false;

	@Override
	public void init() {
		this.jsCallBack_onSuccess = this.getParameter("onSuccess");
		this.jsCallBack_onFailure = this.getParameter("onFailure");

		if (this.jsCallBack_onSuccess != null && this.jsCallBack_onSuccess.length() >0 &&
			this.jsCallBack_onFailure != null && this.jsCallBack_onFailure.length() >0)
			this.hasCallbacks = true;
	}

	@Override
	public void start() {
		if (this.hasCallbacks) {
			String callback = this.jsCallBack_onFailure;

			try {
				System.getProperty("user.home");
				callback = this.jsCallBack_onSuccess;
			} catch(java.security.AccessControlException e) {
				System.err.println("AccessControl issue");
			}
			
			try {
				JSObject win = JSObject.getWindow(this);
				Object[] args = new Object[0];
				
				win.call(callback, args);
			}
			catch (netscape.javascript.JSException e) {
				System.err.println(this.getClass()+" error while execute javascript function '"+callback+"' =>"+e.getMessage());
			}
		}
	}
}
