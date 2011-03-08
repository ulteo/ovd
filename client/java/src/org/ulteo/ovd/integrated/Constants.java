/*
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
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
	public static final String FILE_SEPARATOR = System.getProperty("file.separator");
	public static final String SHORTCUTS_EXTENSION = (OSTools.isWindows() ? ".lnk" : ".desktop");
	public static final String ICONS_EXTENSION = (OSTools.isWindows() ? ".ico" : ".png");

	public static final String HOMEDIR = System.getProperty("user.home");
	public static final String PATH_STARTMENU = (OSTools.isWindows()) ? WindowsPaths.getStartMenuPath() : "";
	public static final String PATH_XDG_APPLICATIONS = Constants.HOMEDIR+Constants.FILE_SEPARATOR+".local/share/applications";
	public static final String PATH_OVD_SPOOL_XDG_APPLICATIONS = "/var/spool/ulteo/ovd/"+System.getProperty("user.name")+"/xdg/applications";
	public static final String PATH_DESKTOP = (OSTools.isWindows()) ? ((OSTools.is_applet) ? WindowsPaths.getDesktopPath() : WindowsRegistryPaths.getDesktopPath()) : LinuxPaths.getDesktopPath();
	public static final String PATH_DOCUMENT = (OSTools.isWindows()) ? WindowsPaths.getPersonalDataPath() : LinuxPaths.getDocumentPath();

	public static final String FILENAME_LAUNCHER = "UlteoOVDIntegratedLauncher"+((OSTools.isWindows()) ? ".exe" : "");
	
	public static final String PATH_CONF = ((OSTools.isWindows()) ? WindowsPaths.getAppDataPath()+Constants.FILE_SEPARATOR : Constants.HOMEDIR+Constants.FILE_SEPARATOR+".") + "ulteo";
	public static final String PATH_OVD_CONF = Constants.PATH_CONF+Constants.FILE_SEPARATOR+"ovd";
	public static final String PATH_NATIVE_CLIENT_CONF = Constants.PATH_OVD_CONF+Constants.FILE_SEPARATOR+"client";

	public static final String PATH_CACHE = Constants.PATH_NATIVE_CLIENT_CONF+Constants.FILE_SEPARATOR+"cache";
	public static final String PATH_CACHE_APPS_ICONS = Constants.PATH_CACHE+Constants.FILE_SEPARATOR+"apps_icons";
	public static final String PATH_CACHE_MIMETYPES_ICONS = Constants.PATH_CACHE+Constants.FILE_SEPARATOR+"mimetypes_icons";

	public static final String PATH_REMOTE_APPS = Constants.PATH_OVD_CONF+Constants.FILE_SEPARATOR+"remoteApps";
	public static final String PATH_ICONS = Constants.PATH_REMOTE_APPS+Constants.FILE_SEPARATOR+"icons";
	public static final String PATH_SHORTCUTS = PATH_REMOTE_APPS+Constants.FILE_SEPARATOR+"Shortcuts";
	
	public static final String DIRNAME_INSTANCES = "instances";
	public static final String DIRNAME_TO_LAUNCH = "to_launch";
	public static final String OVD_ISSUE = "Ulteo OVD";
}
