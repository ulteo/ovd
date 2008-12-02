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
* ExtractFormDataAsObject.java
* ---------------
*/
package org.jpedal.examples.acroform;

import java.io.File;
import java.util.*;

import org.jpedal.PdfDecoder;
import org.jpedal.exception.PdfException;
import org.jpedal.utils.LogWriter;

/**
 * Recommended form data extraction example from version 3.20
 * showing new methods
 */
public class ExtractFormDataAsObject {

	/**output where we put files*/
	private String outputDir = System.getProperty("user.dir");

	/**number of files read*/
	private int fileCount=0;

	/**flag to show if we print messages*/
	static boolean outputMessages=false;

	public int getFileCount(){
		return fileCount;
	}

	/**correct separator for OS */
	String separator = System.getProperty("file.separator");

	/**the decoder object which decodes the pdf and returns a data object*/
	PdfDecoder decodePdf = null;

	/**sample file which can be setup - substitute your own.
	 * If a directory is given, all the files in the directory will be processed*/
	//private static String test_file = "/mnt/shared/storypad/input/acadapp.pdf";
	private static String test_file = "/PDFdata/files-jpedal/Testdokument PDF.pdf";

	public ExtractFormDataAsObject() {
	}

	/**example method to open a file and extract the form data*/
	public ExtractFormDataAsObject(String file_name) {

		//check output dir has separator
		if (outputDir.endsWith(separator) == false)
			outputDir = outputDir + separator+"forms"+separator;

		//create a directory if it doesn't exist
		File output_path = new File(outputDir);
		if (output_path.exists() == false){
			output_path.mkdirs();
		}

		/**
		 * if file name ends pdf, do the file otherwise
		 * do every pdf file in the directory. We already know file or
		 * directory exists so no need to check that, but we do need to
		 * check its a directory
		 */
		if (file_name.toLowerCase().endsWith(".pdf")) {
			decodePage("",file_name);
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
						System.out.println(">>_"+file_name + files[i]);

					decodePage(file_name, files[i]);
				}
			}
		}
	}

	/**
	 * routine to decode a page
	 */
	private void decodePage(String dir,String name) {

		String file_name=dir+name;

        //PdfDecoder returns a PdfException if there is a problem
		try {
			decodePdf = new PdfDecoder(false);

			/**
			 * open the file (and read metadata including form in  file)
			 * NO OTHER ACTIVITY REQUIRED TO GET FORM DATA!!
			 */
			if(outputMessages)
				System.out.println("Opening file :" + file_name);
			decodePdf.openPdfFile(file_name);

		} catch (Exception e) {
			e.printStackTrace();

			System.err.println("Exception " + e + " in pdf code with "+file_name);
		}

		/**
		 * extract data from pdf (if allowed).
		 */
		if ((decodePdf.isEncrypted())&&(!decodePdf.isExtractionAllowed())) {
			if(outputMessages){
				System.out.println("Encrypted settings");
				System.out.println("Please look at SimpleViewer for code sample to handle such files");
			}
		}else{

			fileCount++;

            /**
             * new 3.20 code here
             */

            //get list of components as iterator
            Iterator components= null;
            try {
                components = decodePdf.getNamesForAllFields().iterator();
            } catch (PdfException e) {
                e.printStackTrace();  //To change body of catch statement use File | Settings | File Templates.
            }

            //iterate over all forms and get data for each
            while(components!=null && components.hasNext()){
                String nextCompName= (String) components.next();

                if(outputMessages)
                System.out.println(nextCompName);

                //get data for object
                Object formData=decodePdf.getFormDataForField(nextCompName);

                //do something with data
                //now returns array if name used and multiple matches
                if(formData instanceof Object[]){

                	Object[] multiforms=(Object[]) formData;
                	for(int count=0;count<multiforms.length;count++)
                		System.out.println(nextCompName+' '+multiforms[count]);
                }else
                    System.out.println(nextCompName+ ' ' +formData);

            }
		}

		/**close the pdf file*/
		decodePdf.closePdfFile();

	}

	//////////////////////////////////////////////////////////////////////////
	/**
	 * main routine which checks for any files passed and runs the demo
	 */
	public static void main(String[] args) {

        if(outputMessages)
			System.out.println("Simple demo to extract form data");

		//set to default
		String file_name = test_file;

		//check user has passed us a filename and use default if none
		if (args.length != 1){
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
			if(outputMessages)
				System.out.println("File " + file_name + " not found");
		}
		ExtractFormDataAsObject text2 = new ExtractFormDataAsObject(file_name);
	}

	//return location of files
	public String getOutputDir() {
		return outputDir;
	}
}
