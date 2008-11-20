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
 * PdfReader.java
 * ---------------
 */
package org.jpedal.io;

import org.jpedal.objects.raw.*;
import org.jpedal.color.ColorSpaces;
import org.jpedal.constants.PDFflags;
import org.jpedal.exception.PdfException;
import org.jpedal.exception.PdfSecurityException;
import org.jpedal.fonts.StandardFonts;
import org.jpedal.objects.Javascript;
import org.jpedal.objects.PageLookup;
import org.jpedal.objects.PdfFileInformation;
import org.jpedal.objects.acroforms.utils.ConvertToString;
import org.jpedal.utils.LogWriter;
import org.jpedal.utils.Sorts;
import org.jpedal.utils.Strip;
import org.jpedal.utils.repositories.Vector_Int;

import javax.crypto.Cipher;
import javax.crypto.CipherInputStream;
import javax.crypto.SecretKey;
import javax.crypto.spec.IvParameterSpec;
import javax.crypto.spec.SecretKeySpec;
import java.io.*;
import java.security.MessageDigest;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Map;
import java.util.StringTokenizer;

/**
 * extends PdfFileReader and PdfFilteredFileReader to
 * provide access at object level to data in pdf file
 */
public class PdfReader extends PdfFilteredReader implements PdfObjectReader, Serializable {

	/**points to stream on disk*/
	private int startStreamPointer,endStreamPointer;

	private boolean debugAES=false;

	/**used to cache last compressed object*/
	private byte[] lastCompressedStream=null;

	//text fields
	private Map fields=new HashMap();

	/**location of end ref*/
	private Vector_Int xref=new Vector_Int(100);

	/**used to cache last compressed object*/
	private Map lastOffsetStart,lastOffsetEnd;

	/**used to cache last compressed object*/
	private int lastFirst;

	/**current/last object read*/
	private Map objData=null;

	/**names lookup table*/
	private Map nameLookup=new HashMap();

	/**allows cache of data so not reread if requested consecutive times*/
	private String lastRef="";

	/**Information object holds information from file*/
	PdfFileInformation currentFileInformation = new PdfFileInformation();

	/**pattern to look for in objects*/
	final static private String pattern= "obj";


	/**flag to show if extraction allowed*/
	private boolean extractionIsAllowed = true;

	private final static byte[] endPattern = { 101, 110, 100, 111, 98, 106 }; //pattern endobj

    private final static byte[] newPattern = "obj".getBytes();

    private final static byte[] oldPattern = "xref".getBytes();
    
    private final static byte[] endObj = { 32, 111, 98, 106 }; //pattern endobj

	private final static byte[] lengthString = { 47, 76, 101, 110, 103, 116, 104}; //pattern /Length
	private final static byte[] startStream = { 115, 116, 114, 101, 97, 109};
	private final static byte[] endStream = { 101, 110, 100, 115, 116, 114, 101, 97,109 };

	/**flag to show data encrytped*/
	private boolean isEncrypted = false;

	/**flag to show provider read*/
	private boolean isInitialised=false;

	/**encryption password*/
	private byte[] encryptionPassword = new byte[ 0 ];

	/**info object*/
	private String infoObject=null;

	/**key used for encryption*/
	private byte[] encryptionKey=null;

	/**flag to show if user can view file*/
	private boolean isFileViewable=true;

	/** revision used for encryption*/
	private int rev=0;

	/**length of encryption key used*/
	private int keyLength=5;

	/**P value in encryption*/
	private int P=0;

	/**O value in encryption*/
	private byte[] O=new byte[0];

	/**U value in encryption*/
	private byte[] U=new byte[0];

	/**holds file ID*/
	private String ID="";


	/**flag if password supplied*/
	private boolean isPasswordSupplied=false;

	//<start-13>
	/**cipher used for decryption*/
	private Cipher cipher=null;
	//<end-13>

	/**encryption padding*/
	private String[] padding={"28","BF","4E","5E","4E","75","8A","41","64","00","4E","56","FF","FA","01","08",
			"2E","2E","00","B6","D0","68","3E","80","2F","0C","A9","FE","64","53","69","7A"};

	/**length of each object*/
	private int[] ObjLengthTable;

	private boolean refTableInvalid=false;

	/**size above which objects stored on disk (-1 is off)*/
	private int miniumumCacheSize;
	public boolean interruptRefReading=false;

	// additional values for V4 option
	String EFF,CFM;

	boolean isMetaDataEncypted=true;

	Map StrF,StmF;

	boolean stringsEncoded=false;

	//filter names Identity=no encryption
	String StrFname,StmFname;

	//tell user status on password
	private int passwordStatus=0;
	private Javascript javascript;


	private static boolean alwaysReinitCipher=false;


	static{
		String flag=System.getProperty("org.jpedal.cipher.reinit");
		if(flag!=null && flag.toLowerCase().equals("true"))
			alwaysReinitCipher=true;

	}


	public PdfReader() {

		//setup a list of fields which are string values
		fields.put("T","x");
		fields.put("NM","x");
		fields.put("TM","x");
		fields.put("TU","x");
		fields.put("CA","x");
		fields.put("R","x");
		fields.put("V","x");
		fields.put("RC","x");
		fields.put("DA","x");
		fields.put("DV","x");
		fields.put("JS","x");
		fields.put("Contents","x");

	}

    /**
     * set password as well
     */
    public PdfReader(String password) {
        super();

        if(password==null)
        password="";
        
        setEncryptionPassword(password);
    }

    //pass in Javascript object
	public void setJavaScriptObject(Javascript javascript) {
		this.javascript=javascript;
	}

	/**
	 * read first start ref from last 1024 bytes
	 */
	private int readFirstStartRef() throws PdfException {

		//reset flag
		refTableInvalid=false;
		int pointer = -1;
		int i = 1019;
		StringBuffer startRef = new StringBuffer();

		/**move to end of file and read last 1024 bytes*/
		int block=1024;
		byte[] lastBytes = new byte[block];
		long end;

		/**
		 * set endpoint, losing null chars and anything before EOF
		 */
		final int[] EndOfFileMarker={37,37,69,79,70};
		int valReached=4;
		boolean EOFFound=false;
		try {
			end=eof;

			/**
			 * lose nulls and other trash from end of file
			 */
			int bufSize=255;
			while(true){
				byte[] buffer=new byte[bufSize];

				movePointer(end-bufSize);
				pdf_datafile.read(buffer); //get next chars

				int offset=0;

				for(int ii=bufSize-1;ii>-1;ii--){

					//see if we can decrement EOF tracker or restart check
					if(!EOFFound)
						valReached=4;

					if(buffer[ii]==EndOfFileMarker[valReached]){
						valReached--;
						EOFFound=true;
					}else
						EOFFound=false;

					//move to next byte
					offset--;

					if(valReached<0)
						ii=-1;

				}

				//exit if found values on loop
				if(valReached<0){
					end=end-offset;
					break;
				}else{
					end=end-bufSize;
				}

				//allow for no eof
				if(end<0){
					end=eof;
					break;
				}
			}

			//end=end+bufSize;

			//allow for very small file
			int count=(int)(end - block);

			if(count<0){
				count=0;
				int size=(int)eof;
				lastBytes=new byte[size];
				i=size+3; //force reset below
			}

			movePointer(count);

			pdf_datafile.read(lastBytes);


		} catch (Exception e) {
			LogWriter.writeLog("Exception " + e + " reading last 1024 bytes");
			throw new PdfException( e + " reading last 1024 bytes");
		}

//		for(int ii=0;ii<lastBytes.length;ii++){
//		System.out.print((char)lastBytes[ii]);
//		}
//		System.out.println();

		//look for tref as end of startxref
		int fileSize=lastBytes.length;

		if(i>fileSize)
			i=fileSize-5;

		while (i >-1) {

			if ((lastBytes[i] == 116)
					&& (lastBytes[i + 1] == 120)
					&& (lastBytes[i + 2] == 114)
					&& (lastBytes[i + 3] == 101)
					&& (lastBytes[i + 4] == 102))
				break;


			i--;

		}

		/**trap buggy files*/
		if(i==-1){
			try {
				this.pdf_datafile.close();
			} catch (IOException e1) {
				LogWriter.writeLog("Exception "+e1+" closing file");
			}
			throw new PdfException( "No Startref found in last 1024 bytes ");
		}

		i = i + 5; //allow for word length

		//move to start of value ignoring spaces or returns
		while (i < 1024 && (lastBytes[i] == 10 || lastBytes[i] == 32 || lastBytes[i] == 13))
			i++;

        //move to start of value ignoring spaces or returns
		while ((i < 1024)
				&& (lastBytes[i] != 10)
				&& (lastBytes[i] != 32)
				&& (lastBytes[i] != 13)) {
			startRef.append((char) lastBytes[i]);
			i++;
		}

        /**convert xref to string to get pointer*/
		if (startRef.length() > 0)
			pointer = Integer.parseInt(startRef.toString());

		if (pointer == -1){
			LogWriter.writeLog("No Startref found in last 1024 bytes ");
			try {
				this.pdf_datafile.close();
			} catch (IOException e1) {
				LogWriter.writeLog("Exception "+e1+" closing file");
			}
			throw new PdfException( "No Startref found in last 1024 bytes ");
		}

		return pointer;
	}


	/**set a password for encryption*/
	public void setEncryptionPassword(String password){

		this.encryptionPassword = password.getBytes();
	}


	/**
	 * turns any refs into String or Map
	 */
	public Object resolveToMapOrString(Object command, Object field) {
		/**
		 * convert field into map or string
		 */

		if((fields!=null)&&(fields.get(command)!=null)&&(field instanceof byte[])){

			byte[] fieldBytes=getByteTextStringValue(field,fields);
			field=getTextString(fieldBytes);

		}else if((field instanceof String)&&(((String)field).endsWith(" R"))){
			Object newObj= getObjectValue(field);

			if(newObj instanceof Map){

				Map newField=(Map)newObj;
				/** removed to fix read obj recursive for obj=n=*/
				//newField.put("obj",field); //store name
				this.readStream((String)field,true); //force decode of any streams
				field=newField;

			}else
				field=newObj;

		}//else if(((Map)field).get(command)==null)
		//field=null;

		return field;
	}


	private byte[] readObjectData(int bufSize){

        final int XXX=2*1024*1024;

        boolean debugByte=false;

        int rawSize=bufSize,realPos=0;

		final boolean debug=false;

		final boolean debugFaster=false;

		boolean lengthSet=false; //start false and set to true if we find /Length in metadata
		boolean streamFound=false;

		if(debug)
			System.out.println("=============================");

		boolean isDefaultSize=false;
		if(bufSize<1){
			isDefaultSize=true;
			bufSize=128;
		}

        //array for data
        int ptr=0, maxPtr=bufSize;
        byte[] readData=new byte[maxPtr];


        //make sure fits in memory
		if(miniumumCacheSize!=-1 && bufSize>miniumumCacheSize)
			bufSize=miniumumCacheSize;

		//rest cache flags
		startStreamPointer=-1;
		endStreamPointer=-1;

		int charReached = 0,charReached2=0, charReached3=0,startStreamCount=0, endStreamCount=0,bytesAddedToTempStream=0;

		int miniumumCacheSize=this.miniumumCacheSize;

		boolean cacheStream =((this.miniumumCacheSize!=-1)||debugCaching);

		if(debugCaching && miniumumCacheSize==-1){
			miniumumCacheSize=8192;
			this.miniumumCacheSize=miniumumCacheSize;
		}

		int start=0;

		byte[] tempStreamData=null;
		if(cacheStream)
			tempStreamData=new byte[miniumumCacheSize];

		byte[] array=null,buffer=null,dataRead=null;
		boolean inStream=false,inLimits=false,ignoreByte;

		/**adjust buffer if less than bytes left in file*/
		long pointer=0,lastEndStream=-1,objStart=-1;

		/**read in the bytes, using the startRef as our terminator*/
		ByteArrayOutputStream bis=null;
        if(debugByte)
                bis=new ByteArrayOutputStream();

        /**read the object or block*/
		try {

			byte currentByte=0,lastByte;

			int i=bufSize-1;
			int offset=-bufSize;

			int blocksRead=0;

			int lastEnd=-1,lastComment=-1;

			while (true) {

				i++;

				if(i==bufSize){

					//cache data and update counter
					if(!cacheStream){
						if(blocksRead==1){
							dataRead=buffer;
						}else if(blocksRead>1){

							int bytesRead=dataRead.length;
							int newBytes=buffer.length;
							byte[] tmp=new byte[bytesRead+newBytes];

							//existing data into new array
							System.arraycopy(dataRead, 0, tmp, 0, bytesRead);

							//data from current block
							System.arraycopy(buffer, 0, tmp, bytesRead, newBytes);

							dataRead=tmp;
						}
						blocksRead++;
					}


					//double size of default buffer if lots of reads
					//if((isDefaultSize)&&(bufSize<16384))
					//	bufSize=bufSize*2;

					/**
					 * read the next block
					 */
					pointer = this.getPointer();

					/**adjust buffer if less than bytes left in file*/
					if (pointer + bufSize > eof)
						bufSize = (int) (eof - pointer);

					bufSize += 6;
					buffer = new byte[bufSize];

					/**get bytes into buffer*/
					pdf_datafile.read(buffer);

					if(debug)
						System.out.println("--read block");

					offset=offset+i;
					i=0;

				}

				/**write out and look for endobj at end*/
				lastByte=currentByte;
				currentByte = buffer[i];
				ignoreByte=false;

				//track comments
				if(currentByte=='%')
					lastComment=realPos;

				/**check for endobj at end - reset if not*/
				if (currentByte == endPattern[charReached] &&  !inStream)
					charReached++;
				else
					charReached = 0;

				//also scan for <SPACE>obj after endstream incase no endobj
				if(streamFound &&currentByte == endObj[charReached2] &&  !inStream)
					charReached2++;
				else
					charReached2 = 0;

				/**if length not set we go on endstream in data*/
				if(!lengthSet){

					//also scan for /Length if it had a valid size
					if(rawSize!=-1){
						if(!streamFound &&currentByte == lengthString[charReached3] &&  !inStream){
							charReached3++;
							if(charReached3==6)
								lengthSet=true;
						}else
							charReached3 = 0;
					}

				}

				if(debug)
					System.out.println((pointer+i)+" i="+(i+offset)+ ' ' +currentByte+ ' ' +(char)currentByte);
				/**
				 * if stream can be cached, look for start and see if object outgrows size allowed
				 *
				 * track start and end of stream
				 */

				if(cacheStream){

					if ((!inStream)&&(currentByte == 62) && ((lastByte == 62)))
						inLimits=false;

					if(!inStream){  //keep look out for start of stream

						//look for start of stream and set inStream true
						if (startStreamCount<6 && currentByte == startStream[startStreamCount] && !inLimits){
							startStreamCount++;
						}else
							startStreamCount=0;

						if((startStreamCount == 6)){ //stream start found so log
							inStream=true;

							streamFound=true;

							//add char which will otherwise be missed off
							if(bis!=null && debugByte)
								bis.write(currentByte);

                            readData[ptr]=currentByte;
                            ptr++;
                            if(ptr==maxPtr){
                                if(maxPtr<XXX)
                                maxPtr=maxPtr*2;
                                else
                                maxPtr=maxPtr+100000;

                                byte[] tmpArray=new byte[maxPtr];
                                System.arraycopy(readData,0,tmpArray,0,readData.length);

                                readData=tmpArray;
                            }

                            /**
							 * allow for multiple starts in a stream, and only take account
							 * of the first start
							 */
							if(startStreamPointer == -1){
								ignoreByte=true;

								startStreamCount=0;

								start=i+1;

								if (buffer[start] == 13 && buffer[start+1] == 10) //allow for double linefeed
									start+=2;
								else if((buffer[start]==10)|(buffer[start]==13))
									start++;

								startStreamPointer=(int) (start+pointer);
							}
							//factor in offset
							start=start+offset;
						}
					}else{ //then end of stream

						//store fist miniumumCacheSize bytes so we can add back if not stored on disk
						if(debugCaching || (bytesAddedToTempStream < miniumumCacheSize)){

							if(debugCaching){//make sure its big enough
								if(bytesAddedToTempStream>=tempStreamData.length){

									byte[] newArray=new byte[bytesAddedToTempStream+1000];
									System.arraycopy(tempStreamData, 0, newArray, 0, tempStreamData.length);
									tempStreamData=newArray;
								}
							}

							tempStreamData[bytesAddedToTempStream]=currentByte;
							bytesAddedToTempStream++;
							ignoreByte=true;

						}

						//look for end and keep an eye on size
						if (currentByte == endStream[endStreamCount] && !inLimits)
							endStreamCount++;
						else{
							endStreamCount=0;

							//allow for eendstream
							if (currentByte == endStream[endStreamCount] && !inLimits)
								endStreamCount++;
						}
						if(debug)
							System.out.println(endStreamCount+" "+inLimits+ ' ' +currentByte);

						//if end found and not too big, tag onto bis otherwise we just keep locations for later
						if(endStreamCount == 9){
							//inStream=false;
							endStreamCount=0;
							//tag end of stream

							int j=i-9;

							//add back if not being cached
							if((debugCaching || bytesAddedToTempStream < miniumumCacheSize)){

								for(int aa=0;aa<bytesAddedToTempStream;aa++){

                                    if(bis!=null)
                                    bis.write(tempStreamData[aa]);

                                    readData[ptr]=tempStreamData[aa];
                                    ptr++;
                                    if(ptr==maxPtr){
                                        if(maxPtr<XXX)
                                        maxPtr=maxPtr*2;
                                        else
                                        maxPtr=maxPtr+100000;

                                        byte[] tmpArray=new byte[maxPtr];
                                        System.arraycopy(readData,0,tmpArray,0,readData.length);

                                        readData=tmpArray;
                                    }

                                }

                                //not cached so reset flags
								if(!debugCaching)
									startStreamPointer=-1;
							}//else

							if(startStreamPointer!=-1)
								endStreamPointer=(int) (j+pointer);

							if(endStreamPointer<startStreamPointer){
								startStreamPointer=-1;
								endStreamPointer=-1;
							}
							inStream=false;
							ignoreByte=true;

						}
					}

					if ((!inStream) &&(currentByte == 60) && ((lastByte == 60)))
						inLimits=true;

				}

				if (charReached == 6 || charReached2==4){

					if(!lengthSet)
						break;

					charReached=0;
					charReached2=0;
					lastEnd=realPos;

				}

				if(lengthSet && realPos>=rawSize){
					//System.out.println(realPos+" "+rawSize);
					break;
				}

				if((debugFaster || !ignoreByte) && (debugCaching || !cacheStream || !inStream)){//|| !inStream)
					//if(cacheStream)

                    if(bis!=null)
                    bis.write(currentByte);

                    readData[ptr]=currentByte;

                    ptr++;
                    if(ptr==maxPtr){
                        if(maxPtr<XXX)
                        maxPtr=maxPtr*2;
                        else
                        maxPtr=maxPtr+100000;

                        byte[] tmpArray=new byte[maxPtr];
                        System.arraycopy(readData,0,tmpArray,0,readData.length);

                        readData=tmpArray;
                    }

                }

                realPos++;
			}

			//create byte array to return
			if(cacheStream){

                array=readData;

                if(bis!=null){
                    bis.close();

                    /**get bytes into buffer*/
                    array=bis.toByteArray();
                }
			}else{

				if(blocksRead==1){ //scenario 1 - all in first block
					array=new byte[i];
					System.arraycopy(buffer, 0, array, 0, i);
				}else{
					int bytesRead=dataRead.length;

					array=new byte[bytesRead+i];
					//existing data
					System.arraycopy(dataRead, 0, array, 0, bytesRead);

					//data from current block
					System.arraycopy(buffer, 0, array, bytesRead, i);
				}

				if(lengthSet && lastEnd!=-1 && lastComment!=-1 && lastComment>lastEnd){
					byte[] newArray = new byte[lastEnd];
					System.arraycopy(array, 0, newArray, 0, lastEnd);
					array = newArray;
				}

				//if(!cacheStream || debugCaching){
				if(debugFaster && bis!=null){

                    bis.close();

					/**get bytes into buffer*/
					byte[] testArray=bis.toByteArray();

					if(array.length!=testArray.length){
						System.out.println("Different lengths "+array.length+ ' ' +testArray.length);
						System.exit(1);
					}

					int count=array.length;
					for(int ii=0;ii<count;ii++){
						if(array[ii]!=testArray[ii]){
							System.out.println("Different values at "+ii+" >>"+array[ii]+ ' ' +testArray[ii]);
							System.exit(1);
						}
					}
				}
			}

			if(debug)
				System.out.println("cache="+(endStreamPointer-startStreamPointer)+" array="+array.length+" START="+start);

			//System.out.println(startStreamPointer+" "+endStreamPointer+" "+start);
			if((startStreamPointer!=-1)&&(debugCaching))
				verifyCachedData(cacheStream, array, start);

			if(!cacheStream && !lengthSet)
				array = checkEndObject(array, objStart, lastEndStream);

		} catch (Exception e) {
			e.printStackTrace();
			LogWriter.writeLog("Exception " + e + " reading object");
		}

		return array;
	}

	private byte[] checkEndObject(byte[] array, long objStart, long lastEndStream) {
		int ObjStartCount = 0;

		//check if mising endobj
		for (int i = 0; i < array.length - 8; i++) {

			//track endstream and first or second obj
			if ((ObjStartCount < 2) && (array[i] == 32) && (array[i + 1] == 111) &&
					(array[i + 2] == 98) && (array[i + 3] == 106)) {
				ObjStartCount++;
				objStart = i;
			}
			if ((ObjStartCount < 2) && (array[i] == 101) && (array[i + 1] == 110) &&
					(array[i + 2] == 100) && (array[i + 3] == 115) &&
					(array[i + 4] == 116) && (array[i + 5] == 114) &&
					(array[i + 6] == 101) && (array[i + 7] == 97) && (array[i + 8] == 109))
				lastEndStream = i + 9;
		}

		if ((lastEndStream > 0) && (objStart > lastEndStream)) {
			byte[] newArray = new byte[(int) lastEndStream];
			System.arraycopy(array, 0, newArray, 0, (int) lastEndStream);
			array = newArray;
		}
		return array;
	}

	private void verifyCachedData(boolean cacheStream, byte[] fullArray,int start) throws IOException {

		try{
			//store non-cached and read cached version from disk to test

			int arrayLength=endStreamPointer-startStreamPointer;

			if(arrayLength<0){

				System.out.println("Array size negative "+arrayLength);

				System.out.println(startStreamPointer+" "+endStreamPointer);
				System.out.println(" ");
				for(int jj=0;jj<startStreamPointer+30;jj++)
					System.err.println(jj+" "+(char)fullArray[jj]);
				System.exit(1);
			}

			byte[] array = new byte[arrayLength];
			this.movePointer(startStreamPointer);
			this.pdf_datafile.read(array);

			//System.out.println("end="+endStreamPointer+"<>"+(endStreamPointer-startStreamPointer));
			//System.out.println(arrayLength+"<>"+(fullArray.length)+" "+start);
			arrayLength=array.length;

			boolean failed=false;
			for(int ii=0;ii<arrayLength;ii++){
				if((fullArray[ii+start]!=array[ii])){
					//System.out.println(startStreamPointer+"<>"+endStreamPointer);
					System.out.println("X1 Not same value at "+ii+" =="+fullArray[ii+start]+ ' ' +array[ii]);
					failed=true;
					System.exit(1);
				}
			}
			if(failed)
				System.exit(1);

		}catch(Exception ee){
			ee.printStackTrace();
			System.exit(1);
		}

	}

	/**
	 * read a dictionary object
	 */
	public int readDictionaryAsObject(PdfObject pdfObject, String objectRef, int i, byte[] raw,
                                      int endPoint, String paddingString, boolean isInlineImage){

		final boolean debugFastCode=false;//pdfObject.getDebugMode();//PdfObject.debug;


        if(debugFastCode)
        paddingString=paddingString+"   ";

        int PDFkeyInt=-1,pdfKeyType=-1,level=0;

        //allow for no << at start
        if(isInlineImage)
        	level=1;
        
        Object PDFkey=null;

        //show details in debug mode
        if(debugFastCode){
            System.out.println("\n\n"+paddingString+"------------readDictionaryAsObject ref="+objectRef+" in "+pdfObject+"-----------------\ni="+i+"\nData=>>>>");
            System.out.print(paddingString);
            for(int jj=i;jj<raw.length;jj++){
                System.out.print((char)raw[jj]);

                if(jj>5 && raw[jj-5]=='s' && raw[jj-4]=='t' && raw[jj-3]=='r' && raw[jj-2]=='e' && raw[jj-1]=='a' &&raw[jj]=='m')
                    jj=raw.length;
            }
            System.out.println(paddingString+"\n<<<<-----------------------------------------------------\n");
        }

      //  if(objectRef.equals("23 0 R"))
      //          throw new RuntimeException("xx");
        
        while(true){

        	if(i>=raw.length)
        	break;
        	
        	if(raw[i]=='s' && raw[i+1]=='t' && raw[i+2]=='r' && raw[i+3]=='e' && raw[i+4]=='a' && raw[i+5]=='m')
                    break;

            /**
             * exit conditions
             */
            if ((i>=raw.length ||
                    (endPoint!=-1 && i>=endPoint))||
                    ((raw[i] == 101)&& (raw[i + 1] == 110)&& (raw[i + 2] == 100)&& (raw[i + 3] == 111)))
                break;

            //if(debugFastCode)
            //		System.out.println("i= "+i+" "+raw[i]+" "+(char)raw[i]);

            /**
             * process value
             */
            if(raw[i]==60 && raw[i+1]==60){
            	i++;
            	level++;
            	
            	if(debugFastCode)
            		System.out.println("Level UP to "+level);
            }else if(raw[i]==62 && i+1!=raw.length && raw[i+1]==62){
            	i++;
            	level--;
            	
            	if(debugFastCode)
            		System.out.println("Level down to "+level);
            	
            	if(level==0)
            		break;
            }else if (raw[i] == 47 && (raw[i+1] == 47 || raw[i+1]==32)) { //allow for oddity of //DeviceGray  and / /DeviceGray in colorspace
                i++;
            }else  if (raw[i] == 47) { //everything from /

                i++; //skip /
                int keyLength=0,keyStart=i;

                while (true) { //get key up to space or [ or / or ( or < or carriage return

                    if (raw[i] == 32 || raw[i] == 13 || raw[i] == 9 || raw[i] == 10 || raw[i] == 91 ||
                            raw[i]==47 || raw[i]==40 || raw[i]==60 || raw[i]==62)
                        break;

                    i++;
                    keyLength++;
                }

                /**
                 * get Dictionary key and type of value it takes
                 */
                if(debugFastCode)//used in debug
                    PDFkey=PdfDictionary.getKey(keyStart,keyLength,raw);

                PDFkeyInt=PdfDictionary.getIntKey(keyStart,keyLength,raw);

                //work around for ColorSpace which is an Object UNLESS its in a Page Object
                //when its a list of paired keys
                if(pdfObject.getObjectType()==PdfDictionary.Resources && (PDFkeyInt==PdfDictionary.ColorSpace 
                		|| PDFkeyInt==PdfDictionary.ExtGState || PDFkeyInt==PdfDictionary.Shading
                       || PDFkeyInt==PdfDictionary.XObject || PDFkeyInt==PdfDictionary.Font
                        || PDFkeyInt==PdfDictionary.Pattern)){
                	pdfKeyType=PdfDictionary.VALUE_IS_DICTIONARY_PAIRS;
                }else if (isInlineImage && PDFkeyInt== PdfDictionary.G){
                	PDFkeyInt= ColorSpaces.DeviceGray; 
                }else if (isInlineImage && PDFkeyInt== PdfDictionary.RGB){
                	PDFkeyInt= ColorSpaces.DeviceRGB; 	
                }else if (isInlineImage && PDFkeyInt== PdfDictionary.W){
                	PDFkeyInt= PdfDictionary.Width;
                    pdfKeyType= PdfDictionary.VALUE_IS_INT;
                }else if (isInlineImage && PDFkeyInt== PdfDictionary.D){
                    PDFkeyInt= PdfDictionary.Decode;
                    pdfKeyType= PdfDictionary.VALUE_IS_FLOAT_ARRAY;
                }else if ((pdfObject.getObjectType()==PdfDictionary.ColorSpace || pdfObject.getObjectType()==PdfDictionary.Function) && PDFkeyInt== PdfDictionary.N){
                    pdfKeyType= PdfDictionary.VALUE_IS_FLOAT;    
                }else if (isInlineImage && PDFkeyInt== PdfDictionary.F){
                    PDFkeyInt= PdfDictionary.Filter;
                    pdfKeyType= PdfDictionary.VALUE_IS_MIXED_ARRAY;
                }else if (isInlineImage && PDFkeyInt== PdfDictionary.H){
                    PDFkeyInt= PdfDictionary.Height;
                    pdfKeyType= PdfDictionary.VALUE_IS_INT;
                }else if(PDFkeyInt==PdfDictionary.Gamma && pdfObject.getObjectType()==PdfDictionary.ColorSpace &&
                	pdfObject.getParameterConstant(PdfDictionary.ColorSpace)==ColorSpaces.CalGray){ //its a number not an array
                	pdfKeyType= PdfDictionary.VALUE_IS_FLOAT;
                }else if(PDFkeyInt==PdfDictionary.Size && pdfObject.getObjectType()==PdfDictionary.CompressedObject){ // int not int array
                    pdfKeyType= PdfDictionary.VALUE_IS_INT;
                }else if(PDFkeyInt==PdfDictionary.W && pdfObject.getObjectType()==PdfDictionary.CompressedObject){ // int not int array
                	pdfKeyType= PdfDictionary.VALUE_IS_INT_ARRAY;
                }else
                	pdfKeyType=PdfDictionary.getKeyType(PDFkeyInt);

                if(pdfKeyType==-1 && debugFastCode && pdfObject.getObjectType()!=PdfDictionary.Page){
                    System.out.println(paddingString+PDFkey+" NO type setting for "+PdfDictionary.getKey(keyStart,keyLength,raw));
                   // System.exit(1);
                }

                if(raw[i]==47 || raw[i]==40 || raw[i] == 91) //move back cursor
                    i--;

                //check for unknown Dictionary
                if(pdfKeyType==-1){
                    int count=raw.length-1;
                    for(int jj=i;jj<count;jj++){

                        if(raw[jj]=='<' && raw[jj+1]=='<'){

                            int levels=0;
                            while(true){

                                if(raw[jj]=='<' && raw[jj+1]=='<')
                                    levels++;
                                else if(raw[jj]=='>' && raw[jj+1]=='>')
                                    levels--;

                                jj++;
                                if(levels==0 || jj>=count)
                                    break;
                            }

                            i=jj;

                            jj=count;

                        }else if(raw[jj]=='/')
                            jj=count;
                    }
                }

                /**
                 * now read value
                 */
                if(PDFkeyInt==-1 || pdfKeyType==-1){
                    if(debugFastCode)
                        System.out.println(paddingString+objectRef+" =================Not implemented="+PDFkey+" pdfKeyType="+pdfKeyType);

                }else{

                	//if we only need top level do not read whole tree
                    boolean ignoreRecursion=pdfObject.ignoreRecursion();

                    if(debugFastCode)
                        System.out.println(paddingString+objectRef+" =================Reading value for key="+PDFkey+" ("+PDFkeyInt+") "+pdfKeyType+" ignorRecursion="+ignoreRecursion);
                    
                    switch(pdfKeyType){

                        //read text stream (this is text)
                        //and also special case of [] in W in CID FOnts
                        case PdfDictionary.VALUE_IS_TEXTSTREAM:{

                            if(PDFkeyInt==PdfDictionary.W){

                                boolean isRef=false;

                                if(debugFastCode)
                                    System.out.println(paddingString+"Reading W");

                                //move to start
                                while(raw[i]!='[' ){ //can be number as well

                                    //System.out.println((char) raw[i]);
                                    if(raw[i]=='('){ //allow for W (7)
                                        isRef=false;
                                        break;
                                    }

                                    //allow for number as in refer 9 0 R
                                    if(raw[i]>='0' && raw[i]<='9'){
                                        isRef=true;
                                        break;
                                    }

                                    i++;
                                }

                                //allow for direct or indirect
                                byte[] data=raw;

                                int start=i,j=i;

                                int count=0;

                                //read ref data and slot in
                                if(isRef){
                                    //number
                                    int keyStart2=i,keyLength2=0;
                                    while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47 && raw[i]!=60 && raw[i]!=62){

                                        i++;
                                        keyLength2++;

                                    }
                                    int number=parseInt(keyStart2,i, raw);

                                    //generation
                                    while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47 || raw[i]==60)
                                        i++;

                                    keyStart2=i;
                                    //move cursor to end of reference
                                    while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47 && raw[i]!=60 && raw[i]!=62)
                                        i++;
                                    int generation=parseInt(keyStart2,i, raw);

                                    //move cursor to start of R
                                    while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47 || raw[i]==60)
                                        i++;

                                    if(raw[i]!=82) //we are expecting R to end ref
                                        throw new RuntimeException("3. Unexpected value in file "+raw[i]+" - please send to IDRsolutions for analysis");

                                    if(!ignoreRecursion){
                                    	
	                                    //read the Dictionary data
	                                    data=readObjectAsByteArray(objectRef,false,isCompressed(number,generation),number,generation);
	
	                                    //lose obj at start
	                                    j=3;
	                                    while(data[j-1]!=106 && data[j-2]!=98 && data[j-3]!=111)
	                                        j++;
	
	                                    //skip any spaces after
	                                    while(data[j]==10 || data[j]==13 || data[j]==32)// || data[j]==47 || data[j]==60)
	                                        j++;
	
	                                    //reset pointer
	                                    start=j;
                                    
                                    }
                                }

                                //move to end
                                while(j<data.length){

                                    if(data[j]=='[' || data[j]=='(')
                                        count++;
                                    else if(data[j]==']' || data[j]==')')
                                        count--;

                                    if(count==0)
                                        break;

                                    j++;
                                }

                                if(!ignoreRecursion){
	                                int stringLength=j-start+1;
	                                byte[] newString=new byte[stringLength];
	
	                                System.arraycopy(data, start, newString, 0, stringLength);
	
	                                /**
	                                 * clean up so matches old string so old code works
	                                 */
	                                for(int aa=0;aa<stringLength;aa++){
	                                    if(newString[aa]==10 || newString[aa]==13)
	                                        newString[aa]=32;
	                                }
	
	                                pdfObject.setTextStreamValue(PDFkeyInt, newString);
	
	                                if(debugFastCode)
	                                    System.out.println(paddingString+"W="+new String(newString)+" set in "+pdfObject);
                                }
                                
                                //roll on
                                if(!isRef)
                                    i=j;
                            }else{
                                if(debugFastCode)
                                    System.out.println(paddingString+"Reading Text");

                                try{
                                    //move to start
                                    while(raw[i]!='(' && raw[i]!='<')
                                        i++;

                                    byte startChar=raw[i];

//								i++; //roll past (

                                    int start=i;

                                    //move to end
                                    while(i<raw.length){
                                        //System.out.println(i+"="+raw[i]+" "+(char)raw[i]);
                                        i++;

                                        if(startChar=='(' && (raw[i]==')'&& (i>0 && (raw[i-1]!='\\') || (i>1 && raw[i-2]=='\\'))))
                                            break;
                                        if(startChar=='<' && raw[i]=='>')
                                            break;
                                    }

                                    if(!ignoreRecursion){
	                                    int stringLength=i-start;
	                                    byte[] newString=new byte[stringLength];
	
	                                    System.arraycopy(raw, start, newString, 0, stringLength);
	
	                                    pdfObject.setTextStreamValue(PDFkeyInt, newString);
	
	                                    if(debugFastCode)
	                                        System.out.println(paddingString+"TextStream="+new String(newString)+"");
                                    //}
                                    }
                                }catch(Exception ee){
                                    ee.printStackTrace();
                                    System.exit(1);
                                }
                            }
                            break;

                            //readDictionary keys << /A 12 0 R /B 13 0 R >>
                        }case PdfDictionary.VALUE_IS_DICTIONARY_PAIRS:{

                        if(debugFastCode)                       	
                            System.out.println(paddingString+">>>Reading Dictionary Pairs i="+i+" "+(char)raw[i]+""+(char)raw[i+1]+""+(char)raw[i+2]+""+(char)raw[i+3]+""+(char)raw[i+4]+""+(char)raw[i+5]+""+(char)raw[i+6]);
                        
                        //move cursor to start of text
                        while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47)
                            i++;

                        //set data which will be switched below if ref
                        byte[] data=raw;
                        int j=i;

//                        System.out.println("====>");
//                        for(int aa=i;aa<raw.length;aa++)
//                        	System.out.print((char)raw[aa]);
//                        System.out.println("<====");
                        
                        //get next key to see if indirect
                        boolean isRef=data[j]!='<';

                        if(isRef){

                            //number
                            int keyStart2=i,keyLength2=0;
                            while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47 && raw[i]!=60 && raw[i]!=62){
                               
                            	i++;
                                keyLength2++;
                            }
                            
                            int number=parseInt(keyStart2,i, raw);

                            //generation
                            while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47 || raw[i]==60)
                                i++;

                            keyStart2=i;
                            //move cursor to end of reference
                            while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47 && raw[i]!=60 && raw[i]!=62)
                                i++;
                            int generation=parseInt(keyStart2,i, raw);

                            //move cursor to start of R
                            while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47 || raw[i]==60)
                                i++;

                            if(raw[i]!=82) //we are expecting R to end ref
                                throw new RuntimeException("3. Unexpected value in file "+raw[i]+" - please send to IDRsolutions for analysis");

                            if(!ignoreRecursion){
                            	
	                            //read the Dictionary data
	                            data=readObjectAsByteArray(objectRef,false,isCompressed(number,generation),number,generation);
	
	//							System.out.println("data read is>>>>>>>>>>>>>>>>>>>\n");
	//							for(int ab=0;ab<data.length;ab++)
	//							System.out.print((char)data[ab]);
	//							System.out.println("\n<<<<<<<<<<<<<<<<<<<\n");
	
	                            //lose obj at start
	                            j=3;
	                            while(data[j-1]!=106 && data[j-2]!=98 && data[j-3]!=111)
	                                j++;
	
	                            //skip any spaces after
	                            while(data[j]==10 || data[j]==13 || data[j]==32)// || data[j]==47 || data[j]==60)
	                                j++;

                            }
                        }

                        PdfObject valueObj=PdfObjectFactory.createObject(PDFkeyInt,objectRef);

                        /**
                         * read pairs (stream in data starting at j)
                         */
//
//                    System.out.println("-----------------------------\n");
//                        for(int aa=j;aa<data.length;aa++)
//                        System.out.print((char)data[aa]);
//                    System.out.println("\n-----------------------------valueObj="+valueObj+" PDFkeyInt="+PDFkeyInt);

                        if(ignoreRecursion) //just skip to end
                        	j=readKeyPairs(PDFkeyInt,data, j,-2, null,paddingString);
                        else{
	                        //count values first
	                        int count=readKeyPairs(PDFkeyInt,data, j,-1, null,paddingString);
	                     
	                        //now set values
	                        j=readKeyPairs(PDFkeyInt,data, j,count,valueObj,paddingString);
	
	                        //store value
	                        pdfObject.setDictionary(PDFkeyInt,valueObj);
	
	                        if(debugFastCode)
	                            System.out.println(paddingString+"Set Dictionary "+count+" pairs type "+PDFkey+"  in "+pdfObject+" to "+valueObj);
                        }
                        
                        //update pointer if direct so at end (if ref already in right place)
                        if(!isRef){
                            i=j;

                            if(debugFastCode)
                            System.out.println(i+">>>>"+data[i-2]+" "+data[i-1]+" >"+data[i]+"< "+data[i+1]+" "+data[i+2]);
                        //break at end
                        //if(raw[i]=='>' && raw[i+1]=='>' && raw[i-1]=='>' && raw[i-2]=='>')
                        //return i;

                        }

                        break;

                        //read Object Refs in [] (may be indirect ref)
                   }case PdfDictionary.VALUE_IS_BOOLEAN_ARRAY:{

                       //if(debugFastCode)
                       //    System.out.println(paddingString+"Reading Key Array in "+pdfObject);

                       i=readArray(false, i, endPoint, PdfDictionary.VALUE_IS_BOOLEAN_ARRAY, raw, objectRef, pdfObject,
                               PDFkeyInt, debugFastCode,paddingString);

                       break;

                        //read Object Refs in [] (may be indirect ref)
                    }case PdfDictionary.VALUE_IS_KEY_ARRAY:{

                        //if(debugFastCode)
                        //    System.out.println(paddingString+"Reading Key Array in "+pdfObject);

                        i=readArray(ignoreRecursion, i, endPoint, PdfDictionary.VALUE_IS_KEY_ARRAY, raw, objectRef, pdfObject,
                        		PDFkeyInt, debugFastCode,paddingString);

                        break;

                        //read numbers in [] (may be indirect ref)
                    }case PdfDictionary.VALUE_IS_MIXED_ARRAY:{

                        //if(debugFastCode)
                        //    System.out.println(paddingString+"Reading Mixed Array in "+pdfObject);

                        i=readArray(ignoreRecursion, i, endPoint, PdfDictionary.VALUE_IS_MIXED_ARRAY, raw, objectRef, pdfObject,
                        		PDFkeyInt, debugFastCode,paddingString);

                        break;

                        //read numbers in [] (may be indirect ref)
                    }case PdfDictionary.VALUE_IS_DOUBLE_ARRAY:{

                        //if(debugFastCode)
                        //	System.out.println(paddingString+"Reading Double Array in "+pdfObject);

                        i=readArray(false, i, endPoint, PdfDictionary.VALUE_IS_DOUBLE_ARRAY, raw, objectRef, pdfObject,
                        		PDFkeyInt, debugFastCode,paddingString);

                        break;

                      //read numbers in [] (may be indirect ref)
                    }case PdfDictionary.VALUE_IS_INT_ARRAY:{

                        //if(debugFastCode)
                        //	System.out.println("Reading Float Array");

                        i=readArray(false, i,endPoint, PdfDictionary.VALUE_IS_INT_ARRAY, raw, objectRef, pdfObject,
                        		PDFkeyInt, debugFastCode, paddingString);

                        break;

                        //read numbers in [] (may be indirect ref)
                    }case PdfDictionary.VALUE_IS_FLOAT_ARRAY:{

                        //if(debugFastCode)
                        //	System.out.println("Reading Float Array");

                        i=readArray(false, i, endPoint, PdfDictionary.VALUE_IS_FLOAT_ARRAY, raw, objectRef, pdfObject,
                        		PDFkeyInt, debugFastCode, paddingString);

                        break;

                        //read String (may be indirect ref)
                    }case PdfDictionary.VALUE_IS_UNCODED_STRING:{

                        //if(debugFastCode)
                        //	System.out.println("Reading String");

                        i = readUncodedString(pdfObject, objectRef, i, raw,debugFastCode, PDFkeyInt,paddingString);

                        break;

                        //read true or false
                    }case PdfDictionary.VALUE_IS_BOOLEAN:{

                        i++;
                        //if(debugFastCode)
                        //	System.out.println("Reading constant "+(char)raw[i]+(char)raw[i+1]+(char)raw[i+2]+(char)raw[i+3]);

                        //move cursor to start of text
                        while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47){
                            //System.out.println("skip="+raw[i]);
                            i++;
                        }

                        keyStart=i;
                        keyLength=0;

                        //System.out.println("firstChar="+raw[i]+" "+(char)raw[i]);

                        //move cursor to end of text
                        while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47 && raw[i]!=60 && raw[i]!=62){
                            //System.out.println("key="+raw[i]+" "+(char)raw[i]);
                            i++;
                            keyLength++;
                        }

                        i--;// move back so loop works

                        //store value
                        if(raw[keyStart]=='t' && raw[keyStart+1]=='r' && raw[keyStart+2]=='u' && raw[keyStart+3]=='e') {
                            pdfObject.setBoolean(PDFkeyInt,true);

                            if(debugFastCode)
                                System.out.println(paddingString+"Set Boolean true "+PDFkey+" in "+pdfObject);

                        }else if(raw[keyStart]=='f' && raw[keyStart+1]=='a' && raw[keyStart+2]=='l' && raw[keyStart+3]=='s' && raw[keyStart+4]=='e'){
                            pdfObject.setBoolean(PDFkeyInt,false);

                            if(debugFastCode)
                                System.out.println(paddingString+"Set Boolean false "+PDFkey+" in "+pdfObject);

                        }else
                            throw new RuntimeException("Unexpected value for Boolean value ");


                        break;
                        //read known set of values
                    }case PdfDictionary.VALUE_IS_STRING_CONSTANT:{

                        i++;
                        //if(debugFastCode)
                        //	System.out.println("Reading constant "+(char)raw[i]+(char)raw[i+1]+(char)raw[i+2]+(char)raw[i+3]);

                        //move cursor to start of text
                        while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47){
                            //System.out.println("skip="+raw[i]);
                            i++;
                        }

                        keyStart=i;
                        keyLength=0;

                        //System.out.println("firstChar="+raw[i]+" "+(char)raw[i]);

                        //move cursor to end of text
                        while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47 && raw[i]!=60 && raw[i]!=62){
                            //System.out.println("key="+raw[i]+" "+(char)raw[i]);
                            i++;
                            keyLength++;
                        }

                        i--;// move back so loop works

                        //store value
                        int constant=pdfObject.setConstant(PDFkeyInt,keyStart,keyLength,raw);

                        if(debugFastCode)
                            System.out.println(paddingString+"Set constant "+PDFkey+" in "+pdfObject+" to "+constant);

                        break;

                        //read known set of values
                    }case PdfDictionary.VALUE_IS_STRING_KEY:{

                        i++;
                        //if(debugFastCode)
                        //	System.out.println("Reading constant "+(char)raw[i]+(char)raw[i+1]+(char)raw[i+2]+(char)raw[i+3]);

                        //move cursor to start of text
                        while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47){
                            //System.out.println("skip="+raw[i]);
                            i++;
                        }

                        keyStart=i;
                        keyLength=1;

                        //System.out.println("firstChar="+raw[i]+" "+(char)raw[i]);

                        //move cursor to end of text
                        while(raw[i]!='R'){
                            //System.out.println("key="+raw[i]+" "+(char)raw[i]);
                            i++;
                            keyLength++;
                        }

                        i--;// move back so loop works

                        //set value
                        byte[] stringBytes=new byte[keyLength];
                        System.arraycopy(raw,keyStart,stringBytes,0,keyLength);

                        //store value
                        pdfObject.setStringKey(PDFkeyInt,stringBytes);

                        if(debugFastCode)
                            System.out.println(paddingString+"Set constant "+PDFkey+" in "+pdfObject+" to "+new String(stringBytes));

                        break;

                        //read number (may be indirect ref)
                    }case PdfDictionary.VALUE_IS_INT:{

                        //if(debugFastCode)
                        	//System.out.println("Reading Int number");

                    	//roll on
                		i++;

                		//move cursor to start of text
                		while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47)
                		    i++;

                        i = readNumber(pdfObject, objectRef, i, raw,
								paddingString, debugFastCode, PDFkeyInt, PDFkey);

                        break;

                        //read float number (may be indirect ref)
                    }case PdfDictionary.VALUE_IS_FLOAT:{

                        if(debugFastCode)
                        	System.out.println("Reading Float number");

                        //roll on
                        i++;

                        //move cursor to start of text
                        while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47)
                            i++;

                        keyStart=i;
                        keyLength=0;

                        //move cursor to end of text
                        while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47 && raw[i]!=60 && raw[i]!=62){
                        	i++;
                            keyLength++;
                        }

                        //actual value or first part of ref
                        float number=parseFloat(keyStart,i, raw);

                        //roll onto next nonspace char and see if number
                        int jj=i;
                        while(jj<raw.length &&(raw[jj]==32 || raw[jj]==13 || raw[jj]==10))
                            jj++;

                        //check its not a ref (assumes it XX 0 R)
                        if(raw[jj]>= 48 && raw[jj]<=57){ //if next char is number 0-9 its a ref

                            //move cursor to start of generation
                            while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47 || raw[i]==60)
                                i++;

                            /**
                             * get generation number
                             */
                            keyStart=i;
                            //move cursor to end of reference
                            while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47 && raw[i]!=60 && raw[i]!=62)
                                i++;

                            int generation=parseInt(keyStart,i, raw);

                            //move cursor to start of R
                            while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47 || raw[i]==60)
                                i++;

                            if(raw[i]!=82){ //we are expecting R to end ref
                                throw new RuntimeException("3. Unexpected value in file - please send to IDRsolutions for analysis");
                            }

                            //read the Dictionary data
                            byte[] data=readObjectAsByteArray(objectRef,false,isCompressed((int)number,generation),(int)number,generation);

                            //lose obj at start
                            int j=3;
                            while(data[j-1]!=106 && data[j-2]!=98 && data[j-3]!=111)
                                j++;

                            //skip any spaces after
                            while(data[j]==10 || data[j]==13 || data[j]==32)// || data[j]==47 || data[j]==60)
                                j++;

                            int count=j;

                            //skip any spaces at end
                            while(data[count]!=10 && data[count]!=13 && data[count]!=32){// || data[j]==47 || data[j]==60)
                                count++;
                            }

                            number=parseFloat(j,count, data);

                        }

                        //store value
                        pdfObject.setFloatNumber(PDFkeyInt,number);

                        if(debugFastCode){
                            System.out.println(paddingString+"set key="+PDFkey+" numberValue="+number+" in "+pdfObject);

                        //System.out.println("i="+i+" "+(char)raw[i]+""+(char)raw[i+1]+""+(char)raw[i+2]);
                        }
                        //if(raw[i+1]==47)
                        i--;// move back so loop works
                        //if(raw[i]==47)
                        //	i--;
                        //	i=i-2;
                        //else
                        //	i--;

                        //i=i+keyLength-1;

                        break;

                        //read known Dictionary object which may be direct or indirect
                    }case PdfDictionary.VALUE_IS_DICTIONARY:{

                        if(debugFastCode)
                            System.out.println(paddingString+"Dictionary value (first char="+raw[i]+" )");

                        //roll on
                        if(raw[i]!='<')
                                i++;
                        
                        //move cursor to start of text
                        while(raw[i]==10 || raw[i]==13 || raw[i]==32)
                            i++;

                        //some objects can have a common value (ie /ToUnicode /Identity-H
                        if(raw[i]==47){

                            //	System.out.println("Starts with /");

                            //if it is a < (60) its a direct object, otherwise its a reference so we need to move and move back at end

                            //}else if(raw[i]==60 && 1==2){

                            //move cursor to start of text
                            while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47 || raw[i]==60)
                                i++;

                            keyStart=i;
                            keyLength=0;

                            //move cursor to end of text
                            while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47 && raw[i]!=60 && raw[i]!=62){
                                i++;
                                keyLength++;
                            }

                            i--;// move back so loop works

                            if(!ignoreRecursion){
                            	
	                            PdfObject valueObj=PdfObjectFactory.createObject(PDFkeyInt,objectRef);
	
	                            //store value
	                            int constant=valueObj.setConstant(PDFkeyInt,keyStart,keyLength,raw);
		
	                            if(constant==PdfDictionary.Unknown || isInlineImage){
	
	                                int StrLength=keyLength;
	                                byte[] newStr=new byte[StrLength];
	                                System.arraycopy(raw, keyStart, newStr, 0, StrLength);
	
	                                String s=new String(newStr);
	                                valueObj.setGeneralStringValue(s);
	
	                                if(debugFastCode)
	                                    System.out.println(paddingString+"Set Dictionary type "+PDFkey+" as String="+s+"  in "+pdfObject+" to "+valueObj);
	
	                            }else if(debugFastCode)
	                                System.out.println(paddingString+"Set Dictionary type "+PDFkey+" as constant="+constant+"  in "+pdfObject+" to "+valueObj);
	
	
	                            //store value
	                            pdfObject.setDictionary(PDFkeyInt,valueObj);
                            
                            }
                            
                        }else //allow for empty object
                         if(raw[i]=='e' && raw[i+1]=='n' && raw[i+2]=='d' && raw[i+3]=='o' && raw[i+4]=='b' ){
                        //        return i;

                             if(debugFastCode)
                             System.out.println(paddingString+"Empty object"+new String(raw)+"<<");


                        }else{ //we need to ref from ref elsewhere which may be indirect [ref], hence loop

                            if(debugFastCode)
                            System.out.println(paddingString+"About to read ref orDirect i="+i);


                            if(ignoreRecursion){

                                //roll onto first valid char
                            	while(raw[i]==91 || raw[i]==32 || raw[i]==13 || raw[i]==10){

                                    //if(raw[i]==91) //track incase /Mask [19 19]
                        			//	possibleArrayStart=i;

                        			i++;
                        		}


                                //roll on and ignore
                        		if(raw[i]=='<' && raw[i+1]=='<'){

                                    i=i+2;
                					int reflevel=1;
                					
                					while(reflevel>0){
                                        if(raw[i]=='<' && raw[i+1]=='<'){
                                            i=i+2;
                                            reflevel++;
                                        }else if(raw[i]=='>' && raw[i+1]=='>'){
                                            i=i+2;
                                            reflevel--;
                                        }else
                                            i++;
                                    }
                					i--;
                					
                				}else{ //must be a ref
//                					while(raw[i]!='R')
//                						i++;
//                					i++;
                                   // System.out.println("read ref");
                                    i = readDictionaryFromRefOrDirect(PDFkeyInt,pdfObject,objectRef, i, raw,debugFastCode, PDFkeyInt,PDFkey, paddingString);
                                }

                                if(raw[i]=='/') //move back so loop works
                                    i--;
                                
                            }else{
                            	i = readDictionaryFromRefOrDirect(PDFkeyInt,pdfObject,objectRef, i, raw,debugFastCode, PDFkeyInt,PDFkey, paddingString);
                            }
                         }

                        break;
                    }
                    }
                }
            }
            
            i++;

        }

        //System.out.println(paddingString+"i="+i+" Now at="+(char)raw[i]+(char)raw[i+1]+(char)raw[i+2]+(char)raw[i+3]+(char)raw[i+4]);

        /**
         * look for stream afterwards
         */
        
        int count=raw.length;
        for(int xx=i;xx<count-5;xx++){

            //System.out.println(paddingString+raw[xx]+" "+(char)raw[xx]);

            //avoid reading on subobject ie <<  /DecodeParams << >> >>
        	if(raw[xx]=='>' && raw[xx+1]=='>')
              break;
            
            if(raw[xx] == 's' && raw[xx + 1] == 't' && raw[xx + 2] == 'r' &&
                    raw[xx + 3] == 'e' && raw[xx + 4] == 'a' &&
                    raw[xx + 5] == 'm'){

                if(debugFastCode)
                    System.out.println(paddingString+"Stream found afterwards");

                readStreamIntoObject(pdfObject, debugFastCode,xx, raw, pdfObject,paddingString);
                xx=count;
            }
        }

        return i;

    }
    
    private int readNumber(PdfObject pdfObject, String objectRef, int i,
			byte[] raw, String paddingString, final boolean debugFastCode,
			int PDFkeyInt, Object PDFkey) {
		int keyLength;
		int keyStart;
		
		keyStart=i;
		keyLength=0;

		//move cursor to end of text
		while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47 && raw[i]!=60 && raw[i]!=62 && raw[i]!='('){
		    i++;
		    keyLength++;
		}

		//actual value or first part of ref
		int number=parseInt(keyStart,i, raw);

		
		//roll onto next nonspace char and see if number
		int jj=i;
		while(jj<raw.length &&(raw[jj]==32 || raw[jj]==13 || raw[jj]==10))
		    jj++;

		boolean  isRef=false;
		
		//check its not a ref (assumes it XX 0 R)
		if(raw[jj]>= 48 && raw[jj]<=57){ //if next char is number 0-9 it may be a ref
			
			int aa=jj;
			
			//move cursor to end of number
			while((raw[aa]!=10 && raw[aa]!=13 && raw[aa]!=32 && raw[aa]!=47 && raw[aa]!=60 && raw[aa]!=62))
			    aa++;	
			
			//move cursor to start of text
			while(aa<raw.length && (raw[aa]==10 || raw[aa]==13 || raw[aa]==32 || raw[aa]==47))
			    aa++;
			
			isRef=aa<raw.length && raw[aa]=='R';
			
		}
		
		if(isRef){
		    //move cursor to start of generation
		    while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47 || raw[i]==60)
		        i++;

		    /**
		     * get generation number
		     */
		    keyStart=i;
		    //move cursor to end of reference
		    while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47 && raw[i]!=60 && raw[i]!=62)
		        i++;

		    int generation=parseInt(keyStart,i, raw);

		    //move cursor to start of R
		    while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47 || raw[i]==60)
		        i++;

		    if(raw[i]!=82){ //we are expecting R to end ref
		        throw new RuntimeException("3. Unexpected value in file - please send to IDRsolutions for analysis");
		    }

		    //read the Dictionary data
		    byte[] data=readObjectAsByteArray(objectRef,false,isCompressed(number,generation),number,generation);

		    //lose obj at start
		    int j=3;
		    while(data[j-1]!=106 && data[j-2]!=98 && data[j-3]!=111)
		        j++;

		    //skip any spaces after
		    while(data[j]==10 || data[j]==13 || data[j]==32)// || data[j]==47 || data[j]==60)
		        j++;

		    int count=j;

		    //skip any spaces at end
		    while(data[count]!=10 && data[count]!=13 && data[count]!=32)// || data[j]==47 || data[j]==60)
		        count++;

		    number=parseInt(j,count, data);

		}

		//store value
		pdfObject.setIntNumber(PDFkeyInt,number);

		if(debugFastCode)
		    System.out.println(paddingString+"set key="+PDFkey+" numberValue="+number+" in "+pdfObject);

		//System.out.println((char)raw[i]+""+(char)raw[i+1]+""+(char)raw[i+2]);

		//if(raw[i+1]==47)
		i--;// move back so loop works
		//if(raw[i]==47)
		//	i--;
		//	i=i-2;
		//else
		//	i--;

		//i=i+keyLength-1;
		return i;
	}
	
		
    public int handleColorSpaces(PdfObject pdfObject,int i, byte[] raw, boolean debugFastCode, String paddingString) {
    	
    	final boolean debugColorspace=false;//pdfObject.getDebugMode();// || debugFastCode;

    	if(debugColorspace){
        	System.out.println(paddingString+"Reading colorspace into "+pdfObject);
    	
        	System.out.println(paddingString+"------------>");
			for(int ii=i;ii<raw.length;ii++){
				System.out.print((char)raw[ii]);
			
			if(ii>5 && raw[ii-5]=='s' && raw[ii-4]=='t' && raw[ii-3]=='r' && raw[ii-2]=='e' && raw[ii-1]=='a' &&raw[ii]=='m')
                ii=raw.length;
			}
			
			System.out.println("<--------");
			
    	}
    	
    	//ignore any spaces
    	while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]=='[')
    		i++;
		
		if(raw[i]=='/'){
		
			/**
			 * read the first value which is ID
			**/
			
			i++;
            
            //move cursor to start of text
            while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47){
                //System.out.println("skip="+raw[i]);
                i++;
            }

            int keyStart=i;
            int keyLength=0;

            //System.out.println("firstChar="+raw[i]+" "+(char)raw[i]);

            if(debugColorspace)
	        	System.out.print(paddingString+"Colorspace is /");
	    	
            //move cursor to end of text
            while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47 && raw[i]!=60 && raw[i]!=62 && raw[i]!='[' && raw[i]!=']'){
            	
            	if(debugColorspace)
                System.out.print((char)raw[i]);
            		
                i++;
                keyLength++;
                
                if(i==raw.length)
                	break;
            }
            
            if(debugColorspace)
                System.out.println("");
            	

            i--;// move back so loop works

            //store value
            int constant=pdfObject.setConstant(PdfDictionary.ColorSpace,keyStart,keyLength,raw);

            if(debugColorspace)
            	System.out.println(paddingString+"set ID="+constant+" in "+pdfObject);
            
            i++;//roll on
            
            switch(constant){
            
            case ColorSpaces.CalRGB:{	
				
				if(debugColorspace)
					System.out.println(paddingString+"CalRGB Colorspace");
			
				i=handleColorSpaces(pdfObject, i,  raw, debugFastCode, paddingString+"    ");
            	
            	i++;
            	
				break;
            }case ColorSpaces.CalGray:{	
				
				if(debugColorspace)
					System.out.println(paddingString+"CalGray Colorspace");
			
				i=handleColorSpaces(pdfObject, i,  raw, debugFastCode, paddingString+"    ");
            	
            	i++;
            	
				break;	
            }case ColorSpaces.DeviceCMYK:{	
				
				if(debugColorspace)
					System.out.println(paddingString+"DeviceGray Colorspace");
			
				break;
            }case ColorSpaces.DeviceGray:{	
				
				if(debugColorspace)
					System.out.println(paddingString+"DeviceGray Colorspace");
			
				break;
            }case ColorSpaces.DeviceN:{
			            	
            	if(debugColorspace)
            		System.out.println(paddingString+"DeviceN Colorspace >>"+(char)raw[i]+(char)raw[i+1]+(char)raw[i+2]+(char)raw[i+3]);
            
            	int endPoint=i;
            	while(endPoint<raw.length && raw[endPoint]!=']'){
            		//System.out.println((char)raw[endPoint]+" "+raw[endPoint]);
            		endPoint++;
            	}
            	
            	//read Components
            	i=readArray(false,i, endPoint, PdfDictionary.VALUE_IS_STRING_ARRAY, raw, "", pdfObject,
                        PdfDictionary.Components, debugFastCode,paddingString);

            	while(raw[i]==93 || raw[i]==32 || raw[i]==10 || raw[i]==13)
            	i++;
            	
            	if(debugColorspace)
            		System.out.println(paddingString+"DeviceN Reading altColorspace >>"+(char)raw[i]+(char)raw[i+1]+(char)raw[i+2]+(char)raw[i+3]);
            
            	//read the alt colorspace
            	PdfObject altColorSpace=new PdfColorSpaceObject(1);
            	i=handleColorSpaces(altColorSpace, i,  raw, debugFastCode, paddingString+"    ");
            	pdfObject.setDictionary(PdfDictionary.AlternateSpace,altColorSpace);
            
            	i++;
            	
            	if(debugColorspace)
            		System.out.println(paddingString+"DeviceN Reading tintTransform >>"+(char)raw[i]+(char)raw[i+1]+(char)raw[i+2]+(char)raw[i+3]);
            
            	//read the transform
            	PdfObject tintTransform=new PdfFunctionObject(1);
            	i=handleColorSpaces(tintTransform, i,  raw, debugFastCode, paddingString+"    ");
            	pdfObject.setDictionary(PdfDictionary.tintTransform,tintTransform);
            	
            	i++;
            	
            	break;
			}case ColorSpaces.DeviceRGB:{	
				
				if(debugColorspace)
					System.out.println(paddingString+"DeviceRGB Colorspace");
			
				break;
            }case ColorSpaces.ICC:{
				
				if(debugColorspace)
					System.out.println(paddingString+"ICC Colorspace");
			
				//get the colorspace data
				i=readDictionaryFromRefOrDirect(-1, pdfObject,"", i, raw,debugFastCode, PdfDictionary.ColorSpace,"ICC colorspace", paddingString);
			
				break;
            	
			}case ColorSpaces.Indexed:{
            	
            	if(debugColorspace)
            		System.out.println(paddingString+"Indexed Colorspace - Reading base >>"+(char)raw[i]+""+(char)raw[i+1]+""+(char)+raw[i+2]+"<< i="+i);
     
            	//read the base value
            	PdfObject IndexedColorSpace=new PdfColorSpaceObject(1);
            	i=handleColorSpaces(IndexedColorSpace, i,  raw, debugFastCode, paddingString+"    ");
            	pdfObject.setDictionary(PdfDictionary.Indexed,IndexedColorSpace);
            	
            	if(debugColorspace)
            		System.out.println(paddingString+"Indexed Reading hival starting at >>"+(char)raw[i]+""+(char)raw[i+1]+""+(char)+raw[i+2]+"<<i="+i);
     
            	//onto hival number
            	while(i<raw.length && (raw[i]==32 || raw[i]==13 || raw[i]==10 || raw[i]==']' || raw[i]=='>'))
            	i++;
            	
            	//roll on
        		//i++;

        		//move cursor to start of text
        		//while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47)
        		//    i++;

            	//hival
            	i = readNumber(pdfObject, "", i, raw, paddingString, false, PdfDictionary.hival, "hival");

            	i++;
            	
            	//onto lookup
            	while(i<raw.length && (raw[i]==32 || raw[i]==13 || raw[i]==10))
            	i++;
            	
            	if(debugColorspace)
            		System.out.println(paddingString+"Indexed Reading lookup "+(char)raw[i]+(char)raw[i+1]+(char)raw[i+2]+(char)raw[i+3]+(char)raw[i+4]);
     
            	//read lookup
            	//get the colorspace data (base)
            	//i=readDictionaryFromRefOrDirect(-1, pdfObject,"", i, raw,debugFastCode, -1,"", paddingString);
            	i=handleColorSpaces(pdfObject, i,  raw, debugFastCode, paddingString);
            	
            	i++;
            	
            	break;
            	
			}case ColorSpaces.Lab:{	
				
				if(debugColorspace)
					System.out.println(paddingString+"Lab Colorspace");
			
				i=handleColorSpaces(pdfObject, i,  raw, debugFastCode, paddingString);
            	
            	i++;
            	
				break;	
            
			}case ColorSpaces.Pattern:{	
				
				if(debugColorspace)
					System.out.println(paddingString+"Pattern Colorspace");
			
				break;
			}case ColorSpaces.Separation:{
            	
            	if(debugColorspace)
            		System.out.println(paddingString+"Separation Colorspace");
            
            	int endPoint=i;
            	
            	//roll on to start
            	while(raw[endPoint]=='/' || raw[endPoint]==32 ||raw[endPoint]==10 ||raw[endPoint]==13)
            		endPoint++;
            	
            	int startPt=endPoint;
            	
            	//get name length
            	while(endPoint<raw.length){
            		if(raw[endPoint]=='/' || raw[endPoint]==' ' || raw[endPoint]==13 || raw[endPoint]==10)
            			break;
            	
            		endPoint++;
            	}
            
            	//read name
            	//set value
            	keyLength=endPoint-startPt;
                byte[] stringBytes=new byte[keyLength];
                System.arraycopy(raw,startPt,stringBytes,0,keyLength);

                //store value
                pdfObject.setStringValue(PdfDictionary.Name,stringBytes);

                if(debugColorspace)
                	System.out.println(paddingString+"name="+new String(stringBytes)+" "+pdfObject);
                
                i=endPoint;
            	
                if(raw[i]!=47)
                	i++;
                	
            	if(debugColorspace)
            		System.out.println(paddingString+"Separation Reading altColorspace >>"+(char)raw[i]+(char)raw[i+1]);
            
            	//read the alt colorspace
            	PdfObject altColorSpace=new PdfColorSpaceObject(pdfObject.getObjectRefAsString());
            	i=handleColorSpaces(altColorSpace, i,  raw, debugFastCode, paddingString);
            	pdfObject.setDictionary(PdfDictionary.AlternateSpace,altColorSpace);
            
            	//allow for no gap
            	if(raw[i]!='<')
            		i++;
            	
            	//read the transform
            	PdfObject tintTransform=new PdfFunctionObject(pdfObject.getObjectRefAsString());
            	
            	if(debugColorspace)
            		System.out.println(paddingString+"Separation Reading tintTransform "+(char)raw[i-1]+""+(char)raw[i]+""+(char)raw[i+1]+" into "+tintTransform);
            
            	i=handleColorSpaces(tintTransform, i,  raw, debugFastCode, paddingString);
            	pdfObject.setDictionary(PdfDictionary.tintTransform,tintTransform);
            	
            	i++;
            	
            	break;
			}
            	
            }
            
		}else if(raw[i]=='<' && raw[i+1]=='<'){
			
			if(debugColorspace)
        		System.out.println(paddingString+"Direct object starting "+(char)raw[i]+""+(char)raw[i+1]+""+(char)raw[i+2]);
        
			i = convertDirectDictionaryToObject(pdfObject, "", i, raw, debugFastCode, -1, paddingString);

			//allow for stream
			/**
	         * look for stream afterwards
	         */
	        int count=raw.length, ends=0;
	        for(int xx=i;xx<count-5;xx++){

	            //avoid reading on subobject ie <<  /DecodeParams << >> >>
	           if(raw[xx]=='>' && raw[xx+1]=='>')
	              ends++;
	            if(ends==2){
	                if(debugColorspace)
	                    System.out.println(paddingString+"Ignore Stream as not in sub-object "+pdfObject);

	                break;
	            }

	            if(raw[xx] == 's' && raw[xx + 1] == 't' && raw[xx + 2] == 'r' &&
	                    raw[xx + 3] == 'e' && raw[xx + 4] == 'a' && raw[xx + 5] == 'm'){

	                if(debugColorspace)
	                    System.out.println(paddingString+"Stream found afterwards");

	                readStreamIntoObject(pdfObject, debugFastCode,xx, raw, pdfObject,paddingString);
	                xx=count;
	                
	            }
	        }
	        
	        //if(debugColorspace)
	        //System.out.println(paddingString+"At end="+(char)raw[i]+(char)raw[i+1]+(char)raw[i+2]+"<< i="+i);
		}else if(raw[i]=='<'){ // its array of hex values (ie <FFEE>)
			
			//@mariusz
			//allow for UPPER and lower case
			///PDFdata/sample_pdfs/ghostscript/33-1.pdf
			//put into pdfObject.DecodedStream
			
			i++;
			//find end
			int end=i, validCharCount=0;
			
			//System.err.println("RAW stream ...");
			//for(int y=0;y<raw.length;y++){
			//	System.err.print((char)raw[y]);
			//}
			//System.err.println("\n\n");
			
			//here
			while(raw[end]!='>'){
				if(raw[end]!=32 && raw[end]!=10 && raw[end]!=13)
					validCharCount++;
				end++;
			}
			
			int byteCount=validCharCount>>1;
			byte[] stream=new byte[byteCount];
			
			int byteReached=0,topHex=0,bottomHex=0;;
			while(true){
				while(raw[i]==32 || raw[i]==10 || raw[i]==13)
				i++;
				
				topHex=raw[i];
				
				//convert to number
				
				//System.out.println("-> raw[i]=" + (char)topHex);
				
				if(topHex>='A' && topHex<='F'){
					topHex = topHex - 55;	
				}else if(topHex>='a' && topHex<='f'){
					topHex = topHex - 87;
				}else if(topHex>='0' && topHex<='9'){
					topHex = topHex - 48;
				}else
					throw new RuntimeException("Unexpected number "+(char)raw[i]);
				
				
				i++;
				
				while(raw[i]==32 || raw[i]==10 || raw[i]==13)
					i++;
					
				bottomHex=raw[i];
				
				if(bottomHex>='A' && bottomHex<='F'){
					bottomHex = bottomHex - 55;	
				}else if(bottomHex>='a' && bottomHex<='f'){
					bottomHex = bottomHex - 87;
				}else if(bottomHex>='0' && bottomHex<='9'){
					bottomHex = bottomHex - 48;
				}else
					throw new RuntimeException("Unexpected number "+(char)raw[i]);
				
				i++;
				
				
				//calc total
				int finalValue=bottomHex+(topHex<<4);
				
				stream[byteReached] = (byte)finalValue;
				
				byteReached++;

				//System.out.println((char)topHex+""+(char)bottomHex+" "+byteReached+"/"+byteCount);
				if(byteReached==byteCount)
					break;
			}

			pdfObject.DecodedStream=stream;

			
		}else if(raw[i]=='('){ // its array of hex values (ie (\000\0\027)
	
			//@mariusz
			//octal values up to 3 chars long
			//example /Users/markee/PDFdata/sample_pdfs/todd/B35023_02_test.pdf

			i++;
			//find end
			int end=i, validCharCount=0, itemCount=0;
			//here
			
			// Could be usefull when  having look at the stream
			/*System.out.println("RAW stream ...");
			for(int y=0;y<raw.length;y++){
				System.out.print((char)raw[y]);
			}
			System.out.println("\n\n");*/
			
			int back = 0;
			
			for(back = end;back<raw.length;back++){
				if((raw[back]==')' && raw[back-1]!=92) || (raw[back]==')' && raw[back-1]==92 && raw[back-2]==92)){
					break;
				}
			}
			
			byte[] nRaw = new byte[back-end];
			
			for(int r=end, j=0;r<(back);r++,j++){
				nRaw[j] = raw[r]; 
			}
			
			i = end;
			
			for(int e = 0;e<nRaw.length;e++){
				if(nRaw[e]!=10 && nRaw[e]!=13){
					if(nRaw[e]==92){
						if(nRaw[e+1]==92){
							validCharCount++;
							e++;
						}else if((nRaw[e+1]>=48 && nRaw[e+1]<=57)){
							if(((e+2)< nRaw.length) && (nRaw[e+2]>=48 && nRaw[e+2]<=57)){
								validCharCount++;
								e=e+3;
							}else{
								validCharCount++;
								e++;
							}
						}else if(nRaw[e+1]<48 || nRaw[e+1]>57){
							// if this than entry is not a number but an escape char 
							validCharCount++;
							e++;
						}else{
							throw new RuntimeException("Not accounted for yet");
						}
					}else{
						validCharCount++;
					}
				}else{
					//throw new RuntimeException("Confirm if raw["+end+"]="+raw[end]+" is a valid char!");
				}
			}

			/*System.out.println("Corrected stream ... number of valid chars=" + validCharCount);
			
			for(int w = 0;w<nRaw.length;w++){
				System.out.print((char)nRaw[w]);
			}
			System.out.println("\n\n");*/

			int octVal = 0, byteReached=0;
			
			int byteCount=validCharCount;
			byte[] stream=new byte[byteCount];

			i=0;
			
			while(true){
				
				if(i>=nRaw.length){
					System.err.println("Exited too soon!");
					break;
				}

				while(nRaw[i]==10 || nRaw[i]==13){
					i++;
				}
					
				octVal=nRaw[i];
		
				if(octVal<=122 && octVal>=97){
					stream[byteReached]=(byte)octVal;
				}else if(octVal<=90 && octVal>=65){
					stream[byteReached]=(byte)octVal;
				}else if(octVal==92){
					while(true){
						i++;

						int items = 0;
						int falseI = i;

						for(int q=0;q<3;q++){
							if(falseI<nRaw.length){
								if((nRaw[falseI]>=48 && nRaw[falseI]<=57)&& items<3){
									falseI++;
									items++;
								}
							}
						}

						octVal =0;
						if(items>0){
							for(int o=0;o<items;o++){
								int tmp = 0;
								tmp = (nRaw[i+o]-48);
								
								switch(items){
									case 1:
										break;
									case 2:
										if(o==0){
											tmp = tmp*8;
										}
										break;
									case 3:
										if(o==0){
											tmp = tmp*64;
										}else if(o==1){
											tmp = tmp*8;
										}
										break;
								}
								
								
								octVal = octVal + tmp;
							}
							
							i = i + items -1;

							stream[byteReached]=(byte)octVal;

							break;
						}else{
							if(nRaw[i]==92){//         \\
								stream[byteReached]=(byte)92;
								break;
							}else if(nRaw[i]==114){//  \r
								stream[byteReached]=(byte)114;
								break;
							}else if(nRaw[i]==116){//  \t
								stream[byteReached]=(byte)116;
								break;
							}else if(nRaw[i]==41){//   \)
								stream[byteReached]=(byte)41;
								break;	
							}else if(nRaw[i]==40){//   \(
								stream[byteReached]=(byte)40;
								break;	
							}else if(nRaw[i]==98){//   \b
								stream[byteReached]=(byte)98;
								break;
							}else if(nRaw[i]==110){//  \n
								stream[byteReached]=(byte)110;
								break;
							}else if(nRaw[i]==102){//  \f
								stream[byteReached]=(byte)102;
								break;
							}else if(nRaw[i]==13){//  \13
								stream[byteReached]=(byte)13;
								break;	
							}else{
								System.out.println("Encountered value nRaw["+i+"]=" + nRaw[i]);
							}
						}
					}
				}else if(octVal<48 || octVal>57){
					stream[byteReached]=(byte)octVal;
				}else if(octVal>=48 && octVal<=57){
					stream[byteReached]=(byte)(octVal);
				}else{
					System.out.println("Oct: scenario not yet accounted for ! octVal="+octVal);
				}

				i++;
				
				byteReached++;
				if(byteReached==byteCount)
					break;
				
			}
			
			pdfObject.DecodedStream=stream;
			i=end;

		}else{ //assume its an object
			
			if(debugColorspace)
				System.out.println(paddingString+"(assume object) starts with "+raw[i]+" "+raw[i+1]);

			//number
            int keyStart2=i,keyLength2=0;
            while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47 && raw[i]!=60 && raw[i]!=62){

                i++;
            }
            int number=parseInt(keyStart2,i, raw);
            
            
            //generation
            while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47 || raw[i]==60)
                i++;

            keyStart2=i;
            //move cursor to end of reference
            while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47 && raw[i]!=60 && raw[i]!=62)
                i++;
            int generation=parseInt(keyStart2,i, raw);

            //move cursor to start of R
            while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47 || raw[i]==60)
                i++;

            if(raw[i]!=82) //we are expecting R to end ref
                throw new RuntimeException("3. Unexpected value in file "+raw[i]+" - please send to IDRsolutions for analysis");

            i++;

              //pass in
            pdfObject.setRef(number, generation);

            //read the Dictionary data
            byte[] data=readObjectAsByteArray("1",false,isCompressed(number,generation),number,generation);

            int j=0;
            if(data[0]!='['){
                //lose obj at start
                j=3;
                while(data[j-1]!=106 && data[j-2]!=98 && data[j-3]!=111){
                    j++;
                }
            }

            handleColorSpaces(pdfObject,j,  data, debugFastCode, paddingString);
            
		}

		//if(debugColorspace)
		//System.out.println(paddingString+"return i="+i+" >>"+(char)raw[i]+""+(char)raw[i+1]+""+(char)raw[i+2]);
		
		//roll back if no gap
		if(i<raw.length && (raw[i]==47 || raw[i]=='>'))
			i--;
		
		return i;
	}
    

	/**
	 *
	 * @param pdfObject
	 * @param objectRef
	 * @param i
	 * @param raw
	 * @param debugFastCode
	 * @param PDFkeyInt - -1 will store in pdfObject directly, not as separate object
	 * @param PDFkey
	 * @param paddingString
     * @return
	 */
	private int readDictionaryFromRefOrDirect(int id, PdfObject pdfObject,
                                              String objectRef, int i, byte[] raw,
                                              boolean debugFastCode, 
                                              int PDFkeyInt, Object PDFkey, 
                                              String paddingString) {

		int keyLength;
		int keyStart;
		int possibleArrayStart=-1;

		//@speed - find end so we can ignore once no longer reading into map as well
		//and skip to end of object
		//allow for [ref] or [<< >>] at top level (may be followed by gap)
		//good example is /PDFdata/baseline_screens/docusign/test3 _ Residential Purchase and Sale Agreement - 6-03.pdf
		while(raw[i]==91 || raw[i]==32 || raw[i]==13 || raw[i]==10){

			if(raw[i]==91) //track incase /Mask [19 19]
				possibleArrayStart=i;

			i++;
		}

        //some items like MAsk can be [19 19] or stream
		//and colorspace is law unto itself
		if(PDFkeyInt==PdfDictionary.ColorSpace || id==PdfDictionary.ColorSpace){ //very specific type of object

			PdfObject ColorSpace=(PdfObject) cachedColorspaces.get(objectRef);
			
			//System.out.println(objectRef+" "+ColorSpace+" "+cachedColorspaces);
			
			//read the base value (2 cases - Colorspace pairs or value in XObject
			if(ColorSpace==null && !pdfObject.ignoreRecursion()){

                if(pdfObject.getObjectType()==PdfDictionary.ColorSpace){//pairs
					return handleColorSpaces(pdfObject, i, raw,debugFastCode,paddingString);
					
				}else{ //Direct object in XObject
				ColorSpace=new PdfColorSpaceObject(objectRef);
	        	pdfObject.setDictionary(PdfDictionary.ColorSpace,ColorSpace);
	        	
	        	//cachedColorspaces.put(objectRef,ColorSpace);
	        	return handleColorSpaces(ColorSpace, i, raw,debugFastCode,paddingString);
				}
			}else{//roll on and ignore
				
				//System.out.println("Cached--------------------");
				pdfObject.setDictionary(PdfDictionary.ColorSpace,ColorSpace);
	        	
				if(raw[i]=='<' && raw[i+1]=='<'){
					i=i+2;
					int level=1;
					
					while(level>0){
                   //   System.out.print((char)data[start]);
                        if(raw[i]=='<' && raw[i+1]=='<'){
                            i=i+2;
                            level++;
                        }else if(raw[i]=='>' && raw[i+1]=='>'){
                            i=i+2;
                            level--;
                        }else
                            i++;
                    }

				}else{ //must be a ref
					while(raw[i]!='R')
						i++;
					
					i++;
				}
				
				return i;
			}
				
		}else if(possibleArrayStart!=-1 && (PDFkeyInt==PdfDictionary.Mask || PDFkeyInt==PdfDictionary.TR)){

			//find end
			int endPoint=possibleArrayStart;
			while(raw[endPoint]!=']' && endPoint<=raw.length)
				endPoint++;

			//convert data to new Dictionary object and store
			PdfObject valueObj= PdfObjectFactory.createObject(PDFkeyInt,null);
			pdfObject.setDictionary(PDFkeyInt,valueObj);
            valueObj.ignoreRecursion(pdfObject.ignoreRecursion());

            int type=PdfDictionary.VALUE_IS_INT_ARRAY;
			if(PDFkeyInt==PdfDictionary.TR){
				
				type=PdfDictionary.VALUE_IS_KEY_ARRAY;
			}
			
			i=readArray(pdfObject.ignoreRecursion(), possibleArrayStart, endPoint,
					type, raw, objectRef,
					valueObj,
            		PDFkeyInt, debugFastCode,paddingString);

			if(debugFastCode)
				System.out.println(paddingString+"Set Array "+PDFkey+" for Mask or TR "+valueObj+" in "+pdfObject);

			//rollon
			return i;
		}

		if(raw[i]=='%'){ // if %comment roll onto next line
			while(true){
				i++;
				if(raw[i]==13 || raw[i]==10)
					break;

			}

			//and lose space after
			while(raw[i]==91 || raw[i]==32 || raw[i]==13 || raw[i]==10)
				i++;
		}

		if(raw[i]==60){ //[<<data inside brackets>>]


			i = convertDirectDictionaryToObject(pdfObject, objectRef, i, raw,
					debugFastCode, PDFkeyInt, paddingString);


		}else if(raw[i]==47){ //direct value such as /DeviceGray

			//System.out.println(PDFkeyInt+" "+pdfObject);
			i++;
			keyStart=i;
			while(i<raw.length && raw[i]!=32 && raw[i]!=10 && raw[i]!=13){
                //System.out.println(raw[i]+" "+(char)raw[i]+" "+i+"/"+raw.length);
                i++;
            }
            //convert data to new Dictionary object
			//PdfObject valueObj= PdfObjectFactory.createObject(PDFkeyInt,null);

			//store value
			int constant=pdfObject.setConstant(PDFkeyInt,keyStart,i-keyStart,raw);
			//pdfObject.setDictionary(PDFkeyInt,valueObj);

			if(debugFastCode)
				System.out.println(paddingString+"Set object "+pdfObject+" to "+constant);

		}else{ // ref or [ref]

			int j=i,ref,generation;
			byte[] data=raw;

			while(true){

				//allow for [ref] at top level (may be followed by gap
				while(data[j]==91 || data[j]==32 || data[j]==13 || data[j]==10)
					j++;

                // trap nulls  as well
                boolean hasNull=false;

                while(true){

                    //trap null arrays ie [null null]
                    if(hasNull && data[j]==']')
                            return j;

                    /**
                     * get object ref
                     */
                    keyStart=j;
                    //move cursor to end of reference
                    while(data[j]!=10 && data[j]!=13 && data[j]!=32 && data[j]!=47 && data[j]!=60 && data[j]!=62){
                        
                        //trap null arrays ie [null null]
                        if(hasNull && data[j]==']')
                            return j;

                        j++;
                    }

                    ref=parseInt(keyStart,j, data);

                    //move cursor to start of generation or next value
                    while(data[j]==10 || data[j]==13 || data[j]==32)// || data[j]==47 || data[j]==60)
                        j++;

                        //handle nulls
                        if(ref!=69560)
                        break; //not null
                        else{
                            hasNull=true;
                            if(data[j]=='<') // /DecodeParms [ null << /K -1 /Columns 1778 >>  ] ignore null and jump down to enclosed Dictionary
                            return readDictionaryFromRefOrDirect(id, pdfObject, objectRef,  j, raw, debugFastCode,  PDFkeyInt,  PDFkey, paddingString);

                        }
                }

                /**
				 * get generation number
				 */
				keyStart=j;
				//move cursor to end of reference
				while(data[j]!=10 && data[j]!=13 && data[j]!=32 && data[j]!=47 && data[j]!=60 && data[j]!=62)
					j++;

				generation=parseInt(keyStart,j, data);

				/**
				 * check R at end of reference and abort if wrong
				 */
				//move cursor to start of R
				while(data[j]==10 || data[j]==13 || data[j]==32 || data[j]==47 || data[j]==60)
					j++;

				if(data[j]!=82){ //we are expecting R to end ref
					throw new RuntimeException("ref="+ref+" gen="+ref+" 1. Unexpected value "+data[j]+" in file - please send to IDRsolutions for analysis char="+(char)data[j]);
				}

				//read the Dictionary data
				data=readObjectAsByteArray(objectRef,false,isCompressed(ref,generation),ref,generation);

                if(data==null){
					if(debugFastCode)
						System.out.println(paddingString+"null data");

					break;
				}

                /**
				 * get not indirect and exit if not
				 */
				int j2=0;
				while(j2<3 || (j2>2 &&data[j2-1]!=106 && data[j2-2]!=98 && data[j2-3]!=111)){

                    //allow for /None as value
                    if(data[j2]=='/')
                    break;
                    j2++;
                }

                //skip any spaces
				while(data[j2]==10 || data[j2]==13 || data[j2]==32)// || data[j]==47 || data[j]==60)
					j2++;

				//if indirect, round we go again
				if(data[j2]!=91){
					j=0;
					break;
				}

				j=j2;
        }

			//allow for no data found (ie /PDFdata/baseline_screens/debug/hp_broken_file.pdf)
			if(data!=null){

				/**
				 * get id from stream
				 */
				//skip any spaces
				while(data[j]==10 || data[j]==13 || data[j]==32)// || data[j]==47 || data[j]==60)
					j++;

                boolean isMissingValue=raw[j]=='<';

                if(isMissingValue){ //check not <</Last
                    //find first valid char
                    int xx=j;
                    while(xx<data.length && (raw[xx]=='<' || raw[xx]==10 || raw[xx]==13 || raw[xx]==32))
                        xx++;

                    if(raw[xx]=='/')
                    isMissingValue=false;
                }

                if(isMissingValue){ //missing value at start for some reason

                    /**
                     * get object ref
                     */
                    keyStart=j;
                    //move cursor to end of reference
                    while(data[j]!=10 && data[j]!=13 && data[j]!=32 && data[j]!=47 && data[j]!=60 && data[j]!=62)
                        j++;

                    ref=parseInt(keyStart,j, data);

                    //move cursor to start of generation
                    while(data[j]==10 || data[j]==13 || data[j]==32 || data[j]==47 || data[j]==60)
                        j++;

                    /**
                     * get generation number
                     */
                    keyStart=j;
                    //move cursor to end of reference
                    while(data[j]!=10 && data[j]!=13 && data[j]!=32 && data[j]!=47 && data[j]!=60 && data[j]!=62)
                        j++;

                    generation=parseInt(keyStart,j, data);

                    //lose obj at start
				    while(data[j-1]!=106 && data[j-2]!=98 && data[j-3]!=111){

                        if(data[j]=='<')
                        break;
                        
                        j++;
                    }

                }

                //skip any spaces
				while(data[j]==10 || data[j]==13 || data[j]==32)// || data[j]==47 || data[j]==60)
					j++;

				//move to start of Dict values
				if(data[0]!=60)
					while(data[j]!=60 && data[j+1]!=60){

						//allow for Direct value ie 2 0 obj /WinAnsiEncoding
						if(data[j]==47)
							break;

						j++;
					}

				if(data[j]==47){
					j++; //roll on past /

					keyStart=j;
					keyLength=0;

					//move cursor to end of text
					while(data[j]!=10 && data[j]!=13 && data[j]!=32 && data[j]!=47 && data[j]!=60 && data[j]!=62){
						j++;
						keyLength++;

					}

					i--;// move back so loop works

					if(PDFkeyInt==-1){
						//store value directly
						int constant=pdfObject.setConstant(PDFkeyInt,keyStart,keyLength,data);

						if(debugFastCode)
							System.out.println(paddingString+"Set object Constant directly to "+constant);
					}else{
						//convert data to new Dictionary object
						PdfObject valueObj= PdfObjectFactory.createObject(PDFkeyInt,null);

						//store value
						int constant=valueObj.setConstant(PDFkeyInt,keyStart,keyLength,data);
						pdfObject.setDictionary(PDFkeyInt,valueObj);

						if(debugFastCode)
							System.out.println(paddingString+"Set object to Constant "+PDFkey+" in "+valueObj+" to "+constant);
					}
				}else{

					//convert data to new Dictionary object
					PdfObject valueObj= null;
					if(PDFkeyInt==-1)
						valueObj=pdfObject;
					else{
						valueObj=PdfObjectFactory.createObject(PDFkeyInt,ref,generation);
                       // System.out.println(valueObj+" "+pdfObject.getObjectType()+" PDFkeyInt="+PDFkeyInt);
                        if(PDFkeyInt!=PdfDictionary.Resources)
                            valueObj.ignoreRecursion(pdfObject.ignoreRecursion());
                    }

                    if(debugFastCode){
						//throw new RuntimeException("xx");
						//System.out.println(paddingString+"X1------------------------"+ref+" "+generation+" R >>>>Converting to Dictionary "+valueObj);
					}

                    j=readDictionaryAsObject( valueObj, ref+" "+generation+" R", j, data, data.length, paddingString, false);

					/**
					 * look for stream afterwards
					 */
					//@speed - do I need this?
					//readStreamIntoObject(pdfObject, debugFastCode, j, data, valueObj);

					if(debugFastCode)
						System.out.println(paddingString+"------------------------<<<<Saving Dictionary "+valueObj+" for key "+PDFkey+" into "+pdfObject);

					//store value
					if(PDFkeyInt!=-1)
						pdfObject.setDictionary(PDFkeyInt,valueObj);

				}
			}
		}

		return i;
	}


	private int convertDirectDictionaryToObject(PdfObject pdfObject,
			String objectRef, int i, byte[] raw, boolean debugFastCode,
			int PDFkeyInt, String paddingString) {
		//convert data to new Dictionary object
		PdfObject valueObj=null;

		if(PDFkeyInt==-1)
			valueObj=pdfObject;
		else
			valueObj= PdfObjectFactory.createObject(PDFkeyInt,objectRef);


		if(debugFastCode)
			System.out.println(paddingString+"Reading [<<data>>] to "+valueObj+" into "+pdfObject+" i="+i);

		i=readDictionaryAsObject( valueObj, objectRef, i, raw, raw.length, paddingString, false);

		if(debugFastCode)
			System.out.println(paddingString+"data into pdfObject="+pdfObject+" i="+i);

		//store value (already set above for -1
		if(PDFkeyInt!=-1)
			pdfObject.setDictionary(PDFkeyInt,valueObj);

		//roll on to end
		int count=raw.length;
		while( i<count-1 && raw[i]==62  && raw[i+1]==62){ //
			i++;
			if(i+1<raw.length && raw[i+1]==62) //allow for >>>>
				break;
		}
		return i;
	}
	/**
	 * if pairs is -1 returns number of pairs
	 * otherwise sets pairs and returns point reached in stream
	 */
	private int readKeyPairs(int id,byte[] data, int j,int pairs, PdfObject pdfObject, String paddingString) {

		final boolean debug=false;

		int start=j,level=1;

		int numberOfPairs=pairs;

		if(debug){
			System.out.println("count="+pairs+"============================================\n");
            for(int aa=j;aa<data.length;aa++){
            	System.out.print((char)data[aa]);
            	
            	if(aa>5 && data[aa-5]=='s' && data[aa-4]=='t' && data[aa-3]=='r'&& data[aa-2]=='e' && data[aa-1]=='a' && data[aa]=='m')
                    aa=data.length;
            }
            System.out.println("\n============================================");
        }

		//same routine used to count first and then fill with values
		boolean isCountOnly=false,skipToEnd=false;
		byte[][] keys=null,values=null;
		PdfObject[] objs=null;

		if(pairs==-1){
			isCountOnly=true;
		}else if(pairs==-2){
			isCountOnly=true;
			skipToEnd=true;
		}else{
			keys=new byte[numberOfPairs][];
			values=new byte[numberOfPairs][];
			objs=new PdfObject[numberOfPairs];

			if(debug)
				System.out.println("Loading "+numberOfPairs+" pairs");
		}
		pairs=0;

        while(true){

            //move cursor to start of text
			while(data[start]==9 || data[start]==10 || data[start]==13 || data[start]==32 || data[start]==60)
				start++;

			//exit at end
			if(data[start]==62)
				break;

			//count token or tell user problem
			if(data[start]==47){
				pairs++;
				start++;
            }else
				throw new RuntimeException("Unexpected value "+data[start]+" - not key pair");

			//read token key and save if on second run
			int tokenStart=start;
			while(data[start]!=32 && data[start]!=10 && data[start]!=13 && data[start]!='[' && data[start]!='<')
				start++;

            int tokenLength=start-tokenStart;

			byte[] tokenKey=new byte[tokenLength];
			System.arraycopy(data, tokenStart, tokenKey, 0, tokenLength);

            if(!isCountOnly) //pairs already rolled on so needs to be 1 less
				keys[pairs-1]=tokenKey;

			//now skip any spaces to key or text
			while(data[start]==10 || data[start]==13 || data[start]==32)
				start++;

			boolean isDirect=data[start]==60 || data[start]=='[' || data[start]=='/';

			byte[] dictData=null;

			if(debug)
				System.out.println("token="+new String(tokenKey)+" isDirect "+isDirect);

			if(isDirect){
                //get to start at <<
				while(data[start-1]!='<' && data[start]!='<' && data[start]!='[' && data[start]!='/')
					start++;

                int streamStart=start;
                
                //find end
                boolean isObject=true;
                
                if(data[start]=='<'){
                   start=start+2;
                   level=1;

                    while(level>0){
                   //   System.out.print((char)data[start]);
                        if(data[start]=='<' && data[start+1]=='<'){
                            start=start+2;
                            level++;
                        }else if(data[start]=='>' && data[start+1]=='>'){
                            start=start+2;
                            level--;
                        }else
                            start++;
                    }

                    //System.out.println("\n<---------------"+start);

                    //if(data[start]=='>' && data[start+1]=='>')
                    //start=start+2;
                }else if(data[start]=='['){

                    level=1;
                    start++;

                    boolean inStream=false;

                    while(level>0){

                    	//allow for streams
                        if(!inStream && data[start]=='(')
                            inStream=true;
                        else if(inStream && data[start]==')' && (data[start-1]!='\\' || data[start-2]=='\\' ))
                            inStream=false;

                        //System.out.println((char)data[start]);

                        if(!inStream){
                            if(data[start]=='[')
                                level++;
                            else if(data[start]==']')
                                level--;
                        }

                        start++;
                    }

                    isObject=false;
                }else if(data[start]=='/'){
                    start++;
                    while(data[start]!='/' && data[start]!=10 && data[start]!=13 && data[start]!=32)
                    start++;
                }

                if(!isCountOnly){
					int len=start-streamStart;
					dictData=new byte[len];
					System.arraycopy(data, streamStart, dictData, 0, len);
					//pairs already rolled on so needs to be 1 less
					values[pairs-1]=dictData;

					String ref=pdfObject.getObjectRefAsString();

					//@speed - will probably need to change as we add more items
					
					if(pdfObject.getObjectType()==PdfDictionary.ColorSpace){

                        if(isObject){
							handleColorSpaces(pdfObject, 0,  dictData, debug, paddingString+"    ");
							objs[pairs-1]=pdfObject;
						}else{							
							PdfColorSpaceObject colObject=new PdfColorSpaceObject(ref);
							handleColorSpaces(colObject, 0,  dictData, debug, paddingString+"    ");
							objs[pairs-1]=colObject;							
						}
						
						//handleColorSpaces(-1, valueObj,ref, 0, dictData,debug, -1,null, paddingString);
                	}else if(isObject){
                        PdfObject valueObj=PdfObjectFactory.createObject(id, ref);
                        readDictionaryFromRefOrDirect(id, valueObj,ref, 0, dictData,false, -1,null, paddingString);
                        objs[pairs-1]=valueObj;
                    }
					
					//lose >> at end
					//while(start<data.length && data[start-1]!='>' && data[start]!='>')
					//	start++;
					
				}

			}else{ //its 50 0 R
                //number
				int keyStart2=start,keyLength2=0;
				while(data[start]!=10 && data[start]!=13 && data[start]!=32 && data[start]!=47 &&
						data[start]!=60 && data[start]!=62){
					start++;
					keyLength2++;
				}
				int number=parseInt(keyStart2,start, data);

				//generation
				while(data[start]==10 || data[start]==13 || data[start]==32 || data[start]==47 || data[start]==60)
					start++;

				keyStart2=start;
				//move cursor to end of reference
				while(data[start]!=10 && data[start]!=13 && data[start]!=32 &&
						data[start]!=47 && data[start]!=60 && data[start]!=62)
					start++;

                int generation=parseInt(keyStart2,start, data);

				//move cursor to start of R
				while(data[start]==10 || data[start]==13 || data[start]==32 || data[start]==47 || data[start]==60)
					start++;

				if(data[start]!=82) //we are expecting R to end ref
					throw new RuntimeException("3. Unexpected value in file - please send to IDRsolutions for analysis");

				start++; //roll past

                if(debug)
                        System.out.println("Data in object="+number+" "+generation+" R");

                //read the Dictionary data
				if(!isCountOnly){
					byte[] rawDictData=readObjectAsByteArray("",false,isCompressed(number,generation),number,generation);

                    if(debug){
                        System.out.println("============================================\n");
                        for(int aa=0;aa<rawDictData.length;aa++){
                        System.out.print((char)rawDictData[aa]);
                        
                        if(aa>5 && rawDictData[aa-5]=='s' && rawDictData[aa-4]=='t' && rawDictData[aa-3]=='r'&& rawDictData[aa-2]=='e' && rawDictData[aa-1]=='a' && rawDictData[aa]=='m')
                            aa=rawDictData.length;
                        }
                        System.out.println("\n============================================");
                    }
                    //cleanup
					//lose obj at start
					int jj=0;

					while(jj<3 ||(rawDictData[jj-1]!=106 && rawDictData[jj-2]!=98 && rawDictData[jj-3]!=111)){

                        if(rawDictData[jj]=='/' || rawDictData[jj]=='[' || rawDictData[jj]=='<')
                        break;

                        jj++;

                        if(jj==rawDictData.length){
                            jj=0;
                            break;
                        }
                    }

					//skip any spaces after
					while(rawDictData[jj]==10 || rawDictData[jj]==13 || rawDictData[jj]==32)// || data[j]==47 || data[j]==60)
						jj++;

					int len=rawDictData.length-jj;
					dictData=new byte[len];
					System.arraycopy(rawDictData, jj, dictData, 0, len);
					//pairs already rolled on so needs to be 1 less
					values[pairs-1]=dictData;

					String ref=number+" "+generation+" R";//pdfObject.getObjectRefAsString();

                    if(pdfObject.getObjectType()==PdfDictionary.Font && id==PdfDictionary.Font){//last condition for CharProcs
                       objs[pairs-1]=null;
                       values[pairs-1]=ref.getBytes();
                    }else{

                        //@speed - will probably need to change as we add more items
                        PdfObject valueObj=PdfObjectFactory.createObject(id, ref);

                        if(debug){
                            System.out.println(ref+" ABOUT TO READ OBJ for "+valueObj+" "+pdfObject);

                            System.out.println("-------------------\n");
                            for(int aa=0;aa<dictData.length;aa++){
                            System.out.print((char)dictData[aa]);

                                if(aa>5 && dictData[aa-5]=='s' && dictData[aa-4]=='t' && dictData[aa-3]=='r'&& dictData[aa-2]=='e' && dictData[aa-1]=='a' && dictData[aa]=='m')
                                aa=dictData.length;
                            }
                            System.out.println("\n-------------------");
                        }

                        if(valueObj.getObjectType()==PdfDictionary.ColorSpace)
                            handleColorSpaces(valueObj, 0,  dictData, debug, paddingString+"    ");
                        else
                            readDictionaryFromRefOrDirect(id, valueObj,ref, 0, dictData,debug, -1,null, paddingString);

                        objs[pairs-1]=valueObj;
  
                    }
                }
			}
		}


		if(!isCountOnly)
			pdfObject.setDictionaryPairs(keys,values,objs);

		if(debug)
			System.out.println("done=============================================");

		if(skipToEnd || !isCountOnly)
			return start;
		else
			return pairs;
		
    }

	private void readStreamIntoObject(PdfObject pdfObject,
			boolean debugFastCode, int j, byte[] data, PdfObject valueObj, String paddingString) {
		
		int count=data.length;

		if(debugFastCode)
			System.out.println(paddingString+"Looking for stream");

		byte[] stream=null;

		//may need optimising
		//debug - @speed
		for(int a=j;a<count;a++){
			if ((data[a] == 115)&& (data[a + 1] == 116)&& (data[a + 2] == 114)&&
					(data[a + 3] == 101)&& (data[a + 4] == 97)&& (data[a + 5] == 109)) {

				
				//ignore these characters and first return
				a = a + 6;


				if (data[a] == 13 && data[a+1] == 10) //allow for double linefeed
					a=a+2;
				else if(data[a]==10 || data[a]==13)
					a++;

				int start = a;

				a--; //move pointer back 1 to allow for zero length stream

				/**
				 * if Length set and valid use it
				 */
				int streamLength=0;
				int setStreamLength=pdfObject.getInt(PdfDictionary.Length);

				if(debugFastCode)
					System.out.println(paddingString+"setStreamLength="+setStreamLength);
				
				if(setStreamLength!=-1){

					streamLength=setStreamLength;

					//System.out.println("1.streamLength="+streamLength);
					
					a=start+streamLength;
	
					if((a<count) && data[a]==13 && (a+1<count) && data[a+1]==10)
						a=a+2;

					//check validity
					if (count>(a+9) && data[a] == 101 && data[a + 1] == 110 && data[a + 2] == 100 &&
							data[a + 3] == 115 && data[a + 4] == 116
							&& data[a + 5] == 114 && data[a + 6] == 101 && data[a + 7] == 97 && data[a + 8] == 109){

					}else{
						boolean	isValid=false;
						int current=a;
						//check forwards
						if(a<count){
							while(true){
								a++;
								if(isValid || a==count)
									break;

								if (data[a] == 101 && data[a + 1] == 110 && data[a + 2] == 100 && data[a + 3] == 115 && data[a + 4] == 116
										&& data[a + 5] == 114 && data[a + 6] == 101 && data[a + 7] == 97 && data[a + 8] == 109){

									streamLength=a-start;
									isValid=true;
								}
							}
						}

						if(!isValid){
							a=current;
							if(a>count)
								a=count;
							//check backwords
							while(true){
								a--;
								if(isValid || a<0)
									break;
								
								if (data[a] == 101 && data[a + 1] == 110 && data[a + 2] == 100 && data[a + 3] == 115 && data[a + 4] == 116
										&& data[a + 5] == 114 && data[a + 6] == 101 && data[a + 7] == 97 && data[a + 8] == 109){
									streamLength=a-start;
									isValid=true;
								}
							}
						}

						if(!isValid)
							a=current;
					}
					
				}else{

					/**workout length and check if length set*/
					int end;

					while (true) { //find end

						a++;

						if(a==count)
							break;
						if (data[a] == 101 && data[a + 1] == 110 && data[a + 2] == 100 && data[a + 3] == 115 && data[a + 4] == 116
								&& data[a + 5] == 114 && data[a + 6] == 101 && data[a + 7] == 97 && data[a + 8] == 109)
							break;

					}

					end=a-1;

					if((end>start))
						streamLength=end-start+1;
				}
				
				//lose trailing 10s or 13s
				if(streamLength>1){
					int ptr=start+streamLength-1;
					if(ptr<data.length && ptr>0 && (data[ptr]==10 || data[ptr]==13)){
						streamLength--;
						ptr--;
					}
				}


				/**
				 * either read stream into object from memory or just save position in Map
				 */
				if(startStreamPointer==-1 || debugCaching){

					//System.out.println(start+" "+streamLength+" "+count);
					
					if(start+streamLength>count)
						streamLength=count-start;
					
					//@speed - switch off and investigate
					if(streamLength<0)
						return;
					
					if(streamLength<0)
						throw new RuntimeException("Negative stream length "+streamLength+" start="+start+" count="+count);
					stream = new byte[streamLength];
					System.arraycopy(data, start, stream, 0, streamLength);

				}

				if(startStreamPointer!=-1){

					pdfObject.startStreamOnDisk=startStreamPointer;
					pdfObject.endStreamOnDisk=endStreamPointer;

					//debug code
					if(debugCaching){
						try{
							if(start+streamLength>count)
								streamLength=count-start;

							byte[] stream2 = new byte[streamLength];
							System.arraycopy(data, start, stream2, 0, streamLength);

							int cacheLength=endStreamPointer-startStreamPointer+1;

							//check it matches
							int xx=0;
							for(int jj=this.startStreamPointer;jj<this.endStreamPointer;jj++){
								byte[] buffer = new byte[1];

								/**get bytes into buffer*/
								this.movePointer(jj);
								this.pdf_datafile.read(buffer);

								if(buffer[0]!=stream2[xx]){
									System.out.println("error here");
									System.exit(1);
								}

								xx++;
							}

							if((cacheLength!=streamLength)){//&& (setLength==null)){
								System.out.println("\n");
								System.out.println("lengths cache changed="+cacheLength+" array="+streamLength);

								//for(int ii=0;ii<stream2.length;ii++)
								//	System.out.println(ii+" "+stream2[ii]+" "+(char)stream2[ii]);
								System.exit(1);
							}

						}catch(Exception e){
							System.out.println("ERRor in debug code");
							e.printStackTrace();
							System.exit(1);
						}
					}
				}
				a=count;
			}

		}

		if(debugFastCode && stream!=null)
			System.out.println(paddingString+"stream read "+stream+" saved into "+valueObj);

		if(valueObj!=null){
			valueObj.setStream(stream);

            //and decompress now forsome objects
			if(valueObj.decompressStreamWhenRead())
				readStream(valueObj,true,true,false, valueObj.getObjectType()==PdfDictionary.MetaData, valueObj.isCompressedStream());

		}
	}

	private int readUncodedString(PdfObject pdfObject, String objectRef, int i,
			byte[] raw, boolean debugFastCode, int PDFkeyInt, String paddingString) {

		int keyLength;
		int keyStart;
		//move cursor to end of last command if needed
		while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47)
			i++;

		//move cursor to start of text
		while(raw[i]==10 || raw[i]==13 || raw[i]==32)
			i++;

		//work out if direct (ie /String or read ref 27 0 R
		int j2=i;
		byte[] arrayData=raw;

		boolean isIndirect=raw[i]!=47 && raw[i]!=40; //Some /NAME values start (

		boolean startsWithBrace=raw[i]==40;
		
		//delete
		//@speed - lose this code once Filters done properly
		/**
		 * just check its not /Filter [/FlateDecode ] or [] or [ /ASCII85Decode /FlateDecode ]
		 * by checking next valid char not /
		 */
		boolean isInsideArray=false;
		if(isIndirect){
			int aa=i+1;
			while(aa<raw.length && (raw[aa]==10 || raw[aa]==13 || raw[aa]==32 ))
				aa++;

			if(raw[aa]==47 || raw[aa]==']'){
				isIndirect=false;
				i=aa+1;
				isInsideArray=true;
			}
		}

		if(isIndirect){ //its in another object so we need to fetch

			keyStart=i;
			keyLength=0;

			//move cursor to end of ref
			while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47 && raw[i]!=60 && raw[i]!=62){
				i++;
				keyLength++;
			}

			//actual value or first part of ref
			int ref=parseInt(keyStart,i, raw);

			//move cursor to start of generation
			while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47 || raw[i]==60)
				i++;

			// get generation number
			keyStart=i;
			//move cursor to end of reference
			while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47 && raw[i]!=60 && raw[i]!=62)
				i++;

			int generation=parseInt(keyStart,i, raw);

			//move cursor to start of R
			while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47 || raw[i]==60)
				i++;

			if(raw[i]!=82){ //we are expecting R to end ref
				throw new RuntimeException(paddingString+"2. Unexpected value in file - please send to IDRsolutions for analysis");
			}

			//read the Dictionary data
			arrayData=readObjectAsByteArray(objectRef,false,isCompressed(ref,generation),ref,generation);

			//lose obj at start and roll onto /
			j2=3;
			while(arrayData[j2]!=47)
				j2++;
		}

		//lose /
		j2++;
		
		//allow for no value with /Intent//Filter 
		if(arrayData[j2]==47)
			return j2-1;

		int end=j2+1;

		byte[] stringBytes;

		if(isInsideArray){ //values inside []

			//move cursor to start of text
			while(arrayData[j2]==10 || arrayData[j2]==13 || arrayData[j2]==32 || arrayData[j2]==47)
				j2++;

			int slashes=0;

			//count chars
			byte lastChar=0;
			while(true){
				if(arrayData[end]==']')
					break;

				if(arrayData[end]==47 && (lastChar==32 || lastChar==10 || lastChar==13))//count the / if gap before
					slashes++;

				lastChar=arrayData[end];
				end++;

				if(end==arrayData.length)
					break;
			}

			//set value and ensure space gap
			int charCount=end-slashes,ptr=0;
			stringBytes=new byte[charCount-j2];

			byte nextChar,previous=0;
			for(int ii=j2;ii<charCount;ii++){
				nextChar=arrayData[ii];
				if(nextChar==47){
					if(previous!=32 && previous!=10 && previous!=13){
						stringBytes[ptr]=32;
						ptr++;
					}
				}else{
					stringBytes[ptr]=nextChar;
					ptr++;
				}

				previous=nextChar;
			}
		}else{ //its in data stream directly or (string)

			//count chars
			while(true){
				
				if(startsWithBrace){
					if(arrayData[end]==')')
						break;
				}else if(arrayData[end]==32 || arrayData[end]==10 || arrayData[end]==13 || arrayData[end]==47 || arrayData[end]==62)
					break;

				end++;

				if(end==arrayData.length)
					break;
			}

			//set value
			int charCount=end-j2;
			stringBytes=new byte[charCount];
			System.arraycopy(arrayData,j2,stringBytes,0,charCount);

		}

		/**
		 * finally set the value
		 */
		pdfObject.setStringValue(PDFkeyInt,stringBytes);

		if(debugFastCode)
			System.out.println(paddingString+"String set as ="+new String(stringBytes)+"< written to "+pdfObject);

		//put cursor in correct place (already there if ref)
		if(!isIndirect)
			i=end-1;
		return i;
	}

	/**
	 * read a dictionary object
	 */
	public int readDictionary(String objectRef, int level, Map rootObject, int i, byte[] raw,
			boolean isEncryptionObject, Map textFields, int endPoint){

		boolean preserveTextString=false;

		final boolean debug=false;
		//debug=objectRef.equals("37004 0 R");
		int keyLength=0,keyStart=-1,firstKey=-1;

		Object PDFkey=null;
		int PDFkeyInt=-1,pdfKeyType=-1;

		while(true){

			i++;

			if(i>=raw.length || (endPoint!=-1 && i>=endPoint))
				break;

			//break at end
			if ((raw[i] == 62) && (raw[i + 1] == 62))
				break;

			if ((raw[i] == 101)&& (raw[i + 1] == 110)&& (raw[i + 2] == 100)&& (raw[i + 3] == 111))
				break;

			//handle recursion
			if ((raw[i] == 60) && ((raw[i + 1] == 60))){
				level++;
				i++;

				if(debug)
					System.err.println(level+" << found key="+PDFkey+"= nextchar="+raw[i + 1]);

				Map dataValues=new HashMap();
				rootObject.put(PDFkey,dataValues);
				i=readDictionary(objectRef,level,dataValues,i,raw,isEncryptionObject,textFields,endPoint);

				//i++;
				keyLength=0;
				level--;
				//allow for >>>> with no spaces
				if (raw[i] == 62 && raw[i + 1] == 62)
					i++;

			}else  if (raw[i] == 47 && raw[i+1] == 47) { //allow for oddity of //DeviceGray in colorspace
				i++;
			}else  if (raw[i] == 47 && keyLength==0) { //everything from /

				i++;

				while (true) { //get key up to space or [ or / or ( or < or carriage return

					if ((raw[i] == 32)|| (raw[i] == 13) || (raw[i] == 9) || (raw[i] == 10) ||(raw[i] == 91)||(raw[i]==47)||
							(raw[i]==40)||(raw[i]==60))
						break;

					if(keyLength==0){
						firstKey=raw[i];
						keyStart=i;
					}
					keyLength++;

					i++;
				}

				//allow for / /DeviceGray permutation
				if((raw[i] == 32)&&(raw[i-1] == 47)){
					keyLength++;
				}

				if(keyStart==-1)
					PDFkey=null;
				else
					PDFkey=PdfDictionary.getKey(keyStart,keyLength,raw);

				if(debug)
					System.err.println(level+" key="+PDFkey+ '<');

				//set flag to extract raw text string

				if((textFields!=null)&&(keyLength>0)&&(textFields.containsKey(PDFkey))){
					preserveTextString=true;
				}else
					preserveTextString=false;

				if(raw[i]==47 || raw[i]==40 || raw[i]==60 || raw[i] == 91) //move back cursor
					i--;

			}else if(raw[i]==32 || raw[i]==13 || raw[i]==10){
			}else if(raw[i]==60 && preserveTextString){ //text string <00ff>

				final boolean debug2=true;

				byte[] streamData;
				i++;

				ByteArrayOutputStream bis=null;
				if(debug2)
					bis=new ByteArrayOutputStream();

				int count=0,i2=i;
				/**
				 * workout number of bytes
				 */
				 while(true){

					 i2=i2+2;

					 count++;

					 //ignore returns
					 while(raw[i2]==13|| raw[i2]==10)
						 i2++;

					 if(raw[i2]==62)
						 break;

				 }
				 streamData=new byte[count];
				 count=0;

				 /**
				  * convert to values
				  **/
				 while(true){

					 StringBuffer newValue=new StringBuffer(2);
					 for(int j=0;j<2;j++){
						 newValue.append((char)raw[i]);
						 i++;
					 }

					 if(debug2)
						 bis.write(Integer.parseInt(newValue.toString(),16));

					 streamData[count]=(byte)Integer.parseInt(newValue.toString(),16);
					 count++;

					 //ignore returns
					 while(raw[i]==13 || raw[i]==10)
						 i++;

					 if(raw[i]==62)
						 break;

				 }

				 try{

					 if(debug2)
						 bis.close();

					 if(debug2){
						 byte[] stream2=bis.toByteArray();

						 if(streamData.length!=stream2.length){
							 System.out.println("Different lengths "+streamData.length+ ' ' +stream2.length);
							 System.exit(1);
						 }

						 for(int jj=0;jj<stream2.length;jj++){
							 if(stream2[jj]!=streamData[jj]){
								 System.out.println(jj+" Different values "+stream2[jj]+ ' ' +streamData[jj]);
								 System.exit(1);
							 }
						 }
					 }
					 streamData=decrypt(streamData,objectRef, false,null,false,false);

					 rootObject.put(PDFkey,streamData); //save pair and reset
				 }catch(Exception e){
					 LogWriter.writeLog("[PDF] Problem "+e+" writing text string"+PDFkey);
					 e.printStackTrace();
				 }

				 keyLength=0;

				 //ignore spaces and returns
			}else if(raw[i]==40){ //read in (value) excluding any returns

				if(preserveTextString){
					ByteArrayOutputStream bis=new ByteArrayOutputStream();
					try{
						if(raw[i+1]!=41){ //trap empty field
							while(true){

								i++;
								boolean isOctal=false;

								//trap escape
								if((raw[i]==92)){
									i++;

									if(raw[i]=='b')
										raw[i]='\b';
									else if(raw[i]=='n')
										raw[i]='\n';
									else if(raw[i]=='t')
										raw[i]='\t';
									else if(raw[i]=='r')
										raw[i]='\r';
									else if(raw[i]=='f')
										raw[i]='\f';
									else if(raw[i]=='\\')
										raw[i]='\\';
									else if(Character.isDigit((char) raw[i])){ //octal
										StringBuffer octal=new StringBuffer(3);
										for(int ii=0;ii<3;ii++){
											octal.append((char)raw[i]);
											i++;
										}
										//move back 1
										i--;
										isOctal=true;
										raw[i]=(byte) Integer.parseInt(octal.toString(),8);
									}
								}

								//exit at end
								if(!isOctal && raw[i]==41 &&(raw[i-1]!=92 ||(raw[i-1]==92 && raw[i-2]==92)))
									break;

								bis.write(raw[i]);

							}
						}

						bis.close();

						byte[] streamData=bis.toByteArray();

						//if(stringsEncoded)
						streamData=decrypt(streamData,objectRef, false, null,false,true);

						//substitute dest key otherwise write through
						if(PDFkey.equals("Dest")){
							String destKey=this.getTextString(streamData);
							rootObject.put(PDFkey,nameLookup.get(destKey)); //save pair and reset
						}else
							rootObject.put(PDFkey,streamData); //save pair and reset

					}catch(Exception e){
						LogWriter.writeLog("[PDF] Problem "+e+" handling text string"+PDFkey);
					}
				}else if(isEncryptionObject && keyLength==1 &&(firstKey=='U' || firstKey=='O')){
					int count=32;

					ByteArrayOutputStream bis=new ByteArrayOutputStream();
					while(true){

						i++;

						byte next=raw[i];
						if(next==92){

							i++;
							next=raw[i];

							//map chars correctly
							if(next==114)
								next=13;
							else if(next==110)
								next=10;
							else if(next==116)
								next=9;
							else if(next==102) // \f
							next=12;
							else if(next==98) // \b
								next=8;
							else if(next>47 && next<58){ //octal

								StringBuffer octal=new StringBuffer(3);
								for(int ii=0;ii<3;ii++)
									octal.append((char)raw[i+ii]);

								i=i+2; //roll on extra chars

								//substitute
								next=(byte)(Integer.parseInt(octal.toString(),8));

							}

						}

						bis.write(next);

						count--;

						if(count==0)
							break;

					}
					try{
						bis.close();
						rootObject.put(PDFkey,bis.toByteArray()); //save pair and reset
					}catch(Exception e){
						LogWriter.writeLog("[PDF] Problem "+e+" writing "+PDFkey);
					}

				}else{
					int startValue=i,opPointer=0;
					boolean inComment=false;
					while(true){

						if(!inComment)
							opPointer++;

						if(((raw[i-1]!=92)||(raw[i-2]==92))&&(raw[i]==41))
							break;

						i++;

						if((raw[i]==37)&&(raw[i-1]!=92)) //ignore comments %
						inComment=true;

					}

					inComment=false;
					int p=0;
					char[] value=new char[opPointer];
					while(true){
						if(!inComment){
							if((raw[startValue]==13)|(raw[startValue]==10)){
								value[p]=' ';
								p++;
							}else{
								value[p]=(char)raw[startValue];
								p++;
							}
						}

						//avoid \\) where \\ causes problems and allow for \\\
						if((raw[startValue]!=92)&&(raw[startValue-1]==92)&&(raw[startValue-2]==92))
							raw[startValue-1]=0;

						if((raw[startValue-1]!=92)&(raw[startValue]==41))
							break;

						startValue++;

						if((raw[startValue]==37)&&(raw[startValue-1]!=92)) //ignore comments %
						inComment=true;

					}

					//save pair and reset
					String finalOp = String.copyValueOf(value,0,opPointer);

					if(!finalOp.equals("null"))
						rootObject.put(PDFkey,finalOp);

					if(debug)
						System.err.println(level+" *0 "+PDFkey+"=="+finalOp+ '<');

				}
				keyLength=0;
			}else if(raw[i]==91 && isFDF){ //read in [value] excluding any returns


				Map fdfTable=new HashMap();

				//read paired values
				while(true){

					//find <<
					while (raw[i+1]!=60 && raw[i+2]!=60 && raw[i]!=93)
						i++;


					//find >>
					int end=i;
					while (raw[end+1]!=62 && raw[end+2]!=62 && raw[end]!=93)
						end++;

					if(raw[i]==93)
						break;

					Map ref=new HashMap();
					i=readDictionary(objectRef,1,ref,i+2,raw,isEncryptionObject,textFields,end);
					i--;
					i--;

					String fdfkey=null,value="";
					byte[] pdfFile=getByteTextStringValue(ref.get("T"),ref);
					if(pdfFile!=null)
						fdfkey=getTextString(pdfFile);

					pdfFile=getByteTextStringValue(ref.get("V"),ref);
					if(pdfFile!=null)
						value=getTextString(pdfFile);

					if(fdfkey!=null)
						fdfTable.put(fdfkey,value);
				}

				rootObject.put(PDFkey,fdfTable);

				keyLength=0;

			}else if(raw[i]==91){ //read in [value] excluding any returns

				int startValue=i,opPointer=0;
				boolean inComment=false,convertToHex=false;
				int squareCount=0,count=0;
				char next=' ',last=' ';
				boolean containsIndexKeyword=false;

				while(raw[i]==32){ //ignore any spaces
					opPointer++;
					i++;
				}

				while(true){
					if(raw[i]==92) //ignore any escapes
						i++;

					//check if it contains the word /Indexed
					if((opPointer>7)&&(!containsIndexKeyword)&&(raw[i-7]=='/')&&(raw[i-6]=='I')&&
							(raw[i-5]=='n')&&(raw[i-4]=='d')&&(raw[i-3]=='e')&&(raw[i-2]=='x')&&
							(raw[i-1]=='e')&&(raw[i]=='d')){
						containsIndexKeyword=true;
					}

					//track [/Indexed /xx ()] with binary values in ()
					if((raw[i]==40)&&(raw[i-1]!=92)&&(containsIndexKeyword)){
						convertToHex=true;

						opPointer=opPointer+2;
					}else if(convertToHex){
						if((raw[i]==41)&&(raw[i-1]!=92)){
							opPointer++;

							convertToHex=false;

						}else{
							String hex_value = Integer.toHexString((raw[i]) & 255);
							//pad with 0 if required
							if (hex_value.length() < 2)  //add in char
							opPointer++;

							opPointer=opPointer+hex_value.length();
						}
					}else if(!inComment){
						if((raw[i]==13)||(raw[i]==10)){ //add in char
							opPointer++;
						}else{
							next=(char)raw[i];

							//put space in [/ASCII85Decode/FlateDecode]
							if((next=='/')&&(last!=' ')) //add in char
								opPointer++;

							if((next!=' ')&&(last==')')) //put space in [()99 0 R]
								opPointer++;

							opPointer++;
							last=next;
						}
					}

					if((raw[i-1]!=92)||((raw[i-1]==92)&&(raw[i-2]==92)&&(raw[i-3]!=92))){ //allow for escape and track [] and ()
						if(raw[i]==40)
							count++;
						else if(raw[i]==41)
							count--;
						if(count==0){
							if(raw[i]==91)
								squareCount++;
							else if(raw[i]==93)
								squareCount--;
						}
					}

					if((squareCount==0)&&(raw[i-1]!=92)&&(raw[i]==93))
						break;

					i++;

					//System.err.println(count++);

					if((raw[i]==37)&&(raw[i-1]!=92)&&(squareCount==0)) //ignore comments %
						inComment=true;

				}

				/**
				 * now extract char array at correct size
				 */
				char[] value=new char[opPointer*2];
				int pt=0;
				i=startValue; //move pointer back to start

				//ignore any spaces
				while(raw[i]==32){
					value[pt]=(char)raw[i];
					pt++;
					i++;
				}

				//reset defaults
				inComment=false;
				convertToHex=false;
				squareCount=0;
				count=0;
				next=' ';
				last=' ';

				while(true){

					//if(containsIndexKeyword)
					//System.out.println(i+" "+( raw[i] & 255)+" "+(char)(raw[i] & 255)+" "+Integer.toHexString((raw[i]) & 255));

//					check if it contains the word /Indexed
					if((i>7)&&(!containsIndexKeyword)&&(raw[i-7]=='/')&&(raw[i-6]=='I')&&
							(raw[i-5]=='n')&&(raw[i-4]=='d')&&(raw[i-3]=='e')&&(raw[i-2]=='x')&&
							(raw[i-1]=='e')&&(raw[i]=='d')){
						containsIndexKeyword=true;
					}

					//track [/Indexed /xx ()] with binary values in ()
					if((raw[i]==40)&(raw[i-1]!=92)&&(containsIndexKeyword)){

						//find end
						int start=i+1,end=i;
						while(end<raw.length){
							end++;
							if(raw[end]==')' & (raw[end-1]!=92 ||(raw[end-1]==92 && raw[end-2]==92)))
								break;

						}

						//handle escape chars
						int length=end-start;
						byte[] fieldBytes=new byte[length];

						for(int a=0;a<length;a++){

							if(start==end)
								break;

							byte b=raw[start];
							if(b!=92){
								fieldBytes[a]=b;
							}else{

								start++;

								if(raw[start]=='b')
									fieldBytes[a]='\b';
								else if(raw[start]=='n')
									fieldBytes[a]='\n';
								else if(raw[start]=='t')
									fieldBytes[a]='\t';
								else if(raw[start]=='r')
									fieldBytes[a]='\r';
								else if(raw[start]=='f')
									fieldBytes[a]='\f';
								else if(raw[start]=='\\')
									fieldBytes[a]='\\';
								else if(Character.isDigit((char) raw[start])){ //octal

									StringBuffer octal=new StringBuffer(3);
									for(int ii=0;ii<3;ii++){

										//allow for less than 3 digits
										if(raw[start]==92 || raw[start]==')')
											break;

										octal.append((char)raw[start]);
										start++;
									}
									start--;
									//move back 1
									fieldBytes[a]=(byte) Integer.parseInt(octal.toString(),8);

								}else{
									//start--;
									fieldBytes[a]=raw[start];
								}
							}

							start++;
						}

						//handle encryption
						try {
							fieldBytes=decrypt(fieldBytes,objectRef, false,null,false,false);
						} catch (PdfSecurityException e) {
							e.printStackTrace();
						}

						/**
						 * add to data as hex stream
						 */

						 //start
						 value[pt]=' ';
						 pt++;
						 value[pt]='<';
						 pt++;

						 //data
						 for(int jj=0;jj<length;jj++){
							 String hex_value = Integer.toHexString((fieldBytes[jj] & 255));

							 if (hex_value.length() < 2){ //pad with 0 if required
								 value[pt]='0';
								 pt++;
							 }

							 int hCount=hex_value.length();
							 for(int j=0; j<hCount;j++){
								 value[pt]=hex_value.charAt(j);
								 pt++;
							 }
						 }

						 //end
						 value[pt]='>';
						 pt++;

					}else if(!inComment){
						if((raw[i]==13)|(raw[i]==10)){
							value[pt]=' ';
							pt++;
						}else{
							next=(char)raw[i];
							if((next=='/')&&(last!=' ')){ //put space in [/ASCII85Decode/FlateDecode]
								value[pt]=' ';
								pt++;
							}
							if((next!=' ')&&(last==')')){ //put space in [()99 0 R]
								value[pt]=' ';
								pt++;
							}
							value[pt]=next;
							pt++;

							last=next;
						}
					}

					if((raw[i-1]!=92)|((raw[i-1]==92)&&(raw[i-2]==92)&&(raw[i-3]!=92))){ //allow for escape and track [] and ()
						if(raw[i]==40)
							count++;
						else if(raw[i]==41)
							count--;
						if(count==0){
							if(raw[i]==91)
								squareCount++;
							else if(raw[i]==93)
								squareCount--;
						}
					}

					if((squareCount==0)&&(raw[i-1]!=92)&(raw[i]==93))
						break;

					i++;

					if((raw[i]==37)&&(raw[i-1]!=92)&&(squareCount==0)) //ignore comments %
						inComment=true;

				}


				//save pair and reset
				String finalOp= String.copyValueOf(value,0,pt);
				if(!finalOp.equals("null"))
					rootObject.put(PDFkey,finalOp);

				if(debug)
					System.err.println(level+" *1 "+PDFkey+"=="+finalOp+ '<');

				keyLength=0;

			}else if ((raw[i] != 62)&&(raw[i] != 60)&&(keyLength>0)){

				boolean inComment=false;
				int startValue=i,opPointer=0;

				//calculate size of next value
				while(true){

					if((raw[i]!=13)&&(raw[i]!=9)&&(raw[i]!=10)&&(!inComment)) //add in char
						opPointer++;

					if((raw[i+1]==47)||((raw[i]!=62)&&(raw[i+1]==62)))
						break;

					i++;

					if((raw[i]==37)&&(raw[i-1]!=92)) //ignore comments %
						inComment=true;
				}

				//lose spaces at end, save pair and reset
				while((opPointer>0)&&((raw[startValue+opPointer-1]==32)||(raw[startValue+opPointer-1]==10)||(raw[startValue+opPointer-1]==13)||
						(raw[startValue+opPointer-1]==9)))
					opPointer--;

				//get value
				char[] value=new char[opPointer];
				opPointer--;
				int p=0;
				while(true){

					if((raw[startValue]!=13)&&(raw[startValue]!=9)&&(raw[startValue]!=10)){
						value[p]=(char)raw[startValue];
						p++;
					}

					startValue++;
					if(p>opPointer)
						break;
				}

				String finalOp=String.copyValueOf(value,0,p);

				if(1==2 &&(PDFkey.equals("C1") || PDFkey.equals("C0"))&&finalOp.endsWith(" R")){
					rootObject.put(PDFkey,readObject(new PdfObject(finalOp), finalOp,false, null));
				}else if(!finalOp.equals("null"))
					rootObject.put(PDFkey,finalOp);

				if(debug)
					System.err.println(level+"*2 "+PDFkey+"=="+finalOp+ '<');
				keyLength=0;

			}
		}

		if(debug)
			System.err.println("=====Dictionary read");

		return i;

	}

	private int readArray(boolean ignoreRecursion, int i,int endPoint, int type,byte[] raw,String objectRef,PdfObject pdfObject, int PDFkeyInt,
                          boolean debugFastCode, String paddingString) {

		int keyStart,keyLength;
		//roll on
		i++;
		
		boolean alwaysRead =(PDFkeyInt==PdfDictionary.Kids || PDFkeyInt==PdfDictionary.Annots);

		final boolean debugArray=false || debugFastCode;

		if(debugArray)
			System.out.println(paddingString+"Reading array type="+type+" "+pdfObject);
		
		int currentElement=0, elementCount=0;

		//move cursor to start of text
		while(raw[i]==10 || raw[i]==13 || raw[i]==32)
			i++;

		keyStart=i;
		keyLength=0;

		//work out if direct or read ref ( [values] or ref to [values])
		int j2=i;
		byte[] arrayData=raw;

		//may need to add method to PdfObject is others as well as Mask
		boolean isIndirect=raw[i]!=91 && (PDFkeyInt!=PdfDictionary.Mask && PDFkeyInt!=PdfDictionary.TR && pdfObject.getObjectType()!=PdfDictionary.ColorSpace);

        // allow for /Contents null
        if(raw[i]=='n' && raw[i+1]=='u' && raw[i+2]=='l' && raw[i+2]=='l'){
            isIndirect=false;
            elementCount=1;
        }

        //check indirect and not [/DeviceN[/Cyan/Magenta/Yellow/Black]/DeviceCMYK 36 0 R]
        if(isIndirect){
            //find next value and make sure not /
            int aa=i;

            while(raw[aa]!=93){
                aa++;

                //System.out.println((char)raw[aa]);
                //allow for ref (ie 7 0 R)
                if(aa>=endPoint)
                	break;
                
                if(raw[aa]=='R' && (raw[aa-1]==32 || raw[aa-1]==10 || raw[aa-1]==13))
                		break;
                else if(raw[aa]=='>' && raw[aa-1]=='>'){
                	isIndirect=false;
                	if(debugArray )
                		System.out.println(paddingString+"1. rejected as indirect ref");

                	break;
                }else if(raw[aa]==47){
                    isIndirect=false;
                    if(debugArray )
                		System.out.println(paddingString+"2. rejected as indirect ref - starts with /");

                    break;
                }
            }
        }
        
        if(debugArray && isIndirect)
    		System.out.println(paddingString+"Indirect ref");

        boolean isSingleDirectValue=false; //flag to show points to Single value (ie /FlateDecode)

        int endPtr=-1;


		if(raw[i]==47 && type!=PdfDictionary.VALUE_IS_STRING_ARRAY && PDFkeyInt!=PdfDictionary.TR){ //single value (ie /Filter /FlateDecode )
			
			//see if next char is ]
//			int start=i;
//			
//			System.out.println("next char=====================");
//			while(start<raw.length){
//				System.out.println((char)raw[start]);
//				if(raw[start]==']' || raw[start]==32 || raw[start]==10 || raw[start]==13 )
//					break;
//				
//				start++;
//			}
			
			//ignore spaces
//			if(raw[start]==32 || raw[start]==10 || raw[start]==13)
//				start++;
//			
//			System.out.println(">>>>>>>>>>>>>>>>>>raw[start]="+(char)raw[start]);
//			//if(raw[start]==']'){
//			
//				isSingleValue=true;
			elementCount=1;
//				
			if(debugArray)
        		System.out.println(paddingString+"Direct single value with /");
			//}
		}else{

			int endI=-1;//allow for jumping back to single value (ie /Contents 12 0 R )
			
			if(isIndirect){

				if(debugArray)
	        		System.out.println(paddingString+"------reading data----");

				//allow for indirect to 1 item
				int startI=i;
				
				if(debugArray)
					System.out.print(paddingString+"Indirect object ref=");

				//move cursor to end of ref
				while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47 && raw[i]!=60 && raw[i]!=62){

					if(debugArray)
						System.out.print((char)raw[i]);

					i++;
					keyLength++;
				}

				//actual value or first part of ref
				int ref=parseInt(keyStart,i, raw);

				//move cursor to start of generation
				while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47 || raw[i]==60)
					i++;

				// get generation number
				keyStart=i;
				//move cursor to end of reference
				while(raw[i]!=10 && raw[i]!=13 && raw[i]!=32 && raw[i]!=47 && raw[i]!=60 && raw[i]!=62)
					i++;

				int generation=parseInt(keyStart,i, raw);

				if(debugFastCode)
				    System.out.print(paddingString+" "+generation+"\n");

				// check R at end of reference and abort if wrong
				//move cursor to start of R
				while(raw[i]==10 || raw[i]==13 || raw[i]==32 || raw[i]==47 || raw[i]==60)
					i++;

				if(raw[i]!=82) //we are expecting R to end ref
					throw new RuntimeException(paddingString+"4. Unexpected value "+(char)raw[i]+" in file - please send to IDRsolutions for analysis");
				
				if(ignoreRecursion && !alwaysRead)
					return i;
				
				//read the Dictionary data
				arrayData=readObjectAsByteArray(objectRef,false,isCompressed(ref,generation),ref,generation);

				if(debugArray){
					System.out.println(paddingString+"Raw data is>>>>>>>>>>>>>>");
                    System.out.print(paddingString);
                    for(int aa=0;aa<arrayData.length;aa++)  {
                        System.out.print((char)arrayData[aa]);

                        if(aa>5 && arrayData[aa-5]=='s' && arrayData[aa-4]=='t' && arrayData[aa-3]=='r' && arrayData[aa-2]=='e' && arrayData[aa-1]=='a' && arrayData[aa]=='m')
                        aa=arrayData.length;
                    }

                    System.out.println("\n<<<<<<<<<<<<<<");
                }

				
				//lose obj at start and roll onto [
				j2=0;
				while(arrayData[j2]!=91){

					//allow for % comment
					if(arrayData[j2]=='%'){
						while(true){
							j2++;
							if(arrayData[j2]==13 || arrayData[j2]==10)
								break;
						}
						while(arrayData[j2]==13 || arrayData[j2]==10)
							j2++;
					}
					
					//allow for null
					if(arrayData[j2]=='n' && arrayData[j2+1]=='u' && arrayData[j2+2]=='l' && arrayData[j2+3]=='l')
						break;
					
					if(arrayData[j2]==47){ //allow for value of type  32 0 obj /FlateDecode endob
                        j2--;
                        isSingleDirectValue=true;
                        break;
                    }if ((arrayData[j2]=='<' && arrayData[j2+1]=='<')||
                        ((j2+4<arrayData.length) &&arrayData[j2+3]=='<' && arrayData[j2+4]=='<')){ //also check ahead to pick up [<<
                    	endI=i;
                    	
                    	j2=startI;
                    	arrayData=raw;
                    	
                    	if(debugArray)
                    	System.out.println(paddingString+"Single value, not indirect");
                    	
                    	break;
                    }

                    j2++;
                }
			}
			
			if(j2<0)
				j2=0;

			//skip [ and any spaces
			while(arrayData[j2]==10 || arrayData[j2]==13 || arrayData[j2]==32 || arrayData[j2]==91){
				j2++;
			}

			//count number of elements
			endPtr=j2;
			boolean charIsSpace=false,lastCharIsSpace=true;
			
			//if(debugArray)
			//	System.out.println(paddingString+"----counting elements----");
			
			while(arrayData[endPtr]!=93 ){

                //allow for embedded object
                if(arrayData[endPtr]=='<' && arrayData[endPtr+1]=='<'){
                    int levels=1;

                    elementCount++;

                    if(debugArray)
                    System.out.println("Direct value");

                    while(levels>0){
                        endPtr++;
                        
                        if(arrayData[endPtr]=='<' && arrayData[endPtr+1]=='<'){
                            endPtr++;
                            levels++;
                        }else if(arrayData[endPtr]=='>' && arrayData[endPtr+1]=='>'){
                            endPtr++;
                            levels--;
                        }
                    }
                }
                
                //allow for null
				if(arrayData[endPtr]=='n' && arrayData[endPtr+1]=='u' &&
						arrayData[endPtr+2]=='l' && arrayData[endPtr+3]=='l'){
					//endPtr=endPtr+3;
					elementCount=1;
					break;
				}
				
                if(isSingleDirectValue && (arrayData[endPtr]==32 || arrayData[endPtr]==13 || arrayData[endPtr]==10))
                        break;

                if(endI!=-1 && endPtr>endI) 
                	break;
				
                //System.out.println(arrayData[endPtr]+" "+(char)arrayData[endPtr]);

             
                if(type==PdfDictionary.VALUE_IS_KEY_ARRAY){
                	if(arrayData[endPtr]=='R'  || 
                			(PDFkeyInt==PdfDictionary.TR && arrayData[endPtr]=='/'  )){
                		elementCount++;
                		//System.out.println("XX");
                	}
                }else{

                    //handle (string)
                    if(arrayData[endPtr]=='('){
                        elementCount++;

                       // System.out.println("---XX---");
                        while(true){
                            if((arrayData[endPtr]==')' && arrayData[endPtr-1]!='\\' && arrayData[endPtr-2]!='\\') || 
                            (arrayData[endPtr]==')' && arrayData[endPtr-1]=='\\' && arrayData[endPtr-2]=='\\'))
                            break;
                        
                        //   System.out.println((char)arrayData[endPtr]);
                        endPtr++;
                        }
                    }else{

                        if(arrayData[endPtr]==10 || arrayData[endPtr]==13 || arrayData[endPtr]==32 || arrayData[endPtr]==47)
                            charIsSpace=true;
                        else
                            charIsSpace=false;


                        if(lastCharIsSpace && !charIsSpace ){
                            if(type==PdfDictionary.VALUE_IS_MIXED_ARRAY && arrayData[endPtr]=='R' && arrayData[endPtr-1]!='/'){ //adjust so returns correct count  /R and  on 12 0 R
                                elementCount--;
                            //	System.out.println("XX");
                            }else {
                                elementCount++;
                            //    System.out.println("---------------------");
                            }
                        }


                        lastCharIsSpace=charIsSpace;
                    }
                }

				endPtr++;
			}

			//if(debugArray)
			//	System.out.println(paddingString+"Number of elements="+elementCount);

            if(elementCount==0 && debugArray){
				System.out.println(paddingString+"zero elements found!!!!!!");
				//System.exit(1);
			}
		}
		
		if(ignoreRecursion && !alwaysRead)
			return endPtr;

		//now create array and read values
		float[] floatValues=null;
		int[] intValues=null;
		double[] doubleValues=null;
		byte[][] mixedValues=null;
		byte[][] keyValues=null;
		byte[][] stringValues=null;
        boolean[] booleanValues=null;
        if(type==PdfDictionary.VALUE_IS_FLOAT_ARRAY)
			floatValues=new float[elementCount];
		else if(type==PdfDictionary.VALUE_IS_INT_ARRAY)
			intValues=new int[elementCount];
        else if(type==PdfDictionary.VALUE_IS_BOOLEAN_ARRAY)
			booleanValues=new boolean[elementCount];
        else if(type==PdfDictionary.VALUE_IS_DOUBLE_ARRAY)
			doubleValues=new double[elementCount];
		else if(type==PdfDictionary.VALUE_IS_MIXED_ARRAY)
			mixedValues=new byte[elementCount][];
		else if(type==PdfDictionary.VALUE_IS_KEY_ARRAY)
			keyValues=new byte[elementCount][];
		else if(type==PdfDictionary.VALUE_IS_STRING_ARRAY)
			stringValues=new byte[elementCount][];

		//System.out.println("j2="+j2+" At="+(char)arrayData[j2]+(char)arrayData[j2+1]
		//                +(char)arrayData[j2+2]+(char)arrayData[j2+3]+(char)arrayData[j2+4]);
		/**
		 * read all values and convert
		 */
		
		//if(debugArray)
		//	System.out.println(paddingString+"----Set elements");
		
		//trap null
		if(arrayData[j2]=='n' && arrayData[j2+1]=='u' &&
				arrayData[j2+2]=='l' && arrayData[j2+3]=='l'){
			j2=j2+3;
			keyValues[0]=null;
		}else{///read values
		 while(arrayData[j2]!=93){

			if(endPtr>-1 && j2>=endPtr)
			break;

			 //move cursor to start of text
			 while(arrayData[j2]==10 || arrayData[j2]==13 || arrayData[j2]==32 || arrayData[j2]==47)
				 j2++;

			 keyStart=j2;
			 keyLength=0;

			 //if(debugArray)
			//	 System.out.print("j2="+j2+" value=");

			 boolean isKey=arrayData[j2-1]=='/';
			 
			 //move cursor to end of text
			 if(type==PdfDictionary.VALUE_IS_KEY_ARRAY){

				 while(arrayData[j2]!='R' && arrayData[j2]!=']'){

                     //allow for embedded object
                     if(arrayData[j2]=='<' && arrayData[j2+1]=='<'){
                         int levels=1;

                         if(debugArray)
                         System.out.println("Reading Direct value");

                         while(levels>0){
                            j2++;
					        keyLength++;

                            if(arrayData[j2]=='<' && arrayData[j2+1]=='<'){
                                j2++;
					            keyLength++;
                                levels++;
                            }else if(arrayData[j2]=='>' && arrayData[j2+1]=='>'){
                                j2++;
					            keyLength++;
                                levels--;
                            }
                         }
                         break;
                     }


                     if(isKey && PDFkeyInt==PdfDictionary.TR && arrayData[j2+1]==' ')
						 break;
					 
					 //if(debugArray)
					//	 System.out.print((char)arrayData[j2]);
				 
					 j2++;
					 keyLength++;
				 }
				 j2++;
				 keyLength++;
			 }else{

                 // handle (string)
                 if(arrayData[j2]=='('){
                     while(true){
                         if((arrayData[j2]==')' && arrayData[j2-1]!='\\' && arrayData[j2-2]!='\\') ||
                         (arrayData[j2]==')' && arrayData[j2-1]=='\\' && arrayData[j2-2]=='\\'))
                         break;

                     // while(arrayData[j2]!=')' && (arrayData[j2-1]!='\\' || arrayData[j2-2]!='\\')){
                           // System.out.println((char)arrayData[endPtr]);
                            j2++;
                          keyLength++;
                    }
                 }else{

                     while(arrayData[j2]!=10 && arrayData[j2]!=13 && arrayData[j2]!=32 && arrayData[j2]!=93 && arrayData[j2]!=47){

                         if(arrayData[j2]==62 && arrayData[j2+1]==62)
                             break;

                         //if(debugArray)
                        //	 System.out.print((char)arrayData[j2]);

                         j2++;
                         keyLength++;
                     }
                 }
             }

			 //if(debugArray)
			//	 System.out.print("<"+currentElement+"/"+elementCount+"\n");


			 //actual value or first part of ref
			 if(type==PdfDictionary.VALUE_IS_FLOAT_ARRAY)
				 floatValues[currentElement]=parseFloat(keyStart,j2, arrayData);
			 else if(type==PdfDictionary.VALUE_IS_INT_ARRAY)
				 intValues[currentElement]=parseInt(keyStart,j2, arrayData);
             else if(type==PdfDictionary.VALUE_IS_BOOLEAN_ARRAY){
                 if(raw[keyStart]=='t' && raw[keyStart+1]=='r' && raw[keyStart+2]=='u' && raw[keyStart+3]=='e')
                 booleanValues[currentElement]=true; //(false id default if not set)
             }else if(type==PdfDictionary.VALUE_IS_DOUBLE_ARRAY)
				 doubleValues[currentElement]=parseFloat(keyStart,j2, arrayData);
			 else{

				 //include / so we can differentiate /9 and 9
				 if(keyStart>0 && arrayData[keyStart-1]==47)
					 keyStart--;

				 int len=j2-keyStart;

				 byte[] newValues=new byte[len];
				 System.arraycopy(arrayData, keyStart, newValues, 0, len);

				 if(debugArray)
				    System.out.println(paddingString+"value="+new String(newValues));
					 
				 if(type==PdfDictionary.VALUE_IS_MIXED_ARRAY)
					 mixedValues[currentElement]=newValues;
				 else if(type==PdfDictionary.VALUE_IS_KEY_ARRAY)
					 keyValues[currentElement]=newValues;
				 else if(type==PdfDictionary.VALUE_IS_STRING_ARRAY)
					 stringValues[currentElement]=newValues;
				 
			 }

			 currentElement++;

			 if(currentElement==elementCount)
				 break;


		 }
		}

		 //put cursor in correct place (already there if ref)
		 if(!isIndirect)
			 i=j2;

		 //set value
		 if(type==PdfDictionary.VALUE_IS_FLOAT_ARRAY)
			 pdfObject.setFloatArray(PDFkeyInt,floatValues);
		 else if(type==PdfDictionary.VALUE_IS_INT_ARRAY)
			 pdfObject.setIntArray(PDFkeyInt,intValues);
         else if(type==PdfDictionary.VALUE_IS_BOOLEAN_ARRAY)
			 pdfObject.setBooleanArray(PDFkeyInt,booleanValues);
         else if(type==PdfDictionary.VALUE_IS_DOUBLE_ARRAY)
			 pdfObject.setDoubleArray(PDFkeyInt,doubleValues);
		 else if(type==PdfDictionary.VALUE_IS_MIXED_ARRAY)
			 pdfObject.setMixedArray(PDFkeyInt,mixedValues);
		 else if(type==PdfDictionary.VALUE_IS_KEY_ARRAY)
			 pdfObject.setKeyArray(PDFkeyInt,keyValues);
		 else if(type==PdfDictionary.VALUE_IS_STRING_ARRAY)
			 pdfObject.setStringArray(PDFkeyInt,stringValues);

		 if(debugArray)  {
			 String values="[";

			 if(type==PdfDictionary.VALUE_IS_FLOAT_ARRAY){
				 int count=floatValues.length;
				 for(int jj=0;jj<count;jj++)
					 values=values+floatValues[jj]+" ";

			 }else if(type==PdfDictionary.VALUE_IS_DOUBLE_ARRAY){
				 int count=doubleValues.length;
				 for(int jj=0;jj<count;jj++)
					 values=values+doubleValues[jj]+" ";

			 }else if(type==PdfDictionary.VALUE_IS_INT_ARRAY){
				 int count=intValues.length;
				 for(int jj=0;jj<count;jj++)
					 values=values+intValues[jj]+" ";

             }else if(type==PdfDictionary.VALUE_IS_BOOLEAN_ARRAY){
                  int count=booleanValues.length;
                  for(int jj=0;jj<count;jj++)
                      values=values+booleanValues[jj]+" ";

             }else if(type==PdfDictionary.VALUE_IS_MIXED_ARRAY){
				 int count=mixedValues.length;
				 for(int jj=0;jj<count;jj++)
					 values=values+new String(mixedValues[jj])+" ";

			 }else if(type==PdfDictionary.VALUE_IS_KEY_ARRAY){
				 int count=keyValues.length;
				 for(int jj=0;jj<count;jj++){
					 if(keyValues[jj]==null)
						 values=values+"null ";
					 else
						 values=values+new String(keyValues[jj])+" ";
				 }
			 }else if(type==PdfDictionary.VALUE_IS_STRING_ARRAY){
				 int count=stringValues.length;
				 for(int jj=0;jj<count;jj++){
					 if(stringValues[jj]==null)
						 values=values+"null ";
					 else
						 values=values+new String(stringValues[jj])+" ";
				 }
			 }

			 values=values+" ]";

			 System.out.println(paddingString+pdfObject+" set as Array "+values+" in "+pdfObject);
		 }


		 //roll back so loop works if no spaces
		if(raw[i]==47 || raw[i]==62)
			 i--;

		 return i;
	}

	//@speed - debug code
	public static boolean checkStreamsIdentical(byte[] newStream, byte[] oldStream) {

		
		boolean failed=false;
		try{
			if(newStream==null && oldStream==null){
			}else{

				int newLength=newStream.length;
				int oldLength=oldStream.length;

				if(newLength!=oldLength){
					System.out.println("=========old length="+oldLength+"< new length="+newLength+"<");
					//System.exit(1);
				}

				int cc=oldLength;
				if(newLength<oldLength)
					cc=newLength;
				
				
				for(int aa=0;aa<cc;aa++){

					if(newStream[aa]!=oldStream[aa]){
						System.out.println(aa+" Difference new="+newStream[aa]+" old="+oldStream[aa]);
						//System.exit(1);
						failed=true;
					}
				}

			}
		}catch(Exception ee){

			System.err.println("old="+oldStream);
			System.err.println("new="+newStream);
			ee.printStackTrace();
			System.exit(1);
		}
		
		return failed;
	}

	//@speed - debug code
	public static void checkStringsIdentical(String baseFont, String baseFont2) {

		try{
			if(baseFont2==null && baseFont==null){
			}else if(!baseFont2.equals(baseFont)){
				System.out.println("old="+baseFont+"<\nnew="+baseFont2+"<");

				if(!baseFont.endsWith(" R")) //allow for bug in old cod eof object ref as name
					System.exit(1);

			}
		}catch(Exception ee){
			System.err.println("In exception old value="+baseFont+"< new value="+baseFont2+"<");
			ee.printStackTrace();
			System.exit(1);
		}
	}

	//@speed - debug code
	public static void checkNumbersIdentical(float baseFont, float baseFont2) {

		float diff=baseFont-baseFont2;
		if(diff<0)
			diff=-diff;

		if(diff>0.001f){
			System.out.println("=========old value="+baseFont+"< new value="+baseFont2+"<");
			System.exit(1);
		}

	}

	//@speed - debug code
	public static void checkFloatArraysIdentical(float[] old,float[] newF) {

		if(old==null && newF==null)
			return;

		//System.out.println(baseFont+" "+baseFont2);
		try{
			int oldlength=old.length;
			int newlength=newF.length;

			if(oldlength!=newlength){
				System.out.println("Different lengths "+oldlength+" "+newlength);
				System.exit(1);
			}

			for(int aa=0;aa<oldlength;aa++){
				float diff=old[aa]-newF[aa];

				if(diff<0)
					diff=-diff;

				if(diff>0.0001f){
					System.out.println("Significant Difference in floats");
					System.out.println("--------------------------------");
					for(aa=0;aa<oldlength;aa++)
						System.out.println(old[aa]+" "+newF[aa]);

					System.exit(1);
				}
			}

		}catch(Exception ee){
			System.err.println("old="+old);
			System.err.println("new="+newF);
			ee.printStackTrace();
			System.exit(1);
		}
	}

    //@speed - debug code
	public static void checkBooleanArraysIdentical(boolean[] old,boolean[] newF) {

		if(old==null && newF==null)
			return;

		//System.out.println(baseFont+" "+baseFont2);
		try{
			int oldlength=old.length;
			int newlength=newF.length;

			if(oldlength!=newlength){
				System.out.println("Different lengths "+oldlength+" "+newlength);
				System.exit(1);
			}

			for(int aa=0;aa<oldlength;aa++){
				if(old[aa]!=newF[aa]){
					System.out.println("Significant Difference in Boolean");
					System.out.println("--------------------------------");
					for(aa=0;aa<oldlength;aa++)
						System.out.println(old[aa]+" "+newF[aa]);

					System.exit(1);
				}
			}

		}catch(Exception ee){
			System.err.println("old="+old);
			System.err.println("new="+newF);
			ee.printStackTrace();
			System.exit(1);
		}
	}

    //@speed - debug code
	public static void checkIntArraysIdentical(int[] old,int[] newF) {

		if(old==null && newF==null)
			return;

		//System.out.println(baseFont+" "+baseFont2);
		try{
			int oldlength=old.length;
			int newlength=newF.length;

			if(oldlength!=newlength){
				System.out.println("Different lengths "+oldlength+" "+newlength);
				System.exit(1);
			}

			for(int aa=0;aa<oldlength;aa++){
				if(old[aa]!=newF[aa]){
					System.out.println("Difference in Int");
					System.out.println("--------------------------------");
					for(aa=0;aa<oldlength;aa++)
						System.out.println(old[aa]+" "+newF[aa]);

					System.exit(1);
				}
			}

		}catch(Exception ee){
			System.err.println("old="+old);
			System.err.println("new="+newF);
			ee.printStackTrace();
			System.exit(1);
		}
	}

	//@speed - debug code
	public static void checkDoubleArraysIdentical(double[] old,double[] newF) {

		if(old==null && newF==null)
			return;

		//System.out.println(baseFont+" "+baseFont2);
		try{
			int oldlength=old.length;
			int newlength=newF.length;

			if(oldlength!=newlength){
				System.out.println("Different lengths "+oldlength+" "+newlength);
				System.exit(1);
			}

			for(int aa=0;aa<oldlength;aa++){
				double diff=old[aa]-newF[aa];

				if(diff<0)
					diff=-diff;

				if(diff>0.0001d){
					System.out.println("Significant Difference in doubles "+old[aa]+" "+newF[aa]);
					System.exit(1);
				}
			}

		}catch(Exception ee){
			ee.printStackTrace();
			System.exit(1);
		}
	}

	/**read a stream*/
	final public byte[] readStream(Map objData,String objectRef,boolean cacheValue,
			boolean decompress,boolean keepRaw, boolean isMetaData, boolean isCompressedStream)  {

		Object data=objData.get("DecodedStream");

		BufferedOutputStream streamCache=null;
		byte[] stream;
		String cacheName=null;

		boolean isCachedOnDisk = false;

		//decompress first time
		if(data==null){
			stream=(byte[]) objData.get("Stream");

			isCachedOnDisk=objData.get("startStreamOnDisk")!=null &&
			endStreamPointer - startStreamPointer >= 0;

			if(isCachedOnDisk){
				try{
					/**write to disk raw data*/
					File tempFile=File.createTempFile("jpedal",".bin");
					cacheName=tempFile.getAbsolutePath();
					cachedObjects.put(cacheName,"x");
					streamCache=new BufferedOutputStream(new FileOutputStream(tempFile));

					int buffer=8192;
					byte[] bytes;
					int ptr=startStreamPointer,remainingBytes;
					while(true){

						//handle last n bytes of object correctly
						remainingBytes=1+endStreamPointer-ptr;

						if(remainingBytes<buffer)
							buffer=remainingBytes;
						bytes = new byte[buffer];

						//get bytes into buffer
						this.movePointer(ptr);
						this.pdf_datafile.read(bytes);

						//spool to disk
						streamCache.write(bytes);

						ptr=ptr+buffer;
						if(ptr>=endStreamPointer)
							break;
					}
					streamCache.close();

					File tt=new File(cacheName);

				}catch(Exception e){
					e.printStackTrace();
				}

				//decrypt the stream
				try{
					if(!isCompressedStream && (isMetaDataEncypted || !isMetaData))
						decrypt(null,objectRef, false,cacheName, false,false);
				}catch(Exception e){
					e.printStackTrace();
					stream=null;
					LogWriter.writeLog("Exception "+e);

				}

				objData.put("CachedStream",cacheName);
			}
				
			if(stream!=null){ /**decode and save stream*/

				//decrypt the stream
				try{
					if(!isCompressedStream && (isMetaDataEncypted || !isMetaData))
						stream=decrypt(stream,objectRef, false,null,false,false);
				}catch(Exception e){
					e.printStackTrace();
					stream=null;
					LogWriter.writeLog("Exception "+e);
				}
			}
				

			if(keepRaw)
				objData.remove("Stream");

			int length=1;

			if(stream!=null || isCachedOnDisk){

				//values for CCITTDecode
				int height=1,width=1;
				String value=(String) objData.get("Height");
				if(value!=null){

					//allow for object
					if(value.indexOf('R')!=-1){
						Map heightObj=this.readObject(new PdfObject(value), value,false,null);

						Object indirectHeight=heightObj.get("rawValue");
						if(indirectHeight!=null)
							value=(String) indirectHeight;
					}

					height = Integer.parseInt(value);
				}

				value=(String) objData.get("Width");
				if(value!=null)
					width = Integer.parseInt(value);

				value= getValue((String)objData.get("Length"));
				if(value!=null)
					length= Integer.parseInt(value);

				/**allow for no width or length*/
				if(height*width==1)
					width=length;

				String filter = this.getValue((String) objData.get("Filter"));
				if (filter != null && !filter.startsWith("/JPXDecode") && !filter.startsWith("/DCT")){

					try{

						//ensure ref converted first
						Object param = objData.get("DecodeParms");
						if(param!=null && param instanceof String){
							String ref=(String) param;
							if(ref.endsWith(" R")){
								Map paramObj=this.readObject(new PdfObject(ref), ref,false,null);
								objData.put("DecodeParms",paramObj);
							}
						}

						stream =decodeFilters(stream, filter, objData.get("DecodeParms"),width,height,true,cacheName);

					}catch(Exception e){
						LogWriter.writeLog("[PDF] Problem "+e+" decompressing stream "+filter);
						stream=null;
						isCachedOnDisk=false; //make sure we return null, and not some bum values
					}

					//stop spurious match down below in caching code
					length=1;
				}else if(stream!=null && length!=1 && length<stream.length ){

					/**make sure length correct*/
					if(stream.length!=length){
						byte[] newStream=new byte[length];
						System.arraycopy(stream, 0, newStream, 0, length);

						stream=newStream;
					}
				}
			}

			if(stream!=null && cacheValue)
				objData.put("DecodedStream",stream);

			if((decompress)&&(isCachedOnDisk)){
				int streamLength = (int) new File(cacheName).length();

				byte[] bytes = new byte[streamLength];

				try {
					new BufferedInputStream(new FileInputStream(cacheName)).read(bytes);
				} catch (Exception e) {
					e.printStackTrace();
				}

				/**resize if length supplied*/
				if((length!=1)&&(length<streamLength)){

					/**make sure length correct*/
					byte[] newStream=new byte[length];
					System.arraycopy(bytes, 0, newStream, 0, length);

					bytes=newStream;

				}

				if(debugCaching){
					if(bytes.length!=stream.length){

						System.out.println("Problem with sizes in readStream "+bytes.length+ ' ' +stream.length);
						System.exit(1);
					}
				}

				return bytes;
			}

		}else
			stream=(byte[]) data;

		return  stream;
	}


	/**read a stream*/
	final public byte[] readStream(PdfObject pdfObject, boolean cacheValue,
			boolean decompress,boolean keepRaw, boolean isMetaData, boolean isCompressedStream)  {

		final boolean debugStream=false;

		byte[] data=pdfObject.DecodedStream;
		
		BufferedOutputStream streamCache=null;
		byte[] stream;
		String cacheName=null;
		boolean isCachedOnDisk = false;

		//decompress first time
		if(data==null){

			String objectRef=pdfObject.getObjectRefAsString();
			
			stream=pdfObject.stream;

			if(debugStream){
				System.out.println("raw data="+stream);
				if(stream!=null)
					System.out.println("length="+stream.length);
			}
			
			isCachedOnDisk=pdfObject.startStreamOnDisk!=-1 && endStreamPointer - startStreamPointer >= 0;
			if(isCachedOnDisk){
				try{
					/**write to disk raw data*/
					File tempFile=File.createTempFile("jpedal",".bin");
					cacheName=tempFile.getAbsolutePath();
					cachedObjects.put(cacheName,"x");
					streamCache=new BufferedOutputStream(new FileOutputStream(tempFile));

					int buffer=8192;
					byte[] bytes;
					int ptr=startStreamPointer,remainingBytes;
					while(true){

						//handle last n bytes of object correctly
						remainingBytes=1+endStreamPointer-ptr;

						if(remainingBytes<buffer)
							buffer=remainingBytes;
						bytes = new byte[buffer];

						//get bytes into buffer
						this.movePointer(ptr);
						this.pdf_datafile.read(bytes);

						//spool to disk
						streamCache.write(bytes);

						ptr=ptr+buffer;
						if(ptr>=endStreamPointer)
							break;
					}
					streamCache.close();

					//File tt=new File(cacheName);

				}catch(Exception e){
					e.printStackTrace();
				}

				//decrypt the stream
				try{
					if(!isCompressedStream && (isMetaDataEncypted || !isMetaData))
						decrypt(null,objectRef, false,cacheName, false,false);
				}catch(Exception e){
					e.printStackTrace();
					stream=null;
					LogWriter.writeLog("Exception "+e);
				}

				pdfObject.CachedStream=cacheName;
			}

			if(stream!=null){ /**decode and save stream*/

				//decrypt the stream
				try{
                    if(!isCompressedStream && (isMetaDataEncypted || !isMetaData))
						stream=decrypt(stream,objectRef, false,null,false,false);
				}catch(Exception e){
					e.printStackTrace();
					stream=null;
					LogWriter.writeLog("Exception "+e);
				}
			}

			if(keepRaw)
				pdfObject.stream=null;

			int length=1;

			if(stream!=null || isCachedOnDisk){

				//values for CCITTDecode
				int height=1,width=1;

				int newH=pdfObject.getInt(PdfDictionary.Height);
				if(newH!=-1)
					height=newH;

				int newW=pdfObject.getInt(PdfDictionary.Width);
				if(newW!=-1)
					width=newW;

				int newLength=pdfObject.getInt(PdfDictionary.Length);
				if(newLength!=-1)
					length=newLength;

				/**allow for no width or length*/
				if(height*width==1)
					width=length;

				PdfObject DecodeParms= pdfObject.getDictionary(PdfDictionary.DecodeParms);
				
				PdfArrayIterator filters = pdfObject.getArrayIterator(PdfDictionary.Filter);

				//check not handled elsewhere
				int firstValue=PdfDictionary.Unknown;
				if(filters!=null && filters.hasMoreTokens())
					firstValue=filters.getNextValueAsConstant(false);

				if(debugStream)
					System.out.println("First filter="+firstValue);
				
				if (filters != null && firstValue!=PdfDictionary.Unknown && firstValue!=PdfFilteredReader.JPXDecode && 
						firstValue!=PdfFilteredReader.DCTDecode){

					if(debugStream)
						System.out.println("Decoding stream");
                    try{
                        
                        byte[] globalData=null;//used by JBIG but needs to be read now so we can decode
                        if(DecodeParms!=null){
                        	PdfObject Globals=DecodeParms.getDictionary(PdfDictionary.JBIG2Globals);
                        	if(Globals!=null)
                        		globalData=this.readStream(Globals,true,true,false, false,false);
                        }
						stream =decodeFilters(DecodeParms, stream, filters ,width,height,true,globalData, cacheName);

					}catch(Exception e){
						LogWriter.writeLog("[PDF] Problem "+e+" decompressing stream ");
						stream=null;
						isCachedOnDisk=false; //make sure we return null, and not some bum values
					}

					//stop spurious match down below in caching code
					length=1;
				}else if(stream!=null && length!=1 && length<stream.length ){

					/**make sure length correct*/
					if(stream.length!=length){
						byte[] newStream=new byte[length];
						System.arraycopy(stream, 0, newStream, 0, length);

						stream=newStream;
					}
				}
			}

			
			if(stream!=null && cacheValue)
				pdfObject.DecodedStream=stream;
			
			if(decompress && isCachedOnDisk){
				int streamLength = (int) new File(cacheName).length();

				byte[] bytes = new byte[streamLength];

				try {
					new BufferedInputStream(new FileInputStream(cacheName)).read(bytes);
				} catch (Exception e) {
					e.printStackTrace();
				}

				/**resize if length supplied*/
				if((length!=1)&&(length<streamLength)){

					/**make sure length correct*/
					byte[] newStream=new byte[length];
					System.arraycopy(bytes, 0, newStream, 0, length);

					bytes=newStream;

				}

				if(debugCaching){
					if(bytes.length!=stream.length){

						System.out.println("Problem with sizes in readStream "+bytes.length+ ' ' +stream.length);
						System.exit(1);
					}
				}
				
				return bytes;
			}

		}else
			stream=data;

		if(stream==null)
			return null;
		
		//make a a DEEP copy so we cant alter
		int len=stream.length;
		byte[] copy=new byte[len];
		System.arraycopy(stream, 0, copy, 0, len);
		
		return  copy;
	}

	/**read object with stream offsets and return String of full path*/
	final public String getStreamOnDisk(String ref)  {

		int cacheSetting=miniumumCacheSize;

		this.miniumumCacheSize=0;

		Map obj= readObject(new PdfObject(ref), ref,false, null);

		readStream((String)ref,true);

		miniumumCacheSize=cacheSetting;

		if(obj==null)
			return null;
		else
			return (String) obj.get("CachedStream");

	}

	/**return size of PDF object if uncompressed or compressed block if compressed in bytes
	 *  -1 if not calculated
    public int getObjectSize(String objectRef) {

        int size=-1;

        if(!refTableInvalid){

            boolean isCompressed=isCompressed(objectRef);

            if(objectRef.endsWith(" R")){

                //any stream
                byte[] stream=null,raw=null;

                //read raw object data
                if(isCompressed){

                    int objectID=Integer.parseInt(objectRef.substring(0,objectRef.indexOf(' ')));
                    int compressedID=getCompressedStreamObject(objectRef);
                    String compressedRef=compressedID+" 0 R",startID=null;
                    Map compressedObject,offsetStart=lastOffsetStart,offsetEnd=lastOffsetEnd;
                    int First=lastFirst;
                    byte[] compressedStream;
                    boolean isCached=true; //assume cached

                    //see if we already have values
                    compressedStream=lastCompressedStream;
                    if(lastOffsetStart!=null)
                        startID=(String) lastOffsetStart.get(String.valueOf(objectID));

                    //read 1 or more streams
                    while(startID==null){
                        isCached=false;

                        try{
                            movePointer(compressedRef);
                        }catch(Exception e){
                            LogWriter.writeLog("Exception moving pointer to "+objectRef);
                        }

                        raw = readObjectData(this.ObjLengthTable[compressedID]);

                        size=ObjLengthTable[compressedID];

                        compressedObject=new HashMap();

                        convertObjectBytesToMap(compressedObject, pdfObject, objectRef,false, new HashMap(), false, false, stream, raw,false);

                        //get offsets table see if in this stream
                        offsetStart=new HashMap();
                        offsetEnd=new HashMap();
                        First=Integer.parseInt((String) compressedObject.get("First"));
                        compressedStream=this.readStream(compressedObject,objectRef,true,true,false, false,true);

                        extractCompressedObjectOffset(offsetStart, offsetEnd,First, compressedStream);

                        startID=(String) offsetStart.get(String.valueOf(objectID));

                        compressedRef=(String) compressedObject.get("Extends");

                    }

                    if(!isCached){
                        lastCompressedStream=compressedStream;
                        lastOffsetStart=offsetStart;
                        lastOffsetEnd=offsetEnd;
                        lastFirst=First;
                    }


                }else{

                    int pointer=objectRef.indexOf(' ');
                    int id=Integer.parseInt(objectRef.substring(0,pointer));

                    size=ObjLengthTable[id];

                }
            }

            if(size==0)
                size=-1;
        }

        return size;
    }  /**/

	/**read a stream*/
	final public byte[] readStream(String ref,boolean decompress)  {

		Map currentValues=readObject(new PdfObject(ref), ref,false, null);

		return readStream(currentValues,ref,true,decompress,false, false,false);
	}

    /**
	 * stop cache of last object in readObject
	 *
	 */
	final public void flushObjectCache(){
		lastRef=null;
	}

    final public void resetCache(){
        lastRef=null;
    }

    /**
	 * read an object in the pdf into a Map which can be an indirect or an object
	 *
	 */
	final synchronized public Map readObject(PdfObject pdfObject, String objectRef, boolean isEncryptionObject, Map textFields)  {

		/**return if last read otherwise read*/
		if(lastRef!=null && objectRef!=null && objectRef.equals(lastRef) && pdfObject.isImplemented()==PdfObject.NO){
			return objData;
		}else{

			//not cached if we read just PdfObject (needed as hack for Type3) 
			if(pdfObject.isImplemented()!=PdfObject.FULL)
				lastRef=objectRef;

			boolean debug=false,preserveTextString=false;
			objData=new HashMap();

			//set flag to extract raw text string
			if((textFields!=null)){
				preserveTextString=true;
			}else
				preserveTextString=false;

			if(debug)
				System.err.println("reading objectRef="+objectRef+"< isCompressed="+isCompressed(objectRef));

			boolean isCompressed=isCompressed(objectRef);
            pdfObject.setCompressedStream(isCompressed);

            if(objectRef.endsWith(" R")){

				//any stream
				byte[] stream=null,raw=null;

				/**read raw object data*/
				if(isCompressed){

					int objectID=Integer.parseInt(objectRef.substring(0,objectRef.indexOf(' ')));
					int compressedID=getCompressedStreamObject(objectRef);
					String compressedRef=compressedID+" 0 R",startID=null;
					Map compressedObject,offsetStart=lastOffsetStart,offsetEnd=lastOffsetEnd;
					int First=lastFirst;
					byte[] compressedStream;
					boolean isCached=true; //assume cached

					//see if we already have values
					compressedStream=lastCompressedStream;
					if(lastOffsetStart!=null)
						startID=(String) lastOffsetStart.get(String.valueOf(objectID));

					//read 1 or more streams
					while(startID==null){
						isCached=false;
						try{
							movePointer(compressedRef);
						}catch(Exception e){
							LogWriter.writeLog("Exception moving pointer to "+objectRef);
						}

						raw = readObjectData(this.ObjLengthTable[compressedID]);
						compressedObject=new HashMap();

						convertObjectBytesToMap(compressedObject, compressedRef,isEncryptionObject, textFields, debug, preserveTextString, stream, raw,false);

						/**get offsets table see if in this stream*/
						offsetStart=new HashMap();
						offsetEnd=new HashMap();
						First=Integer.parseInt((String) compressedObject.get("First"));

						compressedStream=this.readStream(compressedObject,compressedRef,true,true,false, false,false);

						extractCompressedObjectOffset(offsetStart, offsetEnd,First, compressedStream);

						startID=(String) offsetStart.get(String.valueOf(objectID));

						compressedRef=(String) compressedObject.get("Extends");

					}

					if(!isCached){
						lastCompressedStream=compressedStream;
						lastOffsetStart=offsetStart;
						lastOffsetEnd=offsetEnd;
						lastFirst=First;
					}

					/**put bytes in stream*/
					int start=First+Integer.parseInt(startID),end=compressedStream.length;

					String endID=(String) offsetEnd.get(String.valueOf(objectID));
					if(endID!=null)
						end=First+Integer.parseInt(endID);
					
					int streamLength=end-start;
					raw = new byte[streamLength];
					System.arraycopy(compressedStream, start, raw, 0, streamLength);

				}else{
					try{
						movePointer(objectRef);
					}catch(Exception e){
						LogWriter.writeLog("Exception moving pointer to "+objectRef);
					}
					int pointer=objectRef.indexOf(' ');
					int id=Integer.parseInt(objectRef.substring(0,pointer));

					if(isEncryptionObject || refTableInvalid)
						raw=readObjectData(-1);
					else if(id>ObjLengthTable.length || ObjLengthTable[id]==0){
						LogWriter.writeLog(objectRef+ " cannot have offset 0");
						raw=new byte[0];
					}else
						raw = readObjectData(ObjLengthTable[id]);

//					if(objectRef.equals("241 0 R"))
//					System.exit(1);
				}

				if(debug)
					System.out.println("convertObjectsToMap");

				if(startStreamPointer!=-1 || raw.length>1){

					try{

                        if(pdfObject.isImplemented()!=PdfObject.NO)
                            readDictionaryAsObject(pdfObject, objectRef,0,raw, -1, "", false);

					}catch(Exception e){
						e.printStackTrace();
					}

                    //@speed
                    if(pdfObject.isImplemented()!=PdfObject.FULL)
                        convertObjectBytesToMap(objData, objectRef,isEncryptionObject, textFields, debug, preserveTextString, stream, raw,isCompressed);
				}
				if(debug)
					System.out.println("converted");
			}else{
	
				byte[] bytes=objectRef.getBytes();

				if(bytes.length>0)
					readDictionary(objectRef,1,objData,0,bytes,isEncryptionObject,textFields,-1);

				LogWriter.writeLog("Direct object read "+objectRef+"<<");

			}

			if(debug)
				System.out.println("object read");

			return objData;
		}
	}

	
	/**
	 * get object as byte[]
	 * @param objectRef is only needed if compressed
	 * @param isEncryptionObject
	 * @param isCompressed
	 * @param objectID
	 * @param gen
	 * @return
	 */
	private byte[] readObjectAsByteArray(String objectRef,boolean isEncryptionObject, boolean isCompressed,int objectID, int gen) {

		byte[] raw;

		//any stream
		byte[] stream=null;

		/**read raw object data*/
		if(isCompressed){

			int compressedID=getCompressedStreamObject(objectID,gen);
			String startID=null;
			Map compressedObject,offsetStart=lastOffsetStart,offsetEnd=lastOffsetEnd;
			int First=lastFirst;
			byte[] compressedStream;
			boolean isCached=true; //assume cached

			//see if we already have values
			compressedStream=lastCompressedStream;
			if(lastOffsetStart!=null)
				startID=(String) lastOffsetStart.get(String.valueOf(objectID));

			//read 1 or more streams
			while(startID==null){
				isCached=false;
				try{
					movePointer(compressedID,0);
				}catch(Exception e){
					LogWriter.writeLog("Exception moving pointer to "+objectID);
				}

				raw = readObjectData(this.ObjLengthTable[compressedID]);
				compressedObject=new HashMap();

                //@speed - convert to Dictionary
				convertObjectBytesToMap(compressedObject, objectRef,isEncryptionObject, null, false, false, stream, raw,false);

				/**get offsets table see if in this stream*/
				offsetStart=new HashMap();
				offsetEnd=new HashMap();
				First=Integer.parseInt((String) compressedObject.get("First"));

                if(isEncrypted){
                    byte[] bytes=((byte[])compressedObject.get("Stream"));

                    try{
                        bytes=decrypt(bytes,compressedID+" 0 R", false,null,false,false);
                    }catch(Exception ee){

                        ee.printStackTrace();
                    }
                    compressedObject.put("Stream",bytes);

                }
                
                compressedStream=readStream(compressedObject,objectRef,true,true,false,false,true);

				extractCompressedObjectOffset(offsetStart, offsetEnd,First, compressedStream);

				startID=(String) offsetStart.get(String.valueOf(objectID));

				String compressedRef=(String) compressedObject.get("Extends");
				if(compressedRef!=null)
					compressedID=Integer.parseInt(compressedRef.substring(0,compressedRef.indexOf(" ")));

			}

			if(!isCached){
				lastCompressedStream=compressedStream;
				lastOffsetStart=offsetStart;
				lastOffsetEnd=offsetEnd;
				lastFirst=First;
			}

			/**put bytes in stream*/
			int start=First+Integer.parseInt(startID),end=compressedStream.length;
			String endID=(String) offsetEnd.get(String.valueOf(objectID));
			if(endID!=null)
				end=First+Integer.parseInt(endID);

			int streamLength=end-start;
			raw = new byte[streamLength];
			System.arraycopy(compressedStream, start, raw, 0, streamLength);

		}else{
			try{
				movePointer(objectID,gen);
			}catch(Exception e){
				LogWriter.writeLog("Exception moving pointer to "+objectRef);
			}

			if(isEncryptionObject || refTableInvalid)
				raw=readObjectData(-1);
			else if(objectID>ObjLengthTable.length)
				return null;
			else
				raw = readObjectData(ObjLengthTable[objectID]);
		}

		return raw;
	}

	/**
	 * @param First
	 * @param compressedStream
	 */
	private void extractCompressedObjectOffset(Map offsetStart, Map offsetEnd,int First, byte[] compressedStream) {

		String lastKey=null,key=null,offset=null;

		final boolean debug=true;
		StringBuffer rawKey=null,rawOffset=null;
		int startKey=0,endKey=0,startOff=0,endOff=0;

		//read the offsets table
		for(int ii=0;ii<First;ii++){

			if(debug){
				rawKey=new StringBuffer();
				rawOffset=new StringBuffer();
			}

			/**work out key size*/
			startKey=ii;
			while(compressedStream[ii]!=32){
				if(debug)
					rawKey.append((char)compressedStream[ii]);
				ii++;
			}
			endKey=ii-1;

			/**extract key*/
			int length=endKey-startKey+1;
			char[] newCommand=new char[length];
			for(int i=0;i<length;i++)
				newCommand[i]=(char)compressedStream[startKey+i];

			key =new String(newCommand);

			/**test key if in debug*/
			if(debug){
				if(!key.equals(rawKey.toString())){
					System.out.println("Different="+key+"<>"+rawKey+ '<');
					System.exit(1);
				}
			}

			/**move to offset*/
			while(compressedStream[ii]==32)
				ii++;

			/**get size*/
			startOff=ii;
			while((compressedStream[ii]!=32)&&(ii<First)){

				if(debug)
					rawOffset.append((char)compressedStream[ii]);

				ii++;
			}
			endOff=ii-1;

			/**extract offset*/
			length=endOff-startOff+1;
			newCommand=new char[length];
			for(int i=0;i<length;i++)
				newCommand[i]=(char)compressedStream[startOff+i];

			offset =new String(newCommand);

			/**test key if in debug*/
			if(debug){
				if(!offset.equals(rawOffset.toString())){
					System.out.println("Different="+offset+"<>"+rawOffset+ '<');
					System.exit(1);
				}
			}

			/**
			 * save values
			 */
			offsetStart.put(key,offset);

			//save end as well
			if(lastKey!=null)
				offsetEnd.put(lastKey,offset);

			lastKey=key;

		}
	}



	private void convertObjectBytesToMap(Map objData, String objectRef, boolean isEncryptionObject, Map textFields,
			boolean debug, boolean preserveTextString,
			byte[] stream, byte[] raw, boolean isCompressed) {

		/**get values*/
		int i = 0;

		ByteArrayOutputStream rawStringAsBytes=new ByteArrayOutputStream();

		char remainderLastChar=' ';

		StringBuffer remainder=new StringBuffer();

        if(!isCompressed){

			//remove the obj start
			while (true) {

                if ((raw[i] == 111)&& (raw[i + 1] == 98)&& (raw[i + 2] == 106))
					break;
				i++;
			}

            i = i + 2;

			//make sure no comment afterwards by rolling onto next CR or < or [ or /
			while(true){

				if(raw[i]==47) //allow for command right after obj
					break;

				i++;
				//System.out.println(i+" "+(char)raw[i]+" "+raw[i]);
				if((raw[i]==10)|(raw[i]==13)|(raw[i]==60)|(raw[i]==91)|(raw[i]==32))
					break;
			}
		}

		if(debug){
			for(int j=i;j<raw.length - 7;j++)
				System.err.print((char)raw[j]);

			System.err.print("<===\n\n");
		}

		//allow for immediate command
		if((raw[i]==47)|(raw[i]==91)) //allow for command or array right after obj
			i--;

		//look for trailer keyword
		while (i < raw.length - 7) {

			i++;

			if(debug)
				System.err.println((char)raw[i]);

			//trap for no endObj
			if(raw[i]=='o' && raw[i+1]=='b' && raw[i+2]=='j')
				break;

			//read a subdictionary
			if ((raw[i] == 60) && ((raw[i + 1] == 60)| (raw[i - 1] == 60))){

				if(raw[i - 1] != 60)
					i++;

				if(debug)
					System.err.println("Read dictionary");
				i=readDictionary(objectRef,1,objData,i,raw,isEncryptionObject,textFields,-1);

				/**handle a stream*/
			}else if ((raw[i] == 115)&& (raw[i + 1] == 116)&& (raw[i + 2] == 114)&& (raw[i + 3] == 101)&& (raw[i + 4] == 97)&& (raw[i + 5] == 109)) {

				if(debug)
					System.err.println("Reading stream");

				//ignore these characters and first return
				i = i + 6;

				if (raw[i] == 13 && raw[i+1] == 10) //allow for double linefeed
					i=i+2;
				else if((raw[i]==10)|(raw[i]==13))
					i++;

				int start = i;

				i--; //move pointer back 1 to allow for zero length stream

				int streamLength=0;
				String setLength=(String)objData.get("Length");
				if(setLength!=null){
					//read indirect
					if(setLength.indexOf(" R")!=-1){
						/**read raw object data*/
						try{
							long currentPos=movePointer(setLength);
							int buffSize=128;
							if(currentPos+buffSize>eof)
								buffSize=(int) (eof-currentPos-1);
							StringBuffer rawChars=new StringBuffer();
							byte[] buf=new byte[buffSize];
							this.pdf_datafile.read(buf);

							int ii=3;

							//find start
							while(true){
								if((ii<buffSize)&&(buf[ii-3]==111)&&(buf[ii-2]==98)&&(buf[ii-1]==106))
									break;
								ii++;
							}

							//find first number
							while(true){
								if((ii<buffSize)&&(Character.isDigit((char)buf[ii])))
									break;
								ii++;
							}

							//read number
							while(true){
								if((ii<buffSize)&&(Character.isDigit((char)buf[ii]))){
									rawChars.append((char)buf[ii]);
									ii++;
								}else
									break;
							}

							movePointer(currentPos);
							setLength=rawChars.toString();

						}catch(Exception e){
							LogWriter.writeLog("Exception moving pointer to "+objectRef);
							setLength=null;
						}
					}

					if(setLength!=null){

						streamLength=Integer.parseInt(setLength);
						
						i=start+streamLength;

						
						if((i<raw.length) && raw[i]==13 && (i+1<raw.length) && raw[i+1]==10)
							i=i+2;

						//check validity
						if ((raw.length>(i+9))&&(raw[i] == 101)&& (raw[i + 1] == 110)&& (raw[i + 2] == 100)&& (raw[i + 3] == 115)&& (raw[i + 4] == 116)
								&& (raw[i + 5] == 114)&& (raw[i + 6] == 101)&& (raw[i + 7] == 97)&& (raw[i + 8] == 109)){

						}else{
							boolean	isValid=false;
							int current=i;
							//check forwards
							if(i<raw.length){
								while(true){
									i++;
									if((isValid)||(i==raw.length))
										break;

									if ((raw[i] == 101)&& (raw[i + 1] == 110)&& (raw[i + 2] == 100)&& (raw[i + 3] == 115)&& (raw[i + 4] == 116)
											&& (raw[i + 5] == 114)&& (raw[i + 6] == 101)&& (raw[i + 7] == 97)&& (raw[i + 8] == 109)){

										//while(raw[i-1]==10 || raw[i-1]==13)
											//	i--;

										streamLength=i-start;
										isValid=true;
									}
								}
							}

							if(!isValid){
								i=current;
								if(i>raw.length)
									i=raw.length;
								//check backwords
								while(true){
									i--;
									if((isValid)||(i<0))
										break;
									if ((raw[i] == 101)&& (raw[i + 1] == 110)&& (raw[i + 2] == 100)&& (raw[i + 3] == 115)&& (raw[i + 4] == 116)
											&& (raw[i + 5] == 114)&& (raw[i + 6] == 101)&& (raw[i + 7] == 97)&& (raw[i + 8] == 109)){
										streamLength=i-start;
										isValid=true;

									}
								}
							}

							if(!isValid)
								i=current;
						}
					}
				}else{

					/**workout length and check if length set*/
					int end;

					while (true) { //find end

						i++;

						if(i==raw.length)
							break;
						if ((raw[i] == 101)&& (raw[i + 1] == 110)&& (raw[i + 2] == 100)&& (raw[i + 3] == 115)&& (raw[i + 4] == 116)
								&& (raw[i + 5] == 114)&& (raw[i + 6] == 101)&& (raw[i + 7] == 97)&& (raw[i + 8] == 109))
							break;

					}

					end=i-1;

					if((end>start))
						streamLength=end-start+1;
				}

				//lose trailing 10s
				if(streamLength>1){
					int ptr=start+streamLength-1;
					if(ptr<raw.length && ptr>0 && raw[ptr]==10){
						streamLength--;
						ptr--;	
					}
				}


				/**
				 * either read stream into object from memory or just save position in Map
				 */
				if((startStreamPointer==-1) ||(debugCaching)){

					if(start+streamLength>raw.length)
						streamLength=raw.length-start;

					stream = new byte[streamLength];
					System.arraycopy(raw, start, stream, 0, streamLength);

                }

				if(startStreamPointer!=-1){

					objData.put("startStreamOnDisk",  new Integer(this.startStreamPointer));
					objData.put("endStreamOnDisk",  new Integer(this.endStreamPointer));

					//debug code
					if(debugCaching){
						try{
							if(start+streamLength>raw.length)
								streamLength=raw.length-start;

							byte[] stream2 = new byte[streamLength];
							System.arraycopy(raw, start, stream2, 0, streamLength);

							int cacheLength=endStreamPointer-startStreamPointer+1;

							//check it matches
							int xx=0;
							for(int jj=this.startStreamPointer;jj<this.endStreamPointer;jj++){
								byte[] buffer = new byte[1];

								/**get bytes into buffer*/
								this.movePointer(jj);
								this.pdf_datafile.read(buffer);

								if(buffer[0]!=stream2[xx]){
									System.out.println("error here");
									System.exit(1);
								}

								xx++;
							}

							if((cacheLength!=streamLength)){//&& (setLength==null)){
								System.out.println("\n");
								System.out.println("lengths cache changed="+cacheLength+" array="+streamLength+" set="+setLength);

								//for(int ii=0;ii<stream2.length;ii++)
								//	System.out.println(ii+" "+stream2[ii]+" "+(char)stream2[ii]);
								System.exit(1);
							}

						}catch(Exception e){
							System.out.println("ERRor in debug code");
							e.printStackTrace();
							System.exit(1);
						}
					}
				}

				i = i + 9; //roll on pointer

			}else if(raw[i]==91){ //handle just a raw array ie [ /Separation /CMAN /DeviceCMYK 8 0 R]
				if(debug)
					System.err.println("read array");

				i=readArray(objectRef,i,objData,raw,isEncryptionObject,textFields);

			}else if((raw[i]!=60)&(raw[i]!=62)){ //direct value

				if(preserveTextString){

					//allow for all escape combinations
					if((raw[i-1]==92)&&(raw[i-2]==92)){
						//stop match on //( or //)
						rawStringAsBytes.write(raw[i]);
					}else if(((raw[i]==40)|(raw[i]==41))&(raw[i-1]!=92)){
						//ignore //
					}else
						rawStringAsBytes.write(raw[i]);
				}

				if(((raw[i]==10)|(raw[i]==13)|(raw[i]==32))){

					if(remainder.length()>0)
						remainder.append(' ');

					remainderLastChar=' ';

				}else{

					/**allow for no spaces in /a/b/c*/
					if((raw[i]==47)&&(remainderLastChar!=' '))
						remainder.append(' ');

					remainderLastChar=(char)raw[i];
					remainder.append(remainderLastChar);
				}
			}

			/**}else if((raw[i]!=60)&(raw[i]!=62)&(raw[i]!=10)&(raw[i]!=13)&(raw[i]!=32)){ //direct value
        			remainder.append((char)raw[i]);				}*/
		}

		/**strip any comment from remainder*/
		if(remainder.length()>0)	{
			int ii=remainder.toString().indexOf('%');
			if(ii>-1)
				remainder.setLength(ii);
		}

		if(remainder.length()>0)	{
			String rawString=remainder.toString().trim();
			if((preserveTextString)&&(rawString.startsWith("("))){
				try {
					rawStringAsBytes.close();

					byte[] streamData=rawStringAsBytes.toByteArray();
					streamData=decrypt(streamData,objectRef, false,null,false,false);
					objData.put("rawValue",streamData); //save pair and reset

				} catch (Exception e) {
					LogWriter.writeLog("Exception "+e+" writing out text string");
				}

			}else{
				if(debug)
					System.err.println("Remainder value="+remainder+"<<");
				objData.put("rawValue",rawString);
			}
		}

		if(stream!=null)
			objData.put("Stream",  stream);
		
		if(debug)
			System.err.println(objData);

		try {
			rawStringAsBytes.close();
		} catch (IOException e) {
			e.printStackTrace();
		}
	}


	/**
	 * read an array
	 */
	private int readArray(String objectRef, int i, Map objData, byte[] raw, boolean isEncryptionObject, Map textFields){


		final boolean debug=false;
		int start=0,end=0;
		boolean maybeKey=false,isSeparation=false;
		//<start-13>
		StringBuffer rawValue=new StringBuffer();
		StringBuffer possKey=new StringBuffer();
		/**<end-13>
        String rawValue="";
        String  possKey="";
        /**/

		boolean containsIndexKeyword=false,convertToHex=false;

		while(true){


			if(debug)
				System.out.println("Raw="+rawValue +"start="+start+" end="+end);

			if(maybeKey){

				int j=i;

				if(debug)
					System.out.println("Poss key char="+(char)raw[j]);

				//find first valid char
				while((raw[j]==13)|(raw[j]==10)|(raw[j]==32))
					j++;

				if(debug)
					System.out.println("now="+(char)raw[j]);
				if((raw[j]==60)&&(raw[j+1]==60)){

					if(debug)
						System.out.println("Poss key");

					i=j;

					if(isSeparation){

						if(debug)
							System.out.println("Store in same level "+possKey);

						//<start-13>
						rawValue.append(possKey);
						rawValue.append(' ');
						/**<end-13>
                        rawValue=rawValue+possKey+" ";
                        /**/
						i=readDictionary(objectRef,1,objData,i,raw,isEncryptionObject,textFields,-1);

					}else{
						Map subDictionary=new HashMap();
						objData.put(possKey.substring(1),subDictionary);
						i=readDictionary(objectRef,1,subDictionary,i,raw,isEncryptionObject,textFields,-1);

						if(debug)
							System.out.println("Sub dictionary="+subDictionary);
					}

					//roll on if needed
					if(raw[i]==62)
						i++;
					//<start-13>
					possKey=new StringBuffer();
					/**<end-13>
                    possKey="";
                    /**/
				}else{

					if(debug)
						System.out.println("Get value");

					if(rawValue.charAt(rawValue.length()-1)!=' '){
						//<start-13>
						rawValue.append(' ');
						/**<end-13>
                    	rawValue=rawValue+" ";
                    	/**/
					}

					//<start-13>
					rawValue.append(possKey);
					rawValue.append(' ');

					possKey=new StringBuffer();
					/**<end-13>
                    rawValue=rawValue+possKey+" ";
                    possKey="";
                    /**/

					maybeKey=false;

					i--;

					if(debug)
						System.out.println("Value="+rawValue);

				}

				//identify possible keys and read

			}else if(!convertToHex && raw[i]==47){

				if(debug)
					System.out.println("Found /");

				maybeKey=true;
				while(true){
					//<start-13>
					possKey.append((char)raw[i]);
					/**<end-13>
                    possKey=possKey+(char)raw[i];
                    /**/
					i++;

					if((raw[i]==47)||(raw[i]==13)||(raw[i]==10)||(raw[i]==32)||(raw[i]==60)||(raw[i]==91)||(raw[i]==93))
						break;

				}

				//allow for no space as in
				if((raw[i]==47)||(raw[i]==91)||(raw[i]==93)||(raw[i]==60))
					i--;
				if(debug)
					System.out.println("Key="+possKey+ '<');

				if(possKey.toString().equals("/Separation")){
					isSeparation=true;
				}else if(possKey.toString().equals("/Indexed"))
					containsIndexKeyword=true;

				//track [/Indexed /xx ()] with binary values in () and convert to hex string
			}else if((raw[i]==40)&&(raw[i-1]!=92)&&(containsIndexKeyword)){
				convertToHex=true;

				//<start-13>
				rawValue.append(" <");
				/**<end-13>
                rawValue=rawValue+" <";
                /**/

			}else if(convertToHex){ //end of stream
				if((raw[i]==41)&&(raw[i-1]!=92)){
					//<start-13>
					rawValue.append('>');
					/**<end-13>
                    rawValue=rawValue+">";
                    /**/

					convertToHex=false;
				}else{ //values

					String hex_value=null;

					//allow for escaped octal up to 3 chars
					if(raw[i]=='\\' && raw[i+1]!=13 && raw[i+1]!=10 && raw[i+1]!=114){
						StringBuffer octal=new StringBuffer(3);
						int count=0;
						for(int ii=1;ii<4;ii++){

							char c=(char)raw[i+1];

							if(c<48 || c>57)
								break;

							octal.append(c);
							count++;

							i++;
						}

						if(count>0)
							hex_value=Integer.toHexString(Integer.parseInt(octal.toString(),8));
					}

					if(hex_value==null)
						hex_value = Integer.toHexString((raw[i]) & 255);
					//pad with 0 if required
					if (hex_value.length() < 2){
						//<start-13>
						rawValue.append('0');
						/**<end-13>
                        rawValue=rawValue+"0";
                        /**/
					}
					//<start-13>
					rawValue.append(hex_value);
					/**<end-13>
                    rawValue=rawValue+hex_value;
                    /**/

				}
				//all other cases
			}else{

				//if(debug)
				//	System.out.println("Else"+" "+(char)raw[i]);

				if((i>0)&&(raw[i-1]==47)){ //needed for [/Indexed /DeviceCMYK 60 1 0 R] to get second /

					//<start-13>
					rawValue.append('/');
					/**<end-13>
                    rawValue=rawValue+"/";
                    /**/
				}

				if ((raw[i] == 13) || (raw[i] == 10)){ //added as lines split in ghostscript output
					//<start-13>
					rawValue.append(' ');
					/**<end-13>
                    rawValue=rawValue+" ";
                    /**/
				}else {

					if((raw[i]=='<')&&(raw[i-1]!=' ' && raw[i-1]!='<')){ //make sure < always has a space before it
						//<start-13>
						rawValue.append(' ');
						/**<end-13>
	                    rawValue=rawValue+" ";
	                    /**/
					}
					if((i>0)&&(raw[i-1]==93)){ //make sure ] always has a space after it
						//<start-13>
						rawValue.append(' ');
						/**<end-13>
	                    rawValue=rawValue+" ";
	                    /**/
					}

					//<start-13>
					rawValue.append((char) raw[i]);
					/**<end-13>
                    rawValue=rawValue+(char) raw[i];
                    /**/
				}
				if((i==0)||((i>0)&&(raw[i-1]!=92))){
					if(raw[i]==91)
						start++;
					else if(raw[i]==93)
						end++;
				}
			}

			if((raw[i]==93)&(start==end))
				break;
			i++;

		}
		objData.put("rawValue",rawValue.toString().trim());

		if(debug)
			System.out.println(rawValue+"<>"+objData);

		return i;
	}

	/**
	 * read FDF
	 */
	final public Map readFDF() throws PdfException{

		int eof=-1,start=-1,end=-1;

		Map objData=new HashMap();

		Map fields=new HashMap();
		fields.put("F","x");
		fields.put("T","x");
		fields.put("V","x");

		try{
			eof = (int) pdf_datafile.length();

			pdf_datafile.readLine(); //lose first line with definition
			start=(int)pdf_datafile.getFilePointer();

			eof=eof-start;
			byte[] fileData=new byte[eof];
			this.pdf_datafile.read(fileData);

			this.convertObjectBytesToMap(objData, "1 0 R",false,fields,false,true,fileData,fileData,false);

			objData=(Map)objData.get("FDF");
		} catch (Exception e) {
			try {
				this.pdf_datafile.close();
			} catch (IOException e1) {
				LogWriter.writeLog("Exception "+e+" closing file");
			}

			throw new PdfException("Exception " + e + " reading trailer");
		}

		return objData;
	}

	/**give user access to internal flags such as user permissions*/
	public int getPDFflag(Integer flag) {

		if(flag.equals(PDFflags.USER_ACCESS_PERMISSIONS))
			return P;
		else if(flag==PDFflags.VALID_PASSWORD_SUPPLIED)
			return passwordStatus;
		else
			return -1;

	}

	/**
	 * read reference table start to see if new 1.5 type or traditional xref
	 * @throws PdfException
	 */
	final public String readReferenceTable() throws PdfException {

		int pointer = readFirstStartRef(),eof=(int)this.eof;
		xref.addElement(pointer);

		if(pointer>=eof){

			LogWriter.writeLog("Pointer not if file - trying to manually find startref");

			refTableInvalid=true;
			return findOffsets();
		}else if(isCompressedStream(pointer,eof))
			return readCompressedStream(pointer);
		else
			return readLegacyReferenceTable(pointer,eof);
	}

	/** Utility method used during processing of type1C files */
	final private int getWord(byte[] content, int index, int size) {
		int result = 0;
		for (int i = 0; i < size; i++) {
			result = (result << 8) + (content[index + i] & 0xff);

		}
		return result;
	}

	/**
	 * read 1.5 compression stream ref table
	 * @throws PdfException
	 */
	private String readCompressedStream(int pointer)
	throws PdfException {

		String rootObject = "", encryptValue = null, value;
		int current,numbEntries;

		while (pointer != -1) {

			if(interruptRefReading)
				return null;

			/**
			 * get values to read stream ref
			 */

			boolean debug=false,preserveTextString=false;
			objData=new HashMap();

			//any stream
			byte[] stream=null;

			/**read raw object data*/
			try{
				movePointer(pointer);
			}catch(Exception e){
				LogWriter.writeLog("Exception moving pointer to "+ pointer);
			}

			byte[] raw = readObjectData(-1);

			/**read the object name from the start*/
			StringBuffer objectName=new StringBuffer();
			char current1,last=' ';
			int matched=0, i1 =0;
			while(i1 <raw.length){
				current1 =(char)raw[i1];

				//treat returns same as spaces
				if(current1 ==10 || current1 ==13)
					current1 =' ';

				if(current1 ==' ' && last==' '){//lose duplicate or spaces
					matched=0;
				}else if(current1 ==pattern.charAt(matched)){ //looking for obj at end
					matched++;
				}else{
					matched=0;
					objectName.append(current1);
				}
				if(matched==3)
					break;
				last= current1;
				i1++;
			}

			//add end and put into Map
			objectName.append('R');
			String ref=objectName.toString();

			//@speed - may be an issue
			convertObjectBytesToMap(objData,ref,false, null, debug, preserveTextString, stream, raw,false);

			PdfObject pdfObject=new PdfCompressedObject(ref);
            pdfObject.setCompressedStream(true);
            readDictionaryAsObject(pdfObject, ref,0,raw, -1, "", false);

			lastRef="-1";

			Map obj_values = objData;

			int[] Index=pdfObject.getIntArray(PdfDictionary.Index);			
			if(Index==null){
				current=0;
				numbEntries=pdfObject.getInt(PdfDictionary.Size);
			}else{				
				current=Index[0];
				numbEntries=Index[1];
			}

			//read the field sizes
			int[] fieldSizes=pdfObject.getIntArray(PdfDictionary.W);			
			
			//now read the xrefs stream
			byte[] xrefs=pdfObject.DecodedStream;

            //if encr
            if(xrefs==null)
                    xrefs=readStream(pdfObject,true,true,false, false, true);

            //now parse the stream and extract values
			int pntr=0;
			int[] defaultValue={1,0,0};
			for(int i=0;i<numbEntries;i++){

				//read the next 3 values
				int[] nextValue=new int[3];
				for(int ii=0;ii<3;ii++){
					if(fieldSizes[ii]==0){
						nextValue[ii]=defaultValue[ii];
					}else{
						nextValue[ii]=getWord(xrefs,pntr,fieldSizes[ii]);
						pntr=pntr+fieldSizes[ii];
					}
				}

				//handle values appropriately
				int id=0,gen;
				switch(nextValue[0]){
				case 0:
					current++;

					break;
				case 1:
					id=nextValue[1];
					gen=nextValue[2];

					storeObjectOffset(current, id, gen,false);
					current++;
					break;
				case 2:
					id=nextValue[1];
					gen=nextValue[2];
					storeObjectOffset(current, id, gen,true);

					current++;

					break;
				default:
					throw new PdfException("Exception Unsupported Compression mode with value "+nextValue[0]);

				}
			}

			/**
			 * now process trailer values - only first set of table values for
			 * root, encryption and info
			 */
			if (rootObject.length() == 0) {

				value = (String) obj_values.get("Root"); //get root
				if (value != null)
					rootObject = value;

				/**
				 * handle encryption 
				 */
				encryptValue = (String) obj_values.get("Encrypt");

				if (encryptValue != null) {
					ID = (String) obj_values.get("ID"); //get ID object
					if (ID == null) {
						ID = "";
					} else {
						ID = Strip.removeArrayDeleminators(ID);

						if (ID.startsWith("<"))
							ID = ID.substring(1, ID.indexOf('>'));
					}
				}

				value = (String) obj_values.get("Info"); //get Info object
				if (value != null && (!isEncrypted || isPasswordSupplied))
					infoObject = value;
				else
					infoObject = null;

			}

			//make sure first values used if several tables and code for prev
			value = (String) obj_values.get("Prev");
			//see if other trailers
			if (value != null) {
				obj_values = new HashMap();
				pointer = Integer.parseInt(value);
			} else
				pointer = -1;
		}

		if(!interruptRefReading){

			if(encryptValue!=null)
				readEncryptionObject(encryptValue);

			calculateObjectLength();
		}

		return rootObject;
	}

	/**
	 * test first bytes to see if new 1.5 style table with obj or contains ref
	 * @throws PdfException
	 */
	private boolean isCompressedStream(int pointer,int eof) throws PdfException {

        int bufSize = 50,charReached=0;

        final int[] objStm={'O','b','j','S','t','m'};
        final int[] XRef={'X','R','e','f'};

        final int UNSET=-1;
		final int COMPRESSED=1;
		final int LEGACY=2;
		int type=UNSET;

        while (true) {

			/** adjust buffer if less than 1024 bytes left in file */
			if (pointer + bufSize > eof)
				bufSize = eof - pointer;

			if(bufSize<0)
				bufSize=50;
			byte[] buffer = new byte[bufSize];

			/** get bytes into buffer */
			movePointer(pointer);

			try{
				pdf_datafile.read(buffer);
			} catch (Exception e) {
				e.printStackTrace();
				throw new PdfException("Exception " + e + " scanning trailer for ref or obj");
			}

            /**look for xref or obj */
			for (int i = 0; i < bufSize; i++) {

				byte currentByte = buffer[i];

                /** check for xref OR end - reset if not */
				if ((currentByte == oldPattern[charReached])&&(type!=COMPRESSED)){
					charReached++;
					type=LEGACY;
				}else if ((currentByte == objStm[charReached] || currentByte == XRef[charReached])&& type!=LEGACY){
					charReached++;
					type=COMPRESSED;
				}else{
					charReached = 0;
					type=UNSET;
				}

				if (charReached == 3)
					break;

			}

			if(charReached==3)
				break;

            //update pointer
            pointer = pointer + bufSize;

        }

        /**
		 * throw exception if no match or tell user which type
		 */
		if(type==UNSET){
			try {
				this.pdf_datafile.close();
			} catch (IOException e1) {
				LogWriter.writeLog("Exception "+1+" closing file");
			}
			throw new PdfException("Exception unable to find ref or obj in trailer");
		}

		if(type==COMPRESSED)
			return true;
		else
			return false;
	}

	/**
	 * read reference table from file so we can locate
	 * objects in pdf file and read the trailers
	 */
	final private String readLegacyReferenceTable(int pointer,int eof) throws PdfException {
		String rootObject = "", value = "", encryptValue=null;

		int current = 0; //current object number
		byte[] Bytes = null;
		int bufSize = 1024;

		int charReached = 0, endTable = 0;
		byte[] pattern = { 37, 37, 69, 79, 70 }; //pattern %%EOF

		Map obj_values = new HashMap();

		/**read and decode 1 or more trailers*/
		while (true) {

			if(interruptRefReading)
				return null;

			try {

				//allow for pointer outside file
				Bytes=readTrailer(bufSize, charReached, pattern, pointer, eof);

			} catch (Exception e) {
				Bytes=null;
				try {
					this.pdf_datafile.close();
				} catch (IOException e1) {
					LogWriter.writeLog("Exception "+e+" closing file");
				}
				throw new PdfException("Exception " + e + " reading trailer");
			}

			if (Bytes == null) //safety catch
				break;

			/**get trailer*/
			int i = 0;
			StringBuffer startRef = new StringBuffer();
			StringBuffer key = new StringBuffer();
			StringBuffer operand = new StringBuffer();

			int maxLen=Bytes.length;

			//for(int a=0;a<100;a++)
			//	System.out.println((char)Bytes[i+a]);
			while (i <maxLen) {//look for trailer keyword
				if (Bytes[i] == 116 && Bytes[i + 1] == 114 && Bytes[i + 2] == 97 && Bytes[i + 3] == 105 &&
						Bytes[i + 4] == 108 && Bytes[i + 5] == 101 && Bytes[i + 6] == 114)
					break;

				i++;
			}

			//save endtable position for later
			endTable = i;

			//move to beyond <<
			while ((Bytes[i] != 60) && (Bytes[i + 1] != 60))
				i++;

			i = readRef2(Bytes, obj_values, i, key, operand);

			//System.out.println(obj_values);

			//handle optional XRefStm
			value = (String) obj_values.get("XRefStm"); //get optional XRefStm
			if(value!=null){
				pointer=Integer.parseInt(value);
			}else{ //usual way

				boolean hasRef=true;

				//look for xref as end of startref
				while ((Bytes[i] != 116)&& (Bytes[i + 1] != 120)
						&& (Bytes[i + 2] != 114)&& (Bytes[i + 3] != 101)&& (Bytes[i + 4] != 102)){

					if(Bytes[i]=='o' && Bytes[i+1]=='b' && Bytes[i+2]=='j'){
						hasRef=false;
						break;
					}
					i++;
				}

				if(hasRef){

					i = i + 8;
					//move to start of value ignoring spaces or returns
					while ((i < maxLen)&& ((Bytes[i] == 10)| (Bytes[i] == 32)| (Bytes[i] == 13)))
						i++;

					//allow for characters between xref and startref
					while ((i < maxLen)&& (Bytes[i] != 10)&& (Bytes[i] != 32)&& (Bytes[i] != 13)) {
						startRef.append((char) Bytes[i]);
						i++;
					}

					/**convert xref to string to get pointer*/
					if (startRef.length() > 0)
						pointer = Integer.parseInt(startRef.toString());
				}
			}
			if (pointer == -1) {
				LogWriter.writeLog("No startRef");

				/**now read the objects for the trailers*/
			} else if ((Bytes[0] == 120)& (Bytes[1] == 114)& (Bytes[2] == 101)& (Bytes[3] == 102)) { //make sure starts xref

				i = 5;

				//move to start of value ignoring spaces or returns
				while (((Bytes[i] == 10)| (Bytes[i] == 32)| (Bytes[i] == 13)))
					i++;

				current = readXRefs(current, Bytes, endTable, i);
				i=endTable;

				/**now process trailer values - only first set of table values for root, encryption and info*/
				if (rootObject.length() == 0) {

					value = (String) obj_values.get("Root"); //get root
					if (value != null)
						rootObject = value;

					/**handle encryption*/
					encryptValue = (String) obj_values.get("Encrypt");

					if (encryptValue != null){
						ID = (String) obj_values.get("ID"); //get ID object
						if(ID==null){
							ID="";
						}else{
							ID=Strip.removeArrayDeleminators(ID);

							if(ID.startsWith("<"))
								ID=ID.substring(1,ID.indexOf('>'));
						}

					}

					value = (String) obj_values.get("Info"); //get Info object
					if ((value != null)&&((!this.isEncrypted)|(this.isPasswordSupplied)))
						infoObject=value;
					else
						infoObject=null;

				}

				//make sure first values used if several tables and code for prev
				value = (String) obj_values.get("Prev");
				//see if other trailers
				if (value != null) {
					//reset values for loop
					bufSize = 1024;
					charReached = 0;
					obj_values = new HashMap();
					pointer = Integer.parseInt(value);

					//track ref table so we can work out object length
					xref.addElement(pointer);

				} else
					pointer = -1;

			} else{
				pointer=-1;
				rootObject = findOffsets();
				refTableInvalid=true;
			}
			if (pointer == -1)
				break;
		}

		/**
		 * check offsets
		 */
		if(!interruptRefReading){
			//checkOffsets(validOffsets);
			if(encryptValue!=null)
				readEncryptionObject(encryptValue);

			if(!refTableInvalid )
				calculateObjectLength();
		}

		return rootObject;
	}

	/**
	 * precalculate sizes for each object
	 */
	private void calculateObjectLength() {

		//add eol to refs as catchall
		this.xref.addElement( (int) eof);


		//get order list of refs
		int[] xrefs=this.xref.get();
		int xrefCount=xrefs.length;
		int[] xrefID=new int[xrefCount];
		for(int i=0;i<xrefCount;i++)
			xrefID[i]=i;
		xrefID=Sorts.quicksort( xrefs, xrefID );

		//get ordered list of objects in offset order
		int objectCount=offset.getCapacity();
		ObjLengthTable=new int[objectCount];
		int[] id=new int[objectCount];
		int[] offsets=new int[objectCount];

		//read from local copies and pop lookup table
		int[] off=offset.get();
		boolean[] isComp=isCompressed.get();
		for(int i=0;i<objectCount;i++){
			if(!isComp[i]){
				offsets[i]=off[i];
				id[i]=i;
			}
		}

		id=Sorts.quicksort( offsets, id );

		int i=0;
		//ignore empty values
		while(true){
			try{
				if(offsets[id[i]]!=0)
					break;
				i++;
			}catch(Exception e){
				e.printStackTrace();
				System.exit(1);
			}
		}

		/**
		 * loop to calc all object lengths
		 * */
		int  start=offsets[id[i]],end;

		//find next xref
		int j=0;
		while(xrefs[xrefID[j]]<start+1)
			j++;

		while(i<objectCount-1){

			end=offsets[id[i+1]];
			int objLength=end-start-1;

			//adjust for any xref
			if(xrefs[xrefID[j]]<end){
				objLength=xrefs[xrefID[j]]-start-1;
				while(xrefs[xrefID[j]]<end+1)
					j++;
			}
			ObjLengthTable[id[i]]=objLength;
			//System.out.println(id[i]+" "+objLength+" "+start+" "+end);
			start=end;
			while(xrefs[xrefID[j]]<start+1)
				j++;
			i++;
		}

		//special case - last object

		ObjLengthTable[id[i]]=xrefs[xrefID[j]]-start-1;
		//System.out.println("*"+id[i]+" "+start+" "+xref+" "+eof);
	}
	/**
	 * read table of values
	 */
	private int readXRefs( int current, byte[] Bytes, int endTable, int i) {

		char flag='c';
		int id=0,tokenCount=0;
		int generation=0;
		int lineLen=0;
		int startLine,endLine;
		boolean skipNext=false;

		int[] breaks=new int[5];
		int[] starts=new int[5];

		// loop to read all references
		while (i < endTable) { //exit end at trailer

			startLine=i;
			endLine=-1;

			/**
			 * read line locations
			 */
			//move to start of value ignoring spaces or returns
			while ((Bytes[i] != 10) & (Bytes[i] != 13)) {
				//scan for %
				if((endLine==-1)&&(Bytes[i]==37))
					endLine=i-1;

				i++;
			}

			//set end if no comment
			if(endLine==-1)
				endLine=i-1;

			//strip any spaces
			while(Bytes[startLine]==32)
				startLine++;

			//strip any spaces
			while(Bytes[endLine]==32)
				endLine--;

			i++;

			/**
			 * decode the line
			 */
			tokenCount=0;
			lineLen=endLine-startLine+1;

			if(lineLen>0){

				//decide if line is a section header or value

				//first count tokens
				int lastChar=1,currentChar;
				for(int j=1;j<lineLen;j++){
					currentChar=Bytes[startLine+j];

					if((currentChar==32)&&(lastChar!=32)){
						breaks[tokenCount]=j;
						tokenCount++;
					}else if((currentChar!=32)&&(lastChar==32)){
						starts[tokenCount]=j;
					}

					lastChar=currentChar;
				}

				//update numbers so loops work
				breaks[tokenCount]=lineLen;
				tokenCount++;

				if(tokenCount==1){ //fix for first 2 values on separate lines

					if(skipNext)
						skipNext=false;
					else{
						current=parseInt(startLine,startLine+breaks[0],Bytes);
						skipNext=true;
					}

				}else if (tokenCount == 2){
					current=parseInt(startLine,startLine+breaks[0],Bytes);
				}else {

					id = parseInt(startLine,startLine+breaks[0],Bytes);
					generation=parseInt(startLine+starts[1],startLine+breaks[1],Bytes);

					flag =(char)Bytes[startLine+starts[2]];

					if ((flag=='n')) { // only add objects in use

						/**
						 * assume not valid and test to see if valid
						 */
						boolean isValid=false;

						//get bytes
						int bufSize=20;

						//adjust buffer if less than 1024 bytes left in file
						if (id + bufSize > eof)
							bufSize = (int) (eof - id);

						if(bufSize>0){
							byte[] buffer = new byte[bufSize];

							/** get bytes into buffer */
							movePointer(id);

							try {
								pdf_datafile.read(buffer);

								//look for space o b j
								for(int ii=4;ii<bufSize;ii++){
									if((buffer[ii-3]==32)&&(buffer[ii-2]==111)&&(buffer[ii-1]==98)&&(buffer[ii]==106)){
										isValid=true;
										ii=bufSize;
									}

								}

								if(isValid){
									storeObjectOffset(current, id, generation,false);
									xref.addElement( id);
								}else{
								}
							} catch (IOException e) {
								e.printStackTrace();
							}
						}

					}
					current++; //update our pointer
				}
			}
		}
		return current;
	}

	private final static int[] powers={1,10,100,1000,10000,100000,1000000,10000000,100000000,
		1000000000};

	/**
	 * turn stream of bytes into a number
	 */
	public static int parseInt(int i, int j, byte[] bytes) {
		int finalValue=0;
		int power=0;

        boolean isNegative=false;
        i--; //decrement  pointer to speed up
		for(int current=j-1;current>i;current--){

            if(bytes[current]=='-'){
             isNegative=true;
            }else{
            finalValue=finalValue+((bytes[current]-48)*powers[power]);
			//System.out.println(finalValue+" "+powers[power]+" "+current+" "+(char)bytes[current]+" "+bytes[current]);
			power++;
            }
        }
		//System.exit(1);

        if(isNegative)
        return -finalValue;
        else
        return finalValue;
	}

	/**
	 * turn stream of bytes into a flaot number
	 */
	private static float parseFloat(int start,int end,byte[] stream) {

		float d=0,dec=0f,num=0f;

		int ptr=end;
		int intStart=start;
		boolean isMinus=false;
		//hand optimised float code

		//find decimal point
		for(int j=end-1;j>start-1;j--){
			if(stream[j]==46){ //'.'=46
				ptr=j;
				break;
			}
		}

		int intChars=ptr;

		int decStart=ptr;

		//allow for minus
		if(stream[start]==43){ //'+'=43
			intChars--;
			intStart++;
		}else if(stream[start]==45){ //'-'=45
			//intChars--;
			intStart++;
			isMinus=true;
		}

		//optimisations
		int intNumbers=intChars-intStart;
		int decNumbers=end-ptr;

		if((intNumbers>4)){ //non-optimised to cover others
			isMinus=false;
			try{
				int count=end-start;
				byte[] floatVal=new byte[count];

				System.arraycopy(stream, start,floatVal,0,count);

				//System.out.println(new String(floatVal)+"<");
				// System.exit(1);
				d=Float.parseFloat(new String(floatVal));
			}catch(Exception e){

				d=0;
			}
		}else{

			float thous=0f,units=0f,tens=0f,hundreds=0f,tenths=0f,hundredths=0f, thousands=0f, tenthousands=0f,hunthousands=0f,millis=0f;
			int c;

			//thousands
			if(intNumbers>3){
				c=stream[intStart]-48;
				switch(c){
				case 1:
					thous=1000.0f;
					break;
				case 2:
					thous=2000.0f;
					break;
				case 3:
					thous=3000.0f;
					break;
				case 4:
					thous=4000.0f;
					break;
				case 5:
					thous=5000.0f;
					break;
				case 6:
					thous=6000.0f;
					break;
				case 7:
					thous=7000.0f;
					break;
				case 8:
					thous=8000.0f;
					break;
				case 9:
					thous=9000.0f;
					break;
				}
				intStart++;
			}

			//hundreds
			if(intNumbers>2){
				c=stream[intStart]-48;
				switch(c){
				case 1:
					hundreds=100.0f;
					break;
				case 2:
					hundreds=200.0f;
					break;
				case 3:
					hundreds=300.0f;
					break;
				case 4:
					hundreds=400.0f;
					break;
				case 5:
					hundreds=500.0f;
					break;
				case 6:
					hundreds=600.0f;
					break;
				case 7:
					hundreds=700.0f;
					break;
				case 8:
					hundreds=800.0f;
					break;
				case 9:
					hundreds=900.0f;
					break;
				}
				intStart++;
			}

			//tens
			if(intNumbers>1){
				c=stream[intStart]-48;
				switch(c){
				case 1:
					tens=10.0f;
					break;
				case 2:
					tens=20.0f;
					break;
				case 3:
					tens=30.0f;
					break;
				case 4:
					tens=40.0f;
					break;
				case 5:
					tens=50.0f;
					break;
				case 6:
					tens=60.0f;
					break;
				case 7:
					tens=70.0f;
					break;
				case 8:
					tens=80.0f;
					break;
				case 9:
					tens=90.0f;
					break;
				}
				intStart++;
			}

			//units
			if(intNumbers>0){
				c=stream[intStart]-48;
				switch(c){
				case 1:
					units=1.0f;
					break;
				case 2:
					units=2.0f;
					break;
				case 3:
					units=3.0f;
					break;
				case 4:
					units=4.0f;
					break;
				case 5:
					units=5.0f;
					break;
				case 6:
					units=6.0f;
					break;
				case 7:
					units=7.0f;
					break;
				case 8:
					units=8.0f;
					break;
				case 9:
					units=9.0f;
					break;
				}
			}

			//tenths
			if(decNumbers>1){
				decStart++; //move beyond.
				c=stream[decStart]-48;
				switch(c){
				case 1:
					tenths=0.1f;
					break;
				case 2:
					tenths=0.2f;
					break;
				case 3:
					tenths=0.3f;
					break;
				case 4:
					tenths=0.4f;
					break;
				case 5:
					tenths=0.5f;
					break;
				case 6:
					tenths=0.6f;
					break;
				case 7:
					tenths=0.7f;
					break;
				case 8:
					tenths=0.8f;
					break;
				case 9:
					tenths=0.9f;
					break;
				}
			}

			//hundredths
			if(decNumbers>2){
				decStart++; //move beyond.
				c=stream[decStart]-48;
				switch(c){
				case 1:
					hundredths=0.01f;
					break;
				case 2:
					hundredths=0.02f;
					break;
				case 3:
					hundredths=0.03f;
					break;
				case 4:
					hundredths=0.04f;
					break;
				case 5:
					hundredths=0.05f;
					break;
				case 6:
					hundredths=0.06f;
					break;
				case 7:
					hundredths=0.07f;
					break;
				case 8:
					hundredths=0.08f;
					break;
				case 9:
					hundredths=0.09f;
					break;
				}
			}

			//thousands
			if(decNumbers>3){
				decStart++; //move beyond.
				c=stream[decStart]-48;
				switch(c){
				case 1:
					thousands=0.001f;
					break;
				case 2:
					thousands=0.002f;
					break;
				case 3:
					thousands=0.003f;
					break;
				case 4:
					thousands=0.004f;
					break;
				case 5:
					thousands=0.005f;
					break;
				case 6:
					thousands=0.006f;
					break;
				case 7:
					thousands=0.007f;
					break;
				case 8:
					thousands=0.008f;
					break;
				case 9:
					thousands=0.009f;
					break;
				}
			}

			//tenthousands
			if(decNumbers>4){
				decStart++; //move beyond.
				c=stream[decStart]-48;
				switch(c){
				case 1:
					tenthousands=0.0001f;
					break;
				case 2:
					tenthousands=0.0002f;
					break;
				case 3:
					tenthousands=0.0003f;
					break;
				case 4:
					tenthousands=0.0004f;
					break;
				case 5:
					tenthousands=0.0005f;
					break;
				case 6:
					tenthousands=0.0006f;
					break;
				case 7:
					tenthousands=0.0007f;
					break;
				case 8:
					tenthousands=0.0008f;
					break;
				case 9:
					tenthousands=0.0009f;
					break;
				}
			}

			//100thousands
			if(decNumbers>5){
				decStart++; //move beyond.
				c=stream[decStart]-48;

				switch(c){
				case 1:
					hunthousands=0.00001f;
					break;
				case 2:
					hunthousands=0.00002f;
					break;
				case 3:
					hunthousands=0.00003f;
					break;
				case 4:
					hunthousands=0.00004f;
					break;
				case 5:
					hunthousands=0.00005f;
					break;
				case 6:
					hunthousands=0.00006f;
					break;
				case 7:
					hunthousands=0.00007f;
					break;
				case 8:
					hunthousands=0.00008f;
					break;
				case 9:
					hunthousands=0.00009f;
					break;
				}
			}

			if(decNumbers>6){
				decStart++; //move beyond.
				c=stream[decStart]-48;

				switch(c){
				case 1:
					millis=0.000001f;
					break;
				case 2:
					millis=0.000002f;
					break;
				case 3:
					millis=0.000003f;
					break;
				case 4:
					millis=0.000004f;
					break;
				case 5:
					millis=0.000005f;
					break;
				case 6:
					millis=0.000006f;
					break;
				case 7:
					millis=0.000007f;
					break;
				case 8:
					millis=0.000008f;
					break;
				case 9:
					millis=0.000009f;
					break;
				}
			}

			dec=tenths+hundredths+thousands+tenthousands+hunthousands+millis;
			num=thous+hundreds+tens+units;
			d=num+dec;

		}

		if(isMinus)
			return -d;
		else
			return d;
	}

	/**
	 * @param Bytes
	 * @param obj_values
	 * @param i
	 * @param key
	 * @param operand
	 */
	private int readRef2(byte[] Bytes, Map obj_values, int i, StringBuffer key, StringBuffer operand) {
		boolean notID;
		int p;

		//now read key pairs until >>
		while (true) {

			i++;

			//exit at closing >>
			if ((Bytes[i] == 62) && (Bytes[i + 1] == 62))
				break;

			if (Bytes[i] == 47) { //everything from /

				i++;
				//get key up to space or [
				while (true) {
					if ((Bytes[i] == 32) | (Bytes[i] == 91)|(Bytes[i] == 10)|(Bytes[i] == 13)|(Bytes[i]==60))
						break;

					key.append((char) Bytes[i]);
					i++;
				}

				if((key.length()==2)&&(key.charAt(0)=='I')&&(key.charAt(1)=='D'))
					notID=false;
				else
					notID=true;

				//ignore spaces some pdf use ID[ so we trap for [ (char 91)
				if ((Bytes[i] != 91)) {
					while (Bytes[i] == 32)
						i++;
				}

				int Oplen=0,brackets=0;

				int start=i; //stop endless loop in next code
				int dictCount=0;
				//get operand up to end
				while (true) {

					char c = (char) Bytes[i];

					if((Bytes[i-1]==60)&&(Bytes[i]==60))
						dictCount++;

					if((c=='(')&&(i>0)&&((Bytes[i-1]!=92)|((i>1)&&(Bytes[i-1]==92)&&(Bytes[i-2]!=92))))
						brackets++;
					else if((c==')')&&(i>0)&&((Bytes[i-1]!=92)|((i>1)&&(Bytes[i-1]==92)&&(Bytes[i-2]!=92))))
						brackets++;

					//System.out.print(c);
					if(i!=start && (c == 47)&notID&&(dictCount==0)) //allow for no spacing
						i--;

					//fix for no gap before any keys
					// ie <</ID [(\215Ga"\224\017\225D\264u\203:\007\2579\302) (\215Ga"\224\017\225D\264u\203:\007\2579\302)]/Info 10 0 R/Prev 34185/Root 13 0 R/Size 29>>
					if(!notID && c==']' && Bytes[i+1]==47)
						break;


					//allow for ID=[<3a4614ef17287563abfd439fbb4ad0ae><7d0c03d0767811d8b491000502832bed>]/Pr
					//also allow for ID[(])()]
					if((!notID)&&(c==']')&&(brackets==0))
						break;

					if ((dictCount==0)&&(((c == 10) | (c == 13) | ((c == 47)&&notID))&&(Oplen>0)))
						break;

					//exit at closing >>
					if ((Bytes[i] == 62) && (Bytes[i + 1] == 62)){
						if(dictCount==0)
							break;
						else
							dictCount--;
					}

					//allow for escape
					if (c == '\\') {
						i++;
						c = (char) Bytes[i];
					}

					//avoid returns at start
					if (( Bytes[i] == 10 || Bytes[i]== 13 || Bytes[i]==32)&&(Oplen==0)){
					}else{
						operand.append((char) Bytes[i]);
						Oplen++;
					}

					i++;
				}

				operand = removeTrailingSpaces(operand);

				//save pair and reset - allow for null value
				String finalValue= operand.toString();
				if(!finalValue.equals("null")){
					p=finalValue.indexOf('%');
					if((p!=-1)&(finalValue.indexOf("\\%")!=p-1))
						finalValue=finalValue.substring(0,p).trim();

					obj_values.put(key.toString(),finalValue);

				}

				key = new StringBuffer();
				operand = new StringBuffer();

			}

			if ((Bytes[i] == 62) && (Bytes[i + 1] == 62))
				break;

		}
		return i;
	}
	/**
	 */
	private byte[] readTrailer(int bufSize, int charReached, byte[] pattern,
			int pointer, int eof) throws IOException {

		/**read in the bytes, using the startRef as our terminator*/
		ByteArrayOutputStream bis = new ByteArrayOutputStream();

		while (true) {

			/** adjust buffer if less than 1024 bytes left in file */
			if (pointer + bufSize > eof)
				bufSize = eof - pointer;

			byte[] buffer = new byte[bufSize];

			/** get bytes into buffer */
			movePointer(pointer);
			pdf_datafile.read(buffer);

			boolean endFound=false;

			/** write out and lookf for startref at end */
			for (int i = 0; i < bufSize; i++) {

				byte currentByte = buffer[i];

				/** check for startref at end - reset if not */
				if (currentByte == pattern[charReached])
					charReached++;
				else
					charReached = 0;

				if (charReached == 5){ //located %%EOF and get last few bytes

					for (int j = 0; j < i+1; j++)
						bis.write(buffer[j]);

					i = bufSize;
					endFound=true;

				}
			}

			//write out block if whole block used
			if(!endFound)
				bis.write(buffer);

			//update pointer
			pointer = pointer + bufSize;

			if (charReached == 5)
				break;

		}

		bis.close();
		return bis.toByteArray();

	}
	/**
	 * read the form data from the file
	 */
	final public PdfFileInformation readPdfFileMetadata(String ref) {

		//read info object (may be defined and object set in different trailers so must be done at end)
		if((infoObject!=null)&&((!isEncrypted)|(isPasswordSupplied)))
			readInformationObject(infoObject);

		//read and set XML value
		if(ref!=null){

            //get data
            PdfMetadataObject metaDataObj=new PdfMetadataObject(ref);
            readObject(metaDataObj, ref,false, null);
            byte[] stream=metaDataObj.DecodedStream;

			currentFileInformation.setFileXMLMetaData(stream);
		}

		return currentFileInformation;
	}

	//////////////////////////////////////////////////////////////////////////
	/**
	 * get value which can be direct or object
	 */
	final public String getValue(String value) {

		if ((value != null)&&(value.endsWith(" R"))){ //indirect

			Map indirectObject=readObject(new PdfObject(value), value,false, null);
			//System.out.println(value+" "+indirectObject);
			value=(String) indirectObject.get("rawValue");

		}

		//allow for null as string
		if(value!=null && value.equals("null"))
			value=null;

		return value;
	}

	/**
	 * get value which can be direct or object
	 */
	private Object getObjectValue(Object value) {

		Map indirectObject=readObject(new PdfObject((String)value), (String)value,false, fields);

		//System.out.println(value+" "+indirectObject);
		int keyCount=indirectObject.size();
		if(keyCount==1){
			Object stringValue= indirectObject.get("rawValue");
			if(stringValue!=null){
				if(stringValue instanceof String)
					value=stringValue;
				else
					value=this.getTextString((byte[]) stringValue);

			}else
				value=indirectObject;
		}else
			value=indirectObject;


		return value;
	}

	/**
	 * get text value as byte stream which can be direct or object
	 */
	final public byte[] getByteTextStringValue(Object rawValue,Map fields) {

		if(rawValue instanceof String){
			String value=(String) rawValue;
			if ((value != null)&&(value.endsWith(" R"))){ //indirect

				Map indirectObject=readObject(new PdfObject(value), value,false, fields);

				rawValue=indirectObject.get("rawValue");

			}else {
				return value.getBytes();
			}
		}

		return (byte[]) rawValue;
	}


	

	/**remove any trailing spaces at end*/
	private StringBuffer removeTrailingSpaces(StringBuffer operand) {

		/*remove any trailing spaces on operand*/
		int l = operand.length();
		for (int ii = l - 1; ii > -1; ii--) {
			if (operand.charAt(ii) == ' ')
				operand.deleteCharAt(ii);
			else
				ii = -2;
		}

		return operand;

	}

	/**
	 * return flag to show if encrypted
	 */
	final public boolean isEncrypted() {
		return isEncrypted;
	}

	/**
	 * return flag to show if valid password has been supplied
	 */
	final public boolean isPasswordSupplied() {
		return isPasswordSupplied;
	}

	/**
	 * return flag to show if encrypted
	 */
	final public boolean isExtractionAllowed() {
		return extractionIsAllowed;
	}

	/**show if file can be displayed*/
	public boolean isFileViewable() {

		return isFileViewable;
	}

	/**
	 * reads the line/s from file which make up an object
	 * includes move
	 */
	final private byte[] decrypt(byte[] data, String ref,boolean isEncryption,
			String cacheName,boolean alwaysUseRC4,
			boolean isString) throws PdfSecurityException{

		boolean debug=false;//ref.equals("100 0 R");

		if((isEncrypted)||(isEncryption)){

			BufferedOutputStream streamCache= null;
			BufferedInputStream bis = null;
			int streamLength=0;

			boolean isAES=false;

			if(cacheName!=null){ //this version is used if we cache large object to disk
				//rename file
				try {

					streamLength = (int) new File(cacheName).length();

					File tempFile2 = File.createTempFile("jpedal",".raw");

					cachedObjects.put(tempFile2.getAbsolutePath(),"x");
					//System.out.println(">>>"+tempFile2.getAbsolutePath());
					ObjectStore.copy(cacheName,tempFile2.getAbsolutePath());
					File rawFile=new File(cacheName);
					rawFile.delete();


					//decrypt
					streamCache = new BufferedOutputStream(new FileOutputStream(cacheName));
					bis=new BufferedInputStream(new FileInputStream(tempFile2));

				} catch (IOException e1) {
					LogWriter.writeLog("Exception "+e1+" in decrypt");
				}
			}

			//default values for rsa
			int keyLength=this.keyLength;
			String algorithm="RC4",keyType="RC4";
			IvParameterSpec ivSpec = null;

			//select for stream or string
			Map AESmap=null;
			String AESname=null;
			if(!isString){
				AESmap=StmF;
				AESname=StrFname;
			}else{
				AESmap=StrF;
				AESname=StmFname;
			}

			//AES identity
			if(!alwaysUseRC4 && AESmap==null && AESname!=null && AESname.equals("Identity"))
				return data;

			//use RC4 as default but override if needed
			if(AESmap!=null){

				//use StmF values in preference
				String lenKey=(String)AESmap.get("Length");
				if(lenKey!=null)
					keyLength=Integer.parseInt(lenKey);

				String crypt=(String) AESmap.get("CFM");

				if(crypt!=null && crypt.equals("/AESV2") && !alwaysUseRC4){

					cipher=null; //force reset as may be rsa

					algorithm="AES/CBC/PKCS5Padding";
					keyType="AES";

					isAES=true;

					//setup CBC
					byte[] iv=new byte[16];
					System.arraycopy(data, 0, iv, 0, 16);
					ivSpec = new IvParameterSpec(iv);

					//and knock off iv data
					int origLen=data.length;
					int newLen=origLen-16;
					byte[] newData=new byte[newLen];
					System.arraycopy(data, 16, newData, 0, newLen);
					data=newData;

					//make sure data correct size
					int diff= (data.length & 15);
					int newLength=data.length;
					if(diff>0){
						newLength=newLength+16-diff;

						newData=new byte[newLength];

						System.arraycopy(data, 0, newData, 0, data.length);
						data=newData;
					}
				}
			}

			byte[] currentKey=new byte[keyLength];

			if(ref.length()>0)
				currentKey=new byte[keyLength+5];

			System.arraycopy(encryptionKey, 0, currentKey, 0, keyLength);

			try{
				//add in Object ref id if any
				if(ref.length()>0){
					int pointer=ref.indexOf(' ');
					int pointer2=ref.indexOf(' ',pointer+1);

					int obj=Integer.parseInt(ref.substring(0,pointer));
					int gen=Integer.parseInt(ref.substring(pointer+1,pointer2));

					currentKey[keyLength]=((byte)(obj & 0xff));
					currentKey[keyLength+1]=((byte)((obj>>8) & 0xff));
					currentKey[keyLength+2]=((byte)((obj>>16) & 0xff));
					currentKey[keyLength+3]=((byte)(gen & 0xff));
					currentKey[keyLength+4]=((byte)((gen>>8) & 0xff));
				}

				byte[] finalKey = new byte[Math.min(currentKey.length,16)];

				if(ref.length()>0){
					MessageDigest currentDigest =MessageDigest.getInstance("MD5");
					currentDigest.update(currentKey);

					//add in salt
					if(isAES && keyLength>=16){
						byte[] salt = {(byte)0x73, (byte)0x41, (byte)0x6c, (byte)0x54};

						currentDigest.update(salt);
					}
					System.arraycopy(currentDigest.digest(),0, finalKey,0, finalKey.length);
				}else{
					System.arraycopy(currentKey,0, finalKey,0, finalKey.length);
				}

				/**only initialise once - seems to take a long time*/
				if(cipher==null)
					cipher = Cipher.getInstance(algorithm);

				SecretKey testKey = new SecretKeySpec(finalKey, keyType);

				if(isEncryption)
					cipher.init(Cipher.ENCRYPT_MODE, testKey);
				else{
					if(ivSpec==null)
						cipher.init(Cipher.DECRYPT_MODE, testKey);
					else //aes
						cipher.init(Cipher.DECRYPT_MODE, testKey,ivSpec);
				}

				//if data on disk read a byte at a time and write back
				if((data==null)||(debugCaching && streamCache!=null)){

					CipherInputStream cis=new CipherInputStream(bis,cipher);
					int nextByte;
					while(true){
						nextByte=cis.read();
						if(nextByte==-1)
							break;
						streamCache.write(nextByte);
					}
					cis.close();
					streamCache.close();
					bis.close();

				}

				if(data!=null)
					data=cipher.doFinal(data);


			}catch(Exception e){

				throw new PdfSecurityException("Exception "+e+" decrypting content");

			}

		}

		if(alwaysReinitCipher)
			cipher=null;

		return data;
	}

	/**
	 * routine to create a padded key
	 */
	private byte[] getPaddedKey(byte[] password){

		/**get 32 bytes for  the key*/
		byte[] key=new byte[32];

		int passwordLength=password.length;
		if(passwordLength>32)
			passwordLength=32;

		System.arraycopy(encryptionPassword, 0, key, 0, passwordLength);

		for(int ii=passwordLength;ii<32;ii++){

			key[ii]=(byte)Integer.parseInt(padding[ii-passwordLength],16);

		}

		return key;
	}

	/**see if valid for password*/
	private boolean testPassword() throws PdfSecurityException{

		int count=32;

		byte[] rawValue=new byte[32];
		byte[] keyValue=new byte[32];

		for(int i=0;i<32;i++)
			rawValue[i]=(byte)Integer.parseInt(padding[i],16);

		byte[] encrypted=(byte[])rawValue.clone();

		if (rev==2) {
			encryptionKey=calculateKey(O,P,ID);
			encrypted=decrypt(encrypted,"", true,null,false,false);

		} else if(rev>=3) {

			//use StmF values in preference
			int keyLength=this.keyLength;

			if(rev==4 && StmF!=null){
				String lenKey=(String)StmF.get("Length");
				if(lenKey!=null)
					keyLength=Integer.parseInt(lenKey);

			}

			count=16;
			encryptionKey=calculateKey(O,P,ID);
			byte[] originalKey=(byte[]) encryptionKey.clone();

			MessageDigest md = null;
			try {
				md = MessageDigest.getInstance("MD5");
			} catch (Exception e) {
				LogWriter.writeLog("Exception "+e+" with digest");
			}

			md.update(encrypted);

			//feed in ID
			byte[] documentID=new byte[ID.length()/2];
			for(int ii=0;ii<ID.length();ii=ii+2){
				String nextValue=ID.substring(ii,ii+2);
				documentID[ii/2]=(byte)Integer.parseInt(nextValue,16);
			}

			keyValue = md.digest(documentID);

			keyValue=decrypt(keyValue,"", true,null,true,false);

			byte[] nextKey = new byte[keyLength];

			for (int i=1; i<=19; i++) {

				for (int j=0; j<keyLength; j++)
					nextKey[j] = (byte)(originalKey[j] ^ i);

				encryptionKey=nextKey;

				keyValue=decrypt(keyValue,"", true,null,true,false);

			}

			encryptionKey=originalKey;

			encrypted = new byte[32];
			System.arraycopy(keyValue,0, encrypted,0, 16);
			System.arraycopy(rawValue,0, encrypted,16, 16);

		}

		boolean isMatch=true;

		for(int i=0;i<count;i++){
			if(U[i]!=encrypted[i]){
				isMatch=false;
				i=U.length;
			}
		}

		return isMatch;
	}

	/**set the key value*/
	private void computeEncryptionKey() throws PdfSecurityException{
		MessageDigest md=null;

		String str="";

		if(debugAES){
			System.out.println("Compute encryption key");
		}

		/**calculate key to use*/
		byte[] key=getPaddedKey(encryptionPassword);

		if(debugAES){
			str="raw before 50 times   ---- ";
			for(int ii=0;ii<key.length;ii++)
				str=str+key[ii]+ ' ';
			System.out.println(str);
		}

		/**feed into Md5 function*/
		try{

			// Obtain a message digest object.
			md = MessageDigest.getInstance("MD5");
			encryptionKey=md.digest(key);

			if(debugAES){
				str="encryptionKey before 50 times   ---- ";
				for(int ii=0;ii<key.length;ii++)
					str=str+key[ii]+ ' ';
				System.out.println(str);
			}

			/**rev 3 extra security*/
			if(rev>=3){
				for (int ii=0; ii<50; ii++)
					encryptionKey = md.digest(encryptionKey);
			}

		}catch(Exception e){
			throw new PdfSecurityException("Exception "+e+" generating encryption key");
		}

		if(debugAES){
			str="returned encryptionKey   ---- ";
			for(int ii=0;ii<encryptionKey.length;ii++)
				str=str+encryptionKey[ii]+ ' ';
			System.out.println(str);
		}

	}

	/**see if valid for password*/
	private boolean testOwnerPassword() throws PdfSecurityException{

		String str="";

		if(debugAES)
			System.out.println("testOwnerPassword "+encryptionPassword.length);

		byte[] originalPassword=this.encryptionPassword;

		byte[] userPasswd=new byte[keyLength];
		byte[] inputValue=(byte[])O.clone();

		if(debugAES){
			str="originalPassword   ---- ";
			for(int ii=0;ii<originalPassword.length;ii++)
				str=str+originalPassword[ii]+ ' ';
			System.out.println(str);

		}

		computeEncryptionKey();

		byte[] originalKey=(byte[])encryptionKey.clone();

		if(rev==2){
			userPasswd=decrypt((byte[])O.clone(),"", false,null,false,false);
		}else if(rev>=3){

			//use StmF values in preference
			int keyLength=this.keyLength;
			if(rev==4 && StmF!=null){
				String lenKey=(String)StmF.get("Length");
				if(lenKey!=null)
					keyLength=Integer.parseInt(lenKey);

			}

			if(debugAES)
				System.out.println("Decrypt 20 times");

			userPasswd=inputValue;
			byte[] nextKey = new byte[keyLength];


			for (int i=19; i>=0; i--) {

				for (int j=0; j<keyLength; j++)
					nextKey[j] = (byte)(originalKey[j] ^ i);

				encryptionKey=nextKey;
				userPasswd=decrypt(userPasswd,"", false,null,true,false);

			}
		}

		//this value is the user password if correct
		//so test
		encryptionPassword = userPasswd;

		computeEncryptionKey();

		boolean isMatch=testPassword();

		if(debugAES && !isMatch){
			System.out.println("Match failed on owner key");
			System.exit(1);
		}

		//put back to original if not in fact correct
		if(isMatch==false){
			encryptionPassword=originalPassword;
			computeEncryptionKey();
		}

		return isMatch;
	}

	/**
	 * find a valid offset
	 */
	final private String findOffsets() throws PdfSecurityException {
		
		LogWriter.writeLog("Corrupt xref table - trying to find objects manually");

		String root_id = "";
		try {
			movePointer(0);
		} catch (Exception e) {
			e.printStackTrace();
		}

		while (true) {
			String line = null;

			int i = (int) this.getPointer();

			try {
				line = pdf_datafile.readLine();
			} catch (Exception e) {
				LogWriter.writeLog("Exception " + e + " reading line");
			}
			if (line == null)
				break;

			if (line.indexOf(" obj") != -1) {

				int pointer = line.indexOf(' ');
				if (pointer > -1) {
					int current_number = Integer.parseInt(line.substring(0,
							pointer));
					storeObjectOffset(current_number, i, 1,false);
				}

			} else if (line.indexOf("Root") != -1) {

				int start = line.indexOf("Root") + 4;
				int pointer = line.indexOf('R', start);
				if (pointer > -1)
					root_id = line.substring(start, pointer + 1).trim();
			} else if (line.indexOf("/Encrypt") != -1) {
				//too much risk on corrupt file
				throw new PdfSecurityException("Corrupted, encrypted file");
			}
		}

		return root_id;
	}

	/**extract  metadata for  encryption object
	 */
	final public void readEncryptionObject(String ref) throws PdfSecurityException {

		//reset flags
		stringsEncoded=false;
		isMetaDataEncypted=true;


		//<start-13>
		//<start-jfr>
		if (!isInitialised) {
			isInitialised = true;
			SetSecurity.init();
		}
		//<end-jfr>

		//get values
		Map encryptionValues = readObject(new PdfObject(ref), ref, true, null);

		if(debugAES)
			System.out.println(encryptionValues);

		//check type of filter and see if supported
		String filter = (String) encryptionValues.get("Filter");
		int v = 1;
		String value = (String) encryptionValues.get("V");
		if (value != null)
			v = Integer.parseInt(value);

		value = (String) encryptionValues.get("Length");
		if (value != null)
			keyLength = Integer.parseInt(value) / 8;

		if(v==3)
			throw new PdfSecurityException("Unsupported Custom Adobe Encryption method " + encryptionValues);

		//throw exception if we have an unknown encryption method
		if ((v > 4) && (filter.indexOf("Standard") == -1))
			throw new PdfSecurityException("Unsupported Encryption method " + encryptionValues);

		//get rest of the values (which are not optional)
		rev = Integer.parseInt((String) encryptionValues.get("R"));
		P = Integer.parseInt((String) encryptionValues.get("P"));

		Object OValue = encryptionValues.get("O");
		if (OValue instanceof String) {
			String hexString = (String) OValue;
			int keyLength = hexString.length() / 2;
			O = new byte[keyLength];

			for (int ii = 0; ii < keyLength; ii++) {
				int p = ii * 2;
				O[ii] =
					(byte) Integer.parseInt(hexString.substring(p, p + 2), 16);
			}

		} else {
			O = (byte[]) OValue;
		}

		Object UValue = encryptionValues.get("U");
		if (UValue instanceof String) {
			String hexString = (String) UValue;
			int keyLength = hexString.length() / 2;
			U = new byte[keyLength];

			for (int ii = 0; ii < keyLength; ii++) {
				int p = ii * 2;
				U[ii] =
					(byte) Integer.parseInt(hexString.substring(p, p + 2), 16);
			}

		} else {
			U = (byte[]) UValue;
		}

		//get additional AES values
		if(v==4){
			Map CF=(Map) encryptionValues.get("CF");

			EFF=(String) encryptionValues.get("EFF");
			CFM=(String) encryptionValues.get("CFM");

			Object encryptionSetting=encryptionValues.get("EncryptMetadata");
			if(encryptionSetting!=null && ((String)encryptionSetting).toLowerCase().equals("false"))
				isMetaDataEncypted=false;

			//now set any specific crypt values for StrF (strings) and StmF (streams)
			String key=(String) encryptionValues.get("StrF");
			if(key!=null){
				key=key.substring(1); //lose /
				StrFname=key;
				StrF=(Map) CF.get(key);
				stringsEncoded=true;
			}else
				StrF=null;

			key=(String) encryptionValues.get("StmF");
			if(key!=null){
				key=key.substring(1); //lose /
				StmFname=key;
				StmF=(Map) CF.get(key);
			}else
				StmF=null;

		}

		//<end-13>
		isEncrypted = true;
		isFileViewable = false;

		LogWriter.writeLog("File has encryption settings");

		try{
			verifyAccess();
		}catch(PdfSecurityException e){
			LogWriter.writeLog("File requires password");
		}

	}

	/**test password and set access settings*/
	private void verifyAccess() throws PdfSecurityException{

		/**assume false*/
		isPasswordSupplied=false;
		extractionIsAllowed=false;

		passwordStatus=PDFflags.NO_VALID_PASSWORD;

		//<start-13>
		/**workout if user or owner password valid*/
		boolean isOwnerPassword =testOwnerPassword();


		if(!isOwnerPassword){
			boolean isUserPassword=testPassword();

			/**test if user first*/
			if(isUserPassword){

				//tell if not default value
				if(encryptionPassword.length>0)
					LogWriter.writeLog("Correct user password supplied ");

				isFileViewable=true;
				isPasswordSupplied=true;

				if((P & 16)==16)
					extractionIsAllowed=true;

				passwordStatus=PDFflags.VALID_USER_PASSWORD;

            }else
				throw new PdfSecurityException("No valid password supplied");

		}else{
			LogWriter.writeLog("Correct owner password supplied");
            isFileViewable=true;
			isPasswordSupplied=true;
			extractionIsAllowed=true;
			passwordStatus=PDFflags.VALID_OWNER_PASSWORD;
		}

		//<end-13>
	}

	/**
	 * calculate the key
	 */
	private byte[] calculateKey(byte[] O,int P,String ID) throws PdfSecurityException{

		if(debugAES)
			System.out.println("calculate key");

		String str="";

		MessageDigest md=null;

		byte[] keyValue=null;

		/**calculate key to use*/
		byte[] key=getPaddedKey(encryptionPassword);

		/**feed into Md5 function*/
		try{

			// Obtain a message digest object.
			md = MessageDigest.getInstance("MD5");

			//add in padded key
			md.update(key);

			//write in O value
			md.update(O);

			byte[] PValue=new byte[4];
			PValue[0]=((byte)((P) & 0xff));
			PValue[1]=((byte)((P>>8) & 0xff));
			PValue[2]=((byte)((P>>16) & 0xff));
			PValue[3]=((byte)((P>>24) & 0xff));

			md.update(PValue);

			str="documentID   ---- ";

			//feed in ID
			byte[] IDbytes=new byte[ID.length()/2];
			for(int ii=0;ii<ID.length();ii=ii+2){
				String nextValue=ID.substring(ii,ii+2);
				IDbytes[ii/2]=(byte)Integer.parseInt(nextValue,16);

				str=str+(IDbytes[ii/2])+ ' ';
			}
			md.update(IDbytes);

			if(debugAES)
				System.out.println(str+" \n"+ID);



			byte[] metadataPad = {(byte)255,(byte)255,(byte)255,(byte)255};

			if (rev==4 && !this.isMetaDataEncypted)
				md.update(metadataPad);

			byte digest[] = new byte[keyLength];
			System.arraycopy(md.digest(), 0, digest, 0, keyLength);

			//for rev 3
			if(rev>=3){
				for (int i = 0; i < 50; ++i)
					System.arraycopy(md.digest(digest), 0, digest, 0, keyLength);
			}

			keyValue=new byte[keyLength];
			System.arraycopy(digest, 0, keyValue, 0, keyLength);

		}catch(Exception e){

			e.printStackTrace();
			//System.exit(1);
			throw new PdfSecurityException("Exception "+e+" generating encryption key");
		}

		/**put significant bytes into key*/
		byte[] returnKey = new byte[keyLength];
		System.arraycopy(keyValue,0, returnKey,0, keyLength);

		return returnKey;
	}

	///////////////////////////////////////////////////////////////////////////
	/**
	 * read information object and return pointer to correct
	 * place
	 */
	final private void readInformationObject(String value) {

		try {

			//LogWriter.writeLog("Information object "+value+" present");

			Map fields=new HashMap();
			String[] names=currentFileInformation.getFieldNames();
			for(int ii=0;ii<names.length;ii++){
				fields.put(names[ii],"z");
			}

			//get info
			Map info_values = readObject(new PdfObject(value), value,false, fields);

			//System.out.println(info_values);
			/**set the information values*/

			//put into fields so we can display
			for (int i = 0; i < names.length; i++){
				Object nextValue=info_values.get(names[i]);
				//System.out.println(names[i]);
				if(nextValue!=null){
					/**allow for stream value*/
					if(nextValue instanceof byte[]){
						//System.out.println(names[i]);
						String textValue=getTextString((byte[]) nextValue);
						//System.out.println(textValue+"<<<<<<<<<<<<<<<<<<<<<<<");
						currentFileInformation.setFieldValue( i,textValue);
					}else if(nextValue instanceof String ){
						String stringValue=(String)nextValue;
						if(stringValue.indexOf("False")!=-1){
							currentFileInformation.setFieldValue( i,"False");
						}else if(stringValue.indexOf("False")!=-1){
							currentFileInformation.setFieldValue( i,"True");
						}else{
							//System.out.println("TEXT value "+nextValue+" in file "+ObjectStore.getCurrentFilename());
							//System.exit(1);
							//currentFileInformation.setFieldValue( i,newValue.toString());
						}
					}else{
						//System.out.println("TEXT value "+nextValue+" in file "+ObjectStore.getCurrentFilename());
						//System.exit(1);
						//currentFileInformation.setFieldValue( i,newValue.toString());
					}
				}
			}


		} catch (Exception e) {
			System.out.println(" problem with info");
			LogWriter.writeLog(
					"Exception " + e + " reading information object "+value);
			//System.exit(1);
		}
	}
	/**
	 * return pdf data
	 */
	public byte[] getPdfBuffer() {
		return pdf_datafile.getPdfBuffer();
	}

	/**
	 * read a text String held in fieldName in string
	 */
	public String getTextString(byte[] rawText) {

		String returnText="";

		//make sure encoding loaded
		StandardFonts.checkLoaded(StandardFonts.PDF);

		String text="";

		//retest on false and true
		final boolean debug=false;

		char[] chars=null;
		if(rawText!=null)
			chars=new char[rawText.length];
		int ii=0;

		StringBuffer convertedText=null;
		if(debug)
			convertedText=new StringBuffer();

		char nextChar;

		TextTokens rawChars=new TextTokens(rawText);

		//test to see if unicode
		if(rawChars.isUnicode()){
			//its unicode
			while(rawChars.hasMoreTokens()){
				nextChar=rawChars.nextUnicodeToken();
				if(nextChar==9){
					if(debug)
						convertedText.append(' ');
					chars[ii]=32;
					ii++;
				}else if(nextChar>31){
					if(debug)
						convertedText.append(nextChar);
					chars[ii]=nextChar;
					ii++;
				}
			}

		}else{
			//pdfDoc encoding

			while(rawChars.hasMoreTokens()){
				nextChar=rawChars.nextToken();

				if(nextChar==9){
					if(debug)
						convertedText.append(' ');
					chars[ii]=32;
					ii++;
				}else if(nextChar>31){
					String c=StandardFonts.getEncodedChar(StandardFonts.PDF,nextChar);

					if(debug)
						convertedText.append(c);

					int len=c.length();

					//resize if needed
					if(ii+len>=chars.length){
						char[] tmp=new char[len+ii+10];
						System.arraycopy(chars, 0, tmp, 0, chars.length);
						chars=tmp;
					}

					//add values
					for(int i=0;i<len;i++){
						chars[ii]=c.charAt(i);
						ii++;
					}
				}
			}
		}


		if(chars!=null)
			returnText=String.copyValueOf(chars,0,ii);

		if(debug){
			if(!convertedText.toString().equals(returnText)){
				System.out.println("Different values >"+convertedText+"<>"+returnText+ '<');
				System.exit(1);
			}
		}

		//System.exit(1);
		return returnText;

	}

	/**
	 * read any Javascript names
	 */
	public void readJavascriptNames(Object nameObj, Javascript javascript){

		Map values=null;

		if(nameObj instanceof String)
			values=readObject(new PdfObject((String)nameObj), (String)nameObj,false,null);
		else
			values=(Map) nameObj;

		String names=getValue((String) values.get("Names"));

		if(names!=null){
			String nameList = Strip.removeArrayDeleminators(names); //get initial pages
			if(nameList.startsWith("<feff")){ //handle [<feff005f00500041004700450031> 1 0 R]
				StringTokenizer keyValues =new StringTokenizer(nameList);
				while(keyValues.hasMoreTokens()){
					String nextKey=keyValues.nextToken();
					nextKey=nextKey.substring(1,nextKey.length()-1);
					String value=keyValues.nextToken()+ ' ' +keyValues.nextToken()+ ' ' +keyValues.nextToken();


					//<start-os>
					javascript.setCode(nextKey,value);
					//<end-os>
				}

			}else if(nameList.indexOf('(')!=-1){ //its a binary list so we need to read from the raw bytes

				/**read the raw bytes so we can decode correctly*/
				String objectRef = (String) nameObj;
				byte[] raw=null;
				
				/**allow for indirect*/
				if(objectRef.endsWith("]"))
					objectRef=Strip.removeArrayDeleminators(objectRef);
				
				if(objectRef.endsWith(" R")){
				
					int ptr=objectRef.indexOf(' ');
					int objectID=Integer.parseInt(objectRef.substring(0,ptr));
					int gen=Integer.parseInt(objectRef.substring(ptr+1,objectRef.indexOf(' ',ptr+1)));
				
					raw = readObjectAsByteArray(objectRef, false,isCompressed(objectID,gen), objectID, gen);
				
				}else
					raw=objectRef.getBytes();
				
				
				int dataLen=raw.length;
				int i=0;

				/**move to /Names*/
				while(true){
					if((raw[i]==47)&&(raw[i+1]==78)&&(raw[i+2]==97)&&(raw[i+3]==109)&&(raw[i+4]==101)&&(raw[i+5]==115))
						break;
					i++;
				}

				i=i+5;

				/**
				 * read all value pairs
				 */
				while(i<dataLen){

					/**
					 *move to first (
					 */
					while(raw[i]!=40){

						i++;
					}
					//i++;

					ByteArrayOutputStream bis=new ByteArrayOutputStream();
					try{
						if(raw[i+1]!=41){ //trap empty field

							/**
							 * read the bytes for the text string
							 */
							while(true){

								i++;
								boolean isOctal=false;

								//trap escape
								if((raw[i]==92)&&((raw[i-1]!=92)||((raw[i-1]==92)&&(raw[i-2]==92)))){

									i++;

									if(raw[i]=='b')
										raw[i]='\b';
									else if(raw[i]=='n')
										raw[i]='\n';
									else if(raw[i]=='t')
										raw[i]='\t';
									else if(raw[i]=='r')
										raw[i]='\r';
									else if(raw[i]=='f')
										raw[i]='\f';
									else if(raw[i]=='\\')
										raw[i]='\\';
									else if(Character.isDigit((char) raw[i])){ //octal
										StringBuffer octal=new StringBuffer(3);
										for(int ii=0;ii<3;ii++){
											octal.append((char)raw[i]);
											i++;
										}
										//move back 1
										i--;
										isOctal=true;
										raw[i]=(byte) Integer.parseInt(octal.toString(),8);
									}

								}

								//exit at end
								if((!isOctal)&&(raw[i]==41)&&((raw[i-1]!=92)||((raw[i-1]==92)&&(raw[i-2]==92))))
									break;

								bis.write(raw[i]);
							}
						}

						//decrypt the text stream
						bis.close();
						byte[] streamData=bis.toByteArray();
						streamData=decrypt(streamData,(String)nameObj, false,null,false,false);

						/**
						 * read object  ref
						 */
						StringBuffer objectName=new StringBuffer();
						i++;

						//lose any dodgy chars
						while((raw[i]==32)|(raw[i]==10)|(raw[i]==13))
							i++;

						int end=i;

						//ignore any extra spaces
						while(raw[end]==32){
							end++;
						}

						//find next start ( or [
						boolean isEmbedded=false;
						while((raw[end]!=40)&(end+1<dataLen)){
							if(raw[end]==91)
								isEmbedded=true;
							if(raw[end]==93) //lookout for ]
							break;
							end++;
						}

						//roll on to get ]
						if(isEmbedded)
							end=end+2;

						int nextStart=end;
						//safety catch for end
						if((!isEmbedded)&&(raw[end]==']')){
							nextStart=dataLen;
						}

						//ignore any extra spaces at end
						while(raw[end]==32)
							end--;
						int charLength=end-i;

						objectName=new StringBuffer(charLength);
						for(int ii=0;ii<charLength;ii++){
							objectName.append((char)raw[ii+i]);

							if(((!isEmbedded)&&(raw[ii+i]==82))|(raw[ii+i]==93))
								break;

						}

						/**
						 * get Javascript action
						 */
						 Map code=this.readObject(new PdfObject(objectName.toString()), objectName.toString(),false,null);

						 //check type
						 String type=(String) code.get("S");


						 //<start-os>
						 setJavascriptCommand(code.get("JS"), streamData);
						 //<end-os>

						 i=nextStart; //next item or exit at end
					}catch(Exception e){
						e.printStackTrace();
					}
				}
			}else{
				LogWriter.writeLog("Javascript format not supported");
			}
		}
	}

	//<start-os>
	private void setJavascriptCommand(Object JS, byte[] streamData) {

		if(JS instanceof String){
			//ensure decoded
			this.readObject(new PdfObject((String)JS), (String)JS,false,null);
			byte[] data=this.readStream((String)JS,true);
			String script=new String(data);

			//store
			javascript.setCode(getTextString(streamData),script);

		}else{
		}
	}

	public void setJavascriptForObject(String objRef,Object code,int actionType) {

		if(code instanceof Map){

			Object JS=((Map)code).get("JS");

			//allow for JS as object
			if(JS instanceof String){
				Map obj=this.readObject(new PdfObject((String)JS), (String)JS,false,this.fields);
				JS=obj.get("Stream");
			}

			if(JS instanceof byte[]){

				String script=this.getTextString((byte[])JS);
				//store
				javascript.storeJavascript(objRef,script,actionType);

			}else if(JS==null){
				//we need to set the field in T to S (hide or unhide), when hovering over this field
				Map codeMap = (Map)code;
				if(((String)codeMap.get("S")).indexOf("Hide")!=-1){
					codeMap.put("T",resolveToMapOrString("T",codeMap.get("T")));
					javascript.storeJavascript(objRef,codeMap,actionType);
				}
			}else {
			}
		}else{


		}
	}

	//<end-os>

	/**
	 * read any names
	 */
	public void readNames(Object nameObj, Javascript javascript){

		Map values=null;

		if(nameObj instanceof String)
			values=readObject(new PdfObject((String)nameObj), (String)nameObj,false,null);
		else
			values=(Map) nameObj;

		Object dests= values.get("Dests");
		String names=getValue((String) values.get("Names"));
		String javaScriptRef=(String) values.get("JavaScript");

		if(names!=null){
			String nameList = Strip.removeArrayDeleminators(names); //get initial pages
			if(nameList.startsWith("<feff")){ //handle [<feff005f00500041004700450031> 1 0 R]
				StringTokenizer keyValues =new StringTokenizer(nameList);
				while(keyValues.hasMoreTokens()){
					String nextKey=keyValues.nextToken();
					nextKey=nextKey.substring(1,nextKey.length()-1);
					String value=keyValues.nextToken()+ ' ' +keyValues.nextToken()+ ' ' +keyValues.nextToken();
					nameLookup.put(nextKey,value);
				}

			}else if(nameList.indexOf('(')!=-1){ //its a binary list so we need to read from the raw bytes

				/**read the raw bytes so we can decode correctly*/
				String objectRef = (String) nameObj;
				byte[] raw=null;
				
				/**allow for indirect*/
				if(objectRef.endsWith("]"))
					objectRef=Strip.removeArrayDeleminators(objectRef);
				
				if(objectRef.endsWith(" R")){
				
					int ptr=objectRef.indexOf(' ');
					int objectID=Integer.parseInt(objectRef.substring(0,ptr));
					int gen=Integer.parseInt(objectRef.substring(ptr+1,objectRef.indexOf(' ',ptr+1)));
				
					raw = readObjectAsByteArray(objectRef, false,isCompressed(objectID,gen), objectID, gen);
				
				}else
					raw=objectRef.getBytes();
				
				int dataLen=raw.length;
				int i=0;

				/**move to /Names*/
				while(true){
					if((raw[i]==47)&&(raw[i+1]==78)&&(raw[i+2]==97)&&(raw[i+3]==109)&&(raw[i+4]==101)&&(raw[i+5]==115))
						break;
					i++;
				}

				i=i+5;

				/**
				 * read all value pairs
				 */
				while(i<dataLen){

					/**
					 *move to first (
					 */
					while(raw[i]!=40){

						i++;
					}
					//i++;

					ByteArrayOutputStream bis=new ByteArrayOutputStream();
					try{
						if(raw[i+1]!=41){ //trap empty field

							/**
							 * read the bytes for the text string
							 */
							while(true){

								i++;
								boolean isOctal=false;

								//trap escape
								if((raw[i]==92)&&((raw[i-1]!=92)||((raw[i-1]==92)&&(raw[i-2]==92)))){

									i++;

									if(raw[i]=='b')
										raw[i]='\b';
									else if(raw[i]=='n')
										raw[i]='\n';
									else if(raw[i]=='t')
										raw[i]='\t';
									else if(raw[i]=='r')
										raw[i]='\r';
									else if(raw[i]=='f')
										raw[i]='\f';
									else if(raw[i]=='\\')
										raw[i]='\\';
									else if(Character.isDigit((char) raw[i])){ //octal
										StringBuffer octal=new StringBuffer(3);
										for(int ii=0;ii<3;ii++){
											octal.append((char)raw[i]);
											i++;
										}
										//move back 1
										i--;
										isOctal=true;
										raw[i]=(byte) Integer.parseInt(octal.toString(),8);
									}

								}

								//exit at end
								if((!isOctal)&&(raw[i]==41)&&((raw[i-1]!=92)||((raw[i-1]==92)&&(raw[i-2]==92))))
									break;

								bis.write(raw[i]);
							}
						}

						//decrypt the text stream
						bis.close();
						byte[] streamData=bis.toByteArray();
						streamData=decrypt(streamData,(String)nameObj, false,null, false,false);

						/**
						 * read object  ref
						 */
						StringBuffer objectName=new StringBuffer();
						i++;

						//lose any dodgy chars
						while((raw[i]==32)|(raw[i]==10)|(raw[i]==13))
							i++;

						int end=i;

						//ignore any extra spaces
						while(raw[end]==32){
							end++;
						}

						//find next start ( or [
						boolean isEmbedded=false;
						while((raw[end]!=40)&(end+1<dataLen)){
							if(raw[end]==91)
								isEmbedded=true;
							if(raw[end]==93) //lookout for ]
								break;
							end++;
						}

						//roll on to get ]
						if(isEmbedded)
							end=end+2;

						int nextStart=end;
						//safety catch for end
						if((!isEmbedded)&&(raw[end]==']')){
							nextStart=dataLen;
						}

						//ignore any extra spaces at end
						while(raw[end]==32)
							end--;
						int charLength=end-i;

						objectName=new StringBuffer(charLength);
						for(int ii=0;ii<charLength;ii++){
							objectName.append((char)raw[ii+i]);

							if(((!isEmbedded)&&(raw[ii+i]==82))|(raw[ii+i]==93))
								break;

						}
						/**
							System.out.println(getTextString(streamData)+" "+objectName+"<<");

							if(getTextString(streamData).toString().indexOf("G2.1375904")!=-1){

							System.exit(1);
							}*/
						//store
						nameLookup.put(getTextString(streamData),objectName.toString());

						i=nextStart; //next item or exit at end
					}catch(Exception e){
						e.printStackTrace();
					}
				}

			}else{
				LogWriter.writeLog("Name list format not supported");

			}

		}else if(dests!=null){
			Map destValues=null;
			if(dests instanceof String)
				destValues=readObject(new PdfObject((String)dests), (String)dests,false,null);
			else
				destValues=(Map) dests;

			//handle any kids
			String kidsObj=(String)destValues.get("Kids");
			if(kidsObj!=null){
				String kids = Strip.removeArrayDeleminators(getValue((String) destValues.get("Kids"))); //get initial pages
				if (kids.length() > 0) {/**allow for empty value and put next pages in the queue*/
					StringTokenizer initialValues =new StringTokenizer(kids, "R");
					while (initialValues.hasMoreTokens())
						readNames(initialValues.nextToken().trim() + " R",javascript);
				}
			}
		}else if(javaScriptRef!=null){


			//<start-os>
			//for the moment just flag Javascript as there
			if(javascript!=null)
				javascript.readJavascript();
			//<end-os>

		}else{
			//handle any kids
			String kidsObj=(String)values.get("Kids");
			if(kidsObj!=null){
				String kids = Strip.removeArrayDeleminators(getValue((String) values.get("Kids"))); //get initial pages
				if (kids.length() > 0) {/**allow for empty value and put next pages in the queue*/				
					StringTokenizer initialValues =new StringTokenizer(kids, "R");
					while (initialValues.hasMoreTokens())
						readNames(initialValues.nextToken().trim() + " R",javascript);
				}	
			}
		}
	}

	/**
	 * convert name into object ref
	 */
	public String convertNameToRef(String value) {

		return (String) nameLookup.get(value);
	}

	/**
	 * convert all object refs (ie 1 0 R) into actual data.
	 * Works recursively to cover all levels.
	 * @param pageLookup
	 */
	public void flattenValuesInObject(boolean addPage,boolean keepKids,Map formData, Map newValues,Map fields, PageLookup pageLookup,String formObject) {
		final boolean debug =false;

		if(debug)
			System.out.println(formData);

		if(addPage)
			newValues.put("PageNumber","1");

		Iterator keys=formData.keySet().iterator();
		while(keys.hasNext()){
			String currentKey=(String) keys.next();
			Object currentValue=null;

			if(debug)
				System.out.println("currentKey="+currentKey);

			if(currentKey.equals("P")){
				//add page
				try{
					Object rawValue=formData.get("P");

					if(rawValue!=null && pageLookup!=null && rawValue instanceof String){
						int page = pageLookup.convertObjectToPageNumber((String) rawValue);
						newValues.put("PageNumber", String.valueOf(page));
						//currentForm.remove("P");
					}
				}catch(Exception e){

				}
			}else if(currentKey.equals("Stream")){
				/**read the stream*/
				byte[] objectData =readStream(formData,formObject,false,true,false, false,false);

				newValues.put("DecodedStream",objectData);
			}else if((!currentKey.equals("Kids"))&&(!currentKey.equals("Parent"))){
				currentValue=formData.get(currentKey);
			}else if((keepKids) &&(currentKey.equals("Kids"))){

				String kidsList=(String) formData.get("Kids");
				if(kidsList!=null){

					Map formObjects=new HashMap();

					//handle any square brackets (ie arrays)
					if (kidsList.startsWith("["))
						kidsList =kidsList.substring(1, kidsList.length() - 1).trim();

					//put kids in the queue
					StringTokenizer kidObjects =new StringTokenizer(kidsList, "R");

					while (kidObjects.hasMoreTokens()) {
						String next_value =kidObjects.nextToken().trim() + " R";

						Map stringValue=new HashMap();
						flattenValuesInObject(true,keepKids,readObject(new PdfObject(next_value), next_value,false,fields), stringValue,fields,pageLookup,formObject);
						formObjects.put(next_value,stringValue);
					}

					newValues.put("Kids",formObjects);

				}
			}

			if(debug)
				System.out.println("currentValue="+currentValue);

			if(currentValue!=null){
				if(currentKey.equals("rawValue")){

					if(currentValue instanceof byte[]){

						byte[] fieldBytes=getByteTextStringValue(currentValue,fields);

						if(fieldBytes!=null)
							currentValue=getTextString(fieldBytes);
					}
				}else  if((fields!=null)&&(fields.get(currentKey)!=null)&&(currentValue instanceof byte[])){

					byte[] fieldBytes=getByteTextStringValue(currentValue,fields);

					if(fieldBytes!=null)
						currentValue=getTextString(fieldBytes);
				}

				if(currentValue instanceof String){ //remap xx=[1 0 R] but not [1 0 R 2 0 R]
					String keyString=currentValue.toString();
					StringTokenizer tokens=new StringTokenizer(keyString);
					if(tokens.countTokens()==3){
						int i1=keyString.indexOf(" R");
						int i2=keyString.indexOf(" R",i1+1);

						if(debug)
							System.out.println("i1="+i1+" i2="+i2);

						if((i2==-1)&&(keyString.endsWith("]")&&(keyString.indexOf(" R")!=-1))){
							keyString=Strip.removeArrayDeleminators(keyString);
							formObject=keyString;
							Map stringValue=new HashMap();
							flattenValuesInObject(addPage,keepKids,readObject(new PdfObject(keyString), keyString,false,fields), stringValue,fields,pageLookup,formObject);
							newValues.put(currentKey,stringValue);

						}else if((i2==-1)&&(keyString.endsWith(" R"))){
							Map stringValue=new HashMap();
							formObject=keyString;
							flattenValuesInObject(addPage,keepKids,readObject(new PdfObject(keyString), keyString,false,fields), stringValue,fields,pageLookup,formObject);
							newValues.put(currentKey,stringValue);
						}else
							newValues.put(currentKey,currentValue);
					}else //if /CalRGB 6 0 R just put back or the moment
						newValues.put(currentKey,currentValue);
				}else if(currentValue instanceof Map){ // rewmap {N=35 0 R}
					Map valueMap=(Map) currentValue;
					Map updatedValue=new HashMap();
					flattenValuesInObject(addPage,keepKids,valueMap, updatedValue,fields,pageLookup,formObject);
					newValues.put(currentKey,updatedValue);
				}else{
					newValues.put(currentKey,currentValue);
				}
			}
		}
	}

	/**
	 * set size over which objects kept on disk
	 */
	public void setCacheSize(int miniumumCacheSize) {
		this.miniumumCacheSize=miniumumCacheSize;

	}

	/**read data directly from PDF*/
	public byte[] readStreamFromPDF(int start, int end) {

		byte[] bytes=new byte[end-start+1];

		//get bytes into buffer
		try {
			movePointer(start);
			pdf_datafile.read(bytes);
		} catch (IOException e) {
			e.printStackTrace();
		}

		return bytes;
	}

	public void setInterruptRefReading(boolean value) {
		interruptRefReading=value;

	}

	public void readStreamIntoMemory(Map downField) {
		String cachedStream = ((String)downField.get("CachedStream"));

		try {
			BufferedInputStream bis = new BufferedInputStream(new FileInputStream(cachedStream));

			int streamLength = (int)new File(cachedStream).length();

			byte[] bytes = new byte[streamLength];

			bis.read(bytes);
			bis.close();

			downField.put("DecodedStream", bytes);
		} catch (Exception e) {
			e.printStackTrace();
		}
	}

}
