/* VChannel.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.1.1.1 $
 * Author: $Author: suvarov $
 * Date: $Date: 2007/03/08 00:26:39 $
 *
 * Copyright (c) 2005 Propero Limited
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author david LECHEVALIER <david@ulteo.com> 2011
 *
 * Purpose: Abstract class for RDP5 channels
 */
package net.propero.rdp.rdp5;

import java.io.IOException;
import java.util.GregorianCalendar;

import org.apache.log4j.Logger;

import net.propero.rdp.Common;
import net.propero.rdp.Constants;
import net.propero.rdp.Input;
import net.propero.rdp.Options;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpPacket;
import net.propero.rdp.RdpPacket_Localised;
import net.propero.rdp.Secure;
import net.propero.rdp.crypto.CryptoException;

public abstract class VChannel {

	protected static Logger logger = Logger.getLogger(Input.class);
	protected Options opt = null;
	protected Common common = null;
	
	private int mcs_id = 0;
	public long packetStat = 0;
	public long lastTime = 0;
	public long packetLimit = 0;
	public boolean limitBandWidth;
	
	public VChannel(Options opt_, Common common_) {
		this.opt = opt_;
		this.common = common_;
		this.lastTime = new GregorianCalendar().getTimeInMillis();
	}
	
	public void setSpoolable(boolean limitBandWidth, int packetLimit) {
		this.limitBandWidth = limitBandWidth;
		this.packetLimit = packetLimit;
	}
	
    /**
     * Provide the name of this channel
     * @return Channel name as string
     */
	public abstract String name();
	
    /**
     * Provide the set of flags specifying working options for this channel
     * @return Option flags
     */
    public abstract int flags();
    
    /**
     * Process a packet sent on this channel
     * @param data Packet sent to this channel
     * @throws RdesktopException
     * @throws IOException
     * @throws CryptoException
     */
	public abstract void process(RdpPacket data) throws RdesktopException, IOException, CryptoException;
	
	/**
	 * Get the MCS ID for this channel
	 * @return the current MCS ID of this channel
	 */
	public int mcs_id(){
		return mcs_id;
	}
	
    /**
     * Set the MCS ID for this channel
     * @param mcs_id New MCS ID
     */
	public void set_mcs_id(int mcs_id){
		this.mcs_id = mcs_id;
	}
	
    /**
     * Initialise a packet for transmission over this virtual channel
     * @param length Desired length of packet
     * @return Packet prepared for this channel
     * @throws RdesktopException
     */
	public RdpPacket_Localised init(int length) throws RdesktopException{
		RdpPacket_Localised s;
		
		s = this.common.secure.init(this.opt.encryption ? Secure.SEC_ENCRYPT : 0,length + 8);
		s.setHeader(RdpPacket.CHANNEL_HEADER);
		s.incrementPosition(8);
				
		return s;
	}
		
    /**
     * Send a packet over this virtual channel
     * @param data Packet to be sent
     * @throws RdesktopException
     * @throws IOException
     * @throws CryptoException
     */
	public void send_packet(RdpPacket_Localised data) throws RdesktopException, IOException, CryptoException
	{
		this.send_packet(data, false);
	}
	
	public void send_packet(RdpPacket_Localised data, boolean spoolable) throws RdesktopException, IOException, CryptoException
	{
		if(this.common.secure == null) 
			return;
		if (! this.common.secure.ready) 
			return;

		if (this.limitBandWidth && spoolable) {
			long currentTime = new GregorianCalendar().getTimeInMillis();
			if (currentTime - this.lastTime >= 1000) {
				this.lastTime = currentTime;
				this.packetStat = 0;
			}
				
			this.packetStat += data.size();
			this.common.secure.spool_packet(data, Constants.encryption ? Secure.SEC_ENCRYPT : 0, this.mcs_id());
			return;
		}
		
		int length = data.size();
		
		int data_offset = 0;
		int packets_sent = 0;
		int num_packets = (length/this.opt.VCChunkMaxSize);
		num_packets += length - (this.opt.VCChunkMaxSize)*num_packets;
		
		while(data_offset < length){
		
			int thisLength = Math.min(this.opt.VCChunkMaxSize, length - data_offset);
			
			RdpPacket_Localised s = this.common.secure.init(Constants.encryption ? Secure.SEC_ENCRYPT : 0, 8 + thisLength);
			s.setLittleEndian32(length);
		
			int flags = ((data_offset == 0) ? VChannels.CHANNEL_FLAG_FIRST : 0);
			if(data_offset + thisLength >= length) flags |= VChannels.CHANNEL_FLAG_LAST;
			
			if ((this.flags() & VChannels.CHANNEL_OPTION_SHOW_PROTOCOL) != 0) flags |= VChannels.CHANNEL_FLAG_SHOW_PROTOCOL;
		
			s.setLittleEndian32(flags);
			s.copyFromPacket(data,data_offset,s.getPosition(),thisLength);
			s.incrementPosition(thisLength);
			s.markEnd();
			
			data_offset += thisLength;		
			
			if(this.common.secure != null) this.common.secure.send_to_channel(s, Constants.encryption ? Secure.SEC_ENCRYPT : 0, this.mcs_id());
			packets_sent++;
		}
	}
	
}
