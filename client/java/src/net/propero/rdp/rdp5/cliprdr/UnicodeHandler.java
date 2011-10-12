/* UnicodeHandler.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.1.1.1 $
 * Author: $Author: suvarov $
 * Date: $Date: 2007/03/08 00:26:40 $
 *
 * Copyright (c) 2005 Propero Limited
 *
 * Purpose: 
 * 
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
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
package net.propero.rdp.rdp5.cliprdr;

import java.awt.datatransfer.Clipboard;
import java.awt.datatransfer.DataFlavor;
import java.awt.datatransfer.StringSelection;
import java.awt.datatransfer.Transferable;
import java.awt.datatransfer.UnsupportedFlavorException;
import java.io.IOException;
import java.io.UnsupportedEncodingException;
import java.nio.charset.Charset;

import org.ulteo.ovd.integrated.OSTools;

import net.propero.rdp.RdpPacket;
import net.propero.rdp.Utilities_Localised;

public class UnicodeHandler extends TypeHandler {

	public boolean formatValid(int format) {
		return (format == CF_UNICODETEXT);
	}

	public boolean mimeTypeValid(String mimeType) {
		return mimeType.equals("text");
	}

	public int preferredFormat() {
		return CF_UNICODETEXT;
	}

	public void handleData(RdpPacket data, int length, ClipInterface c) {
		byte[] array = new byte[length];
		data.copyToByteArray(array, 0, data.getPosition(), length);
		String thingy = new String(array, Charset.forName("UTF-16LE"));

		// Linux use \n instead \r\n
		if (OSTools.isLinux())
			thingy = Utilities_Localised.strReplaceAll(thingy, "" + (char) 0x0d + (char) 0x0a, "" + (char) 0x0a);
		thingy = thingy.replace("\0", "");
		c.copyToClipboard(new StringSelection(thingy));
		//return(new StringSelection(thingy));
	}

	public String name() {
		return "CF_UNICODETEXT";
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
			
			// Linux use \n instead \r\n 
			if (OSTools.isLinux())
				s = Utilities_Localised.strReplaceAll(s, "" + (char) 0x0a, "" + (char) 0x0d + (char) 0x0a);
			byte[] sBytes = null;
			try {
				sBytes = s.getBytes("UTF-16LE");
			} catch (UnsupportedEncodingException e) {
				logger.debug(e.getMessage());
				if (TypeHandler.isOk) {
					TypeHandler.isOk = false;
					logger.info("UTF-16LE is not supported by your JVM");
				}				
			}
			return sBytes;
		}
		return null;
	}

	/* (non-Javadoc)
	 * @see net.propero.rdp.rdp5.cliprdr.TypeHandler#send_data(java.awt.datatransfer.Transferable)
	 */
	public void send_data(Transferable in, ClipInterface c) {
		byte[] data = fromTransferable(in);
		c.send_data(data,data.length);
	}


	@Override
	public Boolean hasNewData(Clipboard clip) throws IOException, UnsupportedFlavorException {
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
