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
package org.ulteo.ovd.disk;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.util.ArrayList;
import java.util.Properties;

import org.apache.log4j.Logger;
import org.ulteo.rdp.rdpdr.OVDRdpdrChannel;


public class LinuxDiskManager extends DiskManager {
	private static Logger logger = Logger.getLogger(LinuxDiskManager.class);
	private Properties xdgProperties = null;
	
	
	/**************************************************************************/
	public LinuxDiskManager(OVDRdpdrChannel diskChannel) {
		super(diskChannel);
	}
	
	/**************************************************************************/
	public boolean init() {
		if (!xdgOpen()) {
			logger.warn("Xdg is absent, the system use default value");
		}
		addStaticDirectory(getXdgDir("XDG_DOCUMENTS_DIR", "Documents"));
		addStaticDirectory(getXdgDir("XDG_DESKTOP_DIR", "Desktop"));
		addDirectoryToInspect("/media");
		addDirectoryToInspect("/mnt");
		return true;
	}

	/**************************************************************************/
	private boolean xdgOpen() {
		String xdgFile = System.getProperty("user.home")+"/.config/user-dirs.dirs";
		xdgProperties = new Properties();
		try {
			xdgProperties.load(new FileInputStream(xdgFile));
		} catch (FileNotFoundException e) {
			logger.warn("Unable to find the xdg file: "+xdgFile);
			return false;
		} catch (IOException e) {
			logger.warn("Unable to read xdg file: "+xdgFile);
			return false;
		}
		return true;
	}
	
	/**************************************************************************/
	private String getXdgDir(String value, String defaultValue) {
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


	/**************************************************************************/
	public ArrayList<String> getNewDrive() {
		ArrayList<String> newDrives = new ArrayList<String>();
		String dirPath;
		File dir = null;
		
		logger.debug("Searching for new drive");
		for (String toInspect : this.directoryToInspect) {
			dir = new File(toInspect);
			if (! dir.exists() || !dir.isDirectory())
				continue;
			for (String dir2 : dir.list()) {
				dirPath = toInspect+"/"+dir2;
				logger.debug("Drive "+dirPath);
				if (! this.isMounted(dirPath) && this.testDir(dirPath)) {
					newDrives.add(dirPath);
				}
			}
		}
		return newDrives;
	}
}
