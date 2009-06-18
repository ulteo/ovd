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
* ExtractPagesAsThumbnails.java
* ---------------
*/
package org.jpedal.examples.images;

//JFC
import java.awt.Graphics2D;
import java.awt.Image;
import java.awt.image.BufferedImage;
import java.io.File;
import java.io.FileOutputStream;

import org.jpedal.PdfDecoder;
import org.jpedal.external.Options;
import org.jpedal.io.JAIHelper;
import org.jpedal.objects.PdfFileInformation;
import org.jpedal.utils.LogWriter;

/**
 * 
 * This example opens a pdf file and extracts the images version of each
 * page which it saves as an image scaled to users specification
 */
public class ExtractPagesAsThumbnails {

	/**output where we put files*/
	private String user_dir = System.getProperty("user.dir");
	
	/**use 72 dpi as default*/
	private int dpi=72;

	/**flag to show if we print messages*/
	public static boolean outputMessages = false;
	

	/**flag to show if we generate multiple sizes*/
	public static boolean multipleSizes = true;
	
	String output_dir="";

	/**correct separator for OS */
	String separator = System.getProperty("file.separator");

	/**the decoder object which decodes the pdf and returns a data object*/
	PdfDecoder decode_pdf = null;

	//type of image to save thumbnails
	private static String format = "png";
	
	/** holding all creators that produce OCR pdf's */
	private String[] ocr = {"TeleForm"};

	/**flag to show if using images at highest quality -switch on with command line flag Dhires*/
	private boolean useHiresImage=false;

	/**sample file which can be setup - substitute your own. 
	 * If a directory is given, all the files in the directory will be processed*/
	private static String test_file = "/mnt/shared/sample_pdfs/general/World Factbook.pdf";

	/**used as part of test to limit pages to first 10*/
	public static boolean isTest=false;
	
	/**scaling to use - default is 100 percent*/
	private static int scaling=100;

	//not called
	private ExtractPagesAsThumbnails() {}

	/**example method to open a file and extract the pages as scaled
	 * thumbnails
	 */
	public ExtractPagesAsThumbnails(String file_name,String output_dir) {

        //get any user set dpi
		String userDpi=System.getProperty("org.jpedal.dpi");
		if(userDpi!=null){
			try{
				
				dpi=Integer.parseInt(userDpi);
				
			}catch(Exception e){
				System.err.println("Problem with value "+userDpi+" (must be integer)");
				System.err.println(e);
				System.exit(1);
			}
		}
		
		//get any user set dpi
		String hiresFlag = System.getProperty("org.jpedal.hires");
		if(PdfDecoder.hires || hiresFlag != null){
			useHiresImage=true;
		}
		
		this.output_dir=output_dir;
		//check output dir has separator
		if (user_dir.endsWith(separator) == false)
			user_dir = user_dir + separator;

		/**
		 * if file name ends pdf, do the file otherwise 
		 * do every pdf file in the directory. We already know file or
		 * directory exists so no need to check that, but we do need to
		 * check its a directory
		 */
		if (file_name.toLowerCase().endsWith(".pdf")) {
			
			if(!ExtractPagesAsThumbnails.isTest)
			output_dir=user_dir + "thumbnails" + separator;
			
			decodeFile(file_name,output_dir);
		} else {

			/**
			 * get list of files and check directory
			 */

			String[] files = null;
			File inputFiles = null;

			/**make sure name ends with a deliminator for correct path later*/
			if (!file_name.endsWith(separator))
				file_name = file_name + separator;

			try {
				inputFiles = new File(file_name);

				if (!inputFiles.isDirectory()) {
					System.err.println(
						file_name + " is not a directory. Exiting program");
				}
				files = inputFiles.list();
			} catch (Exception ee) {
				LogWriter.writeLog(
					"Exception trying to access file " + ee.getMessage());
			}

			/**now work through all pdf files*/
			long fileCount = files.length;

			for (int i = 0; i < fileCount; i++) {

				if (files[i].toLowerCase().endsWith(".pdf")) {
					if (outputMessages)
						System.out.println(file_name + files[i]);

					decodeFile(file_name + files[i],output_dir);
				}
			}
		}

		/**tell user*/
		if(outputMessages)
		System.out.println("Thumbnails created");

	}

	/**
	 * routine to decode a file
	 */
	private void decodeFile(String file_name,String output_dir) {
		
		/**get just the name of the file without 
		 * the path to use as a sub-directory
		 */
		
		String name = "demo"; //set a default just in case
		
		int pointer = file_name.lastIndexOf(separator);
		
		if(pointer==-1)
			pointer = file_name.lastIndexOf('/');
		
		if (pointer != -1){
			name = file_name.substring(pointer + 1, file_name.length() - 4);
		}else if((!ExtractPagesAsThumbnails.isTest)&&(file_name.toLowerCase().endsWith(".pdf"))){
			name=file_name.substring(0,file_name.length()-4);
		}
		
		//create output dir for images
		if(output_dir==null)
			output_dir = user_dir + "thumbnails" + separator ;
		
		//PdfDecoder returns a PdfException if there is a problem
		try {
			decode_pdf = new PdfDecoder(true);

            /**optional JAI code for faster rendering*/
            //org.jpedal.external.ImageHandler myExampleImageHandler=new org.jpedal.examples.handlers.ExampleImageDrawOnScreenHandler();
            //decode_pdf.addExternalHandler(myExampleImageHandler, Options.ImageHandler);

             /**/
			
			/**
	         * font mappings
	         */
			if(!isTest){
		        //set specific cases for Windows
		        String[] aliases1={"helvetica","arial"};
				decode_pdf.setSubstitutedFontAliases("arial",aliases1);
	
				String[] aliases2={"Helvetica-Bold"};
				decode_pdf.setSubstitutedFontAliases("Arial-BoldMT",aliases2);
	
		        String[] aliases3={"Times-Bold","Times-Roman"};
				decode_pdf.setSubstitutedFontAliases("timesbd",aliases3);
	
		        //set general mappings for non-embedded fonts (assumes names the same)
		        PdfDecoder.setFontDirs(new String[]{"C:/windows/fonts/","C:/winNT/fonts/","/System/Library/Fonts/","/Library/Fonts/"});
			}

            //<start-os>
			if(useHiresImage)
			decode_pdf.useHiResScreenDisplay(true);
			//<end-os>
			
			//true as we are rendering page
			decode_pdf.setExtractionMode(0, dpi,dpi/72);
			//don't bother to extract text and images
			
			/**
			 * open the file (and read metadata including pages in  file)
			 */
			if (outputMessages)
				System.out.println("Opening file :" + file_name+" at "+dpi+" dpi");
			
			decode_pdf.openPdfFile(file_name);
			
		} catch (Exception e) {
			//e.printStackTrace();
			
			System.err.println(
					"Exception " + e + " in pdf code");
			
			//System.exit(1);
		}
		
		/**
		 * extract data from pdf (if allowed). 
		 */
		if ((decode_pdf.isEncrypted()&&(!decode_pdf.isPasswordSupplied()))
				&& (!decode_pdf.isExtractionAllowed())) {
			if (outputMessages) {
				System.out.println("Encrypted settings");
				System.out.println(
				"Please look at SimpleViewer for code sample to handle such files");
			}
		} else {
			
			//create a directory if it doesn't exist
			File output_path = new File(output_dir);
			if (!output_path.exists())
				output_path.mkdirs();
			
			//page range
			int start = 1, end = decode_pdf.getPageCount();
			
			//limit to 1st ten pages in testing
			if((end>10)&&(isTest))
				end=10;
			
			/**
			 * extract data from pdf and then write out the pages as images
			 */
			if (outputMessages)
				System.out.println("Thumbnails will be in  " + output_dir);
			
			try {
				
				for (int page = start;
				page < end + 1;
				page++) { //read pages
					
					if (outputMessages)
						System.out.println("Page " + page);
					
					String image_name =name+ '_' +dpi+"_page_" + page;
					
					/**
					 * get PRODUCER and if OCR disable text printing
					 */
					PdfFileInformation currentFileInformation=decode_pdf.getFileInformationData();
					
					String[] values=currentFileInformation.getFieldValues();
					String[] fields=currentFileInformation.getFieldNames();
					
					for(int i=0;i<fields.length;i++){
						
						if(fields[i].equals("Creator")){
							
							for(int j=0;j<ocr.length;j++){
								
								if(values[i].equals(ocr[j])){
									
									decode_pdf.setRenderMode(PdfDecoder.RENDERIMAGES);
									
									/**
									 * if we want to use java 13 JPEG conversion
									 */
									decode_pdf.setEnableLegacyJPEGConversion(true);
									
								}
							}
						}
					}
					
					//get the current page as a BufferedImage
					/**API CHANGE
					 * does not require prior call to decodePage
					 */
					BufferedImage image_to_save =decode_pdf.getPageAsImage(page);
					
					//use this if you want a transparent image
					//BufferedImage image_to_save =decode_pdf.getPageAsTransparentImage(page);
					
					if (image_to_save == null) {
						if (outputMessages)
							System.out.println(
							"No image generated - are you using client mode?");
					} else {
						
						/**BufferedImage does not support any dpi concept. A higher dpi can be created 
						 * using JAI to convert to a higher dpi image*/
						
						//shrink the page to 50% with graphics2D transformation
						//- add your own parameters as needed
						//you may want to replace null with a hints object if you
						//want to fine tune quality.
						
						/** example 1 biliniear scaling
						 AffineTransform scale = new AffineTransform();
						 scale.scale(.5, .5); //50% as a decimal
						 AffineTransformOp scalingOp =new AffineTransformOp(scale, null);
						 image_to_save =scalingOp.filter(image_to_save, null);
						 
						 */
						
						/** example 2 bicubic scaling - better quality but slower
						 to preserve aspect ratio set newWidth or newHeight to -1*/
						
						/**allow user to specify maximum dimension for thumbnail*/
						String maxDimensionAsString = System.getProperty("maxDimension");
						int maxDimension = -1;
						
						if(maxDimensionAsString != null)
							maxDimension = Integer.parseInt(maxDimensionAsString);
						
						if(scaling!=100 || maxDimension != -1){
							int newWidth=image_to_save.getWidth()*scaling/100;
							int newHeight=image_to_save.getHeight()*scaling/100;
							
							Image scaledImage=null;
							if(maxDimension != -1 && (newWidth > maxDimension || newHeight > maxDimension)){
								if(newWidth > newHeight){
									newWidth = maxDimension;
									scaledImage= image_to_save.getScaledInstance(newWidth,-1,BufferedImage.SCALE_SMOOTH);
								} else {
									newHeight = maxDimension;
									scaledImage= image_to_save.getScaledInstance(-1,newHeight,BufferedImage.SCALE_SMOOTH);
								}
							} else {
								scaledImage= image_to_save.getScaledInstance(newWidth,-1,BufferedImage.SCALE_SMOOTH);
							}
							
							if(format.toLowerCase().startsWith("jp"))
								image_to_save = new BufferedImage(scaledImage.getWidth(null),scaledImage.getHeight(null) , BufferedImage.TYPE_INT_RGB);
							else
								image_to_save = new BufferedImage(scaledImage.getWidth(null),scaledImage.getHeight(null) , BufferedImage.TYPE_INT_ARGB);
							
							Graphics2D g2 = image_to_save.createGraphics();
							
							g2.drawImage(scaledImage, 0, 0,null);
						}
						
						String tiffFlag=System.getProperty("org.jpedal.compress_tiff");
						boolean compressTiffs = tiffFlag!=null;

                        if(JAIHelper.isJAIused())
                        JAIHelper.confirmJAIOnClasspath();

                        if(compressTiffs && JAIHelper.isJAIused()){

                            com.sun.media.jai.codec.TIFFEncodeParam params = null;

                            params = new com.sun.media.jai.codec.TIFFEncodeParam();
							params.setCompression(com.sun.media.jai.codec.TIFFEncodeParam.COMPRESSION_DEFLATE);
							
							FileOutputStream os = new FileOutputStream(output_dir + page + image_name+".tif");
							javax.media.jai.JAI.create("encode", image_to_save, os, "TIFF", params);
						}else{
							//save image
							boolean failed=decode_pdf.getObjectStore().saveStoredImage(
									output_dir + page + image_name,
									image_to_save,
									true,
									false,
									format);
						}
						//if you just want to save the image, use something like
						//javax.imageio.ImageIO.write((java.awt.image.RenderedImage)image_to_save,"png",new java.io.FileOutputStream(output_dir + page + image_name+".png"));
						
					}
					
					//flush images in case we do more than 1 page so only contains
					//images from current page
					decode_pdf.flushObjectValues(true);
					//flush any text data read
					
				}
			} catch (Exception e) {
				decode_pdf.closePdfFile();
				System.err.println("Exception " + e.getMessage()+" with thumbnails");
			}
		}
		
		/**close the pdf file*/
		decode_pdf.closePdfFile();
		
	}
	//////////////////////////////////////////////////////////////////////////
	/**
	 * main routine which checks for any files passed and runs the demo
	 */
	public static void main(String[] args) {
		System.out.println("Simple demo to extract images from a page");
		
		//set to default
		String file_name = test_file;
		boolean failed = false;
		//check user has passed us a filename and use default if none
		int len=args.length;
		if (len == 0){
			showCommandLineValues();
		}else if(len == 1){
			file_name = args[0];
			System.out.println("file name="+file_name);
		}else if(len <4){
			
			//input
			file_name = args[0];
			System.out.println("File :" + file_name);
			
			for(int j=1;j<args.length;j++){
				String value=args[j];
				boolean isNumber=isNumber(value);
				
				if(isNumber){
					try{
						scaling=Integer.parseInt(value);
					}catch(Exception e){
						System.out.println(value+" is not an integer");
						System.exit(1);
					}
				}else{
					String in = value.toLowerCase();
					if((in.equals("jpg"))|(in.equals("jpeg")))
						format="jpg";
					else if(in.equals("tif")|in.equals("tiff"))
						format="tif";
					else if(in.equals("png"))
						format="png";
					else{
						failed=true;
						System.out.println("value args not recognised as valid parameter.");
						System.out.println("please enter \"jpg\", \"jpeg\", \"tif\", \"tiff\" or \"png\".");
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
			
			showCommandLineValues();
		}
		
		//check file exists
		File pdf_file = new File(file_name);

		//if file exists, open and get number of pages
		if (pdf_file.exists() == false) {	
			System.out.println("File " + pdf_file + " not found");
			System.out.println("MAy need full path");
			
			return;
		}
		
		ExtractPagesAsThumbnails images1 =
				new ExtractPagesAsThumbnails(file_name,null);
		
	}

	private static void showCommandLineValues() {
		System.out.println("Example can take 1, 2 or 3 parameters");
		System.out.println("Value 1 is the file name or directory of PDF files to process");
		System.out.println("2 optional values of image type (jpeg,tiff,png) and scaling (100 = full size) can also be added");
		System.exit(1);
	}
	
	/**test to see if string or number*/
	private static boolean isNumber(String value) {
		
		//assume true and see if proved wrong
		boolean isNumber=true;
		
		int charCount=value.length();
		for(int i=0;i<charCount;i++){
			char c=value.charAt(i);
			if((c<'0')|(c>'9')){
				isNumber=false;
				i=charCount;
			}
		}
		
		return isNumber;
	}

	/**
	 * @return Returns the output_dir.
	 */
	public String getOutputDir() {
		return output_dir;
	}

}
