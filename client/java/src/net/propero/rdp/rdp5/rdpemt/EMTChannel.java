/*
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 *  Author David LECHEVALIER <david@ulteo.com> 2012
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

package net.propero.rdp.rdp5.rdpemt;
import java.io.IOException;

import java.util.Calendar;

import net.propero.rdp.Common;
import net.propero.rdp.Options;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpPacket;
import net.propero.rdp.RdpPacket_Localised;
import net.propero.rdp.Secure;
import net.propero.rdp.crypto.CryptoException;
import net.propero.rdp.rdp5.VChannel;
import net.propero.rdp.rdp5.VChannels;

public class EMTChannel extends VChannel {
	private static final int SEC_AUTODETECT_RESP_LENGTH     = 0x6;
	private static final int SEC_AUTODETECT_RES_RESP_LENGTH = 0xe;
	
	private static final int SEC_AUTODETECT_REQ            = 0x1000;
	private static final int SEC_AUTODETECT_RSP            = 0x2000;
	
	private static final int TYPE_ID_AUTODETECT_REQUEST    = 0x00;
	private static final int TYPE_ID_AUTODETECT_RESPONSE   = 0x01;
	
	private static final int RDP_RTT_REQUEST_TYPE          = 0x1001;
	private static final int RDP_BW_CONNECT_START          = 0x1014;
	private static final int RDP_BW_SESSION_START          = 0x0014;
	private static final int RDP_BW_PAYLOAD                = 0x0002;
	private static final int RDP_BW_CONNECT_STOP           = 0x002B;
	private static final int RDP_BW_SESSION_STOP           = 0x0429;
	private static final int RDP_BW_RESULTS                = 0x0003;
	
	private static final int field_baseRTT_averageRTT      = 0x0840;
	private static final int field_bandwidth_averageRTT    = 0x0880;
	private static final int field_all                     = 0x08C0;
	
	long startTime = 0;
	long stopTime = 0;
	int dataCount = 0;
	
	
	public EMTChannel(Options opt_, Common common_) {
		super(opt_, common_);
	}
	
	public int flags() {
		return VChannels.CHANNEL_OPTION_INITIALIZED | VChannels.CHANNEL_OPTION_ENCRYPT_RDP;
	}
	
	public String name() {
		return "rdpemt";
	}
	
	public void sendRTTResponse(int sequenceNumber) throws RdesktopException, IOException, CryptoException {
		RdpPacket_Localised s = new RdpPacket_Localised(6);
		s.set8(SEC_AUTODETECT_RESP_LENGTH);      // headerLength
		s.set8(TYPE_ID_AUTODETECT_RESPONSE);     // headerTypeId
		s.setLittleEndian16(sequenceNumber);     // sequenceNumber
		s.setLittleEndian16(0);                  // responseType
		
		this.send(s);
	}
	
	public void sendMesureResult(int sequenceNumber) throws RdesktopException, IOException, CryptoException {
		int delta = (int)(this.stopTime - this.startTime);
		RdpPacket_Localised s = new RdpPacket_Localised(0xe);
		
		s.set8(SEC_AUTODETECT_RES_RESP_LENGTH);  // headerLength
		s.set8(TYPE_ID_AUTODETECT_RESPONSE);     // headerTypeId
		s.setLittleEndian16(sequenceNumber);     // sequenceNumber
		s.setLittleEndian16(RDP_BW_RESULTS);     // responseType
		
		s.setLittleEndian32(delta);
		s.setLittleEndian32(this.dataCount);
		
		this.send(s);
	}
	
	public void process(RdpPacket data) throws RdesktopException, IOException, CryptoException {
		int sequenceNumber;
		int requestType;
		int baseRTT = 0;
		int bandwidth = 0;
		int averageRTT = 0;
		boolean result = false;
		
		data.incrementPosition(1);                       // headerLength
		data.incrementPosition(1);                       // headerTypeId
		sequenceNumber = data.getLittleEndian16();       // sequenceNumber
		requestType = data.getLittleEndian16();          // requestType
		
		switch (requestType) {
		case RDP_RTT_REQUEST_TYPE:
			this.sendRTTResponse(sequenceNumber);
			break;
		
		case RDP_BW_SESSION_START:
			this.common.iso.resetCounter();
		case RDP_BW_CONNECT_START:
			this.startTime = Calendar.getInstance().getTimeInMillis();
			break;
		
		case RDP_BW_PAYLOAD:
			this.dataCount = data.getBigEndian16();
		
		case RDP_BW_CONNECT_STOP:
			this.dataCount = data.getBigEndian16() & 0xFFFF;
			this.stopTime = Calendar.getInstance().getTimeInMillis();
			this.sendMesureResult(sequenceNumber);
			break;
		
		case RDP_BW_SESSION_STOP:
			this.dataCount = this.common.iso.getTotalSend();
			this.stopTime = Calendar.getInstance().getTimeInMillis();
			this.sendMesureResult(sequenceNumber);
			break;
		
		case field_baseRTT_averageRTT:
			result = true;
			baseRTT = data.getLittleEndian32();
			averageRTT = data.getLittleEndian32();
			break;
		
		case field_bandwidth_averageRTT:
			result = true;
			baseRTT = data.getLittleEndian32();
			bandwidth = data.getLittleEndian32();
			break;
		
		case field_all:
			result = true;
			baseRTT = data.getLittleEndian32();
			bandwidth = data.getLittleEndian32();
			averageRTT = data.getLittleEndian32();
			break;
		
		default:
			System.out.println("Unknow request type '"+Integer.toHexString(requestType)+"'");
			break;
		}
		
		if (!result) {
			return;
		}
		
		this.common.iso.updateRTT(baseRTT, bandwidth, averageRTT);
	}
	
	public void send(RdpPacket_Localised data) throws RdesktopException, IOException, CryptoException {
		if(this.common.secure == null) 
			return;
		
		int secFlags = Secure.SEC_ENCRYPT | SEC_AUTODETECT_RSP;
		int len = data.size();
		RdpPacket_Localised s = this.common.secure.init(secFlags, len);
		
		s.copyFromPacket(data, 0, s.getPosition(), len);
		s.incrementPosition(len);
		s.markEnd();
		
		if(this.common.secure != null)
			this.common.secure.send_to_channel(s, secFlags, this.mcs_id());
	}
}
