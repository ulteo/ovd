/* HexDump.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.1.1.1 $
 * Author: $Author: suvarov $
 * Date: $Date: 2007/03/08 00:26:29 $
 *
 * Copyright (c) 2005 Propero Limited
 *
 * Purpose: Manages debug information for all data
 *          sent and received, outputting in hex format
 */
package net.propero.rdp;

import org.apache.log4j.Logger;
import org.apache.log4j.Level;

public class HexDump {
	static Logger logger = Logger.getLogger(HexDump.class);

    /**
     * Construct a HexDump object, sets logging level to Debug
     */
    public HexDump() {
    	logger.setLevel(Level.DEBUG);
    }

    /**
     * Encode data as hex and output as debug messages along with supplied custom message
     * @param data Array of byte data to be encoded
     * @param msg Message to include with outputted hex debug messages
     */
    public static void encode(byte[] data, String msg) {
    	int i, thisline, offset = 0;

	if (msg != null)
		System.out.println(msg);

	String dump = new String();
    	while (offset < data.length)
    	{
    		dump += String.format("%04x ", offset);
    		thisline = data.length - offset;
    		if (thisline > 16)
    			thisline = 16;

    		for (i = 0; i < thisline; i++)
    			dump += String.format("%02x ", data[offset + i]);

    		for (i = 0; i < 16; i++)
    			dump += "   ";

    		for (i = 0; i < thisline; i++)
    			dump += String.format("%c", (data[offset + i] >= 0x20 && data[offset + i] < 0x7f) ? data[offset + i] : '.');

    		dump += "\n";
    		offset += thisline;
    	}
	System.out.println(dump);
    }

    public static void encode(RdpPacket_Localised data, String msg) {
	    byte[] buffer = new byte[data.size()];
	    data.copyToByteArray(buffer, 0, 0, buffer.length);

	    HexDump.encode(buffer, msg);
    }
}
