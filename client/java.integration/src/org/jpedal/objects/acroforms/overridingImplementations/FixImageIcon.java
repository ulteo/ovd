/**
* ===========================================
* Java Pdf Extraction Decoding Access Library
* ===========================================
*
* Project Info:  http://www.jpedal.org
* (C) Copyright 1997-2008, IDRsolutions and Contributors.
*
* 	This file is part of JPedal
*
    This library is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public
    License as published by the Free Software Foundation; either
    version 2.1 of the License, or (at your option) any later version.

    This library is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    General Public License for more details.

    You should have received a copy of the GNU General Public
    License along with this library; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA


*
* ---------------
* FixImageIcon.java
* ---------------
*/
package org.jpedal.objects.acroforms.overridingImplementations;

import java.awt.*;

import javax.swing.*;


public class FixImageIcon extends ImageIcon implements Icon, SwingConstants {
    
    private static final long serialVersionUID = 8946195842453749725L;
    
	private int width = -1;
    private int height = -1;
    private Image image=null;

	private int rotation = 0;

	private int pageRotation = 0;

    public FixImageIcon(Image inImage) {
        image = inImage;
    }
    
    public void setWH(int newWidth,int newHeight){
        width = newWidth;
        height = newHeight;
    }
    
    public int getIconHeight() {
    	
    	if(image==null)
			return height;
        if(height==-1)
            return image.getHeight(null);
        else
            return height;
    }

    public int getIconWidth() {
    	
    		if(image==null)
    			return width;
    		
        if(width==-1)
            return image.getWidth(null);
        else
            return width;
    }
    
    public Image getImage(){
        return image;
    }

    public void setPageRotation(int pageRotation){
    	this.pageRotation  = pageRotation;
    }
    
    public void setRotation(int rotation) {
    	this.rotation  = rotation;
	}
    
    public void paintIcon(Component c, Graphics g, int x, int y) {
		if (image == null)
			return;

		if (c.isEnabled()) {
			g.setColor(c.getBackground());
		} else {
			g.setColor(Color.gray);
		}

		Graphics2D g2 = (Graphics2D) g;

//      g.translate(x, y);
		if (width > 0 && height > 0) {
//			AffineTransform transform = g2.getTransform();
//
//			System.out.println(transform.getScaleX()+" "+transform.getScaleY()
//					+" "+transform.getTranslateX()+" "+transform.getTranslateY()+" "+transform.getShearX()+" "+
//					transform.getShearY()+" "+pRotation+" "+rotation+" "+repaint);
//
//			System.out.println(pageRotation+" "+rotation);
			int rotationRequired = rotation - pageRotation;

//			System.out.println("rotating at = "+rotationRequired*Math.PI/180);

			if (rotationRequired == -90) {
				g2.rotate(-Math.PI / 2);
				g2.translate(-height, 0);
				g2.drawImage(image, 0, 0, height, width, null);
			} else if (rotationRequired == 90) {
				g2.rotate(Math.PI / 2);
				g2.translate(0, -width);
				g2.drawImage(image, 0, 0, height, width, null);
			} else if (rotationRequired == 180) {
				g2.rotate(Math.PI);
				g2.translate(-width, -height);
				g2.drawImage(image, 0, 0, width, height, null);
			} else if (rotationRequired == -180) {
				g2.rotate(Math.PI);
				g2.translate(width, height);
				g2.drawImage(image, 0, 0, width, height, null);
			} else {
				g2.drawImage(image, 0, 0, width, height, null);
			}

//			int absRotation = Math.abs(rotationRequired);
//			if(absRotation == 90){
//				g2.rotate(Math.PI/2);
//				g2.translate(0, -width);
//				g2.drawImage(image,0,0,height,width,null);
//			}else{
//				g2.rotate(rotationRequired*Math.PI/180, x + width / 2, y + height / 2);
//				g2.drawImage(image,0,0,width,height,null);
//			}
//
//			if(pageRotation != rotation){
//				System.out.println("in special code");
//				g2.rotate(-Math.PI/2);
//				g2.translate(-height, 0);
//				g2.drawImage(image,0,0,height,width,null);
//			} else {
//				g2.drawImage(image,0,0,width,height,null);
//			}
		} else
			g2.drawImage(image, 0, 0, null);

		g2.translate(-x, -y);
	}
}
