/*
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2011
 * Author Samuel BOVEE <samuel@ulteo.com> 2010
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


public class OSTools {
	public static boolean is_applet = false;

	public static String detectOS() {
		String osName = System.getProperty("os.name");
		if (osName.startsWith("Windows") || osName.startsWith("Linux"))
			return osName;

		return null;
	}

	public static boolean isSupportedOS() {
		return (OSTools.detectOS() != null);
	}

	public static boolean isWindows() {
		return System.getProperty("os.name").startsWith("Windows");
	}

	public static boolean isLinux() {
		return System.getProperty("os.name").startsWith("Linux");
	}

	public static boolean isMac() {
		return System.getProperty("os.name").startsWith("MAC");
	}

	public static boolean is64() {
		return System.getProperty("os.arch").contains("64");
	}

	public static final class OSInfos {
		public String name = null;
		public String version = null;
		public String arch = null;
	}

	public static OSInfos getOSInfos() {
		OSInfos os_infos = new OSInfos();
		
		os_infos.name = System.getProperty("os.name");
		os_infos.version = System.getProperty("os.version");
		os_infos.arch = System.getProperty("os.arch");

		return os_infos;
	}
}
