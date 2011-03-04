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

	private final static String LINUX = "Linux";
	private final static String MAC = "Mac";
	private final static String WINDOWS = "Windows";
	private static String os_name = null;

	public static String detectOS() {
		if (OSTools.os_name == null)
			OSTools.os_name = System.getProperty("os.name");

		return OSTools.os_name;
	}

	public static boolean isSupportedOS() {
		return (OSTools.isWindows() || OSTools.isLinux() || OSTools.isMac());
	}

	public static boolean isWindows() {
		if (OSTools.detectOS() == null)
			return false;

		return OSTools.os_name.toLowerCase().startsWith(WINDOWS.toLowerCase());
	}

	public static boolean isLinux() {
		if (OSTools.detectOS() == null)
			return false;

		return OSTools.os_name.toLowerCase().startsWith(LINUX.toLowerCase());
	}

	public static boolean isMac() {
		if (OSTools.detectOS() == null)
			return false;

		return OSTools.os_name.toLowerCase().startsWith(MAC.toLowerCase());
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
