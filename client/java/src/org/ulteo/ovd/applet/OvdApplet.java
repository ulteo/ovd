/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

package org.ulteo.ovd.applet;

import java.applet.Applet;
import netscape.javascript.JSObject;

public class OvdApplet extends Applet {
	
	public static final String JS_API_F_SERVER = "serverStatus";
	public static final String JS_API_O_SERVER_CONNECTED = "connected";
	public static final String JS_API_O_SERVER_DISCONNECTED = "disconnected";
	public static final String JS_API_O_SERVER_FAILED = "failed";
	public static final String JS_API_O_SERVER_READY = "ready";

	public static final String JS_API_F_INSTANCE = "applicationStatus";
	public static final String JS_API_O_INSTANCE_STARTED = "started";
	public static final String JS_API_O_INSTANCE_STOPPED = "stopped";
	public static final String JS_API_O_INSTANCE_ERROR = "error";

	@SuppressWarnings("deprecation")
	public void forwardJS(String functionName, Integer instance, String status) {
		try {
			try {
				JSObject win = JSObject.getWindow(this);
				Object[] args = {instance, status};
				win.call(functionName, args);
			} catch (ClassCastException e) {
				// a cast exception is raised when the applet is executed by the 
				// appletViewer class (used by some IDEs) and with OpenJDK JVM. This will 
				// not execute the JS, so it not possible to run an OVD session
				throw new netscape.javascript.JSException(e.getMessage());
			}
		} catch (netscape.javascript.JSException e) {
			System.err.printf("%s error while execute %s(%d, %s) => %s",
					this.getClass(), functionName, instance, status, e.getMessage());
		}
	}
}
