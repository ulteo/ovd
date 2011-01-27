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
* OverStream.java
* ---------------
*/
package org.jpedal.objects.acroforms.decoding;

import java.awt.Color;
import java.awt.Rectangle;
import java.awt.image.BufferedImage;
import java.util.HashMap;
import java.util.Map;
import java.util.StringTokenizer;

import org.jpedal.color.DeviceCMYKColorSpace;
import org.jpedal.fonts.PdfFont;
import org.jpedal.io.PdfObjectReader;
import org.jpedal.objects.acroforms.formData.FormObject;
import org.jpedal.objects.acroforms.utils.ConvertToString;
import org.jpedal.utils.LogWriter;
import org.jpedal.utils.Strip;

/**
 * @author chris
 *
 * reads the form map object and sets up another map which is specified in FormsMapData.txt
 */
public class OverStream implements FormDecoder {
	final public static boolean debugUnimplemented = false;//to show unimplemented parts*/
	final public static boolean debug = false;//print info to screen
	
	/**
	 * exit when an unimplemented feature or error has occured in form/annot code
	 */
	final public static boolean exitOnError=false;
	
	/**
	 * flag to show the icons as they are created
	 */
	protected static boolean showIconsOnCreate=false;
	
    /**shows if annot or form - we assume its a form as we over-ride this class for annot*/
    protected int type;
    
    /* variables for forms to check with the (Ff)  flags field
     * (1<<bit position -1), to get required result
     */
    final public static int READONLY=(1);//1
    final public static int REQUIRED=(1<<1);//2
    final public static int NOEXPORT=(1<<2);//4
	final public static int MULTILINE=(1<<12);//4096;
	final public static int PASSWORD=(1<<13);//8192;
	final public static int NOTOGGLETOOFF=(1<<14);//16384;
	final public static int RADIO=(1<<15);//32768;
	final public static int PUSHBUTTON=(1<<16);//65536;
	final public static int COMBO=(1<<17);//131072;
	final public static int EDIT=(1<<18);//262144;
	final public static int SORT=(1<<19);//524288;
	final public static int FILESELECT=(1<<20);//1048576
	final public static int MULTISELECT=(1<<21);//2097152
	final public static int DONOTSPELLCHECK=(1<<22);//4194304
	final public static int DONOTSCROLL=(1<<23);//8388608
	final public static int COMB=(1<<24);//16777216
	final public static int RADIOINUNISON=(1<<25);//33554432 //same as RICHTEXT
	final public static int RICHTEXT=(1<<25);//33554432 //same as RADIOINUNISON
	final public static int COMMITONSELCHANGE=(1<<26);//67108864
	
	/** transparant image for use when appearance stream is null */
    final protected static BufferedImage OpaqueImage = new BufferedImage(20,20,BufferedImage.TYPE_INT_ARGB);
    
    /** handle of file reader for form streams*/
    protected PdfObjectReader currentPdfFile;
    
    /**stop anyone creating empty  instance*/
    protected OverStream(){}
    
    private static Map currentItems = new HashMap();
    
	/**
	 * initialize internal structure
	 */
	public OverStream(PdfObjectReader inCurrentPdfFile) {
		currentPdfFile = inCurrentPdfFile;
	}
	
	/**
	 * checks if the string <b>toBeChecked</b> has already been setup,
	 * if so returns true, meaning that you should move on the next field
	 */
	public boolean doesItemExist(Object toCheck){
		if(toCheck==null)
			return false;
		return (currentItems.containsKey(toCheck));
	}
	
	/**
	 * checks if the string <b>toAdd</b> is already in the list,
	 * if not adds it and returns true,
	 * returns false if the item should not be added
	 */
	public boolean addItem(Object toAdd){
		if(toAdd==null)
			return false;
		toAdd = Strip.removeArrayDeleminators(toAdd.toString());
		
		if(!doesItemExist(toAdd)){
			currentItems.put(toAdd,"x");
			return true;
		}else {
			return false;
		}
	}
	
	/**
	 * resets the items map, when you open a new page or file
	 */
	public void resetItems(){
		currentItems = new HashMap();
	}
	
	protected Rectangle createBoundsRectangle(String rect,FormObject formObject) {
        
        rect = Strip.removeArrayDeleminators(rect);
        StringTokenizer tok = new StringTokenizer(rect);
        
        double x1=Float.parseFloat(tok.nextToken()),y1=Float.parseFloat(tok.nextToken()),
        x2=Float.parseFloat(tok.nextToken()),y2=Float.parseFloat(tok.nextToken());
        
        if(x1>x2){
        	double tmp = x1;
        	x1 = x2;
        	x2 = tmp;
        }
        if(y1>y2){
        	double tmp = y1;
        	y1 = y2;
        	y2 = tmp;
        }
        
        Rectangle bBox = new Rectangle((int)x1,(int)y1,(int) (x2-x1),(int)(y2-y1));
        
        if(formObject!=null)
        	formObject.setBoundingRectangle(bBox);
        else 
        	return bBox;
        
        return null;
    }

	/**
	 * takes a String <b>colorString</b>, and turns it into the color it represents
	 * e.g. (0.5)  represents gray (127,127,127)
	 */
	protected  Color generateColorFromString(String colorString) {
//		0=transparant
//		1=gray
//		3=rgb
//		4=cmyk
		if(debug)
			System.out.println("CHECK generateColorFromString="+colorString);
		
		colorString = Strip.removeArrayDeleminators(colorString);
		StringTokenizer tokens = new StringTokenizer(colorString,"() ,");
		
		String[] toks = new String[4];
		int i=0;
		while(tokens.hasMoreTokens()){
			
			String tok = tokens.nextToken();
			if(debug)
				System.out.println("token"+(i+1)+ '=' +tok);
			
			toks[i] = tok;
			i++;
		}
		
		Color newColor = null;
		if(i==0){
		    LogWriter.writeFormLog("{stream} CHECK transparent color",debugUnimplemented);
		    newColor = new Color(0,0,0,0);//if num of tokens is 0 transparant, fourth variable my need to be 1
		
		}else if(i==1){
		    if(debug)
		    	System.out.println("{stream} CHECK gray color="+toks[0]);
		    
		    float tok0 = Float.parseFloat(toks[0]);
		    
		    if(tok0<=1){
		    	newColor = new Color(tok0,tok0,tok0);
		    }else {
		    	newColor = new Color((int)tok0,(int)tok0,(int)tok0);
		    }
		    
		}else if(i==3){
		    if(debug)
		        System.out.println("rgb color="+toks[0]+ ' ' +toks[1]+ ' ' +toks[2]);
		    
		    float tok0 = Float.parseFloat(toks[0]);
		    float tok1 = Float.parseFloat(toks[1]);
		    float tok2 = Float.parseFloat(toks[2]);
		    
		    if(tok0<=1 && tok1<=1 && tok2<=1){
		    	newColor = new Color(tok0,tok1,tok2);
		    }else {
		    	newColor = new Color((int)tok0,(int)tok1,(int)tok2);
		    }
		    
		}else if(i==4){
	        LogWriter.writeFormLog("{stream} CHECK cmyk color="+toks[0]+ ' ' +toks[1]+ ' ' +toks[2]+ ' ' +toks[3],debugUnimplemented);
		   /** float[] cmyk = {
		            Float.parseFloat(toks[0]),
		            Float.parseFloat(toks[1]),
		            Float.parseFloat(toks[2]),
		            Float.parseFloat(toks[3])
		    };*/
		    
		    DeviceCMYKColorSpace cs=new DeviceCMYKColorSpace();
		    cs.setColor(toks,3);
		    newColor =(Color) cs.getColor();
		    
		    //newColor = new Color(ColorSpace.getInstance(ColorSpace.TYPE_CMYK),cmyk,1);
		    
		}else{
		    LogWriter.writeFormLog("{stream} ERROR i="+i+" toks="+ConvertToString.convertArrayToString(toks),debugUnimplemented);
		}
		
		/*if(debug){
			BufferedImage img = new BufferedImage(20,20,BufferedImage.TYPE_INT_ARGB);
			Graphics g = img.getGraphics();
			g.setColor(newColor);
			g.fillRect(0,0,20,20);
			g.dispose();
			ShowGUIMessage.showGUIMessage("color",img,"color");
		}*/
		return newColor;
	}

	/**
	 * return the characteristic type
	 */
	public void workOutCharachteristic(String annotationFlags,FormObject formObject) {
		
		if(annotationFlags!=null){
			int flagValue = Integer.parseInt(annotationFlags);
			
			if(((flagValue & 1)==1)){		//bit 1		invisible
				formObject.setCharacteristic(1);
			}
			if(((flagValue & 2)==2)){//bit 2		hidden
				formObject.setCharacteristic(2);
			}
			if(((flagValue & 4)==4)){//bit 3		print
				formObject.setCharacteristic(3);
			}
			if(((flagValue & 8)==8)){//bit 4		nozoom
				formObject.setCharacteristic(4);
			}
			if(((flagValue & 16)==16)){//bit 5	norotate
				formObject.setCharacteristic(5);
			}
			if(((flagValue & 32)==32)){//bit 6	noview
				formObject.setCharacteristic(6);
			}
			if(((flagValue & 64)==64)){//bit 7	readonly
				formObject.setCharacteristic(7);
			}
			if(((flagValue & 128)==128)){//bit 8	locked
				formObject.setCharacteristic(8);
			}
			if(((flagValue & 256)==256)){//bit 9	togglenoview
				formObject.setCharacteristic(9);
			}
		}
		
		if(debug){
            String[] annotes = {"invisible","hidden","print","nozoom","norotate","noview","read only","locked","togglenoview"};
            System.out.print("F - the fields annotation=");
            boolean[] index = formObject.getCharacteristics();
            if(index!=null){
            	for(int i=0;i<index.length;i++){
            		System.out.println(annotes[i]+" is "+index[i]);
            	}
            }
        }
	}

	/**
	 * takes the PDF commands and creates a font 
	 */
	public void decodeFontCommandObj(String fontStream,FormObject formObject){
		
		//now parse the stream into a sequence of tokens
		StringTokenizer tokens=new StringTokenizer(fontStream,"() "); 
		int tokenCount=tokens.countTokens();
		String[] tokenValues=new String[tokenCount];
		int i=0;
		while(tokens.hasMoreTokens()){
			tokenValues[i]=tokens.nextToken();
			i++;
		}
		
		//now work out what it does and build up info
		for(i=tokenCount-1;i>-1;i--){
//			System.out.println(tokenValues[i]+" "+i);
			
			//look for commands 
			if(tokenValues[i].equals("g")){ //set color (takes 1 values
				i--;
				float col=0;
				try{
					col=Float.parseFloat(tokenValues[i]);
				}catch(Exception e){
					LogWriter.writeLog("Error in generating g value "+tokenValues[i]);
				}
				
	            formObject.setTextColor(new Color(col,col,col));
	            
			}else if(tokenValues[i].equals("Tf")){ //set font (takes 2 values - size and font
				i--;
				int textSize=8;
				try{
					textSize=(int) Float.parseFloat(tokenValues[i]);
//					if(textSize==0)
//						textSize = 0;//TODO check for 0 sizes CHANGE size to best fit on 0
				}catch(Exception e){
					LogWriter.writeLog("Error in generating Tf size "+tokenValues[i]);
				}
				
				i--;//decriment for font name
				String font=null;
				try{
					font=tokenValues[i];
					if(font.startsWith("/"))
						font = font.substring(1);
				}catch(Exception e){
					LogWriter.writeLog("Error in generating Tf font "+tokenValues[i]);
				}
				
				
				//map font as carefully as possible
				//using PdfStreamDecoders font routines
/*				if(appearanceObject!=null){
					Map currentFontMap=appearanceObject.getFontMap();
	                PdfFont newFont = (PdfFont)currentFontMap.getxx(Strip.checkRemoveLeadingSlach(tokenValues[i]));
					
	                currentAt.textFont=newFont.setFont(newFont.getFontName(),textSize);
				}else */{
				    PdfFont currentFont=new PdfFont();
				    
	                formObject.setTextFont(currentFont.setFont(font, textSize));
	                
				}
				
                formObject.setTextSize(textSize);

			}else if(tokenValues[i].equals("rg")){
				i--;
				float b=Float.parseFloat(tokenValues[i]);
				i--;
				float g=Float.parseFloat(tokenValues[i]);
				i--;
				float r=Float.parseFloat(tokenValues[i]);
				
	            formObject.setTextColor(new Color(r,g,b));

//				if(debug)
//					System.out.println("rgb="+r+","+g+","+b+" rg     CHECK ="+currentAt.textColor);
				
			}else if(tokenValues[i].equals("Sig")){
				LogWriter.writeFormLog("Sig-  UNIMPLEMENTED="+fontStream+"< "+i,debugUnimplemented);
			}else if(tokenValues[i].equals("\\n")){
			    //ignore \n
				if(debug)
					System.out.println("ignore \\n");
			}else {
			    LogWriter.writeFormLog("{stream} Unknown FONT command "+tokenValues[i]+ ' ' +i+" string="+fontStream,debugUnimplemented);
			}
		}
	}

	public void createAppearanceString(FormObject formObject, Map currentField, PdfObjectReader currentPdfFile,int formPage) {
		
	}
}
