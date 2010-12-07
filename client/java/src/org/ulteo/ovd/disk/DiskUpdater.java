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
import java.util.TimerTask;

import org.apache.log4j.Logger;

import net.propero.rdp.rdp5.rdpdr.RdpdrChannel;
import net.propero.rdp.rdp5.rdpdr.RdpdrDevice;

public class DiskUpdater extends TimerTask {
	Logger logger = Logger.getLogger(DiskUpdater.class);
	private DiskManager diskManager = null;
	private RdpdrChannel rdpdr = null;
	
	/**************************************************************************/
	public DiskUpdater(DiskManager dm, RdpdrChannel rdpdr_) {
		this.diskManager = dm;
		this.rdpdr = rdpdr_;
	}
	
	/**************************************************************************/
	public void run() {
		String sharePath;
		
 		if (! diskManager.rdpdrChannel.isReady()) {
 			return;
 		}
		logger.debug("Update drive list");
		if( !diskManager.isStaticShareMounted()) {
			diskManager.mountStaticShare();
		}

		for (RdpdrDevice device : this.rdpdr.g_rdpdr_device) {
			if (device == null) {
				continue;
			}
			if (device.get_device_type() != RdpdrChannel.DEVICE_TYPE_DISK) {
				logger.debug(device.get_name()+" is not a disk");
				continue;
			}
			if ( device.slotIsFree) {
				logger.debug(device.get_name()+" slot is free");
				continue;
			}
			
			sharePath = device.get_local_path();
			logger.debug("Share path : "+sharePath);
			if (! diskManager.testDir(sharePath)) {
				logger.debug("Unmount : "+sharePath);
				diskManager.unmount(device.get_name(), device.get_local_path());
			}
		}

		//search new drive to mount
		for (String drivePath : diskManager.getNewDrive()) {
			logger.debug("Mount "+drivePath);
			if (! diskManager.isMounted(drivePath))
				diskManager.mount(drivePath);
		}
	}
}
