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

package org.ulteo.ovd.integrated;

import com.ice.jni.registry.Registry;
import com.ice.jni.registry.RegistryException;
import com.ice.jni.registry.RegistryKey;
import java.util.Enumeration;
import org.ulteo.Logger;

public class WindowsRegistryPaths extends WindowsPaths {
	public static String getDesktopPath() {
		RegistryKey key = Registry.openSubkey(Registry.HKEY_CURRENT_USER, "Software\\Microsoft\\Windows\\CurrentVersion\\Explorer\\User Shell Folders", RegistryKey.ACCESS_READ);
		if (key != null) {
			try {
				Enumeration enumValues = key.valueElements();
				while (enumValues.hasMoreElements()) {
					String valueStr = (String) enumValues.nextElement();
					if (! valueStr.equals("Desktop"))
						continue;

					String desktopdir = key.getStringValue("Desktop");

					if (desktopdir.contains("%USERPROFILE%")) {
						String homedir = System.getProperty("user.home");
						homedir = homedir.replaceAll("\\\\", "\\\\\\\\");
						desktopdir = desktopdir.replaceFirst("%USERPROFILE%", homedir);
					}
					else {
						desktopdir = "U:"+desktopdir.substring(desktopdir.lastIndexOf("\\"), desktopdir.length());
					}

					Logger.debug("desktopdir: "+desktopdir);

					return desktopdir;
				}
			} catch (RegistryException ex)  {
				Logger.error("Failed to get the desktop path: "+ex.getMessage());
			}
		}

		Logger.warn("Failed to find the desktop path in the registry");
		return WindowsPaths.nGetDesktopPath();
	}
}
