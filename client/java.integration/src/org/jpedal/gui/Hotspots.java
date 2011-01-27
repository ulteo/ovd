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
* Hotspots.java
* ---------------
*/
package org.jpedal.gui;

import java.awt.Color;
import java.awt.Graphics;
import java.awt.Graphics2D;
import java.awt.Image;
import java.awt.Point;
import java.awt.Rectangle;
import java.awt.Stroke;
import java.awt.geom.AffineTransform;
import java.awt.image.BufferedImage;
import java.io.ByteArrayOutputStream;
import java.io.ObjectOutputStream;
import java.io.Serializable;
import java.net.URL;
import java.util.*;

import javax.imageio.ImageIO;
import javax.swing.ImageIcon;

import org.jpedal.objects.PdfAnnots;
import org.jpedal.utils.LogWriter;

/**
 * holds all hotspots
 */
public class Hotspots implements Serializable {
    
	/**areas for annotations*/
    protected Rectangle[] pos;
    
    private String[] annotationTypes={};
	
    /**colors for annotaions*/
    private Color[] annotationColors;
    
    /**strokes for Annotations - ie border*/
    private Stroke[] annotationStrokes;
    
    /**border color*/
    private Color[] borderColor;

    /**flag to show if has embedded icon*/
    private boolean[] hasOwnIcon;
    
    /**icons for annotaions, marked as transient because they dont serialize*/
    private transient Image[] icons;
    
    /**icons for annotaions*/
    private String[] tooltips;
    
    /**type for each*/
    private int[] annotationType;
    
    /**graphics used for annotations, marked as transient because they dont serialize*/
 	private transient Image[] AnnotationIcons;
 	
 	/**used to workout correct icon to diplay*/
 	private Map annotationLookup=new HashMap();

	/**flag to show each icon has own icon but none present*/
	public static final Integer NO_ICONS = new Integer(0);

	/**flag to show each icon has own icon*/
	public static final Integer EACH_ICON_UNIQUE = new Integer(1);

	/**flag to show each type of Icon has own icon - ie Text,Attachment*/
	public static final Integer EACH_TYPE_UNIQUE = new Integer(2);


	/**
	 * setup link hotspots on page or flush for no hotspots
	 */
	public void setHotspots(PdfAnnots annotsData) {

		int count=annotsData.getAnnotCount();

		if(count>0){

			annotationColors=new Color[count];
			icons=new Image[count];
			annotationStrokes=new Stroke[count];
			borderColor=new Color[count];
            hasOwnIcon=new boolean[count];
            tooltips=new String[count];
			pos=new Rectangle[count];
			annotationType=new int[count];

			for(int i=0;i<count;i++){

				//create a rectangle
				String rawArea=annotsData.getAnnotObjectArea(i);

                if(rawArea==null)
                continue;
                
                String subtype=annotsData.getAnnotSubType(i);

				StringTokenizer values=new StringTokenizer(rawArea);

				int[] coords=new int[4];
				for(int ii=0;ii<4;ii++)
					coords[ii]=(int)Float.parseFloat(values.nextToken());
				int h=coords[1]-coords[3];
				//NOTE fiddle on height because PDF co-ords and Graphics"d are opposites in y -axis
				if(h>0)
					pos[i]=new Rectangle(coords[0],coords[1]-h,coords[2]-coords[0],h);
				else
					pos[i]=new Rectangle(coords[0],coords[3]+h,coords[2]-coords[0],-h);

				//get other values to draw annotation
				annotationColors[i]=annotsData.getAnnotColor(i);
				annotationStrokes[i]=annotsData.getBorderStroke(i);
				borderColor[i]=annotsData.getBorderColor(i);

				tooltips[i]=annotsData.getField(i,"Contents");

                hasOwnIcon[i]=annotsData.hasOwnIcon(i);

                /**setup image - later if you want you own*/
				Object rawValue=annotationLookup.get(subtype);
				int  imageType=0;
				if(rawValue!=null)
				imageType=((Integer) annotationLookup.get(subtype)).intValue();

				if(rawValue==null)
					icons[i]=null;//AnnotationIcons[0];
				else
					icons[i]=AnnotationIcons[imageType];

				annotationType[i]=imageType;
			}

		}else{ //no hotspots so disable
			pos=null;
				annotationStrokes=null;
				annotationColors=null;
				icons=null;
				tooltips=null;
		}
	}
	
	/**remove the annotations displayed*/
	public void flushAnnotationsDisplayed(){
	    annotationColors=null;
        annotationStrokes=null;
        borderColor=null;
        hasOwnIcon=null;
        pos=null;
	}
	
	/**setup icons for annotations*/
	public Hotspots(String[] annotationTypes,String path){
	    
	    init(annotationTypes, path);
	}
	

    /**
     * @param annotationTypes
     * @param path
     */
    private void init(String[] annotationTypes, String path) {
        this.annotationTypes=annotationTypes;
	    int count=annotationTypes.length;
	    
	    AnnotationIcons=new Image[annotationTypes.length];
	    
	    for(int i=1;i<count;i++){
	    	URL img=null;
	        try{
                img =getClass().getResource('/' +path+annotationTypes[i]+".gif");
            }catch(Exception e){
	        	LogWriter.writeLog("Exception "+e+" Unable to log images for annotations");
            }
	       
	        if(img!=null)
	            AnnotationIcons[i]=new ImageIcon(img).getImage();
	        
	        annotationLookup.put(annotationTypes[i],new Integer(i));
	    }
	    
    }
    
    public void checkType(String type){
	    	if(annotationLookup.get(type)==null){
	    		Integer nextItem=new Integer(annotationLookup.keySet().size());
	    		annotationLookup.put(type,nextItem);
	    }
	    
    }

    /**
     * not for general use (used by JPedal internally)
     */
    public Hotspots() {
        
        final String[] defaultTypes={"Other","Text","FileAttachment"};
        init(defaultTypes, "org/jpedal/examples/simpleviewer/annots/");
    }

    /**
     * get any annotation tooltip
     */
    public String getTooltip(Point current_p, Map individualIcons,
            int page) {
        
        String result=null;
        
        if(pos!=null){
		    int count=pos.length;
		    for (int i = 0; i < count; i++) {
		        //if(annotationhotSpots[i]!=null)
		            //System.out.println(annotationhotSpots[i].getBounds());
		        if ((pos[i]!=null)&&(tooltips[i]!=null)
					&& ((pos[i].contains(current_p)))) {
					result = tooltips[i];
					
					//allow for custom mapping on tooltip icons
					if((individualIcons!=null)&&
					(individualIcons.get((page)+"-"+annotationTypes[this.annotationType[i]])==null))
					result=null;
					
					//System.out.println(annotationhotSpots[i]+"<<"+result);
					i = count;
					
				}
			}
		}
        
        return result;
        
    }

    /**
	 * draw any hotspots
	 */
	public void addHotspotsToDisplay(Graphics2D g2, Map individualIcons,int page) {
		

        if (pos != null) {
			int count = pos.length; 
			if (individualIcons != null) {
                Image[]  iconsUsed;

				for(int j=0;j<annotationTypes.length;j++){          
					iconsUsed=(Image[]) individualIcons.get((page)+"-"+annotationTypes[j]);

					int current=0;
					if(iconsUsed!=null){

						for (int i = 0; i < count; i++) {

							if(this.annotationType[i]==j && !hasOwnIcon[i]){ //over-ride with own if set

                                //<link><a name="upsidedown">
                                //draw icons upside down
                                try {
                                    if (iconsUsed[current] != null) {
										AffineTransform af = new AffineTransform();
										af.translate(pos[i].x,pos[i].y+ iconsUsed[current].getHeight(null));
										af.scale(1, -1);
										g2.drawImage(iconsUsed[current],af, null);
									}
								} catch (Exception e) {
									LogWriter.writeLog("Insufficient icons for page");
									i = count;         
								}       
								current++;
							}
						}
					}
				}
			} else {

				for (int i = 0; i < count; i++) {

					if ((pos!=null)&&(pos[i] != null) &&(icons!=null)&&(icons[i]!=null)&& !hasOwnIcon[i]){
                        if ((annotationStrokes[i] != null)) {
							g2.setColor(borderColor[i]);
							g2.setStroke(annotationStrokes[i]);
							g2.draw(pos[i]);
						}
						AffineTransform af = new AffineTransform();
						af.translate(pos[i].x,pos[i].y+ icons[i].getHeight(null));
						af.scale(1, -1);
						if (this.icons[i] != null) {
							g2.drawImage(icons[i], af, null);
						}
					}
				}
			}
		}
	}

    /**
     * @return Returns the annotationhotSpots.
     */
    public Rectangle[] getAnnotationhotSpots() {
        return pos;
    }

}
