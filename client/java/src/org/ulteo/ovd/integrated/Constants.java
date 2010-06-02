/*
 * Copyright (C) 2009 Ulteo SAS
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

public final class Constants {
	public static final String separator = System.getProperty("file.separator");
	public static final String homedir = System.getProperty("user.home");
	public static final String startmenuPath = (OSTools.isWindows()) ? WindowsPaths.getStartMenuPath() : "";
	// ToDo: find a better way (XDG) to get the desktop path on linux
	public static final String desktopPath = (OSTools.isWindows()) ? WindowsPaths.getDesktopPath() : Constants.homedir+Constants.separator+"Desktop";

	public static final String installPath = (OSTools.isWindows()) ? "Y:" : Constants.homedir+Constants.separator+"svn/ovd-lab/rdp-applet/dist";
	public static final String launcher = "UlteoOVDIntegratedLauncher"+((OSTools.isWindows()) ? ".exe" : "");
	public static final String launcherPath = Constants.installPath+Constants.separator+Constants.launcher;

	public static final String confPath = Constants.homedir+Constants.separator+((OSTools.isWindows()) ? "Application Data"+Constants.separator : ".")+"ulteo";
	public static final String confRemoteAppsPath = Constants.confPath+Constants.separator+"remoteApps";
	public static final String instancesPath = Constants.confRemoteAppsPath+Constants.separator+"instances";
	public static final String iconsPath = Constants.confRemoteAppsPath+Constants.separator+"icons";
	public static final String toLaunchPath = Constants.confRemoteAppsPath+Constants.separator+"to_launch";
	public static final String xfceMenuEntriesPath = Constants.homedir+Constants.separator+".local/share/applications";
	public static final String filePrinterName = "OVD_File_Printer";	
}
