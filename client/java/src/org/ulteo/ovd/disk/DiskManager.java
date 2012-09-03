/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David Lechevalier <david@ulteo.com> 2010 2012
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
import java.nio.charset.Charset;
import java.nio.charset.CharsetEncoder;
import java.util.ArrayList;
import java.util.Timer;

import org.apache.log4j.Logger;
import org.ulteo.rdp.rdpdr.OVDRdpdrChannel;


public abstract class DiskManager {
	public static final boolean ALL_MOUNTING_ALLOWED = true;
	public static final boolean MOUNTING_RESTRICTED = false;
	public static final String DEFAULT_SHARE_PREFFIX = "share ";
	
	private static Logger logger = Logger.getLogger(DiskManager.class);
	private static String invalidCharacter = ":\\/|*?<>";

	protected OVDRdpdrChannel rdpdrChannel;
	protected static DiskRedirectionProfile profile;
	private Timer diskAction;
	private boolean isStaticShareMounted = false;
	protected boolean mountingMode = MOUNTING_RESTRICTED;
	private int shareCount;
	
	/**************************************************************************/
	public DiskManager(OVDRdpdrChannel diskChannel, boolean mountingMode_) {
		this.rdpdrChannel = diskChannel;
		this.mountingMode = mountingMode_;
		this.shareCount = 1;
		
		if (DiskManager.profile == null)
			DiskManager.profile = new DiskRedirectionProfile();
	}
	
	/**************************************************************************/
	public void launch() {
		this.diskAction = new Timer();
		this.diskAction.schedule(new DiskUpdater(this), 0, 5000);
	}
	
	public void stop() {
		if (this.diskAction == null)
			return;
		
		this.diskAction.cancel();
		this.diskAction = null;
	}

	/**************************************************************************/
	public static void setDiskProfile(DiskRedirectionProfile profile) {
		DiskManager.profile = profile;
	}
	
	abstract public ArrayList<String> getNewDrive();

	public boolean getMountingMode() {
		return this.mountingMode;
	}

	public String getValidName(String name) {
		try {
			CharsetEncoder encoder = Charset.forName("CP1252").newEncoder();

			if (! encoder.canEncode(name)) {
				return "";
			}
		}
		catch (Exception e) {
			logger.debug("Unable to obtain a valid share name for '"+name+"': "+e.getMessage());
			return "";
		}
		
		char[] characters = invalidCharacter.toCharArray();
		for (int i=0 ; i< characters.length ; i++) {
			name = name.replace("\"", "'");
			name = name.replace(""+characters[i], "_");
		}
		return name;
	}
	
	/**************************************************************************/	
	public boolean testDir(String directoryName) {
		File directory = new File(directoryName);
		return (directory.isDirectory() && 
				directory.canRead());
	}

	/**************************************************************************/
	public String getShareName(String path) {
		File file = new File(path);
		String shareName = this.getValidName(file.getName());
		
		if (shareName.equals(""))
			return DEFAULT_SHARE_PREFFIX + this.shareCount++;
		
		return shareName;
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
		for (String share : DiskManager.profile.getStaticShares()) {
			logger.debug("Static share mounted: "+share);
			mount(share);
		}
		this.isStaticShareMounted = true;
		return true;
	}
}
