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
* ExtractImages.java
* ---------------
*/

package org.jpedal.examples.images;

//JFC
import java.awt.image.BufferedImage;
import java.io.ByteArrayOutputStream;
import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.util.Iterator;
import java.util.Map;

import javax.imageio.ImageIO;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;
import javax.xml.transform.stream.StreamSource;

import org.jpedal.PdfDecoder;

import org.jpedal.io.*;
import org.jpedal.objects.PdfImageData;
import org.jpedal.utils.LogWriter;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.Node;

/**
 * Simple image extraction with images are extracted to a directory
 */
public class ExtractImages
{

	/**use dir for output*/
	private String user_dir = System.getProperty( "user.dir" );
	
	/**flag to show if we print messages*/
	public static boolean outputMessages = true;
	
	/**the decoder object which decodes the pdf and returns a data object*/
	PdfDecoder decode_pdf = null;

	/**correct separator for OS */	
	String separator = System.getProperty( "file.separator" );
	
	/**location output files written to*/
	private String output_dir="";

	public static String testOutputDir="current_images/";

	public static boolean isTest;
	
	//type of image to save
	private static String prefix = "png";

	/**sample file which can be setup - substitute your own. 
	 * If a directory is given, all the files in the directory will be processed*/
	private static String test_file = "/mnt/shared/Poloznicel_nalozbene_test_1.pdf";
	
	public ExtractImages()
	{

	}

	/**example method to open a file and extract the images*/
	public ExtractImages( String file_name )
	{
		
		/**debugging code to create a log*/
		// LogWriter.setupLogFile(true,1,"","v",false);
	//	 LogWriter.log_name =  "/mnt/shared/log.txt"; 
		 /***/
	
		//check root dir has separator
		if( user_dir.endsWith( separator ) == false )
			user_dir = user_dir + separator;
	
		/**
		 * if file name ends pdf, do the file otherwise 
		 * do every pdf file in the directory. We already know file or
		 * directory exists so no need to check that, but we do need to
		 * check its a directory
		 */
		if(file_name.toLowerCase().endsWith(".pdf")){
			decode(file_name);	
		}else{
			
			/**
			 * get list of files and check directory
			 */
			
			String[] files = null;
			File inputFiles = null;
			
			/**make sure name ends with a deliminator for correct path later*/
			if(!file_name.endsWith(separator))
			file_name=file_name+separator;
		
			try
			{
				inputFiles = new File( file_name );
				
				if(!inputFiles.isDirectory()){
					System.err.println(file_name+" is not a directory. Exiting program");
				}
				files = inputFiles.list();
			}
			catch( Exception ee )
			{
				LogWriter.writeLog( "Exception trying to access file " + ee.getMessage() );
			}
		
			/**now work through all pdf files*/
			long fileCount=files.length;
			
			for(int i=0;i<fileCount;i++){
				System.out.println(i+"/ "+fileCount+ ' ' +files[i]);
				
				if(files[i].endsWith(".pdf")){
					System.out.println(file_name+files[i]);
				
					decode(file_name+files[i]);
				}	
			}
		}
	}
	
	/**
	 * routine to open and decode a pdf pages
	 */
	private void decode(String file_name){

		/**get just the name of the file without 
		 * the path to use as a sub-directory or .pdf
		 */
		
		String name="demo"; //set a default just in case
		
		int pointer=file_name.lastIndexOf(separator);
		
		if(pointer!=-1)
		name=file_name.substring(pointer+1,file_name.length()-4);
		
		//PdfDecoder returns a PdfException if there is a problem
		try
		{
			decode_pdf = new PdfDecoder( false );
			
			/**
			 * use this version of setExtraction in code below to alter dpi from 72
			 */
			//decode_pdf.setExtractionMode(PdfDecoder.FINALIMAGES,dpi,1);
			
			//tell JPedal what we want it to extract - flag added so user can run and extract OPI
			String opiFlag=System.getProperty("org.jpedal.opi");
			if(opiFlag==null)
				decode_pdf.setExtractionMode(PdfDecoder.RAWIMAGES+PdfDecoder.FINALIMAGES);
			else
				decode_pdf.setExtractionMode(PdfDecoder.RAWIMAGES+PdfDecoder.FINALIMAGES+PdfDecoder.XFORMMETADATA);
			
			/**
			 * open the file (and read metadata including pages in  file)
			 */
			 if(outputMessages)	
				 System.out.println( "Opening file :" + file_name );
			
			decode_pdf.openPdfFile( file_name );
			
			 //byte version to open file
			 /**
			 FileInputStream file = new FileInputStream(file_name);
			 byte[] arr = toByteArray(file);
			 decode_pdf.openPdfArray(arr);*/
		}
		catch( Exception e )
		{
			System.err.println( "Exception " + e + " in pdf code" );
		}
		
		/**
		 * extract data from pdf (if allowed). 
		 */
		if ((decode_pdf.isEncrypted()&&(!decode_pdf.isPasswordSupplied()))&&(!decode_pdf.isExtractionAllowed())) {
			 if(outputMessages)	{
				System.out.println("Encrypted settings");
				System.out.println("Please look at SimpleViewer for code sample to handle such files");
				System.out.println("Or get support/consultancy");
			 }
		}else{

		//page range
		int start = 1, end =decode_pdf.getPageCount();

		/**
		 * create output dir for images
		 */
		output_dir = user_dir + "images" + separator+name+separator;

		
		//create a directory if it doesn't exist
		File output_path = new File( output_dir );
		if( output_path.exists() == false )
			output_path.mkdirs();

		/**
		 * extract data from pdf and then write out the images
		 */
		 if(outputMessages)	
		System.out.println( "Images will be in directory " + output_dir );
		try
		{
			for( int page = start;page < end + 1;page++ )
			{ //read pages

				//decode the page
				decode_pdf.decodePage( page );

				//get the PdfImages object which now holds the images.
				//binary data is stored in a temp directory and we hold the
				//image name and other info in this object
				PdfImageData pdf_images = decode_pdf.getPdfImageData();

				//image count (note image 1 is item 0, so any loop runs 0 to count-1)
				int image_count = pdf_images.getImageCount();

				//tell user
				if( image_count > 0 ){
					if(outputMessages)
					System.out.println( "Page "+page+" contains " + image_count + " images" );
				
					//create a directory for page
					
					String target=output_dir+separator+page;
					
					
					File page_path = new File( target );
					if( page_path.exists() == false )
						page_path.mkdirs();

				}
				
				//work through and save each image
				for( int i = 0;i < image_count;i++ )
				{
					String image_name = pdf_images.getImageName( i );
					BufferedImage image_to_save;
					
					try{
						
						//get raw version of image (R prefix for raw image)
						image_to_save = decode_pdf.getObjectStore().loadStoredImage('R' + image_name );
						
						String outputDir=output_dir + page +separator;
						
						
						saveImage(image_to_save,outputDir+ 'R' + image_name+ '.' +prefix,prefix);
						  
						//load processed version of image (converted to rgb)
						image_to_save = decode_pdf.getObjectStore().loadStoredImage( image_name );
						
						//save image
						saveImage(image_to_save,outputDir+ image_name+ '.' +prefix,prefix);
						
						/**save metadata as XML file in 1.4/1.5 java - not in 1.3*/
						//<start-13>
						outputMetaDataToXML(file_name, page, pdf_images, i, image_name); 
						//<end-13>
						
					}
					catch( Exception ee )
					{
						System.err.println( "Exception " + ee + " in extracting images" );
					}
				}

				//flush images in case we do more than 1 page so only contains
				//images from current page
				decode_pdf.flushObjectValues(true);
			}
		}
		catch( Exception e )
		{
			decode_pdf.closePdfFile();
			System.err.println( "Exception " + e.getMessage() );
		}
		
		/**tell user*/
		 if(outputMessages)	
		System.out.println( "Images read" );

		}
		
		/**close the pdf file*/
		decode_pdf.closePdfFile();

	}

	/**save image - different versions have different bugs for file formats so we use best for 
	 * each image type
	 * @param image_to_save
	 */
	private void saveImage(BufferedImage image_to_save, String fileName,String prefix) {

        if(JAIHelper.isJAIused())
        JAIHelper.confirmJAIOnClasspath();

        //we recommend JAI for tifs
        if(prefix.indexOf("tif")!=-1 && JAIHelper.isJAIused()){

			try {

				FileOutputStream os = new FileOutputStream(fileName);

				//get tiff compression
				String tiffFlag=System.getProperty("org.jpedal.compress_tiff");
				boolean compressTiffs = tiffFlag!=null;
				
				com.sun.media.jai.codec.TIFFEncodeParam params = null;
				if(compressTiffs){
					params = new com.sun.media.jai.codec.TIFFEncodeParam();
					params.setCompression(com.sun.media.jai.codec.TIFFEncodeParam.COMPRESSION_DEFLATE);
				}
				
				javax.media.jai.JAI.create("encode", image_to_save, os, "TIFF", params);
			} catch (FileNotFoundException e) {
				e.printStackTrace();
			}

		}else{ //default
			try {
				
				ImageIO.write(image_to_save,prefix,new File(fileName));
				
			} catch (IOException e) {
				e.printStackTrace();
			}
		}

	}

	//<start-13>
	/**
	 * write out details of image to XML file
	 */
	private void outputMetaDataToXML(String file_name, int page, PdfImageData pdf_images, int i, String image_name) {
		/**
		 * save xml file with info
		*/
		float x1=pdf_images.getImageXCoord(i);
		float y1=pdf_images.getImageYCoord(i);
		float w=pdf_images.getImageWidth(i);
		float h=pdf_images.getImageHeight(i);
		
		try{
		    //create doc and set root
			DocumentBuilderFactory dbf = DocumentBuilderFactory.newInstance();
			DocumentBuilder db = dbf.newDocumentBuilder();
			Document doc = db.newDocument();
			
		    Node root=doc.createElement("meta");
		    doc.appendChild(root);
		   
		    //add comments
		    Node creation=doc.createComment("Created "+org.jpedal.utils.TimeNow.getShortTimeNow());
		    doc.appendChild(creation);
		    Node info=doc.createComment("Pixel Location of image x1,y1,x2,y2");
		    doc.appendChild(info);
		    Node moreInfo=doc.createComment("x1,y1 is top left corner origin is bottom left corner");
		    doc.appendChild(moreInfo);
		    
		    //add location
		    Element location=doc.createElement("PAGELOCATION");
		    location.setAttribute("x1", String.valueOf(x1));
		    location.setAttribute("y1", String.valueOf((y1 + h)));
		    location.setAttribute("x2", String.valueOf((x1 + w)));
		    location.setAttribute("y2", String.valueOf(y1));
		    root.appendChild(location);
		    
		    //add pdf file extracted from
		    Element fileName=doc.createElement("FILE");
		    fileName.setAttribute("value",file_name);
		    root.appendChild(fileName);
		    
		    //any xform data
		    String parentXform=pdf_images.getParentXForm(image_name);
		    if(parentXform!=null){
		    	
		    		Element opiData=doc.createElement("OPI");
		    		root.appendChild(opiData);
		        
		    		Map XformData=pdf_images.getXFormData(parentXform);
		    		
		    		addOPIDataToXML(opiData, XformData,doc);
		    
		    }
		    
		    //write out
		    //use System.out for FileOutputStream to see on screen
		    if(!isTest){
		        InputStream stylesheet = this.getClass().getResourceAsStream("/org/jpedal/examples/simpleviewer/res/xmlstyle.xslt");
				
				TransformerFactory transformerFactory = TransformerFactory.newInstance();
				Transformer transformer = transformerFactory.newTransformer(new StreamSource(stylesheet));
				transformer.transform(new DOMSource(doc), new StreamResult(output_dir + page +separator+ image_name + ".xml"));
		        
		    }
		    
		}catch(Exception e){
		    e.printStackTrace();
		}
	}
	
	/**
	 * used to recursively write OPI values to output
	 */
	private void addOPIDataToXML(Element opiData, Map XformData,Document doc) {
		Iterator keys=XformData.keySet().iterator();
		while(keys.hasNext()){
			String key=(String)keys.next();
			Object value=XformData.get(key);
			
			if(value instanceof String){
				Element subKey=doc.createElement(key);
				opiData.appendChild(subKey);
				subKey.setAttribute("value",(String)value);
				
			}else if(value instanceof Map){
				
				Element subKey=null;
				
				if((key.equals("1.3"))||(key.equals("2.0"))){
					subKey=opiData;
					subKey.setAttribute("version",key);
				}else{
					subKey=doc.createElement(key);
					opiData.appendChild(subKey);
				}
				addOPIDataToXML(subKey, (Map) value, doc);
			}
		}
	}
	//<end-13>
	
	
	//////////////////////////////////////////////////////////////////////////
	/**
	 * main routine which checks for any files passed and runs the demo
	 */
	public static void main( String[] args )
	{
		if(outputMessages)
		System.out.println( "Simple demo to extract images from a page" );

		//set to default
		String file_name = test_file;
		boolean failed = false;
		
		//check user has passed us a filename and use default if none
		int len=args.length;
		if (len == 0){
			System.out.println("Example can take 1 or 2 parameters");
			System.out.println("Value 1 is the file name or directory of PDF files to process");
			System.out.println("Value 2 is optional values of image type (jpeg,tiff,png). Default is png");
			System.exit(1);
		}else if(len == 1){
			file_name = args[0];
			System.out.println("file name="+file_name);
		}else if(len <3){
			
			//input
			file_name = args[0];
			
			if(outputMessages)
			System.out.println("File :" + file_name);
			
			for(int j=1;j<args.length;j++){
				String value=args[j];
				
				{
					String in = value.toLowerCase();
					if(in.equals("tif")|in.equals("tiff"))
						prefix="tif";
					else if(in.equals("png"))
						prefix="png";
					else{
						failed=true;
						System.out.println("value args not recognised as valid parameter.");
						System.out.println("please enter \"tif\", \"tiff\" or \"png\".");
					}
				}
			}
	
		}else {
			failed=true;
			System.out.println("too many arguments entered - run with no values to see defaults");
		}
		
		if(failed){
			String arguments="";
			for(int a=0;a<args.length;a++)
				arguments=arguments+args[a]+ '\n';
			System.out.println("you entered:\n"+arguments+"as the arguments");
		}

		//check file exists
		File pdf_file = new File( file_name );

		//if file exists, open and get number of pages
		if( pdf_file.exists() == false )
		{
			System.out.println( "File " + file_name + " not found" );
		}
		ExtractImages images1 = new ExtractImages( file_name );
	}
	
	/**
	 * @return Returns the output_dir.
	 */
	public String getOutputDir() {
		return output_dir;
	}
	
	// Method reads from an InputStream and returns a byteArray
	private byte[] toByteArray( InputStream in ) throws IOException
	{
		if ( in == null )
		{
			return null;
		}
		ByteArrayOutputStream buffer = new ByteArrayOutputStream();
		byte[] transfer = new byte[ 8192 ];
		int count = 0;
		while ( count != -1 )
		{
			count = in.read( transfer );
			if ( count > 0 )
			{
				buffer.write( transfer, 0, count );
			}
		}
		return buffer.toByteArray();
	}
}
