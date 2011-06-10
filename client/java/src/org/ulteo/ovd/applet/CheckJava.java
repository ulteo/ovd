/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com>
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
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

import org.ulteo.utils.LayoutDetector;

import netscape.javascript.JSObject;

public class CheckJava extends Applet {
	private String jsCallBack_onSuccess = null;
	private String jsCallBack_onFailure = null;
	private boolean hasCallbacks = false;
	
	private RequestForwarder ajax = null;
	private Thread ajaxThread = null;
	
	private String userLogin = "";
	private String userKeyboardLayout = null;
	
	@Override
	public void init() {
		this.jsCallBack_onSuccess = this.getParameter("onSuccess");
		this.jsCallBack_onFailure = this.getParameter("onFailure");
		
		if (this.jsCallBack_onSuccess != null && this.jsCallBack_onSuccess.length() >0 &&
			this.jsCallBack_onFailure != null && this.jsCallBack_onFailure.length() >0)
			this.hasCallbacks = true;
		
		if (this.hasCallbacks) {
			this.ajax = new RequestForwarder(this);
			this.ajaxThread = new Thread(this.ajax);
		}
	}
	
	@Override
	public void start() {
		if (this.hasCallbacks) {
			String callback = this.jsCallBack_onFailure;
			
			try {
				System.getProperty("user.home");
				this.userLogin = System.getProperty("user.name");
				callback = this.jsCallBack_onSuccess;
			} catch(java.security.AccessControlException e) {
				System.err.println("AccessControl issue");
			}
			this.userKeyboardLayout = LayoutDetector.get();
			
			try {
				JSObject win = JSObject.getWindow(this);
				Object[] args = new Object[0];
				
				win.call(callback, args);
			}
			catch (netscape.javascript.JSException e) {
				System.err.println(this.getClass()+" error while execute javascript function '"+callback+"' =>"+e.getMessage());
			}
			
			this.ajaxThread.start();
		}
	}
	
	@Override
	public void stop() {
		if (this.ajax != null) {
			this.ajax.setDisable();
			this.ajax = null;
			this.ajaxThread = null;
		}
	}
	
	public void ajaxRequest(String sm, String mode, String language, String timezone, String callback) {
		this.ajax.pushOrder(new AjaxOrder(sm, mode, language, timezone, callback));
	}
	
	public String getUserLogin() {
		return this.userLogin;
	}
	
	public String getDetectedKeyboardLayout() {
		return this.userKeyboardLayout;
	}
}
