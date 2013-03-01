/*
 * Copyright (C) 2010-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2010, 2012
 * Author Thomas MOUTON <thomas@ulteo.com> 2013
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

import java.io.File;
import java.io.IOException;
import java.io.UnsupportedEncodingException;
import java.security.InvalidParameterException;
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
import org.ulteo.ovd.Application;
import org.ulteo.ovd.ApplicationInstance;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.disk.DiskManager;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.integrated.RestrictedAccessException;
import org.ulteo.ovd.integrated.SystemLinux;
import org.ulteo.ovd.integrated.SystemWindows;
import org.ulteo.rdp.rdpdr.OVDRdpdrChannel;
import org.ulteo.utils.I18n;

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

	private List<Application> appsList = null;
	private List<ApplicationInstance> appInstancesList = null;
	
	public OvdAppChannel(Options opt_, Common common_) {
		super(opt_, common_);

		this.sharesUsedByApps = new HashMap<RdpdrDevice, List<Integer>>();
		this.listener = new CopyOnWriteArrayList<OvdAppListener>();
		this.known_folers = new CopyOnWriteArrayList<String>();
		this.appsList = new ArrayList<Application>();
		this.appInstancesList = new ArrayList<ApplicationInstance>();
	}
	
	public int flags() {
		return VChannels.CHANNEL_OPTION_INITIALIZED | VChannels.CHANNEL_OPTION_ENCRYPT_RDP;
	}

	public String name() {
		return "ovdapp";
	}

	public void addApplication(Application app_) {
		this.appsList.add(app_);
	}

	public List<Application> getApplicationsList() {
		return this.appsList;
	}
	
	private Application findApplicationById(int appId) {
		for (Application each : this.appsList) {
			if (each.getId() == appId)
				return each;
		}
		
		return null;
	}

	private ApplicationInstance findApplicationInstanceByToken(int token) {
		for (ApplicationInstance each : this.appInstancesList) {
			if (each.getToken() == token)
				return each;
		}
		
		return null;
	}
	
	private ApplicationInstance getNewApplicationInstance(int token, int app_id) throws OvdException {
		Application app = this.findApplicationById(app_id);
		if (app == null) {
			throw new OvdException("Unknown application ID "+app_id);
		}
		if (this.findApplicationInstanceByToken(token) != null) {
			throw new OvdException("Application token "+token+" is already registered");
		}
		return new ApplicationInstance(app, null, token);
	}

	public void process(RdpPacket data) throws RdesktopException, IOException, CryptoException {
		int length = data.size() - data.getPosition();
		if (length < 1) {
			Logger.error("ovdapp channel invalid protocol (packet length error)");
			return;
		}
		
		int order = (int)data.get8();
		int app_id = 0;
		int instance = 0;
		ApplicationInstance ai = null;
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
				if (length < 1 + 4) {
					Logger.error("ovdapp channel invalid order KNOWN_DRIVES (packet length error)");
					break;
				}
				
				int folder_count = data.getLittleEndian32();
				known_folers.clear();

				for(int i = 0 ; i < folder_count ; i++) {
					if (data.size() - data.getPosition() < 4) {
						Logger.error("ovdapp channel invalid order KNOWN_DRIVES (packet length error: expected a uint32)");
						break;
					}
					
					int drive_uid_length = data.getLittleEndian32();
					if (data.size() - data.getPosition() < drive_uid_length) {
						Logger.error("ovdapp channel invalid order KNOWN_DRIVES (packet length error: expected a "+drive_uid_length+" string length)");
						break;
					}
					
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
				if (length != 1 + 4 + 4) {
					Logger.error("ovdapp channel invalid order STARTED (packet length error)");
					break;
				}
				
				app_id = data.getLittleEndian32();
				instance = data.getLittleEndian32();
				
				System.out.println("ovdapp channel started instance "+instance+" of application "+app_id);
				
				ai = this.findApplicationInstanceByToken(instance);
				if (ai == null) {
					try {
						ai = this.getNewApplicationInstance(instance, app_id);
						this.appInstancesList.add(ai);
					} catch (OvdException ex) {
						Logger.error("Failed to create ApplicationInstance: "+ex.getMessage());
						break;
					}
				}
				
				for(OvdAppListener listener : this.listener) {
					listener.ovdInstanceStarted(this, ai);
				}
				
				break;

			case ORDER_STOPPED:
				if (length != 1 + 4) {
					Logger.error("ovdapp channel invalid order STOPPED (packet length error)");
					break;
				}
				
				instance = data.getLittleEndian32();
				
				System.out.println("ovdapp channel stopped instance "+instance);
				
				ai = this.findApplicationInstanceByToken(instance);
				if (ai == null) {
					Logger.error("OvdAppChannel does not know the application instance");
					break;
				}
				
				for(OvdAppListener listener : this.listener) {
					listener.ovdInstanceStopped(ai);
				}
				break;
				
			case ORDER_CANT_START:
				if (length != 1 + 4) {
					Logger.error("ovdapp channel invalid order CANT_START (packet length error)");
					break;
				}
				
				instance = data.getLittleEndian32();
				
				System.out.println("ovdapp channel cant start instance "+instance);
				
				ai = this.findApplicationInstanceByToken(instance);
				if (ai == null) {
					Logger.error("OvdAppChannel does not know the application instance");
					break;
				}
				
				for(OvdAppListener listener : this.listener) {
					listener.ovdInstanceError(ai);
				}
				break;
			
			default:
				System.err.println("ovdapp channel unknown order "+order);	
		}
	}
	
	public boolean isReady() {
		return this.channel_open;
	}
	
	public boolean hasKnownDrive(String id) {
		for (String each: this.known_folers) {
			if (each.equals(id)) {
				return true;
			}
		}
		
		return false;
	}
	
	public boolean hasKnownDrives() {
		return (! this.known_folers.isEmpty());
	}
	
	public void sendStartApp(int token, int app_id) {
		try {
			ApplicationInstance instance = this.getNewApplicationInstance(token, app_id);
			instance.setState(ApplicationInstance.STARTING);
			this.appInstancesList.add(instance);
		} catch(OvdException ex) {
			Logger.error("[sendStartApp] "+ex.getMessage());
			return;
		}
		
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
		if (l == null)
			return;
		
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
