/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2010, 2011, 2012
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

import java.io.IOException;
import java.security.InvalidParameterException;
import java.util.List;
import java.util.UUID;
import java.util.concurrent.CopyOnWriteArrayList;

import org.ulteo.Logger;

import net.propero.rdp.Common;
import net.propero.rdp.Options;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpPacket;
import net.propero.rdp.crypto.CryptoException;
import net.propero.rdp.RdpPacket_Localised;
import net.propero.rdp.rdp5.rdpdr.RdpdrChannel;
import net.propero.rdp.rdp5.rdpdr.RdpdrDevice;
import org.ulteo.ovd.integrated.Constants;

public class OVDRdpdrChannel extends RdpdrChannel {
	
	private static final int CAP_DRIVE_TYPE = 0x0004;
	private static final int DRIVE_CAPABILITY_VERSION_01 = 0x00000001;
	private static final int DRIVE_CAPABILITY_VERSION_02 = 0x00000002;

	private static final int CAP_GENERAL_TYPE = 0x00000001;
	private static final int GENERAL_CAPABILITY_VERSION_01 = 0x00000001;
	private static final int GENERAL_CAPABILITY_VERSION_02 = 0x00000002;
	private static final int RDPDR_DEVICE_REMOVE_PDUS = 0x00000001;
	private static final int RDPDR_CLIENT_DISPLAY_NAME_PDU = 0x00000001;
	private static final int RDPDR_USER_LOGGEDON_PDU = 0x00000004;
	

	private boolean ready = false;	

	private boolean supportUnicode = false;
	private int drive_version = DRIVE_CAPABILITY_VERSION_01;

	private List<DeviceListener> listeners = null;

	public OVDRdpdrChannel(Options opt, Common common) {
		super(opt, common);

		this.listeners = new CopyOnWriteArrayList<DeviceListener>();
	}

	public void process( RdpPacket data ) throws RdesktopException, IOException, CryptoException {
			int handle;
			int magic[] = new int[4];
				magic[0] = data.get8();
				magic[1] = data.get8();
				magic[2] = data.get8();
				magic[3] = data.get8();

				if ((magic[0] == 'r') && (magic[1] == 'D')) {
					if ((magic[2] == 'R') && (magic[3] == 'I')) {
						rdpdr_process_irp(data);
						return;
					}
					if ((magic[2] == 'n') && (magic[3] == 'I')) {
						rdpdr_send_connect();
						rdpdr_send_name();
						return;
					}
					if ((magic[2] == 'C') && (magic[3] == 'C')) {
						/* connect from server */

						return;
					}
					if ((magic[2] == 'r') && (magic[3] == 'd')) {
						/* connect to a specific resource */
						handle = data.getLittleEndian32();
						int ntStatus = data.getLittleEndian32();

						if (handle < 0 || handle >= this.g_rdpdr_device.length) {
							Logger.error("RDPDR: Bad handle: "+handle+" (status: "+ntStatus+")");
							return;
						}
						
						if (ntStatus != 0x00000000) {
							Logger.error("RDPDR: Server failed to connect to resource "+handle);

							this.unregister(handle);

							for (DeviceListener l : this.listeners) {
								l.deviceFailed(this.g_rdpdr_device[handle]);
							}
							return;
						}
						if (g_rdpdr_device[handle] != null) {
							if (g_rdpdr_device[handle].device_type == DEVICE_TYPE_DISK)
								Logger.debug("RDPDR: Server connected to disk resource ["+g_rdpdr_device[handle].local_path+"]");

							if (g_rdpdr_device[handle].device_type == DEVICE_TYPE_PRINTER)
								Logger.debug("RDPDR: Server connected to printer resource ["+((OVDPrinter)g_rdpdr_device[handle]).printer_name+"]");
						}
							

						this.g_rdpdr_device[handle].connected = true;

						for (DeviceListener l : this.listeners) {
							l.deviceConnected(this.g_rdpdr_device[handle]);
						}
						return;
					}
					if ((magic[2] == 'P') && (magic[3] == 'S')) {
						/* server capability */
						rdpdr_process_server_capability(data);
						rdpdr_send_clientcapabilty();
						//rdpdr_send_available();
						return;
					}
					if ((magic[2] == 'L') && (magic[3] == 'U')) {
						Logger.info("Ready to send disk drive");
						rdpdr_send_available();
						return;
					}
				}
				if ((magic[0] == 'R') && (magic[1] == 'P')) {
					if ((magic[2] == 'C') && (magic[3] == 'P')){
						return;
					}
				}
				//Unknown protocol
				Logger.warn("Unkown protocol\n: RDPDR packet type "+ magic[0]+ magic[1]+ magic[2]+ magic[3]);
		}
		
	public void rdpdr_send_clientcapabilty() {
		String magic = "rDPC";
		RdpPacket_Localised s = new RdpPacket_Localised(0x54);
		/* header */
		s.out_uint8p(magic, 4);

		/* message */
		s.setLittleEndian16(5);   /* number of capabilities */
		s.incrementPosition(2);   /* padding */
		
		s.setLittleEndian16(CAP_GENERAL_TYPE);
		s.setLittleEndian16(44);   /*length*/
		s.setLittleEndian32(GENERAL_CAPABILITY_VERSION_02);
		s.setLittleEndian32(0x02);   /* os type (ignored)*/
		s.setLittleEndian32(0);   /* os version */
		s.setLittleEndian16(1);   /* protocol version major */
		s.setLittleEndian16(0x0a);   /* protocol version minor */
		s.setLittleEndian32(0xFFFF); /* ioCode1 */
		s.setLittleEndian32(0);      /* ioCode2 */
		s.setLittleEndian32(RDPDR_DEVICE_REMOVE_PDUS| RDPDR_CLIENT_DISPLAY_NAME_PDU|RDPDR_USER_LOGGEDON_PDU );      /* extendedPDU */
		s.setLittleEndian32(0);      /* Extraflags1 */
		s.setLittleEndian32(0);      /* Extraflags2 */
		s.setLittleEndian32(0);      /* special caps */
		s.setLittleEndian16(2); /* second */
		s.setLittleEndian16(8); /* length */
		s.setLittleEndian32(1);
		s.setLittleEndian16(3);	/* third */
		s.setLittleEndian16(8);	/* length */
		s.setLittleEndian32(1);
		s.setLittleEndian16(CAP_DRIVE_TYPE);	/* fourth */
		s.setLittleEndian16(0);	/* length */
		s.setLittleEndian32(this.drive_version);
		s.setLittleEndian16(5);	/* fifth */
		s.setLittleEndian16(8);	/* length */
		s.setLittleEndian32(1);
		
		s.markEnd();
		
		try {
			this.send_packet(s);
		} catch (Exception e) {
			e.printStackTrace();
		}
	}
	
	private void rdpdr_process_server_capability(RdpPacket s) {
		//Get para from package
		int numberOfCapabilities = s.getLittleEndian16();
		s.incrementPosition(2);	

		for (int i=0 ; i<numberOfCapabilities ; i++) {
			//process capability header
			int capabilityType = s.getLittleEndian16();
			int capabilityLength = s.getLittleEndian16();
			int capabilityVersion = s.getLittleEndian32();
			switch (capabilityType) {
			case CAP_DRIVE_TYPE:
				if (capabilityVersion == DRIVE_CAPABILITY_VERSION_01) {
					this.drive_version = 1;
				}
				if (capabilityVersion == DRIVE_CAPABILITY_VERSION_02) {
					this.drive_version = 2;
					this.supportUnicode = true;
				}
				break;
			default:
				break;
			}
			s.incrementPosition(capabilityLength-8);
		}
	}

	public RdpdrDevice getDeviceFromName(String name) {
		for (RdpdrDevice device : this.g_rdpdr_device) {
			if (device == null)
				continue;

			if (device.get_device_type() != RdpdrChannel.DEVICE_TYPE_DISK)
				continue;

			if (device.slotIsFree)
				continue;

			if (device.get_name().equals(name) )
				return device;
		}
		return null;
	}

	public RdpdrDevice getDeviceFromPath(String path) {
		for (RdpdrDevice device : this.g_rdpdr_device) {
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

	public OVDRdpdrDisk getDeviceFromFile(String file) throws InvalidParameterException {
		int pos = file.lastIndexOf(Constants.FILE_SEPARATOR);

		if (pos == -1)
			throw new InvalidParameterException("'"+file+"' is not a path");

		if (pos == (file.length() - 1))
			throw new InvalidParameterException("'"+file+"' is a directory not a file");

		String path = file.substring(0, pos);
		for (RdpdrDevice device : this.g_rdpdr_device) {
			if (device == null)
				continue;

			if (device.get_device_type() != RdpdrChannel.DEVICE_TYPE_DISK)
				continue;

			if (device.slotIsFree )
				continue;

			String share_local_path = device.get_local_path();
			if (share_local_path.endsWith(Constants.FILE_SEPARATOR))
				share_local_path = share_local_path.substring(0, share_local_path.length() - 1);
			
			if (share_local_path.equals(path) || path.startsWith(share_local_path + Constants.FILE_SEPARATOR))
				return (OVDRdpdrDisk) device;
		}
		
		return null;
	}

	public OVDRdpdrDisk mountDeviceFromFile(String file) {
		int pos = file.lastIndexOf(Constants.FILE_SEPARATOR);
		String path = file.substring(0, pos);
		String name = "tmp"+UUID.randomUUID();

		if (this.mountNewDrive(name, path)) {
			return (OVDRdpdrDisk) this.getDeviceFromName(name);
		}

		Logger.error("Failed to mount drive '"+name+"' ==> '"+path+"'");
		return null;
	}
	
	public boolean isReady() {
		return this.ready;
	}
	
	public boolean mountNewDrive(String name, String path) {
		if (getDeviceFromPath(path) != null) {
			return false;
		}

		int unicodeStrLength = 0;
		byte[] unicodeStr = null;
		if (supportUnicode) {
			unicodeStr = getUnicodeDriveName(name+"\0");
			if (unicodeStr == null)
				this.supportUnicode = false;
			else
				unicodeStrLength = unicodeStr.length;
		}
		
		logger.info("mount a new drive "+name+" => "+path);
		String magic = "rDAD";
		int index = getNextFreeSlot();
		if (index < 0) {
			return false;
		}
		
		OVDRdpdrDisk d = new OVDRdpdrDisk(this, path, name);
		
		RdpPacket_Localised s;
		this.register(d);
		
		s = new RdpPacket_Localised(32+unicodeStrLength);
		s.out_uint8p(magic, 4);
		s.setLittleEndian32(1);   /* deviceCount */
		
		
		s.setLittleEndian32(d.device_type);
		s.setLittleEndian32(index); /* RDP Device ID */

		s.out_uint8p(name, 8);
		
		if (supportUnicode) {
			s.setLittleEndian32(unicodeStrLength);
			s.copyFromByteArray(unicodeStr, 0, s.getPosition(),unicodeStrLength);
			s.incrementPosition(unicodeStrLength);
		}
		else {
			s.setBigEndian32(0);
		}

		s.markEnd();
		try {
			this.send_packet(s);
		} catch (Exception e) {
			e.printStackTrace();
			return false;
		}
		return true;
	}
	
	public boolean mountNewPrinter(OVDPrinter printer) {
		logger.info("mount a new printer "+printer.name);
		String magic = "rDAD";
		int index = getNextFreeSlot();
		if (index < 0) {
			return false;
		}
		
		String printerName = printer.get_display_name();
		int driverlen = 2 * printer.driver.length() + 2;
		int printerlen = 2 * printerName.length() + 2;
		int bloblen = printer.bloblen;
		
		RdpPacket_Localised s;
		this.register(printer);
		
		s = new RdpPacket_Localised(52 + driverlen + printerlen);
		s.out_uint8p(magic, 4);
		s.setLittleEndian32(1);   /* deviceCount */
		
		
		s.setLittleEndian32(printer.device_type);
		s.setLittleEndian32(index); /* RDP Device ID */

		s.out_uint8p(printer.name, 8);
		
		s.setLittleEndian32(24 + driverlen + printerlen + bloblen);
		if (printer.default_printer)
			s.setLittleEndian32(FLAG_DEFAULTPRINTER);
		else
			s.setLittleEndian32(0);
		s.incrementPosition(8);
		s.setLittleEndian32(driverlen);
		s.setLittleEndian32(printerlen);
		s.setLittleEndian32(bloblen);
		s.outUnicodeString(printer.driver, driverlen - 2);
		s.outUnicodeString(printerName, printerlen - 2);

		
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
			RdpdrDevice dev = this.g_rdpdr_device[i];
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

		this.unregister(id);

		return true;
	}

	private int getNextFreeSlot() {
		for (int i=0; i< RDPDR_MAX_DEVICES ; i++) {
			if (this.g_rdpdr_device[i] == null)
				return i;
			if (this.g_rdpdr_device[i].get_device_type() == DEVICE_TYPE_PRINTER)
				continue;
			if (this.g_rdpdr_device[i].slotIsFree)
				return i;
		}
		return -1;
	}

	public void rdpdr_send_available() {
		this.ready = true;
	}
	
 	public boolean register(RdpdrDevice v) {
 		int index = getNextFreeSlot();
 		if (index == -1) {
 			logger.warn("the max number of device is reached");
 			return false;
 		}
		this.g_rdpdr_device[index] = v;
		g_num_devices++;
		return true;
 	}

	public void unregister(int id) {
		RdpdrDevice device = null;
 		try {
			device = this.g_rdpdr_device[id];
		} catch (Exception ex) {
			Logger.error("RDPDR: "+id+" is not a correct device id");
			return;
		}

		this.g_num_devices--;

		device.connected = false;
		device.slotIsFree = true;
		device.set_name("");
		device.set_local_path("");
 	}

	public List<DeviceListener> getDeviceListeners() {
		return this.listeners;
	}

	public void addDeviceListener(DeviceListener listener) {
		this.listeners.add(listener);
	}

	public void removeDeviceListener(DeviceListener listener) {
		this.listeners.remove(listener);
	}
}
