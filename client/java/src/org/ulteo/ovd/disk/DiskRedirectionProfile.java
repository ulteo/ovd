/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David Lechevalier <david@ulteo.com> 2011
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

import java.util.ArrayList;

import org.ulteo.ovd.integrated.Constants;


public class DiskRedirectionProfile {
	protected boolean redirectTSShare = false;
	protected boolean redirectRemoveableShare = false;
	protected ArrayList<String> staticShares = null;
	protected ArrayList<String> directoryToInspect = null;
	
	public DiskRedirectionProfile() {
		this.staticShares = new ArrayList<String>();
		this.directoryToInspect = new ArrayList<String>();
		
		this.addStaticShare(Constants.PATH_DOCUMENT);
		this.addStaticShare(Constants.PATH_DESKTOP);

		this.redirectTSShare = false;
		this.redirectRemoveableShare = true;
	}
	
	public void setTSShareRedirection(boolean value) {
		this.redirectTSShare = value;
	}

	public boolean isTSShareRedirectionActivated() {
		return this.redirectTSShare;
	}

	public void setRemoveableShareRedirection(boolean value) {
		this.redirectRemoveableShare = value;
	}

	public boolean isRemoveableShareRedirectionActivated() {
		return this.redirectRemoveableShare;
	}
	
	public void addStaticShare(String shareName) {
		this.staticShares.add(shareName);
	}
	
	public ArrayList<String> getStaticShares() {
		return this.staticShares;
	}
	
	public void clearStaticShares() {
		this.staticShares.clear();
	}
	
	public void addMonitoredDirectory(String directory) {
		this.directoryToInspect.add(directory);
	}
	
	public ArrayList<String> getMonitoredDirectories() {
		return this.directoryToInspect;
	}
	
	public void clearMonitoredDirectories() {
		this.directoryToInspect.clear();
	}
	
}
