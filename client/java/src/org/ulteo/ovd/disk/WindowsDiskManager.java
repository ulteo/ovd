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
import java.util.ArrayList;

import org.apache.log4j.Logger;
import org.ulteo.ovd.integrated.Constants;
import org.ulteo.rdp.rdpdr.OVDRdpdrChannel;


public class WindowsDiskManager extends DiskManager {
	private static Logger logger = Logger.getLogger(WindowsDiskManager.class);

	/**************************************************************************/
	public WindowsDiskManager(OVDRdpdrChannel diskChannel) {
		super(diskChannel);
	}

	/**************************************************************************/
	public boolean init() {
		addStaticDirectory(Constants.PATH_DESKTOP);
		addStaticDirectory(Constants.PATH_DOCUMENT);
		return true;		
	}
	
	/**************************************************************************/	
	private ArrayList<String> getLogicalDrive() {
		ArrayList<String> newDrives = new ArrayList<String>();
		File []drives = File.listRoots();
		String driveString;
		for (File drive : drives) {
			driveString = drive.getAbsolutePath();
			if (driveString.equalsIgnoreCase("A:\\") || driveString.equalsIgnoreCase("D:\\"))
				continue;
			newDrives.add(driveString);
		}
		return newDrives;
	}
	
	/**************************************************************************/
	public ArrayList<String> getNewDrive() {
		ArrayList<String> newDrives = new ArrayList<String>();
		String dirPath;
		File dir = null;

		logger.debug("Searching for new drive");
		for (String drive : getLogicalDrive()) {
			logger.debug("Drive "+drive);
			dir = new File(drive);
			if (! dir.exists() || !dir.isDirectory())
				continue;
			if (! this.isMounted(drive) && this.testDir(drive)) {
				newDrives.add(drive);
			}
		}
		
		for (String toInspect : this.directoryToInspect)  {
			dir = new File(toInspect);
			if (! dir.exists() || !dir.isDirectory())
				continue;
			for (String dir2 : dir.list()) {
				dirPath = toInspect+"\\"+dir2;
				if (! this.isMounted(dirPath) && this.testDir(dirPath)){
					newDrives.add(dirPath);
				}
			}
		}
		return newDrives;
	}

	/**************************************************************************/
	//http://bugs.sun.com/bugdatabase/view_bug.do?bug_id=4939819
	public boolean testDir(String directoryName) {
		File directory = new File(directoryName);
		return (directory.isDirectory() && 
				directory.canRead());
	}
	
	/**************************************************************************/
	public String getShareName(String path) {
		String share = super.getShareName(path);
		if (path.length() == 3) {
			return path.replace("\\", "");
		}
		return share; 
	}
}
