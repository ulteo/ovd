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
import java.util.Timer;

import org.apache.log4j.Logger;
import org.ulteo.rdp.rdpdr.OVDRdpdrChannel;


public abstract class DiskManager {
	private static Logger logger = Logger.getLogger(DiskManager.class);

	protected OVDRdpdrChannel rdpdrChannel;
	protected ArrayList<String> staticShares;
	protected ArrayList<String> directoryToInspect;
	private Timer diskAction;
	private boolean isStaticShareMounted = false;
	
	/**************************************************************************/
	public DiskManager(OVDRdpdrChannel diskChannel) {
		this.rdpdrChannel = diskChannel;
		this.staticShares = new ArrayList<String>();
		this.directoryToInspect = new ArrayList<String>();
		this.diskAction = new Timer();
	}
	
	/**************************************************************************/
	public void launch() {
		diskAction.schedule(new DiskUpdater(this), 0, 5000);
		
	}
	
	public void stop() {
		diskAction.cancel();
	}

	/**************************************************************************/
	abstract public boolean init();
	abstract public ArrayList<String> getNewDrive();
	

	/**************************************************************************/	
	public boolean testDir(String directoryName) {
		File directory = new File(directoryName);
		return (directory.isDirectory() && 
				directory.canWrite() && 
				directory.canRead());
	}

	/**************************************************************************/
	public String getShareName(String path) {
		File file = new File(path);
		return file.getName();
	}	
	
	/**************************************************************************/
	protected boolean addStaticDirectory(String directoryName) {
		if (new File(directoryName).isDirectory()) {
			this.staticShares.add(directoryName);
			return true;
		}
		return false;
	}
	
	/**************************************************************************/
	protected boolean addDirectoryToInspect(String directoryName) {
		this.directoryToInspect.add(directoryName);
		return false;
	}

	
	/**************************************************************************/
	public boolean isMounted(String path) {
		return this.rdpdrChannel.getDeviceFromPath(path) != null;
	}
	
	/**************************************************************************/
	public boolean mount(String sharePath) {
		String shareName = getShareName(sharePath);
		if (shareName.equals("") || !testDir(sharePath)) {
			return false;
		}
		return this.rdpdrChannel.mountNewDrive(shareName, sharePath);
	}

	/**************************************************************************/
	public boolean mount(String shareName, String sharePath) {
		if (shareName.equals("") || !testDir(sharePath)) {
			return false;
		}
		return this.rdpdrChannel.mountNewDrive(shareName, sharePath);
	}

	
	/**************************************************************************/
	public boolean unmount(String sharePath) {
		String shareName = getShareName(sharePath);
		if (shareName.equals("")) {
			return false;
		}
		return rdpdrChannel.unmountDrive(shareName, sharePath);
	}

	/**************************************************************************/
	public boolean unmount(String shareName, String sharePath) {
		if (shareName.equals("")) {
			return false;
		}
		return rdpdrChannel.unmountDrive(shareName, sharePath);
	}

	/**************************************************************************/
	public boolean isStaticShareMounted() {
		return isStaticShareMounted;
	}

	/**************************************************************************/
	public boolean mountStaticShare() {
		logger.debug("Mount static share");
		for (String share : staticShares) {
			logger.debug("Static share mounted: "+share);
			mount(share);
		}
		this.isStaticShareMounted = true;
		return true;
	}
}
