/* BMPToImageThread.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.1.1.1 $
 * Author: $Author: suvarov $
 * Date: $Date: 2007/03/08 00:26:41 $
 *
 * Copyright (c) 2005 Propero Limited
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2012
 *
 * Purpose: 
 */
package net.propero.rdp.rdp5.cliprdr;

import java.awt.Image;
import java.io.ByteArrayInputStream;

import net.propero.rdp.RdpPacket;
	 public class BMPToImageThread extends Thread {
	 	
	 	RdpPacket data; int length; ClipInterface c;
	 	
	 	public BMPToImageThread(RdpPacket data, int length, ClipInterface c){
	 		super();
	 		this.data = data;
	 		this.length = length;
	 		this.c = c;
	 	}
	 	
	 	public void run(){
			byte[] content = new byte[length];
			
				for(int i = 0; i < length; i++){
					content[i] = (byte) (data.get8() & 0xFF);
				}
				
				Image img = ClipBMP.loadbitmap(new ByteArrayInputStream(content));
			    ImageSelection imageSelection = new ImageSelection(img);
			    c.copyToClipboard(imageSelection);
	 	}
	 	
	 }
