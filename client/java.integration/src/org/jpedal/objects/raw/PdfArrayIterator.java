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

  * PdfArrayIterator.java
  * ---------------
  * (C) Copyright 2008, by IDRsolutions and Contributors.
  *
  *
  * --------------------------
 */
package org.jpedal.objects.raw;

import org.jpedal.fonts.StandardFonts;
import org.jpedal.io.PdfReader;

/**
 * allow fast access to data from PDF object
 *
 */
public class PdfArrayIterator {

	public static final int TYPE_INTEGER = 1;

	byte[][] rawData=null;
	
	//used for Font chars
	boolean hasHexChars=false;
	
	
	int tokenCount=0,currentToken=0,spaceChar=-1;
	
	public PdfArrayIterator(byte[][] rawData) {
		this.rawData=rawData;
		
		if(rawData!=null)
			tokenCount=rawData.length;
	}

	public PdfArrayIterator(String colorspaceObject) {
		byte[][] rawData=new byte[1][];
		rawData[0]=colorspaceObject.getBytes();
		
		tokenCount=1;
	}

	public boolean hasMoreTokens() {
		return currentToken<tokenCount;
	}

	//return type (ie PdfArrayIterator.TYPE_INTEGER)
	public int getNextValueType() {
		
		//allow for non-valid
		if(rawData==null || rawData[currentToken]==null || rawData[currentToken].length==0)
			return PdfDictionary.Unknown;
		else{ //look at first char
			int firstByte=rawData[currentToken][0];
			
			if(firstByte>47 && firstByte<58) //is between 0 and 9
				return TYPE_INTEGER;
			else
				return PdfDictionary.Unknown;
		}
	}

	/**
	 * should only be used with Font Object
	 */
	public String getNextValueAsFontChar(int pointer) {
		
		String value="";
		
		
		if(currentToken<tokenCount){
			
			//allow for non-valid
			if(rawData==null || rawData[currentToken]==null || rawData[currentToken].length==0)
				throw new RuntimeException("NullValue exception with PdfArrayIterator");
			
			
			//lose / at start
			int length=rawData[currentToken].length-1;
			
			byte[] raw=new byte[length];
			
			System.arraycopy(rawData[currentToken], 1, raw, 0, length);
			
			//////////////////////////////////////////////////////
			///getNextValueAsFontChar
		    //ensure its a glyph and not a number
			value=new String(raw);
		    value = StandardFonts.convertNumberToGlyph(value);

			char c=value.charAt(0);
			if (c=='B' | c=='c' | c=='C' | c=='G') {
				int i = 1,l=value.length();
				while (!hasHexChars && i < l)
					hasHexChars =Character.isLetter(value.charAt(i++));
			}
			//////////////////////////////////////////////////////
			
			//see if space
			if(raw.length==5 && raw[0]=='s' && raw[1]=='p' && raw[2]=='a' && raw[3]=='c' && raw[4]=='e')
				spaceChar=pointer;
			

			currentToken++;
		}else
			throw new RuntimeException("Out of range exception with PdfArrayIterator");
		
		return value;
	}

	public int getNextValueAsInteger() {
		
		if(currentToken<tokenCount){
			
			//allow for non-valid
			if(rawData==null || rawData[currentToken]==null || rawData[currentToken].length==0)
				throw new RuntimeException("NullValue exception with PdfArrayIterator");
			
			byte[] raw=rawData[currentToken];
			
			currentToken++;
			
			return PdfReader.parseInt(0,raw.length, raw);

		}else
			throw new RuntimeException("Out of range exception with PdfArrayIterator");
		
	}
	
	public int getNextValueAsConstant(boolean moveToNextAfter) {
		
		if(currentToken<tokenCount){
			
			//allow for non-valid
			if(rawData==null || rawData[currentToken]==null || rawData[currentToken].length==0)
				throw new RuntimeException("NullValue exception with PdfArrayIterator");
			
			byte[] raw=rawData[currentToken];

            if(moveToNextAfter)
			currentToken++;
			
			return PdfDictionary.getIntKey(1,raw.length-1, raw);

		}else
			throw new RuntimeException("Out of range exception with PdfArrayIterator");
	}

	public int getSpaceChar() {
		return spaceChar;
	}

	public boolean hasHexChars() {
		return hasHexChars;
	}

	public int getTokenCount() {
		return this.tokenCount;
	}

	public String getNextValueAsString(boolean rollon) {
		
		String value="";
		
		if(currentToken<tokenCount){
			
			//allow for non-valid
			if(rawData==null || rawData[currentToken]==null || rawData[currentToken].length==0)
				throw new RuntimeException("NullValue exception with PdfArrayIterator");
			
			byte[] raw=rawData[currentToken];
			value=new String(raw);

			if(rollon)
			currentToken++;
		}else
			throw new RuntimeException("Out of range exception with PdfArrayIterator");
		
		return value;
	}

    public int getNextValueAsKey() {

		if(currentToken<tokenCount){

			//allow for non-valid
			if(rawData==null || rawData[currentToken]==null || rawData[currentToken].length==0)
				throw new RuntimeException("NullValue exception with PdfArrayIterator");

			byte[] raw=rawData[currentToken];
            currentToken++;
            
            //System.out.println("String="+new String(raw));

            //System.exit(1);
            return PdfDictionary.getIntKey(0,raw.length,raw);

		}else
			throw new RuntimeException("Out of range exception with PdfArrayIterator");

	}
}
