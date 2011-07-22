/* RdesktopCanvas_Localised.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.2 $
 * Author: $Author: suvarov $
 * Date: $Date: 2007/03/13 18:20:48 $
 *
 * Copyright (c) 2005 Propero Limited
 *
 * Purpose: Java 1.4 specific extension of RdesktopCanvas class
 */
package net.propero.rdp;

import java.awt.image.*;
import java.awt.*;
import java.io.File;
import java.io.IOException;

import javax.imageio.ImageIO;

// Created on 03-Sep-2003

public class RdesktopCanvas_Localised extends RdesktopCanvas {
	private Robot robot = null;

    public void saveToFile(Image image){
        if(this.opt.server_bpp == 8) return;
           
        BufferedImage img = null;
         
        img = new BufferedImage(image.getWidth(null), image.getHeight(null),BufferedImage.TYPE_INT_RGB);
        Graphics g = img.getGraphics();
        g.drawImage(image, 0, 0, null);
        
        // Write generated image to a file
        try {
            // Save as JPEG
            File file = new File("./testimages/" + this.opt.imgCount + ".jpg");
            this.opt.imgCount++;
            ImageIO.write(img, "jpg", file);
        } catch (IOException e) {
        }
        
        g.dispose();
    }    
        
    
	RdesktopCanvas_Localised(Options opt_, Common common_){
		super(opt_,common_);
	}
	
	public void movePointer(int x, int y){
		try {
			Point p = this.getLocationOnScreen();
			x = x + p.x;
			y = y + p.y;
			if (robot != null)
				robot.mouseMove(x, y);
		}
		catch (IllegalStateException e) {
			RdesktopCanvas_Localised.logger.debug("Unable to move pointer: "+e.getMessage());
		}
	}

	protected Cursor createCustomCursor(Image wincursor, Point p, String s, int cache_idx){
		return Toolkit.getDefaultToolkit().createCustomCursor(wincursor, p, "");
	}	
	
		public void addNotify(){
		super.addNotify();
/*
		if (robot == null) {
			try {
				robot = new Robot();
			} catch(AWTException e) {
			logger.warn("Pointer movement not allowed");
			}
		}
*/
	}
        		
	public void update(Graphics g) {
		        
  		Rectangle r = g.getClipBounds();

		if ((r.x < 0) || (r.x >= this.opt.width) || (r.y < 0) || (r.y >= this.opt.height) || (r.width > this.opt.width) || (r.height > this.opt.height)) {
			String msg = new String("[RdesktopCanvas_Localised.update] old: "+r);
			r.x = Math.max(r.x, 0);
			r.x = Math.min(r.x, this.opt.width - 1);
			r.y = Math.max(r.y, 0);
			r.y = Math.min(r.y, this.opt.height - 1);
			r.width = Math.min(r.width, this.opt.width);
			r.height = Math.min(r.height, this.opt.height);
			RdesktopCanvas_Localised.logger.warn(msg+"\tnew: "+r);
		}

	    g.drawImage(backstore.getSubimage(r.x,r.y,r.width,r.height),r.x,r.y,null);
        
        if(this.opt.save_graphics){
            saveToFile(backstore.getSubimage(r.x,r.y,r.width,r.height));
        }
                        
       
        //}
  		
    }
        
}
