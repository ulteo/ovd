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
* TextAsWordlistInBackground.java
* ---------------
*/
package org.jpedal.examples.text;

//JFC

import java.io.File;
import java.io.FileOutputStream;
import java.io.OutputStreamWriter;
import java.util.Iterator;
import java.util.List;

import org.jpedal.PdfDecoder;
import org.jpedal.exception.PdfException;
import org.jpedal.grouping.PdfGroupingAlgorithms;
import org.jpedal.objects.PdfData;
import org.jpedal.objects.PdfPageData;
import org.jpedal.utils.LogWriter;
import org.jpedal.utils.Strip;

/**
 * 
 * Sample code showing how jpedal library can be used with
 * pdf files  to extract text from a specified Rectangle as a set of 
 * words.
 * 
 * This example is based on extractTextInRectangle.java
 * 
 * These can then be entered into an index engine such as Lucene
  */
public class TextAsWordlistInBackground {
	
	/**flag to show if we print messages*/
	static boolean outputMessages=false;

	/**word count - used for testing*/
	private int wordsExtracted=0;
	
	/**output where we put files*/
	private String user_dir = System.getProperty("user.dir");

	/**correct separator for OS */
	String separator = System.getProperty("file.separator");

	/**the decoder object which decodes the pdf and returns a data object*/
	PdfDecoder decodePdf = null;

	/**sample file which can be setup - substitute your own. 
	 * If a directory is given, all the files in the directory will be processed*/
	private static String testFile = "/mnt/shared/sample.pdf";
	
	/**flag to show if file or byte array*/
	private boolean isFile=true;
	
	/**byte array*/
	private byte[] byteArray=null;

	public TextAsWordlistInBackground() {
	}

	/**example method to open a file and extract the raw text*/
	public TextAsWordlistInBackground(String file_name) {
		
		if(outputMessages)
			System.out.println("processing "+file_name);

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
			decodeFile(file_name);
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
					if(outputMessages)
					System.out.println(file_name + files[i]);

					decodeFile(file_name + files[i]);
				}
			}
		}
	}

	/**example method to open a file and extract the raw text*/
	public TextAsWordlistInBackground(byte[] array) {
		
		if(outputMessages)
			System.out.println("processing byte array");

		//check output dir has separator
		if (user_dir.endsWith(separator) == false)
			user_dir = user_dir + separator;

		
		//set values
		this.byteArray=array;
		isFile=false;
		
		//routine will open from array (is otherwise identical)
		decodeFile("byteArray");
		
	}
	
	/**
	 * routine to decode a file
	 */
	private void decodeFile(String file_name) {

		/**get just the name of the file without 
		 * the path to use as a sub-directory or .pdf
		 */
		String name = "demo"; //set a default just in case
		
		int pointer = file_name.lastIndexOf(separator);

		if (pointer != -1)
			name = file_name.substring(pointer + 1, file_name.length() - 4);

		/**
		 * create output dir for text
		 */
		String outputDir=user_dir + "text" + separator + name + separator;

		/**debugging code to create a log
		LogWriter.setupLogFile(true,0,"","",false);
		LogWriter.log_name =  "/mnt/shared/log.txt";
		/***/
		
		//PdfDecoder returns a PdfException if there is a problem
		try {
			decodePdf = new PdfDecoder(false);
			decodePdf.setExtractionMode(PdfDecoder.TEXT); //extract just text
			decodePdf.init(true);
			//make sure widths in data CRITICAL if we want to split lines correctly!!

			/**
			 * open the file (and read metadata including pages in  file)
			 */
			if(outputMessages)
			System.out.println("Opening file :" + file_name);
			
			if(isFile)
				decodePdf.openPdfFile(file_name);
			else
				decodePdf.openPdfArray(byteArray);

		} catch (Exception e) {
			System.err.println("Exception " + e+" in pdf code "+file_name);
		}
		
		/**
		 * extract data from pdf (if allowed). 
		 */
		if ((decodePdf.isEncrypted()) && (!decodePdf.isExtractionAllowed())) {
			if(outputMessages){
				System.out.println("Encrypted settings");
				System.out.println("Please look at SimpleViewer for code sample to handle such files");
			}
		} else{
			//page range
			int start = 1, end = decodePdf.getPageCount();

			/**
			 * extract data from pdf
			 */
			try {
				for (int page = start; page < end + 1; page++) { //read pages

					//decode the page
					decodePdf.decodePageInBackground(page);
					
					//get the PdfData object which now holds the content
					PdfData pdf_text = decodePdf.getPdfBackgroundData();
					
					/** create a grouping object to apply grouping to data*/
					PdfGroupingAlgorithms currentGrouping =
						new PdfGroupingAlgorithms(pdf_text);
					
					/**use whole page size for  demo - get data from PageData object*/
					PdfPageData currentPageData = decodePdf.getPdfBackgroundPageData();
					
					int x1 = currentPageData.getMediaBoxX(page);
					int x2 = currentPageData.getMediaBoxWidth(page)+x1;

					int y2 = currentPageData.getMediaBoxY(page);
					int y1 = currentPageData.getMediaBoxHeight(page)+y2;
					
					//tell user
					if(outputMessages)
					System.out.println(
						"Page "+page+" Extracting text from rectangle ("
							+ x1
							+ ','
                                + y1
							+ ' '
                                + x2
							+ ','
                                + y2
							+ ')');

					/**Co-ordinates are x1,y1 (top left hand corner), x2,y2(bottom right) */
					
					/**The call to extract the list*/
					List words =null;
					
					/**new 7th October 2003 - define punctuation*/
					try{
						words =currentGrouping.extractTextAsWordlist(
							x1,
							y1,
							x2,
							y2,
							page,
							false,
							true,"!.,\"\"\'\'");
					} catch (PdfException e) {
						decodePdf.closePdfFile();
						System.err.println("Exception= "+ e+" in "+file_name);
					}
					
					if (words == null) {
						if(outputMessages)
						System.out.println("No text found");
						
					} else {
						
						//create a directory if it doesn't exist
						File output_path = new File(outputDir);
						if (output_path.exists() == false)
						output_path.mkdirs();
						
						/**each word is stored as 5 consecutive values (word,x1,y1,x2,y2)*/
						int wordCount=words.size()/5;
						
						//update our count
						wordsExtracted=wordsExtracted+wordCount;
						
						/**just a simple message in this example*/
						if(outputMessages)
						System.out.println("Page contains "+wordCount+" words.");
						
						/**
						 * output the data
						 */
						if(outputMessages)
						System.out.println("Writing to " + outputDir + "words-"+page + ".txt");
							
						OutputStreamWriter output_stream =
							new OutputStreamWriter(
								new FileOutputStream(outputDir + "words-"+page + ".txt"),
								"UTF-8");
						
						Iterator wordIterator=words.iterator();
						while(wordIterator.hasNext()){
								
								String currentWord=(String) wordIterator.next();
								/**remove the XML formatting*/
								currentWord=Strip.convertToText(currentWord);
							
								int wx1=(int)Float.parseFloat((String) wordIterator.next());
								int wy1=(int)Float.parseFloat((String) wordIterator.next());
								int wx2=(int)Float.parseFloat((String) wordIterator.next());
								int wy2=(int)Float.parseFloat((String) wordIterator.next());
							
								/**this could be inserting into a database instead*/
							output_stream.write(currentWord+ ',' +wx1+ ',' +wy1+ ',' +wx2+ ',' +wy2+ '\n');

						}
						output_stream.close();
						
					}

					//remove data once written out
					decodePdf.flushObjectValues(false);
					
				}
			} catch (Exception e) {
				decodePdf.closePdfFile();
				System.err.println("Exception "+ e+" in "+file_name);
			}

			/**
			 * flush data structures - not strictly required but included
			 * as example
			 */
			decodePdf.flushObjectValues(true); //flush any text data read

			/**tell user*/
			if(outputMessages)
			System.out.println("Text read");

		}

		/**close the pdf file*/
		decodePdf.closePdfFile();
		
		decodePdf=null;
		
	}
	//////////////////////////////////////////////////////////////////////////
	/**
	 * main routine which checks for any files passed and runs the demo
	 */
	public static void main(String[] args) {
		if(outputMessages)
		System.out.println("Simple demo to extract text objects using background calls");

		//set to default
		String file_name = testFile;

		//check user has passed us a filename and use default if none
		if (args.length==0){
			if(outputMessages)
			System.out.println("Default test file used");
		}else {
			file_name = args[0];
			if(outputMessages)
			System.out.println("File :" + file_name);
		}

		//check file exists
		File pdf_file = new File(file_name);

		//if file exists, open and get number of pages
		if (pdf_file.exists() == false) {
			System.out.println("File " + file_name + " not found");
		}
		TextAsWordlistInBackground text1 = new TextAsWordlistInBackground(file_name);
	}

	/**
	 * return words extracted. We use this in some tests.
	 */
	public int getWordsExtractedCount() {
		return wordsExtracted;
	}

}
