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

import java.io.BufferedReader;
import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.IOException;
import java.util.ArrayList;
import java.util.Properties;

import org.apache.log4j.Logger;
import org.ulteo.ovd.integrated.Constants;
import org.ulteo.ovd.integrated.LinuxPaths;
import org.ulteo.rdp.rdpdr.OVDRdpdrChannel;


public class LinuxDiskManager extends DiskManager {
	private static Logger logger = Logger.getLogger(LinuxDiskManager.class);

	private static String mtabFilename = "/etc/mtab";
	private ArrayList<String> mtabList = null;
	
	
	/**************************************************************************/
	public LinuxDiskManager(OVDRdpdrChannel diskChannel) {
		super(diskChannel);
		this.mtabList = new ArrayList<String>();
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
	public boolean testDir(String directoryName) {
		File directory = new File(directoryName);
		if (directory.isDirectory() && directory.canRead()) {
			for(String entry : this.mtabList) {
				if (entry.equals(directoryName))
					return true;
			}
		}
		return false;
	}
	
	/**************************************************************************/
	private void updateMtab() {
		this.mtabList.clear();
		File file = new File(mtabFilename);
		BufferedReader br = null;
		FileReader fr = null;
		
		try {
			fr = new FileReader(file);
			br = new BufferedReader(fr);

			String line = br.readLine();
			while(line != null) {
				String[] lineContent = line.split(" ");
				if (lineContent.length > 2)
					this.mtabList.add(lineContent[1]);
				line = br.readLine();
			}
			br.close();
			fr.close();
		}
		catch (FileNotFoundException e) {
			logger.warn("Unable to find "+mtabFilename);
		}
		catch (IOException e) {
			logger.warn("Error while reading "+mtabFilename);
		}
	}
	
	/**************************************************************************/
	public ArrayList<String> getNewDrive() {
		ArrayList<String> newDrives = new ArrayList<String>();
		String dirPath;
		File dir = null;
		
		updateMtab();
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
