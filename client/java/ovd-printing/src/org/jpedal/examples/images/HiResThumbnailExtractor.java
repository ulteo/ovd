package org.jpedal.examples.images;

import java.awt.image.BufferedImage;
import java.io.File;
import java.util.HashMap;
import java.util.Map;

import javax.imageio.ImageIO;

import org.jpedal.PdfDecoder;
import org.jpedal.io.ColorSpaceConvertor;
import org.jpedal.constants.JPedalSettings;

public class HiResThumbnailExtractor {
	
	private static boolean debug = true;

    /**correct separator for OS */
    final static String separator = System.getProperty( "file.separator" );

    public static void main(String[] args) throws Exception {
		
		//<link><a name="hiResThumbnailExtr" />
	     /*
	      * Change these variables
	      */
		String fileType="";
		String pdfFile="";
		
		if(args!=null && args.length>1){
			pdfFile = args[0];
			fileType= args[1];
			
			
			if(pdfFile.endsWith(".pdf") && (fileType.equals("jpg") || fileType.equals("png") || fileType.equals("tiff"))){
				extract(fileType,pdfFile);
			}else{
				System.out.println("The file to be processed has to be a pdf and the output filetype can only be jpg,png or tiff");
			}
		}else{
			System.out.println("Not enough arguments passed in! Usage: \"C:\\examples\\1.pdf\" \"jpg\"");
		}
	 }

	 private static void extract(String fileType, String pdfFile)
	         throws Exception {
	     String outputPath = pdfFile.substring(0, pdfFile.indexOf(".pdf")) + separator;
	     File outputPathFile = new File(outputPath);
	     if (!outputPathFile.exists() || !outputPathFile.isDirectory()) {
	         if (!outputPathFile.mkdirs()) {
	        	 if(debug)
	        		 System.err.println("Can't create directory " + outputPath);
	         }
	     }

	     PdfDecoder decoder = new PdfDecoder(true);
	     decoder.openPdfFile(pdfFile);
	     
	     if(debug)
	    	 System.out.println("pdf : " + pdfFile);
	     
	     for (int pageNo = 1; pageNo <= decoder.getPageCount(); pageNo++) {

	         Map mapValues = new HashMap();
	         mapValues.put(JPedalSettings.EXTRACT_AT_BEST_QUALITY, Boolean.TRUE);

	         PdfDecoder.modifyJPedalParameters(mapValues);
	         
	         if(debug)
	        	 System.out.println("page : " + pageNo);


             BufferedImage imageToSave = decoder.getPageAsImage(pageNo);
	         decoder.flushObjectValues(true);

             //image needs to be sRGB for JPEG
             if(fileType.equals("jpg"))
             imageToSave = ColorSpaceConvertor.convertToRGB(imageToSave);

             String outputFileName = outputPath + "page" + pageNo + "." + fileType;
	         File file = new File(outputFileName);
	         ImageIO.write(imageToSave, fileType, file);

	         if(debug)
	        	 System.out.println("Created : " + outputFileName);
	     }
	 } 
	
}
