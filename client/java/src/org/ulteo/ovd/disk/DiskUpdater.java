/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David Lechavalier <david@ulteo.com> 2010-2011
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

import java.util.TimerTask;

import net.propero.rdp.rdp5.rdpdr.RdpdrChannel;
import net.propero.rdp.rdp5.rdpdr.RdpdrDevice;

import org.apache.log4j.Logger;

public class DiskUpdater extends TimerTask {
	Logger logger = Logger.getLogger(DiskUpdater.class);
	private DiskManager diskManager = null;
	
	/**************************************************************************/
	public DiskUpdater(DiskManager dm) {
		this.diskManager = dm;
	}
	
	/**************************************************************************/
	public void run() {
		String sharePath;
		
 		if (! this.diskManager.rdpdrChannel.isReady()) {
 			return;
 		}
		logger.debug("Update drive list");
		if( !this.diskManager.isStaticShareMounted()) {
			this.diskManager.mountStaticShare();
		}

		if (this.diskManager.getMountingMode() != DiskManager.ALL_MOUNTING_ALLOWED)
			return;

		for (RdpdrDevice device : this.diskManager.rdpdrChannel.g_rdpdr_device) {
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
			if (! this.diskManager.testDir(sharePath)) {
				logger.debug("Unmount : "+sharePath);
				this.diskManager.unmount(device.get_name(), device.get_local_path());
			}
		}

 		if (this.diskManager.rdpdrChannel.g_num_devices >= RdpdrChannel.RDPDR_MAX_DEVICES) {
 			return;
 		}
		
		//search new drive to mount
		for (String drivePath : this.diskManager.getNewDrive()) {
			logger.debug("Mount "+drivePath);
			if (! this.diskManager.isMounted(drivePath))
				this.diskManager.mount(drivePath);
		}
	}
}
