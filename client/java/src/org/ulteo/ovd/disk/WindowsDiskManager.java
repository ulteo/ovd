/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David Lechevalier <david@ulteo.com> 2010 2011 2012
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
import java.io.IOException;
import java.io.InputStreamReader;
import java.util.ArrayList;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import javax.swing.filechooser.FileSystemView;

import org.apache.log4j.Logger;
import org.ulteo.rdp.rdpdr.OVDRdpdrChannel;


public class WindowsDiskManager extends DiskManager {
	private static Logger logger = Logger.getLogger(WindowsDiskManager.class);

	private final String netUseCommand = "net use";
	private final String tsProvider = "\\\\tsclient";
	private boolean useTSShareDiscoveryFailback = false;

	/**************************************************************************/
	public WindowsDiskManager(OVDRdpdrChannel diskChannel, boolean mountingMode) {
		super(diskChannel, mountingMode);
	}

	/**************************************************************************/
	private ArrayList<String> getTSSharesByProcess() {
		ArrayList<String> tsShares = new ArrayList<String>();

		Process p;
		BufferedReader input;
		try {
			p = Runtime.getRuntime().exec(this.netUseCommand);

			p.waitFor();
			
			if (p.exitValue() == 0) {
				input = new BufferedReader(new InputStreamReader(p.getInputStream()));
				String line = null;
				
				try {
					while ((line= input.readLine()) != null) {
						line = line.toLowerCase();
						Pattern reg = Pattern.compile(this.tsProvider+"\\S*", Pattern.CASE_INSENSITIVE);
						Matcher m = reg.matcher(line);
						while (m.find())
							tsShares.add(m.group());
					}
				}
				finally {
					input.close();
				}
			}
		} catch (IOException e) {
			logger.warn("Unable to get the entire TS drive list due to "+e.getMessage());
		} catch (InterruptedException e) {
			logger.warn("TS drive list creation stopped due to "+e.getMessage());
		}

		return tsShares;
	}
	
	/**************************************************************************/
	private ArrayList<String> getTSSharesByAPI() {
		return WNetApi.getTSShare();
	}

	/**************************************************************************/
	private ArrayList<String> getTSDrive() {
		if (! DiskManager.profile.isTSShareRedirectionActivated())
			return new ArrayList<String>();
		
		if (this.useTSShareDiscoveryFailback) {
			return getTSSharesByProcess();
		}

		try {
			return this.getTSSharesByAPI();
		}
		catch (UnsatisfiedLinkError e) {
			logger.warn("Unable to get sharelist by the Windows API due to "+e.getMessage());
			this.useTSShareDiscoveryFailback = true;
			return getTSSharesByProcess();
		}
	}
	
	/**************************************************************************/	
	private ArrayList<String> getLogicalDrive() {
		ArrayList<String> newDrives = new ArrayList<String>();
		if (this.mountingMode == MOUNTING_RESTRICTED)
			return newDrives;

		if (! DiskManager.profile.isRemoveableShareRedirectionActivated())
			return newDrives;
		
		File []drives = File.listRoots();
		String driveString;
		for (File drive : drives) {
			driveString = drive.getAbsolutePath();
			if (driveString.equalsIgnoreCase("A:\\"))
				continue;
			newDrives.add(driveString);
		}
		return newDrives;
	}
	
	/**************************************************************************/
	public ArrayList<String> getNewDrive() {
		ArrayList<String> newDrives = new ArrayList<String>();
		if (this.mountingMode == MOUNTING_RESTRICTED)
			return newDrives;

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

		for (String drive : getTSDrive()) {
			logger.debug("TSDrive "+drive);
			dir = new File(drive);

			if (! this.isMounted(drive) && this.testDir(drive)) {
				newDrives.add(drive);
			}
		}
		
		for (String toInspect : DiskManager.profile.getMonitoredDirectories())  {
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
			File drive = new File(path);
			String driveDisplayName = FileSystemView.getFileSystemView().getSystemDisplayName(drive);
			driveDisplayName = this.getValidName(driveDisplayName);
			if (driveDisplayName.equals(""))
				return this.getValidName(path.replace(":\\", ""));
			else
				return driveDisplayName;
		}
		return share; 
	}
}
