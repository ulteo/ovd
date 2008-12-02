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
* FindTextInRectangle.java
* ---------------
*/
package org.jpedal.examples.text;

//JFC

import java.io.File;

import org.jpedal.PdfDecoder;
import org.jpedal.exception.PdfException;
import org.jpedal.grouping.PdfGroupingAlgorithms;
import org.jpedal.grouping.SearchType;
import org.jpedal.objects.PdfPageData;
import org.jpedal.utils.LogWriter;

/**
 * 
 * Sample code showing how jpedal library can be used with
 * pdf files  to find text from a specified Rectangle.
 * 
 * Debugging tip: Set verbose=true in LogWriter to see what is going on.
 */
public class FindTextInRectangle {

	/**output where we put files*/
	private String user_dir = System.getProperty("user.dir");

	/**correct separator for OS */
	String separator = System.getProperty("file.separator");

	/**the decoder object which decodes the pdf and returns a data object*/
	PdfDecoder decodePdf = null;

	/**location output files written to*/
	private String outputDir = "";
	
	/**word to find*/
	private static String textToFind="INVOICE";

	/**sample file which can be setup - substitute your own. 
	 * If a directory is given, all the files in the directory will be processed*/
	//private static String testFile = "/mnt/shared/sample.pdf";
	private static String testFile = "/home/markee/workspace/jpedalDEV/TestInvoice.pdf";
	
	public FindTextInRectangle() {
	}

	/**example method to open a file and extract the raw text*/
	public FindTextInRectangle(String file_name) {

		//check output dir has separator
		if (user_dir.endsWith(separator) == false)
			user_dir = user_dir + separator;

		//create a directory if it doesn't exist
		File output_path = new File(outputDir);
		if (output_path.exists() == false)
			output_path.mkdirs();

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
				System.out.println(i + "/ " + fileCount + ' ' + files[i]);

				if (files[i].toLowerCase().endsWith(".pdf")) {
					System.out.println(file_name + files[i]);

					decodeFile(file_name + files[i]);
				}
			}
		}
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
		outputDir = user_dir + "text" + separator + name + separator;

		//ensure a directory for data
		File page_path = new File(outputDir + separator);
		if (page_path.exists() == false)
			page_path.mkdirs();

		/**debugging code to create a log
		LogWriter.setupLogFile(true,0,"","v",false);
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
			System.out.println("Opening file :" + file_name);
			decodePdf.openPdfFile(file_name);

		} catch (Exception e) {
			System.err.println("Exception " + e + " in pdf code");
		}

		/**
		 * extract data from pdf (if allowed). 
		 */
		if ((decodePdf.isEncrypted()&&(!decodePdf.isPasswordSupplied())) && (!decodePdf.isExtractionAllowed())) {
			System.out.println("Encrypted settings");
			System.out.println(
				"Please look at SimpleViewer for code sample to handle such files");
			System.out.println("Or get support/consultancy");
		} else {
			//page range
			int start = 1, end = decodePdf.getPageCount();
			
			System.out.println(PdfDecoder.version);
			System.out.println("Looking for word ="+textToFind+ '<');
			/**
			 * extract data from pdf
			 */
			try {
				for (int page = start; page < end + 1; page++) { //read pages

				    System.out.println("=========================");
				    System.out.println("Page "+page);
				    System.out.println("=========================");
				    
					//decode the page
					decodePdf.decodePage(page);

					/** create a grouping object to apply grouping to data*/
					PdfGroupingAlgorithms currentGrouping =decodePdf.getGroupingObject();
					if(currentGrouping!=null){
						    
						/**use whole page size for  demo - get data from PageData object*/
						PdfPageData currentPageData = decodePdf.getPdfPageData();
						int x1 = currentPageData.getMediaBoxX(page);
						int x2 = currentPageData.getMediaBoxWidth(page)+x1;
	
						int y2 = currentPageData.getMediaBoxY(page);
						int y1 = currentPageData.getMediaBoxHeight(page)+y2;
						
						//tell user
						System.out.println(
							"Scanning for text ("+textToFind+") rectangle ("
								+ x1
								+ ','
                                    + y1
								+ ' '
                                    + x2
								+ ','
                                    + y2
								+ ')');
	
						/**Co-ordinates are x1,y1 (top left hand corner), x2,y2(bottom right) */
						
						/**co-ords for start of object are returned in float object.
						 * if not found co-ords=null
						 * if found co_ords[0]=x1, co_ords[1]=y
						 */
						float[] co_ords=null ;
						
						try{
							co_ords =currentGrouping.findTextInRectangle(
								x1,
								y1,
								x2,
								y2,
								page,
							textToFind,
							SearchType.CASE_SENSITIVE);
						} catch (PdfException e) {
							decodePdf.closePdfFile();
							System.err.println("Exception " + e.getMessage());
							e.printStackTrace();
						}
						
						if (co_ords == null) {
							System.out.println("Text not found");
						} else {
							System.out.println("Text found at "+co_ords[0]+" , "+co_ords[1]);
						}
					}

					//remove data once written out
					decodePdf.flushObjectValues(false);
				}
			} catch (Exception e) {
				decodePdf.closePdfFile();
				System.err.println("Exception " + e.getMessage());
			}

			/**
			 * flush data structures - not strictly required but included
			 * as example
			 */
			decodePdf.flushObjectValues(true); //flush any text data read

			/**tell user*/
			System.out.println("Text read");

		}

		/**close the pdf file*/
		decodePdf.closePdfFile();

	}
	//////////////////////////////////////////////////////////////////////////
	/**
	 * main routine which checks for any files passed and runs the demo
	 */
	public static void main(String[] args) {
		System.out.println("Simple demo to find  text in page");

		//set to default
		String file_name = testFile;

		//check user has passed us a filename and use default if none
		if (args.length != 2){
			System.out.println("Please pass \"fileName\" \"text\" as 2 paramters on command line");
			System.out.println("You will need to use quotes if they contain spaces");
		}else {
			file_name = args[0];
			textToFind=args[1];
			System.out.println("File :" + file_name+" looking for "+textToFind);
		}

		//check file exists
		File pdf_file = new File(file_name);

		//if file exists, open and get number of pages
		if (pdf_file.exists() == false) {
			System.out.println("File " + file_name + " not found");
		}
		FindTextInRectangle text1 = new FindTextInRectangle(file_name);
	}
}
