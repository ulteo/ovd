/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

public interface JSForwarder {
	public static final String JS_API_F_SERVER = "serverStatus";
	public static final String JS_API_O_SERVER_CONNECTED = "connected";
	public static final String JS_API_O_SERVER_DISCONNECTED = "disconnected";
	public static final String JS_API_O_SERVER_FAILED = "failed";
	public static final String JS_API_O_SERVER_READY = "ready";
	
	public void forwardJS(String functionName, Integer instance, String status);
}
