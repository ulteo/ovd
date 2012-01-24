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

package org.ulteo.utils.jni;

import java.awt.GraphicsEnvironment;
import java.awt.Rectangle;
import java.awt.Window;
import java.lang.reflect.InvocationTargetException;
import java.lang.reflect.Method;
import org.ulteo.Logger;
import org.ulteo.ovd.integrated.OSTools;

public class WorkArea {
	private static boolean loadLibrary = true;

	public static void disableLibraryLoading() {
		WorkArea.loadLibrary = false;
	}

	public static Rectangle getWorkAreaSize() {
		if (WorkArea.loadLibrary && OSTools.isLinux()) {
			try {
				int[] area = getWorkAreaSizeForX();
				if (area.length == 4)
					return new Rectangle(area[0], area[1], area[2], area[3]);
			} catch (UnsatisfiedLinkError e) {
				Logger.error("Failed to execute method: "+e.getMessage());
			}

			Logger.error("Failed to get the client workarea.");
		}

		return GraphicsEnvironment.getLocalGraphicsEnvironment().getMaximumWindowBounds();
	}

	private static native int[] getWorkAreaSizeForX();

	public static long getX11WindowId(Window wnd) {
		Object peer = wnd.getPeer();
		if (peer == null)
			return 0;

		ClassLoader classLoader = WorkArea.class.getClassLoader();
		try {
			Class XBaseWindowClass = classLoader.loadClass("sun.awt.X11.XBaseWindow");
			Method getWindowMethod = XBaseWindowClass.getMethod("getWindow", (Class[]) null);

			Long window_id = (Long) getWindowMethod.invoke(peer, (Object[]) null);
			return window_id.longValue();
		} catch (ClassNotFoundException ex) {
			Logger.error("Failed to find XBaseWindow class: "+ex.getMessage());
		} catch (NoSuchMethodException ex) {
			Logger.error("Failed to find XBaseWindow.getWindow() method: "+ex.getMessage());
		} catch (IllegalAccessException ex) {
			Logger.error("Failed to access to XBaseWindow.getWindow() method: "+ex.getMessage());
		} catch (InvocationTargetException ex) {
			Logger.error("Failed to invoke XBaseWindow.getWindow() method: "+ex.getMessage());
		}

		return 0;
	}

	public static void setFullscreenWindow(Window wnd, boolean enabled) {
		if (! WorkArea.loadLibrary || ! OSTools.isLinux())
			return;

		long x11_id = getX11WindowId(wnd);
		WorkArea.setFullscreenWindow(x11_id, enabled);
	}

	private static native void setFullscreenWindow(long window_id, boolean enabled);
}
