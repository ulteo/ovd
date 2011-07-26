/* TextHandler.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.1.1.1 $
 * Author: $Author: suvarov $
 * Date: $Date: 2007/03/08 00:26:41 $
 *
 * Copyright (c) 2005 Propero Limited
 *
 * Purpose: 
 */
package net.propero.rdp.rdp5.cliprdr;

import java.awt.datatransfer.Clipboard;
import java.awt.datatransfer.DataFlavor;
import java.awt.datatransfer.StringSelection;
import java.awt.datatransfer.Transferable;
import java.awt.datatransfer.UnsupportedFlavorException;
import java.io.IOException;

import net.propero.rdp.RdpPacket;
import net.propero.rdp.Utilities_Localised;

public class TextHandler extends TypeHandler {

	public boolean formatValid(int format) {
		return (format == CF_TEXT);
	}

	public boolean mimeTypeValid(String mimeType) {
		return mimeType.equals("text");
	}

	public int preferredFormat() {
		return CF_TEXT;
	}

	public Transferable handleData(RdpPacket data, int length) {
		String thingy = "";
		for(int i = 0; i < length; i++){
			int aByte = data.get8();
			if(aByte != 0) thingy += (char) (aByte & 0xFF);
		}
		return(new StringSelection(thingy));
	}

	public String name() {
		return "CF_TEXT";
	}


	public byte[] fromTransferable(Transferable in) {
		String s;
		if (in != null)
		{
			try {
				s = (String)(in.getTransferData(DataFlavor.stringFlavor));
			} 
			catch (Exception e) {
				s = "";
			}
			
			// TODO: think of a better way of fixing this
			s = s.replace('\n',(char) 0x0a);
			//s = s.replaceAll("" + (char) 0x0a, "" + (char) 0x0d + (char) 0x0a);
            s = Utilities_Localised.strReplaceAll(s, "" + (char) 0x0a, "" + (char) 0x0d + (char) 0x0a);
			return s.getBytes();
		}
		return null;
	}

	/* (non-Javadoc)
	 * @see net.propero.rdp.rdp5.cliprdr.TypeHandler#handleData(net.propero.rdp.RdpPacket, int, net.propero.rdp.rdp5.cliprdr.ClipInterface)
	 */
	public void handleData(RdpPacket data, int length, ClipInterface c) {
		String thingy = "";
		for(int i = 0; i < length; i++){
			int aByte = data.get8();
			if(aByte == 0) {
				break;
			}
			thingy += (char) (aByte & 0xFF);
		}
		c.copyToClipboard (new StringSelection(thingy));
	}

	/* (non-Javadoc)
	 * @see net.propero.rdp.rdp5.cliprdr.TypeHandler#send_data(java.awt.datatransfer.Transferable, net.propero.rdp.rdp5.cliprdr.ClipInterface)
	 */
	public void send_data(Transferable in, ClipInterface c) {
		String s;
		if (in != null)
		{
			try {
				s = (String)(in.getTransferData(DataFlavor.stringFlavor));
			} 
			catch (Exception e) {
				s = "";
			}
			
			// TODO: think of a better way of fixing this
			s = s.replace('\n',(char) 0x0a);
			//s = s.replaceAll("" + (char) 0x0a, "" + (char) 0x0d + (char) 0x0a);
			s = Utilities_Localised.strReplaceAll(s, "" + (char) 0x0a, "" + (char) 0x0d + (char) 0x0a);
            
			
            //return s.getBytes();
			c.send_data(s.getBytes(),s.length());
		}
	}

	@Override
	public Boolean hasNewData(Clipboard clip) throws UnsupportedFlavorException, IOException {
		if (! clip.isDataFlavorAvailable(DataFlavor.stringFlavor))
			return false;
		int newHash;
		String text = (String)clip.getData(DataFlavor.stringFlavor);
		newHash = text.hashCode();
		if (newHash == this.hash) {
			return false;
		}
		else {
			this.hash = newHash;
			return true;
		}
	}

}
