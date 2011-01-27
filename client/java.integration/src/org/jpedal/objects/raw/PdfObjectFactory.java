/**
 * ===========================================
 * Java Pdf Extraction Decoding Access Library
 * ===========================================
 *
 * Project Info:  http://www.jpedal.org
 *
 * (C) Copyright 2008, IDRsolutions and Contributors.
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

  * PdfObjectFactory.java
  * ---------------
  * (C) Copyright 2008, by IDRsolutions and Contributors.
  *
  *
  * --------------------------
 */
package org.jpedal.objects.raw;

/**
 * return required object according to key
 */
public class PdfObjectFactory {
    public static PdfObject createObject(int id, String ref) {

        switch(id){

        	case PdfDictionary.CharProcs:
        		return new PdfFontObject(ref);

        	case PdfDictionary.CIDSystemInfo:
	    		return new PdfFontObject(ref);

	        case PdfDictionary.CIDToGIDMap:
	    		return new PdfFontObject(ref);

            case PdfDictionary.ColorSpace:
                return new PdfColorSpaceObject(ref);

            case PdfDictionary.DecodeParms:
                return new PdfDecodeParmsObject(ref);

            case PdfDictionary.DescendantFonts:
                return new PdfFontObject(ref);

            case PdfDictionary.Encoding:
                return new PdfFontObject(ref);

            case PdfDictionary.ExtGState:
                return new PdfExtGStateObject(ref);

            case PdfDictionary.Font:
                return new PdfFontObject(ref);

            case PdfDictionary.FontDescriptor:
                return new PdfFontObject(ref);

            case PdfDictionary.FontFile:
                return new PdfFontObject(ref);

            case PdfDictionary.FontFile2:
                return new PdfFontObject(ref);

            case PdfDictionary.FontFile3:
                return new PdfFontObject(ref);

            case PdfDictionary.Function:
                return new PdfFunctionObject(ref);

            case PdfDictionary.Group:
                return new PdfObject(ref);

            case PdfDictionary.JBIG2Globals:
                return new PdfDecodeParmsObject(ref);

            case PdfDictionary.Mask:
                return new PdfMaskObject(ref);    

            case PdfDictionary.OPI:
                return new PdfXObject(ref);

            case PdfDictionary.Pattern:
                return new PdfPatternObject(ref);
                
            case PdfDictionary.Resources:
                return new PdfResourcesObject(ref);

            case PdfDictionary.Shading:
                return new PdfShadingObject(ref);

            case PdfDictionary.SMask:
                return new PdfMaskObject(ref);

            case PdfDictionary.Style:
                return new PdfFontObject(ref);    
                
            case PdfDictionary.ToUnicode:
                return new PdfFontObject(ref); 
                
            case PdfDictionary.TR:
            	return new PdfMaskObject(ref); 
            
            case PdfDictionary.XObject:
                return new PdfXObject(ref);    
            
            default:

        }
        return null;
    }

     public static PdfObject createObject(int id, int ref,int gen) {

        switch(id){

        	case PdfDictionary.CharProcs:
        		return new PdfFontObject(ref, gen);

        	case PdfDictionary.CIDSystemInfo:
        		return new PdfFontObject(ref, gen);

        	case PdfDictionary.CIDToGIDMap:
        		return new PdfFontObject(ref, gen);

            case PdfDictionary.ColorSpace:
                return new PdfColorSpaceObject(ref,gen);

            case PdfDictionary.DecodeParms:
               return new PdfDecodeParmsObject(ref, gen);

            case PdfDictionary.DescendantFonts:
                return new PdfFontObject(ref, gen);

            case PdfDictionary.Encoding:
                 return new PdfFontObject(ref, gen);

            case PdfDictionary.ExtGState:
                return new PdfExtGStateObject(ref,gen);

            case PdfDictionary.FontDescriptor:
                return new PdfFontObject(ref, gen);

            case PdfDictionary.FontFile:
                return new PdfFontObject(ref, gen);

            case PdfDictionary.FontFile2:
                return new PdfFontObject(ref, gen);

            case PdfDictionary.FontFile3:
                return new PdfFontObject(ref, gen);

            case PdfDictionary.Function:
                return new PdfFunctionObject(ref,gen);
               
            case PdfDictionary.Group:
                return new PdfObject(ref, gen);

            case PdfDictionary.JBIG2Globals:
                return new PdfDecodeParmsObject(ref,gen);

            case PdfDictionary.Mask:
                return new PdfMaskObject(ref,gen);

            case PdfDictionary.OPI:
                return new PdfXObject(ref,gen);

            case PdfDictionary.Pattern:
                return new PdfPatternObject(ref,gen);

            case PdfDictionary.Resources:
                return new PdfResourcesObject(ref,gen);

            case PdfDictionary.Shading:
                return new PdfShadingObject(ref, gen);

            case PdfDictionary.SMask:
                return new PdfMaskObject(ref,gen);    

            case PdfDictionary.TR:
                return new PdfMaskObject(ref,gen);

            case PdfDictionary.ToUnicode:
                return new PdfFontObject(ref,gen); 
                
            case PdfDictionary.XObject:
        		return new PdfXObject(ref, gen);
                

            default:

        }
        return null;
    }
}
