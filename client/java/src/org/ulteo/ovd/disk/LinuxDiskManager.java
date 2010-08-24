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
import org.ulteo.ovd.integrated.Constants;
import org.ulteo.ovd.integrated.LinuxPaths;
import org.ulteo.rdp.rdpdr.OVDRdpdrChannel;


public class LinuxDiskManager extends DiskManager {
	private static Logger logger = Logger.getLogger(LinuxDiskManager.class);
	
	
	/**************************************************************************/
	public LinuxDiskManager(OVDRdpdrChannel diskChannel) {
		super(diskChannel);
	}
	
	/**************************************************************************/
	public boolean init() {
		addStaticDirectory(Constants.PATH_DOCUMENT);
		addStaticDirectory(Constants.PATH_DESKTOP);
		addDirectoryToInspect("/media");
		addDirectoryToInspect("/mnt");
		return true;
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
