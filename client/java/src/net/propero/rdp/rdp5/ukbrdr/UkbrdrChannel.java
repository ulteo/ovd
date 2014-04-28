/*
 * Copyright (C) 2014 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2014
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

package net.propero.rdp.rdp5.ukbrdr;

import java.io.IOException;
import java.io.UnsupportedEncodingException;

import net.propero.rdp.Common;
import net.propero.rdp.CommunicationMonitor;
import net.propero.rdp.Input;
import net.propero.rdp.Options;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpPacket;
import net.propero.rdp.RdpPacket_Localised;
import net.propero.rdp.crypto.CryptoException;
import net.propero.rdp.rdp5.VChannel;
import net.propero.rdp.rdp5.VChannels;

import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.utils.jni.WindowsTweaks;
import org.ulteo.utils.jni.UkbrdrForward;



public class UkbrdrChannel extends VChannel {
	private enum message_type {
		UKB_INIT,
		UKB_CARET_POS,
		UKB_IME_STATUS,
		UKB_PUSH_TEXT,
		UKB_PUSH_COMPOSITION,
		UKB_STOP_COMPOSITION,
	};
	
	private enum ime_state {
		UKB_IME_DESACTIVATED,
		UKB_IME_ACTIVATED,
	};
	
    protected static final int RDP_INPUT_UNICODE = 5;
		
	private int caretX;
	private int caretY;
	private ime_state imeState;
	private boolean useSeamless = false;
	private boolean isReady = false;
	
    
	public UkbrdrChannel(Options opt_, Common common_, boolean useSeamless) {
		super(opt_, common_);
		this.imeState = ime_state.UKB_IME_ACTIVATED;
		this.useSeamless = useSeamless;
		this.isReady = false;
	}
	
	
	public boolean isReady() {
		return this.isReady;
	}
	

	public void process(RdpPacket data) throws RdesktopException, IOException, CryptoException {
		int temp = data.getLittleEndian16();
		data.incrementPosition(2);   // flags
		data.incrementPosition(4);   // length
		
		
		message_type type = message_type.values()[temp];
		switch (type) {
		case UKB_INIT:
			this.isReady = true;
			this.common.canvas.useLocalIME(true);
			break;
			
		case UKB_CARET_POS:
			this.caretX = data.getLittleEndian32();
			this.caretY = data.getLittleEndian32();
			if (OSTools.isWindows())
				WindowsTweaks.setIMEPosition(this.caretX, this.caretY, this.useSeamless);
			
			if (OSTools.isLinux())
				UkbrdrForward.setIMEPosition(this.caretX, this.caretY);
			
			break;
			
		case UKB_IME_STATUS:
			this.imeState = ime_state.values()[(int)data.get8()];
			Input input = this.common.canvas.getInput();
			boolean state = (this.imeState == ime_state.UKB_IME_ACTIVATED);
			
	        if (input.supportIME() == false) {
	        	logger.info("IME is not supported");
	            break; /* Not supported */
	        }

	        if (input.getImeActive() == state) {
	            break; /* State unchanged */
	        }

	        input.setImeActive(state);
			
			break;
			
		default:
			logger.warn("Unknown message");
			break;
		}
	}
	
	
	public void stopComposition() {
		RdpPacket_Localised s = new RdpPacket_Localised(8);
		
		s.setLittleEndian16(message_type.UKB_STOP_COMPOSITION.ordinal());
		s.setLittleEndian16(0);
		s.setLittleEndian32(0);
		s.markEnd();
		
		this.send(s);
	}
	
	public void sendPreedit(String str) {
		int data_len = 2 * (str.length() + 1);
		int packet_len = data_len + 8;
		
		
		RdpPacket_Localised s = new RdpPacket_Localised(packet_len);
		
		s.setLittleEndian16(message_type.UKB_PUSH_COMPOSITION.ordinal());
		s.setLittleEndian16(0);
		s.setLittleEndian32(2*(str.length()+1));
		
		byte[] sBytes = null;
		try {
			sBytes = str.getBytes("UTF-16LE");
		} catch (UnsupportedEncodingException e) {
			logger.debug(e.getMessage());
			logger.info("UTF-16LE is not supported by your JVM");
		}
		
		s.copyFromByteArray(sBytes, 0, s.getPosition(), sBytes.length);
		s.markEnd();
		
		this.send(s);
		
	}
	
	public void sendInput(char c) {
		this.common.rdp.sendInput(Input.getTime(), RDP_INPUT_UNICODE, 0, c, 0);
	}
	
	
	protected void send(RdpPacket_Localised data) {
		CommunicationMonitor.lock(this);
		
		try {
			this.send_packet(data);
		} catch (RdesktopException e) {
			System.err.println(e.getMessage());
			e.printStackTrace();
            if(!this.common.underApplet) System.exit(-1);
		} catch (IOException e) {
			System.err.println(e.getMessage());
			e.printStackTrace();
            if(!this.common.underApplet) System.exit(-1);
		} catch (CryptoException e) {
			System.err.println(e.getMessage());
			e.printStackTrace();
            if(!this.common.underApplet) System.exit(-1);
		}
		
		CommunicationMonitor.unlock(this);
	}

	public int flags() {
		return VChannels.CHANNEL_OPTION_INITIALIZED | VChannels.CHANNEL_OPTION_ENCRYPT_RDP;
	}

	public String name() {
		return "ukbrdr";
	}
}
