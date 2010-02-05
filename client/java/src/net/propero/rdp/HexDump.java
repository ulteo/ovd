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
    public static void encode(byte[] data, String msg/*PrintStream out*/) {
    	int i, thisline, offset = 0;

    	while (offset < data.length)
    	{
    		System.out.print(String.format("%04x ", offset));
    		thisline = data.length - offset;
    		if (thisline > 16)
    			thisline = 16;

    		for (i = 0; i < thisline; i++)
    			System.out.print(String.format("%02x ", data[offset + i]));

    		for (i = 0; i < 16; i++)
    			System.out.print("   ");

    		for (i = 0; i < thisline; i++)
    			System.out.print(String.format("%c", (data[offset + i] >= 0x20 && data[offset + i] < 0x7f) ? data[offset + i] : '.'));

    		System.out.println("");
    		offset += thisline;
    	}
    }
		/*int count = 0;
		String index;
		String number;
		
		logger.debug(msg);
		
		while(count < data.length) {
		    index = Integer.toHexString(count);
		    switch(index.length()) {
		    	case(1):
		    		index = "0000000".concat(index);
		    		break;
		    	case(2):
		    		index = "000000".concat(index);
		    		break;
		    	case(3):
		    		index = "00000".concat(index);
		    		break;
		    	case(4):
		    		index = "0000".concat(index);
		    		break;
		    	case(5):
		    		index = "000".concat(index);
		    		break;
		    	case(6):
		    		index = "00".concat(index);
		    		break;
		    	case(7):
		    		index = "0".concat(index);
		    		break;
		    	case(8):
		    		break;
		    	default:
		    		return;
		    }
		    
		    index += ": ";
		    //out.print(index + ": ");
		    for(int i = 0; i < 16; i++) {
		    	if(count >= data.length) {
		    		break;
		    	}
		    	number = Integer.toHexString((data[count]&0x000000ff));
		    	switch(number.length()) {
		    		case(1):
		    			number = "0".concat(number);
		    			break;
		    		case(2):
		    			break;
		    		default:
		    			logger.debug(index);
		    			//out.println("");
		    			return;
		    	}
		    	index+= (number + " "); 
		    	//out.print(number + " ");
		    	count++;
		    }
		    logger.debug(index);
		    //out.println("");
		}
	
    }*/
}
