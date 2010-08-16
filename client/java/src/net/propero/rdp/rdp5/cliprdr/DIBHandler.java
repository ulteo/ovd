/* DIBHandler.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.1.1.1 $
 * Author: $Author: suvarov $
 * Date: $Date: 2007/03/08 00:26:40 $
 *
 * Copyright (c) 2005 Propero Limited
 *
 * Purpose: 
 */
package net.propero.rdp.rdp5.cliprdr;

import java.awt.Frame;
import java.awt.Image;
import java.awt.MediaTracker;
import java.awt.datatransfer.Clipboard;
import java.awt.datatransfer.DataFlavor;
import java.awt.datatransfer.Transferable;
import java.awt.datatransfer.UnsupportedFlavorException;
import java.awt.image.ImageObserver;
import java.io.IOException;
import org.apache.log4j.Logger;

import net.propero.rdp.Common;
import net.propero.rdp.Input;
import net.propero.rdp.RdpPacket;
import net.propero.rdp.Utilities_Localised;


public class DIBHandler extends TypeHandler implements ImageObserver {

	protected static Logger logger = Logger.getLogger(Input.class);
	protected Common common = null;
	
	public DIBHandler(Common common_) {
		this.common = common_;
	}
	
	public boolean formatValid(int format) {
		return (format == CF_DIB);
	}

	public boolean mimeTypeValid(String mimeType) {
		return mimeType.equals("image");
	}

	public int preferredFormat() {
		return CF_DIB;
	}
		 
	public String name() {
		return "CF_DIB";
	}
	
	public void handleData(RdpPacket data, int length, ClipInterface c) {
		//System.out.println("DIBHandler.handleData");
		BMPToImageThread t = new BMPToImageThread(data, length, c);
		t.start();
	}

	public void send_data(Transferable in, ClipInterface c) {
		byte[] out = null;
		
		try {
            if (in != null && in.isDataFlavorSupported(Utilities_Localised.imageFlavor)) {
                Image img = (Image)in.getTransferData(Utilities_Localised.imageFlavor);
                ClipBMP b = new ClipBMP();
                
                MediaTracker mediaTracker = new MediaTracker(new Frame());
                mediaTracker.addImage(img, 0);
                
                try
			    {
			      mediaTracker.waitForID(0);
			    }
			    catch (InterruptedException ie)
			    {
			      System.err.println(ie);
                  if(!this.common.underApplet) System.exit(1);
			    }
                if(img == null) return;
                
                int width = img.getWidth(this);
                int height = img.getHeight(this);
                out = b.getBitmapAsBytes(img,width,height);

                c.send_data(out,out.length);
            }
        } catch (UnsupportedFlavorException e) {
            System.err.println("Failed to send DIB: UnsupportedFlavorException");
        } catch (IOException e) {
            System.err.println("Failed to send DIB: IOException");
        }

	}

	public boolean imageUpdate(Image arg0, int arg1, int arg2, int arg3, int arg4, int arg5) {
		return false;
	}

	@Override
	public Boolean hasNewData(Clipboard clip) throws UnsupportedFlavorException, IOException {
		if (! clip.isDataFlavorAvailable(DataFlavor.imageFlavor))
			return false;
		int newHash;
		Image img = (Image)clip.getData(DataFlavor.imageFlavor);
		//TODO find better method to get hash for the img
		newHash = img.getHeight(null) + 1000*img.getWidth(null);
		if (newHash == this.hash) {
			return false;
		}
		else {
			this.hash = newHash;
			return true;
		}
	}

}
