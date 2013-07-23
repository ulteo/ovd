/* RdpdrChannel.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.0
 * Author: tomqq (hekong@gmail.com)
 * Author David Lechevalier <david@ulteo.com> 2011 2012
 * Date: 2009/05/16
 *
 * Copyright (c) tomqq
 * Copyright (C) 2011-2012 Ulteo SAS
 *
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

import java.io.IOException;
import java.io.UnsupportedEncodingException;
import java.net.InetAddress;
import java.nio.charset.Charset;
import java.nio.charset.UnsupportedCharsetException;
import java.util.LinkedList;

import org.apache.log4j.Logger;

import net.propero.rdp.Options;
import net.propero.rdp.Common;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpPacket;
import net.propero.rdp.RdpPacket_Localised;
import net.propero.rdp.crypto.CryptoException;
import net.propero.rdp.rdp5.VChannel;
import net.propero.rdp.rdp5.VChannels;

public class RdpdrChannel extends VChannel {
	public static final int IRP_MJ_CREATE                    = 0x00;
	public static final int IRP_MJ_CLOSE                     = 0x02;
	public static final int IRP_MJ_READ	                     = 0x03;
	public static final int IRP_MJ_WRITE                     = 0x04;
	public static final int	IRP_MJ_QUERY_INFORMATION         = 0x05;
	public static final int IRP_MJ_SET_INFORMATION           = 0x06;
	public static final int IRP_MJ_QUERY_VOLUME_INFORMATION	 = 0x0a;
	public static final int IRP_MJ_DIRECTORY_CONTROL         = 0x0c;
	public static final int IRP_MJ_DEVICE_CONTROL            = 0x0e;
	public static final int IRP_MJ_LOCK_CONTROL              = 0x11;

	public static final int IRP_MN_QUERY_DIRECTORY           = 0x01;
	public static final int IRP_MN_NOTIFY_CHANGE_DIRECTORY   = 0x02;
	
	public static final int RDPDR_MAX_DEVICES                = 0x80;
	public static final int DEVICE_TYPE_DISK                 = 0x08;
	public static final int DEVICE_TYPE_SERIAL               = 0x01;
	public static final int DEVICE_TYPE_PARALLEL             = 0x02;
	public static final int DEVICE_TYPE_PRINTER              = 0x04;
	public static final int DEVICE_TYPE_SCARD                = 0x20;
	
	public static final int PATH_MAX = 256;
	public static final int STATUS_INVALID_DEVICE_REQUEST = 0xc0000010;
	public static final int STATUS_PENDING                = 0x00000103;
	public static final int STATUS_INVALID_HANDLE         = 0xc0000008;
	public static final int STATUS_CANCELLED              = 0xc0000120;
	public static final int STATUS_INVALID_PARAMETER	  = 0xc000000d;
	public static final int STATUS_SUCCESS			      = 0x00000000;
	public static final int STATUS_NOT_SUPPORTED          = 0xc00000bb;
	public static final int FLAG_DEFAULTPRINTER           = 0xc0000002;
	
	public int g_num_devices = 0;
	LinkedList g_iorequest = new LinkedList();
	public RdpdrDevice[] g_rdpdr_device;
	protected static Logger logger = Logger.getLogger(RdpdrChannel.class);
	
	public RdpdrChannel(Options opt, Common common) {
		super(opt, common);
		this.g_rdpdr_device = new RdpdrDevice[RDPDR_MAX_DEVICES];
		g_num_devices = 0;
	}
	
	public int flags() {
		return VChannels.CHANNEL_OPTION_INITIALIZED | VChannels.CHANNEL_OPTION_COMPRESS_RDP;
	}
	
	public String name() {
		return "rdpdr";
	}
	int sequence_count = 0;

	public byte[] getUnicodeDriveName(String str) {
		byte[] unicodeStr = null;
		
		try {
			unicodeStr = str.getBytes("CP1252");
		} catch (UnsupportedEncodingException e) {
			logger.debug(e.getMessage());
			logger.info("CP1252 is not supported by your JVM");
			return null;
		}
		
		return unicodeStr;
	}
	
	public void process( RdpPacket data ) throws RdesktopException, IOException, CryptoException {
		int handle;
		int magic[] = new int[4];
			magic[0] = data.get8();
			magic[1] = data.get8();
			magic[2] = data.get8();
			magic[3] = data.get8();
			if ((magic[0] == 'r') && (magic[1] == 'D')){
				if ((magic[2] == 'R') && (magic[3] == 'I')){
					rdpdr_process_irp(data);
					return;
				}
				if ((magic[2] == 'n') && (magic[3] == 'I')){
					rdpdr_send_connect();
					rdpdr_send_name();
					return;
				}
				if ((magic[2] == 'C') && (magic[3] == 'C')){
					/* connect from server */
					rdpdr_send_clientcapabilty();
					rdpdr_send_available();
					return;
				}
				if ((magic[2] == 'r') && (magic[3] == 'd')){
					/* connect to a specific resource */
					handle = data.getBigEndian32();
					System.out.print("RDPDR: Server connected to resource "+handle+"\n");
					return;
				}
				if ((magic[2] == 'P') && (magic[3] == 'S')){
					/* server capability */
					return;
				}
			}
			if ((magic[0] == 'R') && (magic[1] == 'P')){
				if ((magic[2] == 'C') && (magic[3] == 'P')){
//					System.out.println("printercache_process,type="+data.getLittleEndian32());
					//printercache_process(s);
					return;
				}
			}
			//Unknown protocol
			System.out.print("Unkown protocol\n: RDPDR packet type "+ magic[0]+ magic[1]+ magic[2]+ magic[3]+"\n");
	}
	
	public void rdpdr_send_connect() {
		String magic = "rDCC";
		RdpPacket_Localised s;

		s = new RdpPacket_Localised( 12 );
		s.out_uint8p(magic, 4);
		s.setLittleEndian16(1);
		s.setLittleEndian16(5);
		s.setBigEndian32(0x815ed39d);
		s.markEnd();
		
		try {
			this.send_packet(s);
		} catch (Exception e) {
			e.printStackTrace();
		}

	}
		
	public void rdpdr_send_name() {
			String magic = "rDNC";
			RdpPacket_Localised s;
			int hostlen;

			String g_rdpdr_clientname = "";
			try {
				InetAddress localMachine = InetAddress.getLocalHost();
				g_rdpdr_clientname = localMachine.getHostName();
			}
			catch (java.net.UnknownHostException e) {
				System.err.println("Unable to get hostname of the machine");
			}
			hostlen = (g_rdpdr_clientname.length() + 1) * 2;

			s = new RdpPacket_Localised( 16 + hostlen );
			s.out_uint8p(magic, 4);
			s.setLittleEndian16(0x63);
			s.setLittleEndian16(0x72);
			s.setBigEndian32(0);
			s.setLittleEndian32(hostlen);
			s.outUnicodeString(g_rdpdr_clientname, hostlen - 2);
			s.markEnd();
			
			try {
				this.send_packet(s);
			} catch (Exception e) {
				e.printStackTrace();
			}
	}
		
	public void rdpdr_send_clientcapabilty(){
		String magic = "rDPC";
		RdpPacket_Localised s = new RdpPacket_Localised(0x50);
		s.out_uint8p(magic, 4);
		s.setLittleEndian32(5);
		s.setLittleEndian16(1);
		s.setLittleEndian16(0x28);
		s.setLittleEndian32(1);
		s.setLittleEndian32(2);
		s.setLittleEndian16(2);
		s.setLittleEndian16(5);
		s.setLittleEndian16(1);
		s.setLittleEndian16(5);
		s.setLittleEndian16(0xFFFF);
		s.setLittleEndian16(0);
		s.setLittleEndian32(0);
		s.setLittleEndian32(3);
		s.setLittleEndian32(0);
		s.setLittleEndian32(0);
		s.setLittleEndian16(2); /* second */
		s.setLittleEndian16(8); /* length */
		s.setLittleEndian32(1);
		s.setLittleEndian16(3);	/* third */
		s.setLittleEndian16(8);	/* length */
		s.setLittleEndian32(1);
		s.setLittleEndian16(4);	/* fourth */
		s.setLittleEndian16(8);	/* length */
		s.setLittleEndian32(1);
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
	

	/* Returns the size of the payload of the announce packet */
	public int announcedata_size() {
		int size, i;
		size = 8;		/* static announce size */
		size += g_num_devices * 0x14;

		for (i = 0; i < g_num_devices; i++)
		{
			if (this.g_rdpdr_device[i].device_type == DEVICE_TYPE_PRINTER)
			{
				Printer printerinfo = (Printer) this.g_rdpdr_device[i].pdevice_data.get("PRINTER");
				printerinfo.bloblen = 0;
				size += 0x18;
				size += 2 * printerinfo.driver.length() + 2;
				size += 2 * printerinfo.printer_name.length() + 2;
				size += printerinfo.bloblen;
			}
		}

		return size;
	}
	
	public boolean register(RdpdrDevice v) {
		this.g_rdpdr_device[g_num_devices] = v;
		//this.g_rdpdr_device[g_num_devices].set_local_path("c:\\temp\\");
		//this.g_rdpdr_device[g_num_devices].set_name("fo");
		g_num_devices++;
		System.out.println("devic is:"+v.get_device_type());
		return true;
	}
	
	public void rdpdr_send_available() {
		logger.debug("Rdpdr channel is ready");
		String magic = "rDAD";
		//uint32 driverlen, printerlen, bloblen;
		int i;
		RdpPacket_Localised s;
		//PRINTER *printerinfo;

		s = new RdpPacket_Localised (announcedata_size());
		s.out_uint8p(magic, 4);
		s.setLittleEndian32(g_num_devices);

		for (i = 0; i < g_num_devices; i++){
			if (this.g_rdpdr_device[i].get_device_type() == DEVICE_TYPE_DISK)
				continue;
			System.out.println("automount device "+i);
			s.setLittleEndian32(this.g_rdpdr_device[i].device_type);
			System.out.println("device_type:"+this.g_rdpdr_device[i].device_type);
			s.setLittleEndian32(i); /* RDP Device ID */
			s.out_uint8p(this.g_rdpdr_device[i].name, 8);
			System.out.println("name:"+this.g_rdpdr_device[i].name);

			switch (this.g_rdpdr_device[i].device_type){
				case DEVICE_TYPE_PRINTER:
					Printer printerinfo = (Printer) this.g_rdpdr_device[i].pdevice_data.get("PRINTER");
					String printerName = printerinfo.get_display_name();
					int driverlen = 2 * printerinfo.driver.length() + 2;
					int printerlen = 2 * printerName.length() + 2;
					int bloblen = printerinfo.bloblen;

					s.setLittleEndian32(24 + driverlen + printerlen + bloblen);
					if (printerinfo.default_printer)
						s.setLittleEndian32(FLAG_DEFAULTPRINTER);
					else
						s.setLittleEndian32(0);
					s.incrementPosition(8);
					s.setLittleEndian32(driverlen);
					s.setLittleEndian32(printerlen);
					s.setLittleEndian32(bloblen);
					s.outUnicodeString(printerinfo.driver, driverlen - 2);
					s.outUnicodeString(printerName, printerlen - 2);
					//s.out_uint8p(null, bloblen);
					byte[] tmp = new byte[s.size()];
					s.copyToByteArray(tmp, 0, 0, s.size());
					break;
				default:
					s.setBigEndian32(0);
			}
		}
		s.markEnd();
		try {
			this.send_packet(s);
		} catch (Exception e) {
			e.printStackTrace();
		}
	}
	
	public void rdpdr_process_irp( RdpPacket s ) {
		int[] result = new int[1];
		String filename = "";
		RdpPacket_Localised out;
		boolean rw_blocking = true;
		int status = STATUS_INVALID_DEVICE_REQUEST;
		RdpdrDevice fns;
		RdpPacket_Localised buffer = new RdpPacket_Localised(1024);
		
		int 
		length = 0,
		desired_access = 0,
		request,
		file,
		info_level,
		buffer_len,
		id,
		major,
		minor,
		device,
		offset,
		bytes_in,
		bytes_out,
		error_mode,
		share_mode, disposition, total_timeout, interval_timeout, flags_and_attributes = 0;
		result[0] = 0;
		
		//Get para from package
		device = s.getLittleEndian32();
		file   = s.getLittleEndian32();
		id     = s.getLittleEndian32();
		major  = s.getLittleEndian32();
		minor  = s.getLittleEndian32();
		fns    = this.g_rdpdr_device[device];//Get the device type abstract handle
//		System.out.println("HANDLE: " + device + g_rdpdr_device[device].name);
		
		buffer_len = 0;
		//buffer.set8(0);
		
		switch (fns.get_device_type()){
			case DEVICE_TYPE_SERIAL:
				rw_blocking = false;
				break;

			case DEVICE_TYPE_PARALLEL:
				rw_blocking = false;
				break;

			case DEVICE_TYPE_PRINTER:
				break;

			case DEVICE_TYPE_DISK:
				//rw_blocking = false;
				rw_blocking = true;
				break;

			case DEVICE_TYPE_SCARD:
			default:
				System.out.print("IRP for bad device " + device + "\n");
				return;
		}
		
		switch (major){
			case IRP_MJ_CREATE:
//				System.out.println("IRP:IRP_MJ_CREATE" + " ["+(sequence_count++) + "]");
				desired_access = s.getBigEndian32();
				s.incrementPosition(0x08); /* unknown */
				error_mode = s.getLittleEndian32();
				share_mode = s.getLittleEndian32();
				disposition = s.getLittleEndian32();
				flags_and_attributes = s.getLittleEndian32();
				length = s.getLittleEndian32();
				
				byte[] tempFileName = new byte[PATH_MAX];
				if (length>0 && (length / 2) < 256){
					filename = rdp_in_unistr(s, length);
					filename = filename.trim();
				}
				else{
					filename = "";
				}

				status = this.g_rdpdr_device[device].create(device, desired_access,
						share_mode, disposition,flags_and_attributes, filename,result);
				buffer_len = 1;
				buffer.set8(0);
				break;

			case IRP_MJ_CLOSE:
//				System.out.println("IRP:IRP_MJ_CLOSE" + " ["+(sequence_count++) + "]");
				status = this.g_rdpdr_device[device].close(file);
				buffer.set8(0);
				break;

			case IRP_MJ_READ:
//				System.out.println("IRP:IRP_MJ_READ" + " ["+(sequence_count++) + "]");
				if(this.g_rdpdr_device[device].device_type==DEVICE_TYPE_PRINTER){
					status = STATUS_NOT_SUPPORTED;
					buffer.set8(0);
					break;
				}
				length = s.getLittleEndian32();
				offset = s.getLittleEndian32();
				if (!rdpdr_handle_ok(device, file)) {
					status = STATUS_INVALID_HANDLE;
					break;
				}
				if (true){//(rw_blocking) {	/* Complete read immediately */
					byte[] buf = new byte[length];
					status = this.g_rdpdr_device[device].read(file, buf, length, offset, result);
					buffer_len = result[0];
					if(buffer_len<0)
						buffer_len = 0;
					buffer = new RdpPacket_Localised(buffer_len);
					buffer.copyFromByteArray(buf, 0, buffer.getPosition(), buffer_len);
					break;
				}
				
			case IRP_MJ_WRITE:
//				System.out.println("IRP:IRP_MJ_WRITE" + " ["+(sequence_count++) + "]");
				buffer_len = 1;
				buffer.set8(0);
				length = s.getLittleEndian32();
				offset = s.getLittleEndian32();
				s.incrementPosition(0x18);
				if (!rdpdr_handle_ok(device, file)) {
					status = STATUS_INVALID_HANDLE;
					break;
				}
				if (true){//(rw_blocking) {	/* Complete write immediately */
					byte[] buf = new byte[length];
					s.copyToByteArray(buf, 0, s.getPosition(), length);
					status = this.g_rdpdr_device[device].write(file, buf, length, offset, result);
					break;
				}

			case IRP_MJ_QUERY_INFORMATION:
//				System.out.println("IRP:IRP_MJ_QUERY_INFORMATION" + " ["+(sequence_count++) + "]");
				if (fns.get_device_type()!= DEVICE_TYPE_DISK) {
					status = STATUS_INVALID_HANDLE;
					break;
				}
				info_level = s.getLittleEndian32();
				int OldPosition4 = buffer.getPosition();
				status = Disk.disk_query_information(file, info_level, buffer);
				result[0] = buffer_len = buffer.getPosition() - OldPosition4;
				break;

			case IRP_MJ_SET_INFORMATION:
//				System.out.println("IRP:IRP_MJ_SET_INFORMATION" + " ["+(sequence_count++) + "]");
				if (fns.get_device_type()!= DEVICE_TYPE_DISK) {
					status = STATUS_INVALID_HANDLE;
					break;
				}
				info_level = s.getLittleEndian32();
				int OldPosition = buffer.getPosition();
				status = new Disk(this, null, null).disk_set_information(file, info_level,s, buffer);
				result[0] = buffer_len = buffer.getPosition() - OldPosition;
				break;

			case IRP_MJ_QUERY_VOLUME_INFORMATION:
//				System.out.println("IRP:IRP_MJ_QUERY_VOLUME_INFORMATION" + " ["+(sequence_count++) + "]");
				if (fns.get_device_type()!= DEVICE_TYPE_DISK){
					status = STATUS_INVALID_HANDLE;
					System.out.println("status = STATUS_INVALID_HANDLE");
					break;
				}
				info_level = s.getLittleEndian32();
				int OldPosition2 = buffer.getPosition();
				status = Disk.disk_query_volume_information(file, info_level, buffer);
				result[0] = buffer_len = buffer.getPosition() - OldPosition2;
				break;

			case IRP_MJ_DIRECTORY_CONTROL:
//				System.out.println("\nIRP:IRP_MJ_DIRECTORY_CONTROL" + " ["+(sequence_count++) + "]");
				if (fns.get_device_type()!= DEVICE_TYPE_DISK){
					//System.out.println("status = STATUS_INVALID_HANDLE;");
					status = STATUS_INVALID_HANDLE;
					break;
				}
				switch (minor){
					case IRP_MN_QUERY_DIRECTORY:
						//System.out.println("IRP_MJ_DIRECTORY_CONTROL:IRP_MN_QUERY_DIRECTORY");
						info_level = s.getLittleEndian32();
						s.incrementPosition(1);
						length = s.getLittleEndian32();
						s.incrementPosition(0x17);
						if (length>0 && (length / 2) < 256){
							filename = rdp_in_unistr(s, length);
							filename = filename.trim();
							//System.out.println("filename="+filename);
						}
						else{
							filename = "";
						}
						//System.out.println("filename="+filename);
						int OldPosition3 = buffer.getPosition();
						status = Disk.disk_query_directory(file, info_level, filename, buffer);
						result[0] = buffer_len = buffer.getPosition() - OldPosition3;
						if (buffer_len==0)
							buffer_len++;
						//System.out.println("Buffer Len="+buffer_len);
						break;

					case IRP_MN_NOTIFY_CHANGE_DIRECTORY:
						//System.out.println("IRP_MJ_DIRECTORY_CONTROL:IRP_MN_NOTIFY_CHANGE_DIRECTORY");
						info_level = s.getLittleEndian32(); /* notify mask */
						Disk.g_notify_stamp = true;
						result[0] = buffer_len = 0;
						//status = STATUS_SUCCESS;
						//System.out.println("Buffer Len="+buffer_len);
						//break;
						info_level = s.getLittleEndian32();
						Disk.g_notify_stamp = true;
						status = Disk.disk_create_notify(file, info_level);
						result[0] = 0;
						if (status == STATUS_PENDING)
							add_async_iorequest(device, file, id, major, length,fns, 0, 0, null, 0);
						break;

					default:
						status = STATUS_INVALID_PARAMETER;
						//System.out.println("IRP major="+major+" minor="+minor);
				}
				break;

			case IRP_MJ_DEVICE_CONTROL:
//				System.out.println("IRP:IRP_MJ_DEVICE_CONTROL" + " ["+(sequence_count++) + "]");
				bytes_out = s.getLittleEndian32();
				bytes_in = s.getLittleEndian32();
				request = s.getLittleEndian32();
				s.incrementPosition(0x14);
				buffer = new RdpPacket_Localised(bytes_out + 0x14);
				if (buffer==null)
				{
					status = STATUS_CANCELLED;
					break;
				}
				int OldPosition5 = buffer.getPosition();
				status = Disk.disk_device_control(file, request, s, buffer);
				result[0] = buffer_len = buffer.getPosition() - OldPosition5;

/*
				//Serial SERIAL_WAIT_ON_MASK 
				if (status == STATUS_PENDING)
				{
					if (add_async_iorequest
					    (device, file, id, major, length, fns, 0, 0, null, 0))
					{
						status = STATUS_PENDING;
						break;
					}
				}
*/
//				System.out.println("Buffer Len="+buffer_len);
				break;

			case IRP_MJ_LOCK_CONTROL:
//				System.out.println("IRP:IRP_MJ_LOCK_CONTROL" + " ["+(sequence_count++) + "]");
				if (fns.get_device_type()!= DEVICE_TYPE_DISK)
				{
					status = STATUS_INVALID_HANDLE;
					break;
				}
				info_level = s.getLittleEndian32();
				buffer.set8(0);
				status = STATUS_SUCCESS;
				result[0] = buffer_len = 0;//buffer.size();
				break;

			default:
				System.out.print("Unknown protocol:\n IRP major="+major+" minor="+minor);
				break;
		}
		
		if (status != STATUS_PENDING){
//			System.out.println("rdpdr_send_completion,buffer_len="+buffer_len);
			rdpdr_send_completion(device, id, status, result[0], buffer, buffer_len);
			sequence_count++;
		}
	}
	
	public static String rdp_in_unistr(RdpPacket s, int len){
		int i = 0;
		byte[] temp = new byte[len];
		String ret = "";

		s.copyToByteArray(temp, 0, s.getPosition(), len);
		try {
			ret = new String(temp, Charset.forName("UTF-16LE"));
			return ret;
		}
		catch (UnsupportedCharsetException e) {
			logger.warn("UTF-16LE is not supported");
		}
		
		// Use the older method (this method can not manage chinease filename)
		char[] str = new char[len/2];
		while (i < len / 2){
			str[i++] = (char)s.get8();
			s.incrementPosition(1);
		}
		return new String(str);
	}
	
	void rdpdr_send_completion(int device, int id, int status, 
			int result, RdpPacket_Localised buffer, int buffer_len) {
		
		String magic = "rDCI";
		RdpPacket_Localised s = new RdpPacket_Localised(20 + buffer_len);

		s.out_uint8p(magic, 4);
		s.setLittleEndian32(device);
		s.setLittleEndian32(id);
		s.setLittleEndian32(status);
		s.setLittleEndian32(result);
		s.copyFromPacket(buffer, 0, s.getPosition(), buffer_len);
//		System.out.println("buffer.length="+buffer.size()+"\n");
		s.markEnd();
		
		try {
			this.send_packet(s, this.limitBandWidth);
		} catch (Exception e) {
			e.printStackTrace();
		}
	}
	
	public static void main(String[] args){
//		RdpdrChannel c = new RdpdrChannel();
//		RdpdrDevice testDisk = new Disk();
//		c.register(testDisk);
//		System.out.print(c.g_rdpdr_device[0].get_device_type());
		
	}
	
	public boolean rdpdr_handle_ok(int device, int handle) {
		switch (g_rdpdr_device[device].device_type) {
			case DEVICE_TYPE_PARALLEL:
			case DEVICE_TYPE_SERIAL:
			case DEVICE_TYPE_PRINTER:
			case DEVICE_TYPE_SCARD:
				if (this.g_rdpdr_device[device].handle != handle)
					return false;
				break;
			case DEVICE_TYPE_DISK:
				if (((FILEINFO)(Disk.g_fileinfo.get(handle))).get_device_id() != device)
					return false;
				break;
		}
		return true;
	}
	
	//unimplemented method
	static boolean
	add_async_iorequest(int device, int file, int id, int major, int length,
			    RdpdrDevice fns, int total_timeout, int interval_timeout, RdpPacket buffer,int offset){
		return true;
	}
}

