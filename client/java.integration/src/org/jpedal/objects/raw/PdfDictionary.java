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


import java.util.Iterator;
import java.util.Map;

import org.jpedal.color.ColorSpaces;
import org.jpedal.fonts.StandardFonts;
import org.jpedal.io.PdfFilteredReader;
import org.jpedal.io.PdfObjectReader;

/**
 * holds actual data for PDF file to process
 */
public class PdfDictionary {

    final public static int Unknown=-1;

    /**
     * all key values as hashed values
     */
    
    final public static int AIS=1120547;
    
    final public static int Alternate=2054519176;
    
    final public static int AlternateSpace=-1247101998;

    final public static int Annots=1044338049;

    final public static int AntiAlias=2055039589;
    
    final public static int Array=1111634266;

    final public static int ArtBox=1142050954;

    final public static int Ascent=859131783;

    final public static int AvgWidth=1249540959;
    
    final public static int BlackPoint=1886161824;

    final public static int Background=1921025959;

    final public static int BaseEncoding=1537782955;

    final public static int BaseFont=678461817;

    final public static int BBox=303185736;

    final public static int BitsPerComponent=-1344207655;
    
    final public static int BitsPerSample=-1413045608;

    final public static int BlackIs1=1297445940;

    final public static int BleedBox=1179546749;
    
    final public static int Blend=1010122310;

    final public static int Bounds=1161709186;

    final public static int BM=4637;

    final public static int BPC=1187859;

    final public static int C0=4864;

    final public static int C1=4865;
    
    final public static int CA=4881;
    
    final public static int ca=13105;

    final public static int CapHeight=1786204300;

    final public static int Catalog=827289723;

    final public static int CIDSystemInfo=1972801240;

    final public static int CharProcs=2054190454;

    final public static int CharSet=1110863221;

    final public static int CIDFontType0C=-1752352082;

    final public static int CIDToGIDMap=946823533;

    final public static int CMap=320680256;

    final public static int CMapName=827223669;
    
    //use ColorSpaces.DeviceCMYK for general usage
    final private static int CMYK=320678171;

    final public static int Colors=1010783618;

    final public static int ColorSpace=2087749783;

    final public static int Columns=1162902911;
    
    final public static int Components=1920898752;
    
    final public static int CompressedObject=23;

    final public static int Contents=1216184967;

    final public static int Coords=1061308290;

    final public static int Count=1061502551;

    final public static int CropBox=1076199815;

    final public static int CS=4899;

    final public static int CVMRC=639443494;

    final public static int D=20;

    final public static int DCT=1315620;

    final public static int Decode=859785322;

    final public static int DecodeParms=1888135062;

    final public static int DescendantFonts=-1547306032;

    final public static int Descent=860451719;

    final public static int Differences=1954328750;

    final public static int Domain=1026641277;

    final public static int DP=5152;

    final public static int DW=5159;

    final public static int EarlyChange=1838971823;

    final public static int Encode=859785587;

    final public static int EncodedByteAlign=-823077984;

    final public static int Encoding=1232564598;

    final public static int Extend=1144345468;

    final public static int ExtGState=-1938465939;

    final public static int F=22;

    final public static int Filter=1011108731;

    final public static int First=960643930;

    final public static int FirstChar=1283093660;

    final public static int Flags=1009858393;

    final public static int Font=373243460;

    final public static int FontBBox=676429196;

    final public static int FontDescriptor=-1044665361;

    final public static int FontFamily=2071816377;

    final public static int FontFile=746093177;

    final public static int FontFile2=2021292334;

    final public static int FontFile3=2021292335;

    final public static int FontMatrix=-2105119560;

    final public static int FontName=879786873;

    final public static int FontStretch=2038281912;

    final public static int FontWeight=2004579768;

    final public static int Form=373244477;

    final public static int FormType=982024818;

    final public static int Function=1518239089;

    final public static int Functions=2122150301;

    final public static int FunctionType=2127019430;
    
    final public static int G=23;
    
    final public static int Gamma=826096968;

    final public static int Group=1111442775;

    final public static int H=24;

    final public static int Height=959926393;
    
    final public static int hival=960901492;

    final public static int I=25;
    
    final public static int Identity=1567455623;

    final public static int Identity_H=2038913669;

    final public static int Identity_V=2038913683;

    final public static int IM=6429;

    final public static int Image=1026635598;

    final public static int ImageMask=1516403337;
    
    final public static int Index=1043608929;
    
    //used to hold Indexed Colorspace read, not direct key in PDF
    final public static int Indexed=895578984;

    final public static int Intent=1144346498;

    final public static int ItalicAngle=2055844727;

    final public static int JBIG2Globals=1314558361;

    final public static int K=27;

    final public static int Kids=456733763;

    final public static int Lang=472989239;

    final public static int LastChar=795440262;

    final public static int Leading=878015336;

    final public static int Length=1043816557;

    final public static int Length1=929066303;

    final public static int Length2=929066304;

    final public static int Length3=929066305;

    final public static int LW=7207;

    //use StandardFonts.MacExpert as public value
    final static int MacExpertEncoding=-1159739105;

    //use StandardFonts.MAC as public value
    final static int MacRomanEncoding=-1511664170;

    final public static int Mask=489767739;

    final public static int Matrix=1145198201;

    final public static int MaxWidth=1449495647;

    final public static int MediaBox=1313305473;

    final public static int MetaData=24;

    final public static int MissingWidth=-1884569950;

    final public static int Multiply=1451587725;

    final public static int N=30;

    final public static int Name=506543413;
    
    final public static int None=507461173;
    
    final public static int OP=7968;
    
    final public static int op=16192;

    final public static int OPI=2039833;
    
    final public static int OPM=2039837;

    final public static int Ordering=1635480172;

    final public static int PaintType=1434615449;

    final public static int Page=540096309;

    final public static int Pages=825701731;

    final public static int Parent=1110793845;

    final public static int Pattern=1146450818;

    final public static int PatternType=1755231159;

    //use StandardFonts.PDF as public value
    final static int PDFDocEncoding=1602998461;

    final public static int Predictor=1970893723;

    final public static int ProcSet=860059523;

    final public static int Range=826160983;

    final public static int Registry=1702459778;

    final public static int Resources=2004251818;
    
    //convert to DeviceRGB
    final public static int RGB=2234130;

    final public static int Rotate=1144088180;

    final public static int Rows=574572355;

    final public static int SA=8977;

    public final static int Shading=878474856;

    final public static int ShadingType=1487255197;
    
    final public static int Size=590957109;
    
    final public static int SM=8989;

    final public static int SMask=489767774;

    //use StandardFonts.STD as public value
    final static int StandardEncoding=-1595087640;

    final public static int StemH=1144339771;

    final public static int StemV=1144339785;

    final public static int StructParents=-1113539877;

    final public static int Style=1145650264;

    final public static int Subtype=1147962727;

    final public static int Supplement=2104860094;

    final public static int TilingType=1619174053;
    
    final public static int tintTransform=-1313946392;

    final public static int ToUnicode=1919185554;
    
    final public static int TR=9250;

    final public static int TrimBox=1026982273;

    final public static int Type=608780341;

    final public static int W=39;
    
    final public static int WhitePoint=2021497500;

    //use StandardFonts.WIN as public value
    final static int WinAnsiEncoding = 1524428269;

    final public static int Width=959726687;

    final public static int Widths=876896124;

    final public static int XHeight=962547833;

    final public static int XObject=979194486;

    final public static int XStep=591672680;

    final public static int YStep=591672681;

    /**
     * types of Object value found
     */

    public static final int VALUE_IS_DICTIONARY = 1;

    public static final int VALUE_IS_DICTIONARY_PAIRS = 2;

    public static final int VALUE_IS_STRING_CONSTANT = 3;

    public static final int VALUE_IS_STRING_KEY = 4;

    public static final int VALUE_IS_INT = 6;

    public static final int VALUE_IS_FLOAT = 7;

    public static final int VALUE_IS_BOOLEAN = 8;

    public static final int VALUE_IS_INT_ARRAY = 9;

    public static final int VALUE_IS_FLOAT_ARRAY = 10;

    public static final int VALUE_IS_BOOLEAN_ARRAY = 12;

    public static final int VALUE_IS_KEY_ARRAY = 14;

    public static final int VALUE_IS_DOUBLE_ARRAY = 16;

    public static final int VALUE_IS_MIXED_ARRAY = 18;

    public static final int VALUE_IS_STRING_ARRAY = 20;
    
    public static final int VALUE_IS_TEXTSTREAM=25;

    public static final int VALUE_IS_UNCODED_STRING = 30;

    /**
     * convert stream int key for dictionary entry
     */
    public static Object getKey(int keyStart, int keyLength, byte[] raw) {

        //save pair and reset
        byte[] bytes=new byte[keyLength];

        int id = generateChecksum(keyStart, keyLength, raw);

        System.arraycopy(raw,keyStart,bytes,0,keyLength);

        return new String(bytes);
    }

    /**
     * convert stream int key for dictionary entry
     */
    public static int getIntKey(int keyStart, int keyLength, byte[] raw) {

        /**

        byte[] a="Multiply".getBytes();

        keyStart=0;
        keyLength=a.length;
        raw=a;
        //PdfObject.debug=true;

    	byte[] bytes=new byte[keyLength];

        System.arraycopy(raw,keyStart,bytes,0,keyLength);

        System.out.println("final public static int "+new String(bytes)+"="+generateChecksum(keyStart, keyLength, raw)+";");
        System.exit(1);

        /**/

        //get key
        int id = generateChecksum(keyStart, keyLength, raw);
        int PDFkey=id;// standard setting is to use value

        /**
         * non-standard values
         */
        switch(id){

            case BPC:
                PDFkey=BitsPerComponent;
                break;

            case CMYK:
            	PDFkey=ColorSpaces.DeviceCMYK;
            	break;
            	
            case CS:
                PDFkey=ColorSpace;
                break;

            case DCT:
            	return PdfFilteredReader.DCTDecode;

            case DP:
                PDFkey=DecodeParms;
                break;

            case PdfFilteredReader.Fl:
                PDFkey=PdfFilteredReader.FlateDecode;
                break;
                
            case IM:
                PDFkey=ImageMask;
                break;

            case MacExpertEncoding:
            	PDFkey=StandardFonts.MACEXPERT;
            	break;

            case MacRomanEncoding:
                PDFkey=StandardFonts.MAC;
                break;

            case PDFDocEncoding:
                PDFkey=StandardFonts.PDF;
                break;
                
            case RGB:
            	PDFkey=ColorSpaces.DeviceRGB;
            	break;

            case StandardEncoding:
                PDFkey=StandardFonts.STD;
                break;

            case WinAnsiEncoding:
                PDFkey=StandardFonts.WIN;
                break;
        }

        return PDFkey;
    }

	public static int generateChecksum(int keyStart, int keyLength, byte[] raw) {
		//convert token to unique key
        int id=0,x=0,next;

        for(int i2=keyLength-1;i2>-1;i2--){
            next=raw[keyStart+i2];

            next=next-48;

            id=id+((next)<<x);

            x=x+8;
        }
		return id;
	}

    /**
     * get type of object
     */
    public static int getKeyType(int id) {

        int PDFkey=-1;


        switch(id){

	        case Alternate:
	        	return VALUE_IS_STRING_CONSTANT;
        	
        	case AIS:
        		return VALUE_IS_BOOLEAN;
        		
            case Annots:
                return VALUE_IS_KEY_ARRAY;

            case AntiAlias:
                return VALUE_IS_BOOLEAN;

            case Array:
                return VALUE_IS_FLOAT_ARRAY;

            case ArtBox:
                return VALUE_IS_FLOAT_ARRAY;

            case Background:
                return VALUE_IS_FLOAT_ARRAY;

            case BaseEncoding:
                return VALUE_IS_STRING_CONSTANT;

            case BaseFont:
                return VALUE_IS_UNCODED_STRING;

            case BBox:
                return VALUE_IS_FLOAT_ARRAY;

            case BitsPerComponent:
                    return VALUE_IS_INT;
                    
            case BitsPerSample:
            	return VALUE_IS_INT;

            case BlackIs1:
            	return VALUE_IS_BOOLEAN;

            case BlackPoint:
                return VALUE_IS_FLOAT_ARRAY;

            case BleedBox:
                return VALUE_IS_FLOAT_ARRAY;

            case Blend:
                return VALUE_IS_INT;

            case Bounds:
                return VALUE_IS_FLOAT_ARRAY;

            case BM:
                return VALUE_IS_MIXED_ARRAY;
            
            case C0:
                return VALUE_IS_FLOAT_ARRAY;

            case C1:
                return VALUE_IS_FLOAT_ARRAY;

            case CA:
                return VALUE_IS_FLOAT;

            case ca:
                return VALUE_IS_FLOAT;
                
            case CharProcs:
                return VALUE_IS_DICTIONARY_PAIRS;

            case CharSet:
                return VALUE_IS_TEXTSTREAM;

            case CMapName:
                return VALUE_IS_UNCODED_STRING;

            case Colors:
                return VALUE_IS_INT;

            case ColorSpace:
                return VALUE_IS_DICTIONARY;

            case Columns:
                return VALUE_IS_INT;

            case Contents:
                return VALUE_IS_KEY_ARRAY;

            case Coords:
                return VALUE_IS_FLOAT_ARRAY;

            case Count:
                return VALUE_IS_INT;

            case CropBox:
                return VALUE_IS_FLOAT_ARRAY;

            case CIDSystemInfo:
                return VALUE_IS_DICTIONARY;

            case CIDToGIDMap:
                return VALUE_IS_DICTIONARY;

            case CVMRC:
            	return VALUE_IS_STRING_CONSTANT;

            case Decode:
                return VALUE_IS_FLOAT_ARRAY;

            case DecodeParms:
                return VALUE_IS_DICTIONARY;

            case DescendantFonts:
                return VALUE_IS_DICTIONARY;

            case Differences:
                return VALUE_IS_MIXED_ARRAY;

            case Domain:
                return VALUE_IS_FLOAT_ARRAY;

            case DW:
                return VALUE_IS_INT;

            case EarlyChange:
                return VALUE_IS_INT;

            case EncodedByteAlign:
                return VALUE_IS_BOOLEAN;

            case Encode:
                return VALUE_IS_FLOAT_ARRAY;

            case Encoding:
                return VALUE_IS_DICTIONARY;

            case Extend:
                return VALUE_IS_BOOLEAN_ARRAY;

            case ExtGState:
                return VALUE_IS_DICTIONARY;

            case Filter:
                return VALUE_IS_MIXED_ARRAY;

            case FirstChar:
                return VALUE_IS_INT;

            case Flags:
                return VALUE_IS_INT;

            case Font:
                return VALUE_IS_DICTIONARY;

            case FontBBox:
                return VALUE_IS_FLOAT_ARRAY;

            case FontDescriptor:
                return VALUE_IS_DICTIONARY;

            case FontFile:
                return VALUE_IS_DICTIONARY;

            case FontFile2:
                return VALUE_IS_DICTIONARY;

            case FontFile3:
                return VALUE_IS_DICTIONARY;

            case FontMatrix:
                return VALUE_IS_DOUBLE_ARRAY;

            case FontName:
                return VALUE_IS_UNCODED_STRING;

            case FontStretch:
            	return VALUE_IS_UNCODED_STRING;

            case FormType:
                return VALUE_IS_INT;

            case Function:
                return VALUE_IS_DICTIONARY;

            case Functions:
                return VALUE_IS_KEY_ARRAY;

            case FunctionType:
                return VALUE_IS_INT;

            case Gamma:
                return VALUE_IS_FLOAT_ARRAY;

            case Group:
                return VALUE_IS_DICTIONARY;

            case Height:
                return VALUE_IS_INT;
                
            case Index:
            	return VALUE_IS_INT_ARRAY;

            case ImageMask:
            	return VALUE_IS_BOOLEAN;

            case Intent:
            	return VALUE_IS_UNCODED_STRING;

            case K:
                return VALUE_IS_INT;

            case Kids:
                return VALUE_IS_KEY_ARRAY;

            case JBIG2Globals:
                return VALUE_IS_DICTIONARY;

            case LastChar:
                return VALUE_IS_INT;

            case Length:
                return VALUE_IS_INT;

            case Length1:
                return VALUE_IS_INT;

            case Length2:
                return VALUE_IS_INT;

            case Length3:
                return VALUE_IS_INT;

            case LW:
                return VALUE_IS_FLOAT;

            case Mask:
                return VALUE_IS_DICTIONARY;

            case Matrix:
                return VALUE_IS_FLOAT_ARRAY;

            case MediaBox:
            	return VALUE_IS_FLOAT_ARRAY;

            case MissingWidth:
                return VALUE_IS_INT;

            case Name:
                return VALUE_IS_UNCODED_STRING;

            case OP:
            	return VALUE_IS_BOOLEAN;
            	
            case op:
            	return VALUE_IS_BOOLEAN;
            	
            case OPI:
            	return VALUE_IS_DICTIONARY;
            	
            case OPM:
            	return VALUE_IS_FLOAT;

            case Ordering:
                return VALUE_IS_TEXTSTREAM;

            case PaintType:
                return VALUE_IS_INT;

            case Pattern:
                return VALUE_IS_DICTIONARY;

            case PatternType:
                return VALUE_IS_INT;

            case Parent:
                return VALUE_IS_STRING_KEY;

            case Predictor:
                return VALUE_IS_INT;

            case ProcSet:
                return VALUE_IS_MIXED_ARRAY;

            case Range:
                return VALUE_IS_FLOAT_ARRAY;

            case Registry:
                return VALUE_IS_TEXTSTREAM;

            case Resources:
                return VALUE_IS_DICTIONARY;

            case Rotate:
                return VALUE_IS_INT;

            case Shading:
                return VALUE_IS_DICTIONARY;

            case Rows:
                return VALUE_IS_INT;

            case SA:
                return VALUE_IS_BOOLEAN;

            case ShadingType:
                return VALUE_IS_INT;
            
            case Size:
            	return VALUE_IS_INT_ARRAY;

            case SMask:
                return VALUE_IS_DICTIONARY;

            case StemV:
                return VALUE_IS_INT;
                
            case StructParents:
            	return VALUE_IS_INT;
                
            case Style:
                return VALUE_IS_DICTIONARY;
                
            case Subtype:
                return VALUE_IS_STRING_CONSTANT;

            case Supplement:
                return VALUE_IS_INT;
                
            case TilingType:
            	return VALUE_IS_INT;
                
            case ToUnicode:
                return VALUE_IS_DICTIONARY;

            case TR:
                return VALUE_IS_DICTIONARY;

            case TrimBox:
            	return VALUE_IS_FLOAT_ARRAY;

            case Type:
                return VALUE_IS_STRING_CONSTANT;

                //hack as odd structure
            case W:
                return VALUE_IS_TEXTSTREAM;
                
            case WhitePoint:
                return VALUE_IS_FLOAT_ARRAY;

            case Width:
                return VALUE_IS_INT;

            case Widths:
                return VALUE_IS_FLOAT_ARRAY;

            case XObject:
                return VALUE_IS_DICTIONARY;
              
            case XStep:
            	return VALUE_IS_FLOAT;
            	
            case YStep:
            	return VALUE_IS_FLOAT;
            	
            default:

                if(PdfObject.debug){
                    System.out.println("No type value set for "+id+" getKeyType(int id) in PdfDictionay");
                   // System.exit(1);

                }

                break;

        }


        return PDFkey;
    }
    
    public static PdfObject convertMapToNewObject(Map resValue, PdfObjectReader currentPdfFile) {
    	
		PdfObject pdfObject=null,Resources=null;
		
		final boolean debug=false;
		
		if(debug)
		System.out.println("convert Map to PdfObject "+resValue);

		{

			Resources=new PdfResourcesObject(1);

			if(resValue==null)
				return Resources;
			
			Iterator it=resValue.keySet().iterator();
			while(it.hasNext()){
				Object key=it.next();
				
				if(debug)
					System.out.println("convert Map to PdfObject");

				if(!key.equals("ProcSet") && !key.equals("Encoding")){

					Map values=null;
					Object test=resValue.get(key);
					if(test instanceof String){
						values=currentPdfFile.readObject(new PdfObject(1), (String)test,false,null);
						
					}else
						values=(Map)resValue.get(key);

					int size=values.keySet().size();
					int i=0;
					byte[][] keys=new byte[size][];
					byte[][] byteValue=new byte[size][];
					PdfObject[] objValue=new PdfObject[size];

					Iterator objs=(values).keySet().iterator();
					while(objs.hasNext()){
						String objKey=(String) objs.next();

						keys[i]=objKey.getBytes();

						if(key.equals("XObject")){
							pdfObject=new PdfXObject(1);
							Resources.setDictionary(PdfDictionary.XObject,pdfObject);

                        }else if(key.equals("ExtGState")){
                            pdfObject=new PdfExtGStateObject(1);
                            Resources.setDictionary(PdfDictionary.ExtGState,pdfObject);

                        }else if(key.equals("Shading")){
							pdfObject=new PdfShadingObject(1);
							Resources.setDictionary(PdfDictionary.Shading,pdfObject);

						}else if(key.equals("Pattern")){
							pdfObject=new PdfPatternObject(1);
							Resources.setDictionary(PdfDictionary.Pattern,pdfObject);

							
						}else if(key.equals("ColorSpace")){
							pdfObject=new PdfColorSpaceObject(1);
							Resources.setDictionary(PdfDictionary.ColorSpace,pdfObject);

							
						}else if(key.equals("Font")){
							pdfObject=new PdfFontObject(1);
							Resources.setDictionary(PdfDictionary.Font,pdfObject);

							
						}else{
							System.out.println("Key ="+key);
							System.exit(1);
						}

						String ref=(String) values.get(objKey);

						if(!ref.endsWith(" R")){
							System.out.println("(convertMapToNewObject) Unexpected value "+ref);
							System.exit(1);
						}
						
						//read data
						if(pdfObject.getObjectType()==PdfDictionary.ColorSpace)
							currentPdfFile.handleColorSpaces(pdfObject, 0,  ref.getBytes(), false, "    ");    
							else
						currentPdfFile.readObject(pdfObject, ref,false,null);
						objValue[i]=pdfObject;
						byteValue[i]=ref.getBytes();
						
						i++;
					}

					pdfObject.setDictionaryPairs(keys,byteValue,objValue);

				}	
			}
		}
		return Resources;
	}
}