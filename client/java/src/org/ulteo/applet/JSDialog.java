/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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

package org.ulteo.applet;

import java.applet.Applet;
import java.net.URL;

public class JSDialog {
	public static final int ERROR_UNKNOWN = 0;
	public static final int ERROR_USAGE = 1;
	public static final int ERROR_MEMORY = 2;
	public static final int ERROR_SECURITY = 3;
	public static final int ERROR_SSH = 4;
	public static final int ERROR_VNC = 5;

	private Applet applet = null;
	private String function_error = null;

	public JSDialog(Applet applet) {
		this.applet = applet;	
	}

	public boolean init() {
		this.function_error = this.applet.getParameter("errorCallback");
		if (this.function_error == null)
			return false;
		
		if (this.function_error.equals(""))
			return false;
		
		return true;
	}

	protected void talk(int status) {
		String message = null;

		switch(status) {
		case ERROR_UNKNOWN:
			message = "Unknown error";
			break;
		case ERROR_USAGE:
			message = "Usage error";
			break;
		case ERROR_MEMORY:
			message = "Not enough memory";
			break;
		case ERROR_SECURITY:
			message = "Security issue";
			break;
		case ERROR_SSH:
			message = "SSH connection error";
			break;
		case ERROR_VNC:
			message = "VNC connection error";
			break;
		default:
			System.err.println("Unknown status");
			return;
		}
		
		String url = "javascript:"+this.function_error+"("+status+ ", '"+message+"');";
		System.out.println(this.getClass()+" call javascript '"+url+"')");
		this.openUrl(url);
	}
	
	public void forwardFocusInfo(boolean focus) {
		String url = "javascript:daemon."+(focus?"focusGained":"focusLost")+"();";
		System.out.println(this.getClass()+" call javascript '"+url+"')");
		this.openUrl(url);
	}

	protected void openUrl(String url) {
		try {
			this.applet.getAppletContext().showDocument(new URL(url));
		} catch(Exception e) {
			System.err.println(this.getClass()+" couldn't execute javascript "+e.getMessage());
		}
	}
}
