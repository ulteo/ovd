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
* FormXObject.java
* ---------------
*/
package org.jpedal.objects.acroforms.decoding.images;

import org.jpedal.io.ObjectStore;
import org.jpedal.io.PdfObjectReader;
import org.jpedal.objects.PdfPageData;
import org.jpedal.objects.acroforms.utils.ConvertToString;
import org.jpedal.objects.raw.PdfDictionary;
import org.jpedal.objects.raw.PdfObject;
import org.jpedal.parser.PdfStreamDecoder;
import org.jpedal.render.DynamicVectorRenderer;
import org.jpedal.utils.LogWriter;
import org.jpedal.utils.Strip;

import java.awt.*;
import java.awt.geom.AffineTransform;
import java.awt.image.BufferedImage;

import java.util.Map;
import java.util.StringTokenizer;

/**
 * @author chris
 * 
 * 
 */
public class FormXObject {

    private static final boolean debug = false;
    private static final boolean showImage = false;
    
    private int width = 20;
    private int height = 20;
    
    private String whenToScale=null;
    
    private Map formFieldValues=null;

    /** handle on object reader */
    private PdfObjectReader currentPdfFile;

    
    
    
    
    /**
     * 
     */
    public FormXObject(PdfObjectReader currentPdfFile) {
        this.currentPdfFile = currentPdfFile;
    }
    
    private FormXObject() {
    }
    
    
    
    /**
     * decode appearance stream and convert into VectorRenderObject we can redraw
     * */
    public BufferedImage decode(Map currentValues){

    	boolean useHires=false;
    	
    	if(debug)
    		System.out.println("XObject Values="+currentValues);

    	try{

    		/**
    		 * generate local object to decode the stream
    		 */
    		//PdfStreamDecoder glyphDecoder=new PdfStreamDecoder();
    		
    		//switch to hires as well
    		PdfStreamDecoder glyphDecoder=new PdfStreamDecoder(currentPdfFile,useHires,true);
            
    		ObjectStore localStore = new ObjectStore();
    		glyphDecoder.setStore(localStore);

    		
    		/**
    		 * create renderer object
    		 */
    		DynamicVectorRenderer glyphDisplay=new DynamicVectorRenderer(0,false,20,localStore);
    		
    		//fix for hires
    		
    		if(!useHires)
    			glyphDisplay.setOptimisedRotation(false);
    		else
    			glyphDisplay.setHiResImageForDisplayMode(useHires);
    		
    		glyphDecoder.init(false,true,15,0,new PdfPageData(),0,glyphDisplay,currentPdfFile);

    		/**read any resources*/
    		try{
    			Map resValue =(Map) currentPdfFile.resolveToMapOrString("Resources", currentValues.get("Resources"));	

    			PdfObject Resources = PdfDictionary.convertMapToNewObject(resValue,currentPdfFile);
    			
    			if (resValue != null)
    				glyphDecoder.readResources(Resources,false);
    				
    		}catch(Exception e){
    			e.printStackTrace();
    			System.out.println("Exception "+e+" reading resources in XForm");
    		}

            /**decode the stream*/
    		byte[] commands=(byte[]) currentPdfFile.resolveToMapOrString("DecodedStream", currentValues.get("DecodedStream"));


            if(commands!=null)
    			glyphDecoder.decodeStreamIntoObjects(commands);


    		boolean ignoreColors=glyphDecoder.ignoreColors;

    		glyphDecoder=null;

    		localStore.flush();

    		org.jpedal.fonts.glyph.T3Glyph form= new org.jpedal.fonts.glyph.T3Glyph(glyphDisplay, 0,0,ignoreColors,"");

    		String rect = (String)currentPdfFile.resolveToMapOrString("BBox", currentValues.get("BBox"));
    		float rectX1=0,rectY1=0;
    		if(rect!=null){
    			rect = Strip.removeArrayDeleminators(rect);

    			StringTokenizer tok = new StringTokenizer(rect);
    			float x1=Float.parseFloat(tok.nextToken()),
    			y1=Float.parseFloat(tok.nextToken()),
    			x2=Float.parseFloat(tok.nextToken()),
    			y2=Float.parseFloat(tok.nextToken());

    			rectX1 = (x1);
    			rectY1 = (y1);
    			width=(int) (x2-x1);
    			height=(int) (y2-y1);
    			if(debug)
    				System.out.println("rectx="+rectX1+" recty="+rectY1+" w="+width+" h="+height);

    			if(formFieldValues!=null){
    				if(whenToScale==null || whenToScale.equals("A")){
    					//scale icon to fit BBox
    					Rectangle formRect = (Rectangle)formFieldValues.get("rect");
    					if(formRect.width!=width || formRect.height!=height){
//  						System.out.println("field - width="+formRect.width+" height="+formRect.height+
//  						" App - width="+width+" height="+height);


    						/** default is A Always scale to fit Form BBox, look in org.jpedal.fonts.T3Glyph D1 D0 */
    						LogWriter.writeFormLog("{stream} XObject MK IF A command, the icon should be scaled to fit the BBox",false);
    					}
    				}else if(whenToScale.equals("N")){
    					//do nothing as already does this
    				}else {
    					LogWriter.writeFormLog("{XObject} XObject MK IF Unimplemented command="+whenToScale,false);
    				}
    			}
    		}


    		if(width<0)
    			width=-width;
    		if(height<0)
    			height=-height;

    		if(width==0 || height==0)
    			return null;

    		BufferedImage aa=null;
    		Graphics2D g2=null;

    		String matrix = (String) currentPdfFile.resolveToMapOrString("Matrix", currentValues.get("Matrix"));
    		if(matrix!=null){
    			StringTokenizer tok = new StringTokenizer(Strip.removeArrayDeleminators(matrix));
    			float a = Float.parseFloat(tok.nextToken());
    			float b = Float.parseFloat(tok.nextToken());
    			float c = Float.parseFloat(tok.nextToken());
    			float d = Float.parseFloat(tok.nextToken());
    			float e = Float.parseFloat(tok.nextToken());
    			float f = Float.parseFloat(tok.nextToken());

    			int offset;
    			if(c<0){
    				aa=new BufferedImage(height,width,BufferedImage.TYPE_INT_ARGB);
    				offset=width;
    			}else{
    				aa=new BufferedImage(width,height,BufferedImage.TYPE_INT_ARGB);
    				offset=height;

    				if(e!=0f){
    					e = -rectX1;
    				}
    				if(f!=0f){
    					f = -rectY1;
    				}
    			}
    			g2=(Graphics2D) aa.getGraphics();
    			
    			AffineTransform flip=new AffineTransform();
		
    			flip.translate(0, offset);
    			flip.scale(1, -1);

    			g2.setTransform(flip);
    			
    			//removed to fix display_error.pdf
    			if(debug)
    				System.out.println("matrix="+matrix);

//  			System.out.println("rectX1 = "+rectX1);
//  			System.out.println("rectY1 = "+rectY1);
//  			System.out.println("width = "+width);
//  			System.out.println("height = "+height);

    			AffineTransform affineTransform = new AffineTransform(a,b,c,d,e,f);
    			g2.transform(affineTransform);
    		} else {
    			aa=new BufferedImage(width,height,BufferedImage.TYPE_INT_ARGB);

    			g2=(Graphics2D) aa.getGraphics();

    			AffineTransform flip=new AffineTransform();
    			flip.translate(0, height);
    			flip.scale(1, -1);
    			g2.setTransform(flip);
    		}

    		form.render(0,g2, 0f);

    		/**
			x = (e*rectX1)/e;//possible fixes for future problems
			y = (f*rectY1)/f;
    		 */

////		if(debug)
////		System.out.println("rect x="+rectX1+" y="+rectY1+" w="+width+" h="+height);
//  		}

//  		AffineTransformOp invert =new AffineTransformOp(flip,ColorSpaces.hints);
//  		image=invert.filter(image,null);

    		//System.out.println(g2);

    		g2.dispose();

            return aa;

    	}catch(Exception e){
    		e.printStackTrace();

    		return null;
    	}
    }
}
