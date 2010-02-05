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
import java.util.ArrayList;
import java.util.List;

import net.propero.rdp.Common;
import net.propero.rdp.Options;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpPacket;
import net.propero.rdp.RdpPacket_Localised;
import net.propero.rdp.crypto.CryptoException;
import net.propero.rdp.rdp5.VChannel;
import net.propero.rdp.rdp5.VChannels;

public class OvdAppChannel extends VChannel {
	public static final int	ORDER_INIT		= 0x00;
	public static final int	ORDER_START		= 0x01;
	public static final int	ORDER_STARTED	= 0x02;
	public static final int	ORDER_STOPPED	= 0x03;
	public static final int	ORDER_LOGOFF	= 0x04;
	public static final int ORDER_CANT_START= 0x06;
	
	private boolean channel_open = false;
	
	private List<OvdAppListener> listener = null;
	
	public OvdAppChannel(Options opt_, Common common_) {
		super(opt_, common_);
		
		this.listener = new ArrayList<OvdAppListener>();
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
				System.out.println("ovdapp channel init from server");
				this.channel_open = true;

				for(OvdAppListener listener : this.listener) {
					listener.ovdInited(this);
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
				System.err.println("ovdapp channel unknown oreder "+order);	
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
	
	public void addOvdAppListener(OvdAppListener listener) {
		this.listener.add(listener);
	}

	public void removeOvdAppListener(OvdAppListener listener) {
		this.listener.remove(listener);
	}
}
