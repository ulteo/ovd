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

import org.apache.log4j.Logger;
import org.ulteo.ovd.integrated.Constants;
import org.ulteo.rdp.rdpdr.OVDRdpdrChannel;


public class LinuxDiskManager extends DiskManager {
	private static Logger logger = Logger.getLogger(LinuxDiskManager.class);

	private static String mtabFilename = "/etc/mtab";
	private static final String rdpdrFolderDirectory = ".rdp_drive";
	private ArrayList<String> mtabList = null;
	private final String[] removeableShare = {
			"/media", 
			"/mnt", 
			Constants.HOMEDIR + "/.gvfs", // Add support to gnome shares
	};
	
	
	/**************************************************************************/
	public LinuxDiskManager(OVDRdpdrChannel diskChannel, boolean mountingMode) {
		super(diskChannel, mountingMode);
		this.mtabList = new ArrayList<String>();
	}
	
	/**************************************************************************/
	private ArrayList<String> getRdpShare() {
		String rdpdrDirectory = Constants.HOMEDIR + File.separator + LinuxDiskManager.rdpdrFolderDirectory;
		ArrayList<String> result = new ArrayList<String>();
		File dir = new File(rdpdrDirectory);
		String sharePath;
		
		if (!DiskManager.profile.isTSShareRedirectionActivated())
			return result;
		
		if (! dir.exists() || !dir.isDirectory())
			return result;
		
		for (String shareName : dir.list()) {
			sharePath = rdpdrDirectory + File.separator + shareName;
			if (! this.isMounted(sharePath) && this.testDir(sharePath))
				result.add(sharePath);
		}
		
		return result;
	}
	
	/**************************************************************************/
	private ArrayList<String> getRemovableShares() {
		ArrayList<String> result = new ArrayList<String>();
		String sharePath;
		
		if (!DiskManager.profile.isRemoveableShareRedirectionActivated())
			return result;
		
		for (int i = 0 ; i < this.removeableShare.length ; i++) {
			File dir = new File(this.removeableShare[i]);
			if (! dir.exists() || ! dir.isDirectory())
				continue;
	
			for (String shareName : dir.list()) {
				sharePath = this.removeableShare[i] + File.separator + shareName;
				
				if (! this.isMounted(sharePath) && this.testDir(sharePath))
					result.add(sharePath);
			}
		}
		
		return result;
	}
	
	/**************************************************************************/
	private void updateMtab() {
		this.mtabList.clear();

		if (this.mountingMode == MOUNTING_RESTRICTED)
			return;

		File file = new File(mtabFilename);
		BufferedReader br = null;
		FileReader fr = null;
		
		try {
			fr = new FileReader(file);
			br = new BufferedReader(fr);

			String line = br.readLine();
			while(line != null) {
				String[] lineContent = line.split(" ");
				// In the mtab file, space are replaced by "\040"
				String path =  lineContent[1].replace("\\040", " ");
				if (lineContent.length > 2)
					this.mtabList.add(path);
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
		if (this.mountingMode == MOUNTING_RESTRICTED)
			return newDrives;
		
		String dirPath;
		File dir = null;
		
		updateMtab();
		logger.debug("Searching for new drive");
		for (String toInspect : DiskManager.profile.getMonitoredDirectories()) {
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
		
		for (String drive : getRdpShare()) {
			logger.debug("Drive "+drive);
			dir = new File(drive);
			
			if (! this.isMounted(drive) && this.testDir(drive))
				newDrives.add(drive);
		}
		
		for (String drive : this.getRemovableShares()) {
			logger.debug("Drive "+drive);

			if (! this.isMounted(drive) && this.testDir(drive))
				newDrives.add(drive);
		}
		
		return newDrives;
	}
}
