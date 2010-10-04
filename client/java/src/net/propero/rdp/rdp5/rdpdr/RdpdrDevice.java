/* RdpdrDevice.java
 * Component: ported from RdeskTop
 * 
 * Revision: $Revision: 1.0
 * Author: tomqq (hekong@gmail.com)
 * Date: 2009/05/16
 *
 * Copyright (c) tomqq
 *
 * Purpose: 
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307
 * USA
 * 
 * (See gpl.txt for details of the GNU General Public License.)
 * 
 */
package net.propero.rdp.rdp5.rdpdr;
import java.util.HashMap;
import java.util.Map;

import net.propero.rdp.RdpPacket;

public abstract class RdpdrDevice {
	//to implement "typedef struct rdpdr_device_info"
	public int device_type;
	public int handle;
	public String name;
	public String local_path;
	public boolean slotIsFree = false;
	public boolean connected = false;
	public Map pdevice_data = new HashMap(); //take of structured point func
	protected RdpdrChannel rdpdr = null;

	public RdpdrDevice(RdpdrChannel rdpdr_){
		this.rdpdr = rdpdr_;
	}

	public abstract int create(int device, int desired_access, int share_mode, int disposition, int flags_and_attributes, String filename,int[] result);
	public abstract int read(int handle, byte[] data, int length, int offset, int[] result); 
	public abstract int write(int handle, byte[] data, int length, int offset, int[] result);
	public abstract int close(int file);
	public abstract int device_control(int file, int request, RdpPacket in, RdpPacket out);

	//to implement "typedef struct rdpdr_device_info"
	public int get_device_type() {
		return device_type;
	}
	
	public void set_local_path(String x) {
		local_path = x;
	}
	public String get_local_path() {
		return local_path;
	}
	
	public void set_name(String x) {
		name = x;
	}
	public String get_name() {
		return name;
	}
	
	public void set_handle(int x) {
		handle = x;
	}
	public int get_handle() {
		return handle;
	}
}
