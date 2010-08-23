/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author David Lechevalier <david@ulteo.com> 2010
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
package org.ulteo.rdp.rdpdr;

import org.apache.log4j.Logger;

import net.propero.rdp.Common;
import net.propero.rdp.Options;
import net.propero.rdp.RdpPacket_Localised;
import net.propero.rdp.rdp5.rdpdr.Disk;
import net.propero.rdp.rdp5.rdpdr.RdpdrChannel;
import net.propero.rdp.rdp5.rdpdr.RdpdrDevice;

public class OVDRdpdrChannel extends RdpdrChannel {
	private static Logger logger = Logger.getLogger(OVDRdpdrChannel.class);
	private boolean ready = false;	

	public OVDRdpdrChannel(Options opt, Common common) {
		super(opt, common);
	}
	
	public RdpdrDevice getDeviceFromPath(String path) {
		for (RdpdrDevice device : g_rdpdr_device) {
			if (device == null)
				continue;
				
			if (device.get_device_type() != RdpdrChannel.DEVICE_TYPE_DISK)
				continue;

			if (device.slotIsFree )
				continue;
		
			if (device.get_local_path().equals(path) )
				return device;
		}
		return null;
	}
	
	public boolean isReady() {
		return this.ready;
	}
	
	public boolean mountNewDrive(String name, String path) {
		if (getDeviceFromPath(path) != null) {
			return false;
		}

		logger.info("mount a new drive "+name+" => "+path);
		String magic = "rDAD";
		int index = getNextFreeSlot();
		Disk d = new Disk(path, name);
		
		RdpPacket_Localised s;
		this.register(d);
		
		s = new RdpPacket_Localised(30);
		s.out_uint8p(magic, 4);
		s.setLittleEndian32(1);
		
		
		s.setLittleEndian32(d.device_type);
		s.setLittleEndian32(index); /* RDP Device ID */
		s.out_uint8p(d.name, 8);
		s.setBigEndian32(0);
		s.markEnd();
		try {
			this.send_packet(s);
		} catch (Exception e) {
			e.printStackTrace();
			return false;
		}
		return true;
	}
	
	public boolean unmountDrive(String name, String path) {
		logger.info("unmount the drive "+name+ " => "+path);
		String magic = "rDMD";
		int id = -1;

		for (int i = 0 ; i< RDPDR_MAX_DEVICES ; i++) {
			RdpdrDevice dev = g_rdpdr_device[i];
			if (dev.name.equals(name) && dev.local_path.equals(path)) {
				id = i;
				break;
			}
		}
		if (id == -1) {
			logger.warn("Unable to find the share name");
			return false;
		}

		RdpPacket_Localised s;
		s = new RdpPacket_Localised(30);
		s.out_uint8p(magic, 4);
		s.setLittleEndian32(1);		/* number of device */
		
		s.setLittleEndian32(id);
		s.markEnd();
		try {
			this.send_packet(s);
		} 
		catch (Exception e) {
			e.printStackTrace();
			return false;
		}
		g_rdpdr_device[id].slotIsFree = true;
		g_rdpdr_device[id].set_name("");
		g_rdpdr_device[id].set_local_path("");

		return true;
	}

	private int getNextFreeSlot() {
		for (int i=0; i< RDPDR_MAX_DEVICES ; i++) {
			if (g_rdpdr_device[i] == null)
				return i;
			if (g_rdpdr_device[i].slotIsFree)
				return i;
		}
		return -1;
	}

	public void rdpdr_send_available() {
		this.ready = true;
		super.rdpdr_send_available();
	}
	
 	public boolean register(RdpdrDevice v) {
 		int index = getNextFreeSlot();
 		if (index == -1) {
 			logger.warn("the max number of device is reached");
 			return false;
 		}
		OVDRdpdrChannel.g_rdpdr_device[index] = v;
		return true;
 	}

}
