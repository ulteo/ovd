package net.propero.rdp.rdp5.ukbrdr;

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


public class UkbrdrChannel extends VChannel {
	private enum message_type {
		UKB_INIT,
		UKB_CARET_POS,
		UKB_IME_STATUS,
		UKB_PUSH_TEXT,
		UKB_PUSH_COMPOSITION
	};
		
		
	private int caretX;
	private int caretY;
	
    
	public UkbrdrChannel(Options opt_, Common common_) {
		super(opt_, common_);
		
		if (this.opt.debug_seamless)
			logger.setLevel(Level.DEBUG);

		System.out.println("Construct");		
	}

	/* Split input into lines, and call linehandler for each line. */
	public void process(RdpPacket data) throws RdesktopException, IOException, CryptoException {
		int temp = data.getLittleEndian16();
		int flags = data.getLittleEndian16();
		int len = data.getLittleEndian32();
		
		System.out.println("new packet type["+temp+"] flags["+flags+"] len["+len+"]");		
		
		message_type type = message_type.values()[temp];
		switch (type) {
		case UKB_INIT:
			
			System.out.println("init msg: version "+data.getLittleEndian16());
			break;
			
		case UKB_CARET_POS:
			this.caretX = data.getLittleEndian32();
			this.caretY = data.getLittleEndian32();
			
			System.out.println("caret change to position ["+this.caretX+"-"+this.caretY+"]");
			break;
			
		case UKB_IME_STATUS:
			System.out.println("ime status");
			break;
			
		case UKB_PUSH_COMPOSITION:
			System.out.println("push composition");
			break;
			
		case UKB_PUSH_TEXT:
			System.out.println("push text");
			break;

		default:
			System.out.println("Unknown problem");
			break;
		}
	}
	
	
	protected void send(String command, String args) throws RdesktopException, IOException, CryptoException {
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
		return "ukbrdr";
	}
}
