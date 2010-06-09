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

import net.propero.rdp.rdp5.rdpdr.RdpdrChannel;
import net.propero.rdp.rdp5.rdpdr.RdpdrDevice;

public class DiskUpdater extends TimerTask {
	private DiskManager diskManager = null;
	
	/**************************************************************************/
	public DiskUpdater(DiskManager dm) {
		this.diskManager = dm;
	}
	
	/**************************************************************************/
	public void run() {
		System.out.println("Update drive list");
		if (! diskManager.rdpdrChannel.isReady())
			return;
		System.out.println("Update drive list");

		String sharePath;
		
		if( !diskManager.isStaticShareMounted()) {
			diskManager.mountStaticShare();
		}
		System.out.println("Update drive list");

		for (RdpdrDevice device : RdpdrChannel.g_rdpdr_device) {
			if (device == null) {
				System.out.println("device is null");
				continue;
			}
			if (device.get_device_type() != RdpdrChannel.DEVICE_TYPE_DISK) {
				System.out.println("not a disk");
				continue;
			}
			if ( device.slotIsFree) {
				System.out.println("slot is free");
				continue;
			}
			
			sharePath = device.get_local_path();
			System.out.println("share path : "+sharePath);
			if (! diskManager.testDir(sharePath)) {
				System.out.println("unmount");
				diskManager.unmount(device.get_local_path());
			}
		}
		System.out.println("Update drive list");

		//search new drive to mount
		for (String drivePath : diskManager.getNewDrive()) {
			System.out.println("mount "+drivePath);
			if (! diskManager.isMounted(drivePath))
				diskManager.mount(drivePath);
		}
	}
}
