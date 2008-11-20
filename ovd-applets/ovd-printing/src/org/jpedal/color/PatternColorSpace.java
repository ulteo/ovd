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
* PatternColorSpace.java
* ---------------
*/
package org.jpedal.color;

//<start-os>
import com.idrsolutions.pdf.color.shading.ShadingFactory;
import org.jpedal.exception.PdfException;
import org.jpedal.io.ObjectStore;
import org.jpedal.io.PdfObjectReader;
import org.jpedal.objects.GraphicsState;
import org.jpedal.objects.PdfPageData;
import org.jpedal.objects.raw.*;
import org.jpedal.parser.PdfStreamDecoder;
import org.jpedal.render.DynamicVectorRenderer;
import org.jpedal.utils.Matrix;

import java.awt.*;
import java.awt.geom.AffineTransform;
import java.awt.image.AffineTransformOp;
import java.awt.image.BufferedImage;

//<end-os>

/**
 * handle Pattern ColorSpace
 */
public class PatternColorSpace extends GenericColorSpace{

//<start-os>
//	<start-13>

	//flag to track rotation which needs custom handling
	private boolean isRotated=false;

	PdfObjectReader currentPdfFile=null;
	private BufferedImage img;
	
	private int XStep,YStep;

	private boolean isPrinting=false;

	static final private boolean debug=false;
	private boolean colorsReversed;

	public PatternColorSpace(boolean isPrinting, PdfObjectReader currentPdfFile){

		value = ColorSpaces.Pattern;
		this.isPrinting=isPrinting;

		currentColor = new PdfColor(1.0f,1.0f,1.0f);
		this.currentPdfFile = currentPdfFile;

    }

	/**
	 * convert color value to pattern
	 */
	public void setColor(String[] value_loc,int operandCount){

        PdfPatternObject PatternObj=(PdfPatternObject) patterns.get(value_loc[0]);

        byte[] streamData=currentPdfFile.readStream(PatternObj,true,true,true, false,false);

        /**
		 * initialise common values
		 */

        // see which type of Pattern (shading or tiling)
        final int shadingType= PatternObj.getInt(PdfDictionary.PatternType);

        // get optional matrix value
		float[][] matrix=null;
        float[] inputs=PatternObj.getFloatArray(PdfDictionary.Matrix);

        if(inputs!=null){
			
			if(shadingType==1){
				float[][] Nmatrix={{inputs[0],inputs[1],0f},{inputs[2],inputs[3],0f},{0f,0f,1f}};
				matrix=Nmatrix;
			}else{
				float[][] Nmatrix={{inputs[0],inputs[1],0f},{inputs[2],inputs[3],0f},{inputs[4],inputs[5],1f}};

				if(Nmatrix[2][0]<0)
					colorsReversed=true;
				else
					colorsReversed=false;

				matrix=Matrix.multiply(Nmatrix,CTM);
			}
		}


		/**
		 * set pattern
		 */
		if((shadingType == 1)){ //tiling

			this.currentColor = setupTiling(PatternObj,matrix,streamData);
		}else if(shadingType == 2 && PdfStreamDecoder.useShading){ //shading

			this.currentColor = setupShading(PatternObj,matrix);

		}else if(PdfStreamDecoder.useShading){
		}
	}

	static int count=0;

	/**
	 */
	private PdfPaint setupTiling(PdfObject PatternObj,float[][] matrix,byte[] streamData) {

		/**
		 * work out if upsidedown
		 */
		boolean isUpsideDown=false;
		if(matrix!=null){

			//markee
			isRotated=matrix[1][0]!=0 && matrix[0][1]!=0 && matrix[0][0]!=0 && matrix[1][1]!=0;

			isUpsideDown=((matrix[1][1]<0)|(matrix[0][1]<0));

			//used by rotation code
			if(isUpsideDown && matrix[0][1]>0 && matrix[1][0]>0)
				isUpsideDown=false;

		}

		/**
		 * get values for pattern for PDF object
		 */
		int PaintType=PatternObj.getInt(PdfDictionary.PaintType);
		

		//int TilingType=PatternObj.getInt(PdfDictionary.TilingType);
		
		//float[] BBox=PatternObj.getFloatArray(PdfDictionary.BBox);
		
		XStep=(int) PatternObj.getFloatNumber(PdfDictionary.XStep);
		YStep=(int) PatternObj.getFloatNumber(PdfDictionary.YStep);
		
		/**
		 * adjust matrix to suit
		 **/
		if(matrix!=null){
			//allow for upside down
			if(matrix[1][1]<0)
				matrix[2][1]=YStep;

			// needed for reporttype file
			if(matrix[1][0]!=0.0)
				matrix[2][1]=-matrix[1][0];

		}

		PdfObject Resources=PatternObj.getDictionary(PdfDictionary.Resources);

		float dx=0,dy=0;

		/**
		 * convert stream into an image
		 */

		//decode and create graphic of glyph
		PdfStreamDecoder glyphDecoder=new PdfStreamDecoder();
		glyphDecoder.setStreamType(PdfStreamDecoder.PATTERN);
		ObjectStore localStore = new ObjectStore();
		glyphDecoder.setStore(localStore);

		DynamicVectorRenderer glyphDisplay=new DynamicVectorRenderer(0,false,20,localStore);
		glyphDisplay.setOptimisedRotation(false);
		
		try{
			glyphDecoder.init(false,true,7,0,new PdfPageData(),0,glyphDisplay,currentPdfFile);

			/**read the resources for the page*/
			if (Resources != null)
				glyphDecoder.readResources(Resources,true);

			glyphDecoder.setDefaultColors(gs.getStrokeColor(),gs.getNonstrokeColor());

			/**
			 * setup matrix so scales correctly
			 **/
			GraphicsState currentGraphicsState=new GraphicsState(0,0);
			//multiply to get new CTM
			if(matrix!=null)
				currentGraphicsState.CTM =matrix;

			glyphDecoder.decodePageContent(null,0,0, currentGraphicsState, streamData);

		} catch (PdfException e1) {
			e1.printStackTrace();
		}


		//flush as image now created
		glyphDecoder=null;

		//ensure positive
		if(XStep<0)
			XStep=-XStep;
		if(YStep<0)
			YStep=-YStep;

		/**
		 * if image is generated larger than slot we draw it into we
		 * will lose definition. To avoid this, we draw at full size and
		 * scale only when drawn onto page
		 */
		//workout unscaled tile size
		float rawWidth=0,rawHeight=0;
		boolean isDownSampled=false;

		if(matrix!=null){
			rawWidth=matrix[0][0];
			if(rawWidth==0)
				rawWidth=matrix[0][1];
			if(rawWidth<0)
				rawWidth=-rawWidth;
			rawHeight=matrix[1][1];
			if(rawHeight==0)
				rawHeight=matrix[1][0];
			if(rawHeight<0)
				rawHeight=-rawHeight;
			//isDownSampled=((rawHeight>YStep)|(rawWidth>XStep));
		}

		AffineTransform imageScale=null;

		if(matrix!=null){

			dx=matrix[0][0];

			if(dx==0)
				dx=matrix[0][1];
			if(dx<0)
				dx=-dx;
			dy=matrix[1][1];

			if(dy==0)
				dy=matrix[1][0];
			if(dy<0)
				dy=-dy;

			dx=dx*XStep;
			dy=dy*YStep;

			//default values
			int imgW=(int)XStep;
			int imgH=(int)YStep;

			if(isUpsideDown){

				//System.out.println(matrix[0][0]+" "+matrix[0][1]+" "+matrix[1][0]+" "+matrix[1][1]);

				int xCount=(int)(XStep/dx);
				int yCount=(int)(YStep/dy);

				if(xCount>0 && yCount>0){
					imgW=(int)((xCount+1)*dx);//XStep;
					imgH=(int)((yCount+1)*dy);

					XStep=imgW;
					YStep=imgH;
				}
			}

			if(isDownSampled){
				img=new BufferedImage((int)(rawWidth+.5f),(int)(rawHeight+.5f),BufferedImage.TYPE_INT_ARGB);
				imageScale=AffineTransform.getScaleInstance(XStep/rawWidth,YStep/rawHeight);
			}else
				img=new BufferedImage(imgW,imgH,BufferedImage.TYPE_INT_ARGB);

			Graphics2D g2=img.createGraphics();

			AffineTransform defaultAf=g2.getTransform();

			g2.setClip(new Rectangle(0,0,img.getWidth(),img.getHeight()));

			/**
			 * allow for tile not draw from 0,0
			 */
			int startX=0;
			Rectangle actualTileRect=glyphDisplay.getOccupiedArea().getBounds();
			int rectX=actualTileRect.x;
			if(rectX<0){
				startX= (int) (-rectX*matrix[0][0]);
				//System.out.println("startX>>>>"+startX);
			}

			if(!isRotated){
				//if tile is smaller than Xstep,Ystep, tesselate to fill
				for(float y=0;y<YStep;y=y+dy){
					for(float x=startX;x<XStep;x=x+dx){

						if(isUpsideDown)
							g2.translate(x,-y);
						else
							g2.translate(x,y);
						glyphDisplay.paint(g2,null,imageScale,null,false,false);
						g2.setTransform(defaultAf);

						//if(debugTiling)
						//ShowGUIMessage.showGUIMessage("x",img,"x");
					}
				}
			}

		}else{


			int imgW=(int)XStep;
			int imgH=(int)YStep;
			if(isDownSampled){
				img=new BufferedImage((int)(rawWidth+.5f),(int)(rawHeight+.5f),BufferedImage.TYPE_INT_ARGB);
				imageScale=AffineTransform.getScaleInstance(XStep/rawWidth,YStep/rawHeight);
			}else
				img=new BufferedImage(imgW,imgH,BufferedImage.TYPE_INT_ARGB);

			Graphics2D g2=img.createGraphics();

			glyphDisplay.paint(g2,null,null,null,false,false);


			if(isUpsideDown && img.getHeight()>1){
				AffineTransform flip=new AffineTransform();
				flip.translate(0, img.getHeight());
				flip.scale(1, -1);
				AffineTransformOp invert =new AffineTransformOp(flip,ColorSpaces.hints);
				img=invert.filter(img,null);
			}
		}
		localStore.flush();

		/**
		 * create paint using image
		 */

//		g2=img.createGraphics();
//		g2.setPaint(Color.BLACK);
//		g2.drawRect(0,0,img.getWidth()-1, img.getHeight()-1);

		//ShowGUIMessage msg = new ShowGUIMessage();
		//ShowGUIMessage.showGUIMessage("x", img, "x");

		if(img!=null){
			PdfPaint paint=new PdfTexturePaint(img,  new Rectangle(0, 0, img.getWidth() , img.getHeight()));

			if(isRotated) {
				paint = new RotatedTexturePaint(glyphDisplay, matrix, XStep, YStep, dx, dy, imageScale);
			}

			return paint;
		}else
			return null;
	}

	/**
	 */
	private PdfPaint setupShading(PdfObject PatternObj,float[][] matrix) {

		/**
		 * get the shading object
		 */

		PdfObject Shading=PatternObj.getDictionary(PdfDictionary.Shading);
		
		/**
		 * work out colorspace
		 */
		PdfObject ColorSpace=Shading.getDictionary(PdfDictionary.ColorSpace);
		
		//convert colorspace and get details
		GenericColorSpace newColorSpace=ColorspaceFactory.getColorSpaceInstance(false, currentPdfFile, ColorSpace);
		
		
		if(Shading==null){
			return null;
		}else{
			return ShadingFactory.createShading(Shading,isPrinting, pageHeight,newColorSpace,currentPdfFile,matrix,pageHeight,colorsReversed);
		}
	}

//	<end-13>	
//<end-os>
}
