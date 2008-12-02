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
* ExtractClippedImages.java
* ---------------
*/

package org.jpedal.examples.images;

import java.awt.*;
import java.awt.image.BufferedImage;
import java.io.File;
import java.io.FileOutputStream;
import java.io.OutputStreamWriter;
import java.util.ResourceBundle;

import org.jpedal.PdfDecoder;
import org.jpedal.io.JAIHelper;
import org.jpedal.io.ColorSpaceConvertor;
import org.jpedal.objects.PdfImageData;
import org.jpedal.utils.LogWriter;
import org.jpedal.utils.Messages;

import javax.imageio.ImageIO;

/**
 * Sample code providing a workflow which extracts clipped images and places versions
 * scaled to specific heights
 * 
 * It is run using the format
 *
 * java -cp libraries_needed org/jpedal/examples/ ExtractClippedImages $inputDir $processedDir $logFile h1 dir1 h2 dir2 ... hn dirn 
 * 
 * Values with SPACES but be surrounded by "" as in "This is one value"
 * The values passed are
 *
 * $inputDir - directory containing files
 * $processedDIr - directory to put files in
 * $log - path and name of logfile
 *
 * Any number of h - height required in pixels as an integer for output (-1 means keep current size) dir1 - directory to write out images
 *
 * So to create 3 versions of the image (one at original size, one at 100 and one at 50 pixels high), you would use
 *
 * java -cp libraries_needed org/jpedal/examples/ ExtractScalesImages /export/files/ /export/processedFiles/ /logs/image.log -1 /output/raw/ 100 /output/medium/ 50 /output/thumbnail/
 *
 * Note image quality depends on the raw image in the original.
 * 
 * This can be VERY memory intensive 
 *
 */
public class ExtractClippedImages
{

	/**flag to show if we print messages*/
	public static boolean outputMessages = false;
	
	/**directory to place files once decoded*/
	private static String processed_dir="processed";
	
	/**used for regression tests by IDR solutions*/
	public static boolean testing=false;

	/**rootDir containing files*/
	private static String inputDir="";
	
	/**number of output directories*/
	private static int outputCount;
	
	/**sizes to output at -1 means unchanged*/
	private static float[] outputSizes;
	
	/**target directories for files*/
	private static String[] outputDirectories;
	
	/**the decoder object which decodes the pdf and returns a data object*/
	PdfDecoder decode_pdf = null;

	/**correct separator for OS */	
	static final private String separator = System.getProperty( "file.separator" );
	
	/**location output files written to*/
	private String output_dir="clippedImages";
	
	/**type of image to save*/
	private String imageType = "tiff";

    /**background colour to add to JPEG*/
    private Color backgroundColor=Color.WHITE;

    private static RenderingHints hint = null;
	
	static {
		hint =
		new RenderingHints(
				RenderingHints.KEY_INTERPOLATION,
				RenderingHints.VALUE_INTERPOLATION_BICUBIC);
		hint.put(
				RenderingHints.KEY_ANTIALIASING,
				RenderingHints.VALUE_ANTIALIAS_OFF);
		hint.put(
				RenderingHints.KEY_DITHERING,
				RenderingHints.VALUE_DITHER_DISABLE);
	/**
		hint.put(
				RenderingHints.KEY_RENDERING,
				RenderingHints.VALUE_RENDER_QUALITY);
		/**
		hint.put(
				RenderingHints.KEY_ANTIALIASING,
				RenderingHints.VALUE_ANTIALIAS_ON);
		hint.put(
				RenderingHints.KEY_DITHERING,
				RenderingHints.VALUE_DITHER_ENABLE);
				
		hint.put(
				RenderingHints.KEY_COLOR_RENDERING,
				RenderingHints.VALUE_COLOR_RENDER_QUALITY);
		hint.put(
				RenderingHints.KEY_FRACTIONALMETRICS,
				RenderingHints.VALUE_FRACTIONALMETRICS_ON);
				*/
	}

	
	/**example method to extract the images from a directory*/
	public ExtractClippedImages( String rootDir )
	{

        String newImageType=System.getProperty("org.jpedal.imageType");
        if(newImageType!=null){
            imageType=newImageType.toLowerCase();

            if(imageType.equals("tif") || imageType.equals("tiff") )
                imageType="tiff";
            else if(imageType.equals("jpg")|| imageType.equals("jpeg") )
                imageType="jpg";
            else if(!imageType.equals("png"))
                exit("Imagetype "+imageType+" not supported");
        }
        
        processFiles( rootDir );
		
	}
	
	/**example method to extract the images from a directory*/
	private void processFiles( String rootDir )
	{
	
		/**make sure name ends with a deliminator for correct path later*/
		if((!rootDir.endsWith("\\"))&&(!rootDir.endsWith("/")))
			rootDir=rootDir+separator;
		
		/**make sure name ends with a deliminator for correct path later*/
		if(!processed_dir.endsWith(separator))
			processed_dir=processed_dir+separator;
		
		/**check it is a directory*/
		File testDir=new File(rootDir);
		if(!testDir.isDirectory()){
			exit("No root directory "+rootDir);
		}
		
		/**
		 * get list of files 
		 */
		String[] files = null;
			
		try{
			File inputFiles = new File( rootDir );
			System.out.println(inputFiles.getAbsolutePath());
			if(!inputFiles.isDirectory()){
				System.err.println(rootDir+" is not a directory. Exiting program");
			}
			files = inputFiles.list();
		}catch( Exception ee ){
			exit( "Exception trying to access file " + ee.getMessage() );
		}
	
		/**now work through all pdf files*/
		long fileCount=files.length;
		
		for(int i=0;i<fileCount;i++){
			
			if(files[i].toLowerCase().endsWith(".pdf")){
				
				if(outputMessages)
				System.out.println(rootDir+files[i]);
			
				
				/**
				 * decode the file
				 */
				decode(rootDir+files[i],72);
				
				/**archive the file*/
				File currentFile=new File(rootDir+files[i]);
				currentFile.renameTo(new File(processed_dir+files[i]));
			}	
		}
	}
	
	/**
	 * exit routine for code
	 */
	private static void exit(String string) {
		
		System.out.println("Exit message "+string);
		LogWriter.writeLog("Exit message "+string);
		
	}

	/**
	 * routine to decode a PDF file
	 */
	private void decode(String file_name,int dpi){

		/**setup the output direct
		/**get just the name of the file without 
		 * the path to use as a sub-directory or .pdf
		 */
		
		String name="demo"; //set a default just in case
		
		LogWriter.writeLog("==================");
		LogWriter.writeLog("File "+file_name);
		
		int pointer=file_name.lastIndexOf(separator);
		
		if(pointer!=-1)
		name=file_name.substring(pointer+1,file_name.length()-4);
		
		//PdfDecoder returns a PdfException if there is a problem
		 try{
			
		 	decode_pdf = new PdfDecoder( false );
			decode_pdf.setExtractionMode(PdfDecoder.FINALIMAGES+PdfDecoder.CLIPPEDIMAGES,dpi,1);
		
			 /** open the file (and read metadata including pages in  file)*/
			  decode_pdf.openPdfFile( file_name );
			 
		 }catch( Exception e ){
			  exit(Messages.getMessage("PdfViewerError.Exception")+ ' ' +
					  e + ' ' +Messages.getMessage("PdfViewerError.OpeningPdfFiles"));
		 }
		 
		/**
		 * extract data from pdf (if allowed). 
		 */
		if ((decode_pdf.isEncrypted()&&(!decode_pdf.isPasswordSupplied()))&&(!decode_pdf.isExtractionAllowed())) {
			exit(Messages.getMessage("PdfViewerError.EncryptedNotSupported"));
		}else{
		
		//page range
		int start = 1, end =decode_pdf.getPageCount();

		try{
			for( int page = start;page < end + 1;page++ ){ //read pages

				LogWriter.writeLog(Messages.getMessage("PdfViewerDecoding.page")+ ' ' +page);
				
				//decode the page
				decode_pdf.decodePage( page );

				//get the PdfImages object which now holds the images.
				//binary data is stored in a temp directory and we hold the
				//image name and other info in this object
				PdfImageData pdf_images = decode_pdf.getPdfImageData();

				//image count (note image 1 is item 0, so any loop runs 0 to count-1)
				int image_count = pdf_images.getImageCount();

				//tell user
				if( image_count > 0 )
					LogWriter.writeLog("page"+ ' ' +page+"contains "+image_count
					+ " images");
				else
					LogWriter.writeLog("No bitmapped images on page "+page);
				
				LogWriter.writeLog("Writing out images");
				//work through and save each image
				for( int i = 0;i < image_count;i++ ){
					
					String image_name =pdf_images.getImageName( i );
					BufferedImage image_to_save;
					
					float x1=pdf_images.getImageXCoord(i);
					float y1=pdf_images.getImageYCoord(i);
					float w=pdf_images.getImageWidth(i);
					float h=pdf_images.getImageHeight(i);
					
					for(int versions=0;versions<outputCount;versions++){
						try{
							//find out format image was saved in
							String type = decode_pdf.getObjectStore().getImageType( image_name );
	
							//load image (converted to rgb)
							
							
							image_to_save =decode_pdf.getObjectStore().loadStoredImage(  "CLIP_"+image_name );
							
							int index = file_name.lastIndexOf('\\');
							if(index==-1)
								index = file_name.lastIndexOf('/');
							if(index==-1)
								index=0;
							String nameToUse = file_name.substring(index,file_name.length()-4);
							String outputName=outputDirectories[versions]+nameToUse + '_' + page + '_' +i;
							
							float scaling=1;
							
							int newHeight=image_to_save.getHeight();
							
							//scale
							if(outputSizes[versions]>0){
								
								scaling=outputSizes[versions]/newHeight;
								
								if(scaling>1){
									scaling=1;
								}else{
									/**
									 AffineTransform scale = new AffineTransform();
									 scale.scale(scaling, scaling); //scale
									 AffineTransformOp scalingOp =new AffineTransformOp(scale, hint);
									 //image_to_save =scalingOp.filter(image_to_save, null);
									 */
									 
									 /**
									AffineTransformOp op = new AffineTransformOp(
											AffineTransform.getScaleInstance(scaling,
													scaling),
											AffineTransformOp.TYPE_BILINEAR);
									image_to_save = 
									op.filter(image_to_save, null);
								*/
									Image scaledImage= image_to_save.getScaledInstance(-1,(int)outputSizes[versions],BufferedImage.SCALE_SMOOTH);
								
									image_to_save = new BufferedImage(scaledImage.getWidth(null),scaledImage.getHeight(null) , BufferedImage.TYPE_INT_ARGB);
							
									Graphics2D g2 =image_to_save.createGraphics();
									
									g2.drawImage(scaledImage, 0, 0,null);
									//ImageIO.write((RenderedImage) scaledImage,"PNG",new File(outputName));
									
									
								}
							}
							
							String tiffFlag=System.getProperty("org.jpedal.compress_tiff");
							boolean compressTiffs = tiffFlag!=null;

                            //if(compressTiffs)
                            JAIHelper.confirmJAIOnClasspath();

                            //no transparency on JPEG so give background and draw on
                            if(imageType.startsWith("jp")){

                                int iw=image_to_save.getWidth();
                                int ih=image_to_save.getHeight();
                                BufferedImage background=new BufferedImage(iw,ih, BufferedImage.TYPE_INT_RGB);

                                Graphics2D g2=(Graphics2D)background.getGraphics();
                                g2.setPaint(backgroundColor);
                                g2.fillRect(0,0,iw,ih);

                                g2.drawImage(image_to_save,0,0,null);
                                image_to_save= background;
                                
                            }

                            if(testing){ //used in regression tests
                            	decode_pdf.getObjectStore().saveStoredImage( outputName, image_to_save, true, false, imageType );
                            }else if(JAIHelper.isJAIused() && imageType.startsWith("tif")){
                                
                            	LogWriter.writeLog("Saving image with JAI "+outputName+ '.'+imageType);
                            	
                            	com.sun.media.jai.codec.TIFFEncodeParam params = null;

                                if(compressTiffs){
                                    params = new com.sun.media.jai.codec.TIFFEncodeParam();
								    params.setCompression(com.sun.media.jai.codec.TIFFEncodeParam.COMPRESSION_DEFLATE);
                                }

                                FileOutputStream os = new FileOutputStream(outputName+".tif");
								
								javax.media.jai.JAI.create("encode", image_to_save, os, "TIFF", params);

                                os.flush();
								os.close();
								
							}else{
								
								//save image
								LogWriter.writeLog("Saving image "+outputName+ '.'+imageType);
								ImageIO.write(image_to_save,imageType,new File(outputName+ '.'+imageType));
								//decode_pdf.getObjectStore().saveStoredImage( outputName, image_to_save, true, false, imageType );
							}
							//save an xml file with details
							/**
							 * output the data
							 */
							//LogWriter.writeLog( "Writing out "+(outputName + ".xml"));
							OutputStreamWriter output_stream =
							new OutputStreamWriter(
									new FileOutputStream(outputName + ".xml"),
									"UTF-8");

							output_stream.write(
							"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
							output_stream.write(
							"<!-- Pixel Location of image x1,y1,x2,y2\n");
							output_stream.write("(x1,y1 is top left corner)\n");
							output_stream.write(
							"(origin is bottom left corner)  -->\n");
							output_stream.write("\n\n<META>\n");
							output_stream.write(
									"<PAGELOCATION x1=\""+ x1+ "\" "
									+ "y1=\""+ (y1+h)+ "\" "
									+ "x2=\""+ (x1+w)+ "\" "
									+ "y2=\""+ (y1)+ "\" />\n");
							output_stream.write("<FILE>"+file_name+"</FILE>\n"); 
							output_stream.write("<ORIGINALHEIGHT>"+newHeight+"</ORIGINALHEIGHT>\n"); 
							output_stream.write("<SCALEDHEIGHT>"+image_to_save.getHeight()+"</SCALEDHEIGHT>\n"); 
							output_stream.write("<SCALING>"+scaling+"</SCALING>\n"); 
							output_stream.write("</META>\n");
							output_stream.close();
						}catch( Exception ee ){
							LogWriter.writeLog( "Exception " + ee + " in extracting images" );
						}
					}
				}

				//flush images in case we do more than 1 page so only contains
				//images from current page
				decode_pdf.flushObjectValues(true);
				
				//System.out.println("debug end");
				//System.exit(1);
			}
		}catch( Exception e ){
			decode_pdf.closePdfFile();
			LogWriter.writeLog( "Exception " + e.getMessage() );
		}
		
		}

		/**close the pdf file*/
		decode_pdf.closePdfFile();

	}
	
	/**
	 * @return Returns the output_dir.
	 */
	public String getOutputDir() {
		return output_dir;
	}
	
	/**
	 * main routine which checks for any files passed and runs the demo
	 */
	public static void main( String[] args )
	{

        long start=System.currentTimeMillis();
	
		Messages.setBundle(ResourceBundle.getBundle("org.jpedal.international.messages"));
		
		if(outputMessages)
		System.out.println( "Simple demo to extract images from a page at various heights" );

        /**exit and report if wrong number of values*/
		if(((args.length & 1)==0)|( args.length< 5 )){
			
			LogWriter.writeLog("Values read");
			LogWriter.writeLog("inputDir="+inputDir);
			LogWriter.writeLog("processedDir="+processed_dir);
			LogWriter.writeLog("logFile="+LogWriter.log_name);
			LogWriter.writeLog("Directory and height pair values");
			for(int i=3;i<outputCount;i++)
				LogWriter.writeLog(args[i]);
			
			if(( args.length< 5 )|((args.length & 1)==0)){
				System.out.println("Requires");
				System.out.println("inputDir processedDir logFile");
				System.out.println("height Directory (as many pairs as you like)");
				exit( "Not enough parameters passed to software" );
			}else
				exit("Incorrect number of values");
		}			
			
		/**read path values*/
		inputDir =args[0];
		processed_dir=args[1];
		
		String logging=System.getProperty("org.jpedal.logging");
		if(logging!=null && logging.toLowerCase().equals("true")){
			LogWriter.log_name=args[2];
			LogWriter.setupLogFile(true,0,"1.0","",false);
		}
		
		/**check input directory exists*/
		File pdf_file = new File( inputDir );
		
		/**check processed exists and create if not*/
		File processedDir=new File(processed_dir);
		if(!processedDir.exists())
			processedDir.mkdirs();

		/**if dir exists, open and get number of pages*/
		if( pdf_file.exists() == false )
			exit( "Directory " + inputDir + " not found" );
		
		/**read output values*/
		outputCount=(args.length-3)/2;
			
		/**read and create output directories*/
		outputSizes=new float[outputCount];
		outputDirectories=new String[outputCount];		
		for(int i=0;i<outputCount;i++){
			
			try{
				outputSizes[i]=Float.parseFloat(args[3+(i*2)]);
			}catch(Exception e){
				exit("Exception "+e+" reading integer "+args[3+(i*2)]);
			}
			
			try{
				outputDirectories[i]=args[4+(i*2)];
				
				/**make sure has separator*/
				if((!outputDirectories[i].endsWith("\\"))&&(!outputDirectories[i].endsWith("/")))
					outputDirectories[i]=outputDirectories[i]+separator;
				
				File dir=new File(outputDirectories[i]);
				if(!dir.exists())
					dir.mkdirs();
			}catch(Exception e){
				exit("Exception "+e+" with directory "+args[4+(i*2)]);
			}
			
		}
		
		ExtractClippedImages images1 = new ExtractClippedImages( inputDir );
		
		LogWriter.writeLog("Process completed");

        long end=System.currentTimeMillis();

        System.out.println("Took "+(end-start)/1000+" seconds");
	}
}
