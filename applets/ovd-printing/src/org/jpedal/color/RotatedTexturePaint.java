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
* RotatedTexturePaint.java
* ---------------
*/
package org.jpedal.color;

import org.jpedal.render.DynamicVectorRenderer;

import java.awt.image.BufferedImage;
import java.awt.image.ColorModel;
import java.awt.*;
import java.awt.geom.Rectangle2D;
import java.awt.geom.AffineTransform;

public class RotatedTexturePaint implements Paint, PdfPaint {

    BufferedImage img;

	/**copy of raw tile if rotated*/
	DynamicVectorRenderer glyphDisplay=null;

	TexturePaint rotatedPaint;

	private float[][] matrix;

	private float yStep;

	private float dx;

	private float dy;

	private AffineTransform imageScale;

	private float xStep;

	private float xx;

	private float yy;

    public RotatedTexturePaint(DynamicVectorRenderer glyphDisplay, float[][] matrix, float xStep, float yStep, float dx, float dy, AffineTransform imageScale) {
        this.glyphDisplay = glyphDisplay;

        this.matrix = matrix;
        this.xStep = xStep;
        this.yStep = yStep;
        this.dx = dx;
        this.dy = dy;

        this.imageScale = imageScale;

        if (this.matrix[0][0] != 0 && this.matrix[1][1] != 0) {
            xx = this.xStep * this.matrix[0][1];
            yy = this.yStep * this.matrix[1][0];
        }
    }


    public PaintContext createContext(ColorModel cm,Rectangle db, Rectangle2D ub,
			AffineTransform xform, RenderingHints hints) {

        //create each rotated as single huge panel to fit gap as workaround to java

        float startX=0,startY=0;

        //workout required size
        int w=(int)(ub.getWidth());
        int h=(int)(ub.getHeight());

        BufferedImage image=new BufferedImage(w,h, BufferedImage.TYPE_INT_ARGB);
        Graphics2D g2 = image.createGraphics();

        AffineTransform defaultAf2=g2.getTransform();

        float offX=0,offY=0;

        float rotatedWidth=(xStep *matrix[0][0])-(yStep *matrix[1][0]);

        float rotatedHeight=-(yStep *matrix[1][1])-(xStep *matrix[0][1]);
        float shapeW=ub.getBounds().width;
        float shapeH=ub.getBounds().height;
        //int shapeCountW=(int)((shapeW/rotatedWidth));
        int shapeCountH=(int)((shapeH/rotatedHeight));

        if(shapeCountH>1){

            offX=(shapeW-(rotatedHeight*(shapeCountH)));//-19;
            offY=5-(shapeH-(rotatedWidth*shapeCountH));//-32;

        }else if(rotatedHeight>shapeW){
            offX=rotatedHeight-shapeW;//5;
            offY=shapeH-rotatedWidth;//20;
        }else{
            offX=(shapeH-rotatedHeight);//28;
            offY=(shapeW-rotatedWidth);//-5;
        }

        //if tile is smaller than Xstep,Ystep, tesselate to fill
        float y=0;
        for(y=0;y<h+ yStep +dy;y=y+dy){

            startY=-yy-yy;

            for(float x=-dx;x<w+ xStep +dx;x=x+dx){

//					if(isUpsideDown)
//						g2.translate(x+startX,-(y+startY));
//					else
                g2.translate(offX+x+startX,offY+y+startY);

                glyphDisplay.paint(g2,null,imageScale,null,false,false);
                g2.setTransform(defaultAf2);

                startY=startY+yy;

            }
            startX=startX-xx;

        }

        Rectangle rect=ub.getBounds();
        rotatedPaint=new TexturePaint(image,new Rectangle(rect.x,rect.y,rect.width,rect.height));

        return rotatedPaint.createContext(cm, db, ub, xform, new RenderingHints(
					RenderingHints.KEY_RENDERING,
					RenderingHints.VALUE_RENDER_QUALITY));
    }

    public void setScaling(double cropX,double cropH,float scaling){

	}

	public boolean isPattern() {
		return false;
	}

	public void setPattern(int dummy) {

	}

	public int getRGB() {
		return 0;
	}

    public int getTransparency() {
        return 0;  //To change body of implemented methods use File | Settings | File Templates.
    }
}
