/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author David Lechavalier <david@ulteo.com> 2010
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

import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.util.Properties;

public class LinuxPaths {

	
	private static Properties xdgOpen() {
		String xdgFile = System.getProperty("user.home")+"/.config/user-dirs.dirs";
		Properties xdgProperties = new Properties();
		try {
			xdgProperties.load(new FileInputStream(xdgFile));
		} catch (FileNotFoundException e) {
			org.ulteo.Logger.error("Unable to find the xdg file: "+xdgFile);
			return null;
		} catch (IOException e) {
			org.ulteo.Logger.error("Unable to read xdg file: "+xdgFile);
			return null;
		}
		return xdgProperties;
	}
	
	private static String getXdgDir(Properties xdgProperties, String value, String defaultValue) {
		String homeDir = System.getProperty("user.home");
		String xdgValue = defaultValue;
		if (xdgProperties != null) {
			xdgValue = xdgProperties.getProperty(value, defaultValue);
		}
		if (xdgValue.startsWith("/"))
			return xdgValue;
		xdgValue = xdgValue.replace("$HOME/", "");
		return homeDir+"/"+xdgValue.replaceAll("\"", "");
	}
	
	private static void xdgClose(Properties xdgProperties) {
		xdgProperties = null;
	}
	
	private static String getPath(String value, String defaultPath) {
		Properties xdgProperties = xdgOpen();
		String path = getXdgDir(xdgProperties, value, defaultPath);
		xdgClose(xdgProperties);
		return path;		
	}
	
	public static String getDesktopPath() {
		return getPath("XDG_DESKTOP_DIR", "Desktop");
	}

	public static String getDocumentPath() {
		return getPath("XDG_DOCUMENTS_DIR", "Documents");
	}
}
