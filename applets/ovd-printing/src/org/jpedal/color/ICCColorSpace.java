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
 * ICCColorSpace.java
 * ---------------
 */
package org.jpedal.color;

import java.awt.color.ColorSpace;
import java.awt.color.ICC_ColorSpace;
import java.awt.color.ICC_Profile;
import java.awt.image.BufferedImage;
import java.awt.image.DataBuffer;
import java.awt.image.DataBufferByte;
import java.awt.image.Raster;
import java.io.ByteArrayInputStream;

import javax.imageio.ImageIO;
import javax.imageio.ImageReader;
import javax.imageio.stream.ImageInputStream;

import org.jpedal.io.PdfObjectReader;
import org.jpedal.objects.raw.PdfDictionary;
import org.jpedal.objects.raw.PdfObject;
import org.jpedal.utils.LogWriter;


/**
 * handle ICCColorSpace
 */
public class ICCColorSpace
extends GenericColorSpace {

	//cache values to speed up translation
	private int[] a1,b1,c1;


	public ICCColorSpace(PdfObject colorSpace) {

		//set cache to -1 as flag
		a1=new int[256];
		b1=new int[256];
		c1=new int[256];

		for(int i=0;i<256;i++){
			a1[i]=-1;
			b1[i]=-1;
			c1[i]=-1;
		}

		value = ColorSpaces.ICC;
		cs = ColorSpace.getInstance(ColorSpace.CS_sRGB);

		byte[] icc_data=colorSpace.DecodedStream;

		if (icc_data == null)
			LogWriter.writeLog("Error in ICC data");
		else {
			try{
				cs = new ICC_ColorSpace(ICC_Profile.getInstance(icc_data));
			}catch(Exception e){
				LogWriter.writeLog("[PDF] Problem "+e.getMessage()+" with ICC data ");
				failed=true;
			}
		}

		componentCount=cs.getNumComponents();
	}


	/**
	 * set color (in terms of rgb)
	 */
	final public void setColor(String[] number_values,int items) {

		float[] colValues=new float[items];

		for(int ii=0;ii<items;ii++)
			colValues[ii]=Float.parseFloat(number_values[ii]);

		setColor(colValues,items);
	}

	/**set color*/
	final public void setColor(float[] operand,int size) {

		float[] values=new float[size];
		int[] lookup=new int[size];

		for(int i=0;i<size;i++){
			float val=operand[i];
			values[i]=val;
			lookup[i]=(int)(val*255);
		}

		if((size==3)&&(a1[lookup[0]]!=-1)&&
				(b1[lookup[1]]!=-1)&&(c1[lookup[2]]!=-1))
		{
			currentColor=new PdfColor(a1[lookup[0]],b1[lookup[1]],c1[lookup[2]]);

		}else{
			values=cs.toRGB(values);

			currentColor=new PdfColor(values[0],values[1],values[2]);

			if(size==3){
				a1[lookup[0]]=(int)(values[0]*255);
				b1[lookup[1]]=(int)(values[1]*255);
				c1[lookup[2]]=(int)(values[2]*255);
			}
		}
	}

	/**
	 * convert Index to RGB
	 */
	public byte[] convertIndexToRGB(byte[] data){

		if(componentCount==4)
			return convert4Index(data);
		else
			return data;

	}		

	/**
	 * <p>
	 * Convert DCT encoded image bytestream to sRGB
	 * </p>
	 * <p>
	 * It uses the internal Java classes and the Adobe icm to convert CMYK and
	 * YCbCr-Alpha - the data is still DCT encoded.
	 * </p>
	 * <p>
	 * The Sun class JPEGDecodeParam.java is worth examining because it contains
	 * lots of interesting comments
	 * </p>
	 * <p>
	 * I tried just using the new IOImage.read() but on type 3 images, all my
	 * clipping code stopped working so I am still using 1.3
	 * </p>
	 */
	public BufferedImage JPEGToRGBImage(
			byte[] data,int w,int h,float[] decodeArray,int pX,int pY) {

		int type=getJPEGTransform(data);

		if(data.length>9 && data[6] == 'J' && data[7] == 'F' && data[8] == 'I' && data[9] == 'F'){
			return nonRGBJPEGToRGBImage(data,w,h, null,pX,pY);
		}

		boolean useICC=(alternative!=PdfDictionary.Unknown && 
				alternative==ColorSpaces.DeviceRGB) && 
				this.componentCount==3 && (type==0 || type==3) && 
				(intent==null || (intent!=null && intent.equals("RelativeColorimetric")));	

		if(useICC)
			return algorithmicICCToRGB(data,w,h,false,pX,pY);
		else
			return nonRGBJPEGToRGBImage(data,w,h, null,pX,pY);
	}

	public BufferedImage algorithmicICCToRGB(
			byte[] data, int w, int h,boolean debug, int pX, int pY) {

		BufferedImage image = null;

		ImageReader iir=null;
		ImageInputStream iin=null;

		ByteArrayInputStream in = new ByteArrayInputStream(data);

		try{

			iir = (ImageReader)ImageIO.getImageReadersByFormatName("JPEG").next();
			ImageIO.setUseCache(false);

			iin = ImageIO.createImageInputStream((in));
			iir.setInput(iin, true);   //new MemoryCacheImageInputStream(in));

			Raster ras=iir.readRaster(0,null);

			ras=cleanupRaster(ras,0,pX,pY,componentCount);
			w=ras.getWidth();
			h=ras.getHeight();   	

			byte[] new_data = new byte[w * h * 3];

			//reuse variable
			data=((DataBufferByte)ras.getDataBuffer()).getData();

			int pixelCount = w * h*3;
			float lastR=0,lastG=0,lastB=0;
			int pixelReached = 0;
			float lastIn1=-1,lastIn2=-1,lastIn3=-1;

			for (int i = 0; i < pixelCount; i = i + 3) {

				float in1 = ((data[i] & 255))/255f;
				float in2 = ((data[1+i] & 255))/255f;
				float in3 = ((data[2+i] & 255))/255f;

				float[] outputValues=new float[3];
				if((lastIn1==in1)&&(lastIn2==in2)&&(lastIn3==in3)){
					//use existing values   
				}else{//work out new

					if (debug)
						System.out.println(in1+" "+in2+ ' ' +in3);

					float[] inputValues={in1,in2,in3};

					outputValues=cs.toRGB(inputValues);
					outputValues=inputValues;

					//reset values
					lastR=(outputValues[0]*255);
					lastG=(outputValues[1]*255);
					lastB=(outputValues[2]*255);

					lastIn1=in1;
					lastIn2=in2;
					lastIn3=in3;
				}

				new_data[pixelReached++] =(byte) lastR;
				new_data[pixelReached++] = (byte)lastG;
				new_data[pixelReached++] = (byte)lastB;

				/**
				buffer.setElemDouble(pixelReached++,x*outRed);
				buffer.setElemDouble(pixelReached++,x*outGreen);
				buffer.setElemDouble(pixelReached++,x*outBlue);
				 */
			}



			try {
				/***/
				int[] bands = {0,1,2};

				DataBuffer db = new DataBufferByte(new_data, new_data.length);
				image =new BufferedImage(w,h,BufferedImage.TYPE_INT_RGB);

				Raster raster =Raster.createInterleavedRaster(db,w,h,w * 3,3,bands,null);
				image.setData(raster);

			} catch (Exception e) {
				System.out.println(e);
				e.printStackTrace();
				LogWriter.writeLog("Exception " + e + " with 24 bit RGB image");
			}

		}catch(Exception e){
		}

		try {
			in.close();
			iir.dispose();
			iin.close();
		} catch (Exception ee) {
			LogWriter.writeLog("Problem closing  " + ee);
		}
		return image;
	}	

}
