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
	private String jsFunction = null;

	@Override
	public void init() {
		this.jsFunction = this.getParameter("jsFunction");
	}

	@Override
	public void start() {
		if (this.jsFunction != null) {
			try {
				JSObject win = JSObject.getWindow(this);
				Object[] args = new Object[0];
				
				win.call(this.jsFunction, args);
			}
			catch (netscape.javascript.JSException e) {
				System.err.println(this.getClass()+" error while execute javascript function '"+this.jsFunction+"' =>"+e.getMessage());
			}
		}
	}
}
