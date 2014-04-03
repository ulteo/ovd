package net.propero.rdp.rdp5.imer;

import java.io.IOException;

import net.propero.rdp.Common;
import net.propero.rdp.HexDump;
import net.propero.rdp.Options;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpPacket;
import net.propero.rdp.RdpPacket_Localised;
import net.propero.rdp.crypto.CryptoException;
import net.propero.rdp.rdp5.VChannel;
import net.propero.rdp.rdp5.VChannels;
import org.apache.log4j.Level;


public class IMEChannel extends VChannel {

    
	public IMEChannel(Options opt_, Common common_) {
		super(opt_, common_);
		
		if (this.opt.debug_seamless)
			logger.setLevel(Level.DEBUG);

		System.out.println("Construct");		
	}

	/* Split input into lines, and call linehandler for each line. */
	public void process(RdpPacket data) throws RdesktopException, IOException, CryptoException {
		int len = data.getEnd() - data.getPosition();
		byte[] buffer = new byte[len];
		
		data.copyToByteArray(buffer, 0, data.getPosition(), len);
		
		HexDump.encode(buffer, "data received");
	}
	
	
	protected void imer_send(String command, String args) throws RdesktopException, IOException, CryptoException {
		RdpPacket_Localised s;
		String text = command + "," +"," + args + "\n";
			
		logger.debug("Sending Seamless message: " + text);
			
		byte[] textBytes = text.getBytes("UTF-8");
		s = new RdpPacket_Localised(textBytes.length);
		s.copyFromByteArray(textBytes, 0, 0, textBytes.length);
		s.markEnd();
		send_packet(s);
			
	}

	public int flags() {
		return VChannels.CHANNEL_OPTION_INITIALIZED | VChannels.CHANNEL_OPTION_ENCRYPT_RDP;
	}

	public String name() {
		return "imer";
	}
}
