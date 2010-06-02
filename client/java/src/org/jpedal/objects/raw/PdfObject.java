/**
 * ===========================================
 * Java Pdf Extraction Decoding Access Library
 * ===========================================
 *
 * Project Info:  http://www.jpedal.org
 *
 * (C) Copyright 2007, IDRsolutions and Contributors.
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

  * PdfObject.java
  * ---------------
  * (C) Copyright 2007, by IDRsolutions and Contributors.
  *
  *
  * --------------------------
 */
package org.jpedal.objects.raw;

import org.jpedal.fonts.StandardFonts;

/**
 * holds actual data for PDF file to process
 */
public class PdfObject implements Cloneable{

    protected int colorspace=PdfDictionary.Unknown, subtype=PdfDictionary.Unknown,type=PdfDictionary.Unknown;

    private int BitsPerComponent=-1, Count=0, FormType=-1, Length=-1,Length1=-1,Length2=-1,Length3=-1,Rotate=-1; //-1 shows unset

    private float[] ArtBox, BBox, BleedBox, CropBox, Decode,Domain, Matrix, MediaBox, Range, TrimBox;

    protected  PdfObject ColorSpace=null, DecodeParms=null, Encoding=null,Function=null, 
    Resources=null,Shading=null, SMask=null;

    private boolean ignoreRecursion=false;
    
    //used by font code
    protected boolean isZapfDingbats=false, isSymbol=false;

    private boolean isCompressedStream=false;

    protected int generalType=PdfDictionary.Unknown; // some Dictionaries can be a general type (ie /ToUnicode /Identity-H)

    private String generalTypeAsString=null; //some values (ie CMAP can have unknown settings)


    private String Parent=null,Name=null;
    private byte[] rawParent,rawName=null;
    public static boolean debug=false;

    /**used to implement gradually*/
    public static final int NO = 0;
    public static final int PARTIAL = 1;
    public static final int FULL = 2;

    String ref=null;
    int intRef,gen;

    protected boolean hasStream=false;

    public byte[] stream=null,DecodedStream=null;
    public int startStreamOnDisk=-1,endStreamOnDisk=-1;
    public String CachedStream=null;

    private byte[][] Filter=null, TR=null;

    private byte[][] keys;

    private byte[][] values;

    private PdfObject[] objs;

    private PdfObject(){

    }
    
    public PdfObject(byte[] bytes) {
	}

    public PdfObject(int intRef, int gen) {
    	setRef(intRef,  gen);
    }
    
    public void setRef(int intRef, int gen){
    	this.intRef=intRef;
        this.gen=gen;
        
        //force reset as may have changed
        ref=null;
        
    }

    public PdfObject(String ref){
        this.ref=ref;
    }

    public PdfObject(int type) {
        this.generalType=type;
    }

    protected boolean[] deepCopy(boolean[] input){

        if(input==null)
        return null;

        int count=input.length;

        boolean[] deepCopy=new boolean[count];
        System.arraycopy(input,0,deepCopy,0,count);

        return deepCopy;
    }

    protected float[] deepCopy(float[] input){

        if(input==null)
        return null;

        int count=input.length;

        float[] deepCopy=new float[count];
        System.arraycopy(input,0,deepCopy,0,count);

        return deepCopy;
    }

    protected double[] deepCopy(double[] input){

        if(input==null)
        return null;

        int count=input.length;

        double[] deepCopy=new double[count];
        System.arraycopy(input,0,deepCopy,0,count);

        return deepCopy;
    }

    protected int[] deepCopy(int[] input){

        if(input==null)
        return null;

        int count=input.length;

        int[] deepCopy=new int[count];
        System.arraycopy(input,0,deepCopy,0,count);

        return deepCopy;
    }

    protected byte[][] deepCopy(byte[][] input){

        if(input==null)
        return null;

        int count=input.length;

        byte[][] deepCopy=new byte[count][];
        System.arraycopy(input,0,deepCopy,0,count);

        return deepCopy;
    }

    /**
     * added to make it easier to upgrade gradually
     **/
    public int isImplemented() {
        return NO;
    }

    public PdfObject getDictionary(int id){

        switch(id){

	        case PdfDictionary.ColorSpace:
	            return ColorSpace;

            case PdfDictionary.DecodeParms:
                return DecodeParms;

            case PdfDictionary.Function:
                return Function;

            case PdfDictionary.Resources:
                return Resources;

            case PdfDictionary.Shading:
                return Shading;
                
            case PdfDictionary.SMask:
	        	return SMask;

            default:


                return null;
        }
    }

    public int getGeneralType(int id){

        //special case
        if(id==PdfDictionary.Encoding && isZapfDingbats) //note this is Enc object so local
            return StandardFonts.ZAPF;
        else if(id==PdfDictionary.Encoding && isSymbol) //note this is Enc object so local
            return StandardFonts.SYMBOL;
        else
            return generalType;
    }

    public String getGeneralStringValue(){
        return generalTypeAsString;
    }

    public void setGeneralStringValue(String generalTypeAsString){
        this.generalTypeAsString=generalTypeAsString;
    }

    public void setIntNumber(int id,int value){

        switch(id){

	        case PdfDictionary.BitsPerComponent:
	    		BitsPerComponent=value;
	    		break;

            case PdfDictionary.Count:
                Count=value;
                break;

            case PdfDictionary.FormType:
                FormType=value;
                break;

            case PdfDictionary.Length:
                Length=value;
                break;

            case PdfDictionary.Length1:
                Length1=value;
                break;
            
            case PdfDictionary.Length2:
                Length2=value;
                break;
                
            case PdfDictionary.Length3:
                Length3=value;
                break;
                
            case PdfDictionary.Rotate:
    		    Rotate=value;
    		    break;

            default:

        }
    }

    public void setFloatNumber(int id,float value){

        switch(id){

//	        case PdfDictionary.BitsPerComponent:
//	    		BitsPerComponent=value;
//	    		break;

            default:

        }
    }

    public int getInt(int id){

        switch(id){

        	case PdfDictionary.BitsPerComponent:
        		return BitsPerComponent;
        
            case PdfDictionary.Count:
                return Count;

            case PdfDictionary.FormType:
                return FormType;

            case PdfDictionary.Length:
                return Length;
            
            case PdfDictionary.Length1:
                return Length1;
                
            case PdfDictionary.Length2:
                return Length2;
            
            case PdfDictionary.Length3:
                return Length3;

            case PdfDictionary.Rotate:
                return Rotate;

            default:

                return PdfDictionary.Unknown;
        }
    }

    public float getFloatNumber(int id){

        switch(id){

//        	case PdfDictionary.BitsPerComponent:
//        		return BitsPerComponent;

            default:

                return PdfDictionary.Unknown;
        }
    }

    public boolean getBoolean(int id){

        switch(id){


            default:

        }

        return false;
    }

    public void setBoolean(int id,boolean value){

        switch(id){


            default:

        }
    }



    public void setDictionary(int id,PdfObject value){

        switch(id){

        	case PdfDictionary.ColorSpace:
        		ColorSpace=value;
        		break;
        		
            case PdfDictionary.DecodeParms:
                DecodeParms=value;
                break;

            case PdfDictionary.Function:
                Function=value;
                break;

            case PdfDictionary.Resources:
                Resources=value;
                break;

            case PdfDictionary.Shading:
                Shading=value;
            break;
            
            case PdfDictionary.SMask:
	        	SMask=value;
			break;

            default:

        }
    }

    /**
     * flag set for embedded data
     */
    public boolean hasStream() {
        return hasStream;
    }


    public int setConstant(int pdfKeyType, int keyStart, int keyLength, byte[] raw) {


        return PdfDictionary.Unknown;
    }

    public int getParameterConstant(int key) {
        int def= PdfDictionary.Unknown;

        switch(key){

	        case PdfDictionary.ColorSpace:
	            return colorspace;
            
            case PdfDictionary.Subtype:
                return subtype;

            case PdfDictionary.Type:
                return type;

        }

        return def;
    }

    /**
     * common values shared between types
     */
    public int setConstant(int pdfKeyType, int id) {
        int PDFvalue =id;


        /**
         * map non-standard
         */
        switch(id){

            case PdfDictionary.FontDescriptor:
                PDFvalue =PdfObjectTypes.Font;
                break;
           
        }


        switch(pdfKeyType){

        	case PdfDictionary.ColorSpace:
        		colorspace=PDFvalue;
        		break;
        		
        	case PdfDictionary.Subtype:
    		subtype=PDFvalue;
    		break;
    		
            case PdfDictionary.Type:

                //@speed if is temp hack as picks up types on some subobjects
                //if(type==PdfDictionary.Unknown)
                this.type=PDFvalue;

                break;
        }

        return PDFvalue;
    }

    public float[] getFloatArray(int id) {

        float[] array=null;
        switch(id){

            case PdfDictionary.ArtBox:
        		return deepCopy(ArtBox);

            case PdfDictionary.BBox:
        		return deepCopy(BBox);

            case PdfDictionary.BleedBox:
                return deepCopy(BleedBox);

            case PdfDictionary.CropBox:
                return deepCopy(CropBox);
            
            case PdfDictionary.Decode:
        		return deepCopy(Decode);

            case PdfDictionary.Domain:
                return deepCopy(Domain);

            case PdfDictionary.Matrix:
        		return deepCopy(Matrix);

            case PdfDictionary.MediaBox:
                return deepCopy(MediaBox);
                
            case PdfDictionary.Range:
                return deepCopy(Range);

            case PdfDictionary.TrimBox:
                return deepCopy(TrimBox);

            default:

        }

        return deepCopy(array);
    }

     public byte[][] getKeyArray(int id) {

        switch(id){


            default:

        }

        return null;
    }

    public double[] getDoubleArray(int id) {

        double[] array=null;
        switch(id){

            default:

        }

        return deepCopy(array);
    }

    public boolean[] getBooleanArray(int id) {

        boolean[] array=null;
        switch(id){

            default:

        }

        return deepCopy(array);
    }
    
    public int[] getIntArray(int id) {

        int[] array=null;
        switch(id){

            default:

        }

        return deepCopy(array);
    }

    public void setFloatArray(int id,float[] value) {

        switch(id){

            case PdfDictionary.ArtBox:
	            ArtBox=value;
	            break;

            case PdfDictionary.BBox:
	            BBox=value;
	            break;

            case PdfDictionary.BleedBox:
                BleedBox=value;
                break;
            
            case PdfDictionary.CropBox:
                CropBox=value;
                break;

            case PdfDictionary.Decode:
	        	Decode=ignoreIdentity(value);
	        break;
	        
            case PdfDictionary.Domain:
	            Domain=value;
	        break;
	        
	        case PdfDictionary.Matrix:
	            Matrix=value;
	        break;
        
            case PdfDictionary.MediaBox:
                MediaBox=value;
                break;

            case PdfDictionary.Range:
                Range=value;
                break;
            
            case PdfDictionary.TrimBox:
                TrimBox=value;
                break;

            default:

        }

    }

    /**ignore identity value which makes no change*/
    private float[] ignoreIdentity(float[] value) {

        boolean isIdentity =true;
        if(value!=null){

            int count=value.length;
            for(int aa=0;aa<count;aa=aa+2){
                if(value[aa]==0f && value[aa+1]==1f){
                    //okay
                }else{
                    isIdentity =false;
                    aa=count;
                }
            }
        }

        if(isIdentity)
            return null;
        else
            return value;
    }

    public void setIntArray(int id,int[] value) {

        switch(id){

            default:

        }

    }

    public void setBooleanArray(int id,boolean[] value) {

        switch(id){

            default:

        }

    }
    
    public void setDoubleArray(int id,double[] value) {

        switch(id){

            default:

        }

    }


    public void setMixedArray(int id,byte[][] value) {

        switch(id){

            case PdfDictionary.Filter:
	
                Filter=value;
	            break;
            
               
            default:

        }

    }


     public String getStringValue(int id,int mode) {

        byte[] data=null;

        //get data
        switch(id){

            case PdfDictionary.Name:

                data=rawName;
                break;
                     
        }


        //convert
        switch(mode){
            case PdfStrings.STANDARD:

                //setup first time
                if(data!=null)
                    return new String(data);
                else
                    return null;


            case PdfStrings.LOWERCASE:

                //setup first time
                if(data!=null)
                    return new String(data);
                else
                    return null;

            case PdfStrings.REMOVEPOSTSCRIPTPREFIX:

                //setup first time
                if(data!=null){
                	int len=data.length;
                	if(len>6 && data[6]=='+'){ //lose ABCDEF+ if present
                		int length=len-7;
                		byte[] newData=new byte[length];
                		System.arraycopy(data, 7, newData, 0, length);
                		return new String(newData);
                	}else
                		return new String(data);
                }else
                    return null;

            default:
                throw new RuntimeException("Value not defined in getStringValue(int,mode)");
        }
    }

    public String getStringValue(int id) {

        String str=null;
        switch(id){

            case PdfDictionary.Name:

                            //setup first time
                            if(Name==null && rawName!=null)
                            Name=new String(rawName);

                            return Name;
            
//            case PdfDictionary.Parent:
//
//                //setup first time
//                if(Filter==null && rawParent!=null)
//                    Parent=new String(rawParent);
//
//                return Parent;

            default:

        }

        return str;
    }

    public String getStringKey(int id) {

        String str=null;
        switch(id){

            case PdfDictionary.Parent:

                //setup first time
                if(Filter==null && rawParent!=null)
                    Parent=new String(rawParent);

                return Parent;

            default:

        }

        return str;
    }

    public String getTextStreamValue(int id) {

        String str=null;
        switch(id){

//            case PdfDictionary.Filter:
//
//            //setup first time
//            if(Filter==null && rawFilter!=null)
//            Filter=new String(rawFilter);
//
//            return Filter;

            default:

        }

        return str;
    }

    public void setStringValue(int id,byte[] value) {

        switch(id){

             case PdfDictionary.Name:
                rawName=value;
            break;
//            case PdfDictionary.Parent:
//                rawParent=value;
//                break;

            default:

        }

    }

    public void setStringKey(int id,byte[] value) {

           switch(id){

               case PdfDictionary.Parent:
                   rawParent=value;
                   break;

               default:

           }

       }


    public void setTextStreamValue(int id,byte[] value) {

        switch(id){

//            case PdfDictionary.Filter:
//                rawFilter=value;
//            break;

            default:

        }

    }


    public byte[] getStream() {
    	
    	if(DecodedStream==null)
    		return null;
    	
    	//make a a DEEP copy so we cant alter
		int len=DecodedStream.length;
		byte[] copy=new byte[len];
		System.arraycopy(DecodedStream, 0, copy, 0, len);
		
        return copy;
    }

    public void setStream(byte[] stream) {
        this.stream=stream;
    }

    public String getObjectRefAsString() {

        if(ref==null)
            ref=intRef+" "+gen+" R";

        return this.ref;
    }

    public int getObjectRefID() {

        return intRef;
    }

    public int getObjectRefGeneration() {

        return gen;
    }

    public PdfArrayIterator getArrayIterator(int id) {

        switch(id){

            case PdfDictionary.Filter:
                return new PdfArrayIterator(Filter);

            default:

                return null;
        }
    }

    public void setDictionaryPairs(byte[][] keys, byte[][] values, PdfObject[] objs) {

        this.keys=keys;
        this.values=values;
        this.objs=objs;


    }

    public PdfKeyPairsIterator getKeyPairsIterator() {
        return new PdfKeyPairsIterator(keys,values,objs);
    }

    /**
     * used to debug specific Objects
     * @return
     */
    public boolean getDebugMode() {
        debug=false;
        return debug;
    }

    public void setKeyArray(int id, byte[][] keyValues) {

        switch(id){

            default:

        }
    }
    
    public void setStringArray(int id, byte[][] keyValues) {

        switch(id){

        case PdfDictionary.TR:
        	TR=keyValues;
        	break;
        	
            default:

        }
    }
    
    public byte[][] getStringArray(int id) {

        switch(id){

        case PdfDictionary.TR:
        	return deepCopy(TR);

            default:

        }

		return null;
	}

    final public Object clone()
	{
		Object o = null;
		try
		{
			o = super.clone();
		}
		catch( Exception e ){
        }

		return o;
	}

    

	public boolean decompressStreamWhenRead() {
		return false;
	}

	public int getObjectType() {
		return PdfDictionary.Unknown;
	}

	public byte[] getStringValueAsByte(int id) {
		return null;
	}

    public boolean isCompressedStream() {
        return isCompressedStream;
    }

     public void setCompressedStream(boolean isCompressedStream) {
        this.isCompressedStream=isCompressedStream;
    }

	/**do not cascade down whole tree*/
    public boolean ignoreRecursion() {
		return ignoreRecursion;
	}
    
    /**do not cascade down whole tree*/
    public void ignoreRecursion(boolean ignoreRecursion) {
		this.ignoreRecursion=ignoreRecursion;
	}
    
    
}
