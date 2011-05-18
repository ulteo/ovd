/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
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

package org.ulteo.rdp;

import java.io.IOException;
import java.io.UnsupportedEncodingException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.concurrent.CopyOnWriteArrayList;

import net.propero.rdp.Common;
import net.propero.rdp.Options;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpPacket;
import net.propero.rdp.RdpPacket_Localised;
import net.propero.rdp.crypto.CryptoException;
import net.propero.rdp.rdp5.VChannel;
import net.propero.rdp.rdp5.VChannels;
import net.propero.rdp.rdp5.rdpdr.RdpdrDevice;
import org.ulteo.Logger;

public class OvdAppChannel extends VChannel {
	public static final int	ORDER_INIT	= 0x00;
	public static final int	ORDER_START	= 0x01;
	public static final int	ORDER_STARTED	= 0x02;
	public static final int	ORDER_STOPPED	= 0x03;
	public static final int	ORDER_LOGOFF	= 0x04;
	public static final int ORDER_STOP	= 0x05;
	public static final int ORDER_CANT_START= 0x06;
	public static final int	ORDER_START_WITH_ARG= 0x07;
	public static final int	ORDER_KNOWN_DRIVES = 0x20;
	
	public static final int DIR_TYPE_SHARED_FOLDER = 0X01;
	public static final int DIR_TYPE_RDP_DRIVE     = 0x02;
	public static final int DIR_TYPE_KNOWN_DRIVE  = 0x03;
	public static final int DIR_TYPE_HTTP_URL  = 0x10;
	
	private boolean channel_open = false;
	
	private List<OvdAppListener> listener = null;
	private List<String> known_folers = null;

	private HashMap<RdpdrDevice, List<Integer>> sharesUsedByApps = null;
	
	public OvdAppChannel(Options opt_, Common common_) {
		super(opt_, common_);

		this.sharesUsedByApps = new HashMap<RdpdrDevice, List<Integer>>();
		this.listener = new CopyOnWriteArrayList<OvdAppListener>();
		this.known_folers = new CopyOnWriteArrayList<String>();
	}
	
	public int flags() {
		return VChannels.CHANNEL_OPTION_INITIALIZED | VChannels.CHANNEL_OPTION_ENCRYPT_RDP;
	}

	public String name() {
		return "ovdapp";
	}
	
	public void process(RdpPacket data) throws RdesktopException, IOException, CryptoException {
		int order = (int)data.get8();
		int instance = 0;
		switch( order ) {
			case ORDER_INIT:
				if (! this.channel_open) {
					Logger.debug("[RDP - "+this.opt.hostname+"] OVDApp channel is ready");
					this.channel_open = true;

					for(OvdAppListener listener : this.listener) {
						listener.ovdInited(this);
					}
				}
				
				break;
			
			case ORDER_KNOWN_DRIVES:
				int folder_count = data.getLittleEndian32();
				known_folers.clear();

				for(int i = 0 ; i < folder_count ; i++) {
					int drive_uid_length = data.getLittleEndian32();
					byte[] stringData = new byte[drive_uid_length];
					
					data.copyToByteArray(stringData, 0, data.getPosition(), drive_uid_length);
					data.incrementPosition(drive_uid_length);
					
					try {
						known_folers.add(new String(stringData, "UTF-16LE"));
					} catch (UnsupportedEncodingException ex) {
						logger.error("Failed to send startapp: UTF-16LE is not supported by your JVM: "+ex.getMessage());
						break;
					}
				}
				break;

			case ORDER_STARTED:
				instance = data.getLittleEndian32();
				
				System.out.println("ovdapp channel started instance "+instance);
				for(OvdAppListener listener : this.listener) {
					listener.ovdInstanceStarted(instance);
				}
				
				break;

			case ORDER_STOPPED:
				instance = data.getLittleEndian32();
				
				System.out.println("ovdapp channel stopped instance "+instance);
				for(OvdAppListener listener : this.listener) {
					listener.ovdInstanceStopped(instance);
				}
				break;
				
			case ORDER_CANT_START:
				instance = data.getLittleEndian32();
				
				System.out.println("ovdapp channel cant start instance "+instance);
				for(OvdAppListener listener : this.listener) {
					listener.ovdInstanceError(instance);
				}
				break;
			
			default:
				System.err.println("ovdapp channel unknown order "+order);	
		}
	}
	
	public boolean isReady() {
		return this.channel_open;
	}
	
	public void sendStartApp(int token, int app_id) {
		RdpPacket_Localised out = new RdpPacket_Localised(9);
		out.set8(ORDER_START);
		out.setLittleEndian32(token);
		out.setLittleEndian32(app_id);
		out.markEnd();
		
		try {
			this.send_packet( out );
		} catch( RdesktopException e ) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		} catch( IOException e ) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		} catch( CryptoException e ) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		}
	}

	public void sendStartApp(int token, int app_id, int shareType, String sharename, String path) {
		byte[] sharenameBytes = null;
		byte[] pathBytes = null;

		try {
			sharenameBytes = sharename.getBytes("UTF-16LE");
			pathBytes = path.getBytes("UTF-16LE");
		} catch (UnsupportedEncodingException ex) {
			logger.error("Failed to send startapp: UTF-16LE is not supported by your JVM: "+ex.getMessage());
			return;
		}
		
		RdpPacket_Localised out = new RdpPacket_Localised(18 + sharenameBytes.length + pathBytes.length);
		out.set8(ORDER_START_WITH_ARG);
		out.setLittleEndian32(token);
		out.setLittleEndian32(app_id);
		out.set8(shareType);
		out.setLittleEndian32(sharenameBytes.length);
		out.copyFromByteArray(sharenameBytes, 0, out.getPosition(), sharenameBytes.length);
		out.setPosition(out.getPosition() + sharenameBytes.length);
		out.setLittleEndian32(pathBytes.length);
		out.copyFromByteArray(pathBytes, 0, out.getPosition(), pathBytes.length);
		out.setPosition(out.getPosition() + pathBytes.length);
		out.markEnd();

		try {
			this.send_packet( out );
		} catch( RdesktopException e ) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		} catch( IOException e ) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		} catch( CryptoException e ) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		}
	}

	public void sendStopApp(int token) {
		RdpPacket_Localised out = new RdpPacket_Localised(9);
		out.set8(ORDER_STOP);
		out.setLittleEndian32(token);
		out.markEnd();
		
		try {
			this.send_packet(out);
		} catch( RdesktopException e ) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		} catch( IOException e ) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		} catch( CryptoException e ) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		}
	}

	public void sendLogoff() {
		RdpPacket_Localised out = new RdpPacket_Localised(1);
		out.set8(ORDER_LOGOFF);
		out.markEnd();
		
		try {
			this.send_packet(out);
		} catch (RdesktopException e) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		} catch (IOException e) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		} catch (CryptoException e) {
			System.err.println( e.getMessage() );
			e.printStackTrace();
		}
	}
	
	public void addOvdAppListener(OvdAppListener listener) {
		this.listener.add(listener);
	}

	public void removeOvdAppListener(OvdAppListener listener) {
		this.listener.remove(listener);
	}

	public void addShareUsedByApp(RdpdrDevice device, int instance) {
		if (! this.sharesUsedByApps.containsKey(device))
			this.sharesUsedByApps.put(device, new ArrayList<Integer>());
		
		this.sharesUsedByApps.get(device).add(new Integer(instance));
	}

	public void removeShareUsedByApp(RdpdrDevice device, int instance) {
		Integer i = null;
		List<Integer> l = this.sharesUsedByApps.get(device);
		for (Integer each : l) {
			if (each.intValue() == instance) {
				i = each;
				break;
			}
		}

		if (i == null) {
			logger.error("Failed to find application instance "+instance);
			return;
		}

		l.remove(i);

		if (l.size() == 0)
			this.sharesUsedByApps.remove(device);
	}

	public boolean isShareUsed(RdpdrDevice device) {
		if (this.sharesUsedByApps.containsKey(device))
			return true;
		
		return false;
	}
}
