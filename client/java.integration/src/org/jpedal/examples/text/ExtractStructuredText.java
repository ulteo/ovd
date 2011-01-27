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
* ExtractStructuredText.java
* ---------------
*/
package org.jpedal.examples.text;

import java.io.File;
import java.io.FileInputStream;
import java.io.InputStream;
import java.util.HashSet;
import java.util.Set;

import org.jpedal.PdfDecoder;

import org.jpedal.exception.PdfException;
import org.jpedal.exception.PdfSecurityException;

import org.jpedal.utils.LogWriter;
import org.w3c.dom.Document;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.Transformer;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamSource;
import javax.xml.transform.stream.StreamResult;

/**
 *
 * Sample code showing how jpedal library can be used with
 * pdf files  to extract structed text from a PDF
 *
 * Debugging tip: Set verbose=true in LogWriter to see what is going on.
 */
public class ExtractStructuredText {

	/**used as part of test to limit pages to first 10*/
	public static boolean isTest = false;

	public static final boolean debug = true;


	/**output where we put files*/
	protected static String output = System.getProperty("user.dir") + "xml";

	/**flag to show if we display messages*/
	public static boolean showMessages = true;

	/**correct separator for OS */
	protected String separator = System.getProperty("file.separator");

	/**the decoder object which decodes the pdf and returns a data object*/
	protected PdfDecoder decodePdf = null;

	/**location output files written to*/
	protected String outputFile = "";

	/**sample file which can be setup - substitute your own.
	 * If a directory is given, all the files in the directory will be processed*/
	private static String testFile = "/PDFdata/sample_pdfs/acroforms/myform.pdf";

	public ExtractStructuredText() {
	}

	/**example method to open a file or dir and extract the Structured Content to outputDir*/
	public ExtractStructuredText(String root, String outputDir) {

		output = outputDir;

		//check output dir has separator
		if (output.endsWith(separator) == false)
			output = output + separator;

		//create a directory if it doesn't exist
		File output_path = new File(output);
		if (output_path.exists() == false)
			output_path.mkdirs();

		/**
		 * if file name ends pdf, do the file otherwise
		 * do every pdf file in the directory. We already know file or
		 * directory exists so no need to check that, but we do need to
		 * check its a directory
		 */
		if (root.toLowerCase().endsWith(".pdf")) {
			decodeFile(root);
		} else {

			/**
			 * get list of files and check directory
			 */

			String[] files = null;
			File inputFiles = null;

			/**make sure name ends with a deliminator for correct path later*/
			if (!root.endsWith(separator))
				root = root + separator;

			try {
				inputFiles = new File(root);

				if (!inputFiles.isDirectory()) {
					System.err.println(root
							+ " is not a directory. Exiting program");
				}
				files = inputFiles.list();
			} catch (Exception ee) {
				LogWriter.writeLog("Exception trying to access file "
						+ ee.getMessage());
			}

			/**now work through all pdf files*/
			long fileCount = files.length;

			for (int i = 0; i < fileCount; i++) {
				if (showMessages)
					System.out.println(i + "/ " + fileCount + ' ' + files[i]);

				if (files[i].toLowerCase().endsWith(".pdf")) {
					if (showMessages)
						System.out.println(root + files[i]);

					decodeFile(root + files[i]);
				}
			}
		}
	}
	
	/**
	 * routine to decode a file
	 */
	protected void decodeFile(String file_name) {

		/**get just the name of the file without
		 * the path to use as a sub-directory or .pdf
		 */
		String name = "demo"; //set a default just in case

		int pointer = file_name.lastIndexOf(separator);

		name = file_name.substring(pointer + 1, file_name.length() - 4);

		/**
		 * create output dir for text
		 */
		outputFile = output + separator + name + ".xml";

		/**debugging code to create a log
		 LogWriter.setupLogFile(true,0,"","v",false);
		 LogWriter.log_name =  "/mnt/shared/log.txt";
		 */

		//PdfDecoder returns a PdfException if there is a problem
		try {
			decodePdf = new PdfDecoder(false);

			if (showMessages)
				System.out.println("\n----------------------------");

			/**
			 * open the file (and read metadata including pages in  file)
			 */
			if (showMessages)
				System.out.println("Opening file :" + file_name);

			decodePdf.openPdfFile(file_name);

		} catch (PdfSecurityException se) {
			System.err.println("Security Exception " + se
					+ " in pdf code for text extraction on file "
					+ decodePdf.getObjectStore().getCurrentFilename());
			//e.printStackTrace();
		} catch (PdfException se) {
			System.err.println("Pdf Exception " + se
					+ " in pdf code for text extraction on file "
					+ decodePdf.getObjectStore().getCurrentFilename());
			//e.printStackTrace();
		} catch (Exception e) {
			System.err.println("Exception " + e
					+ " in pdf code for text extraction on file "
					+ decodePdf.getObjectStore().getCurrentFilename());
			//e.printStackTrace();
		}

		/**
		 * extract data from pdf (if allowed).
		 */
		if ((decodePdf.isEncrypted() && (!decodePdf.isPasswordSupplied()))
				&& (!decodePdf.isExtractionAllowed())) {
			if (showMessages) {
				System.out.println("Encrypted settings");
				System.out
						.println("Please look at SimpleViewer for code sample to handle such files");
				System.out.println("Or get support/consultancy");
			}
		} else {

			/**
			 * extract data from pdf
			 */
			try {

				//read pages -if you already have code this is probably
				//all you need!
				Document tree = decodePdf.getMarkedContent();

				if (tree == null) {
					if (showMessages)
						System.out.println("No text found");
				} else {

					/**
					 * format tree
					 */
					InputStream stylesheet = this.getClass()
							.getResourceAsStream(
									"/org/jpedal/examples/text/xmlstyle.xslt");

					TransformerFactory transformerFactory = TransformerFactory
							.newInstance();

					/**output tree*/
					try {
						Transformer transformer = transformerFactory
								.newTransformer(new StreamSource(stylesheet));

						//useful for debugging
						//transformer.transform(new DOMSource(tree), new StreamResult(System.out));

						transformer.transform(new DOMSource(tree),
								new StreamResult(outputFile));
						
						//System.out.println("Output "+outputFile);

					} catch (Exception e) {
						e.printStackTrace();
					}

					/**
					 * output the data
					 */
					if (showMessages)
						System.out.println("Writing to " + outputFile);

				}

				if (showMessages)
					System.out.println("\n----------done--------------");

				//remove data once written out
				decodePdf.flushObjectValues(false);

			} catch (Exception e) {
				decodePdf.closePdfFile();
				System.err.println("Exception " + e.getMessage());
				e.printStackTrace();
				System.out.println(decodePdf.getObjectStore()
						.getCurrentFilename());
			}

			/**
			 * flush data structures - not strictly required but included
			 * as example
			 */
			decodePdf.flushObjectValues(true); //flush any text data read

			/**tell user*/
			if (showMessages)
				System.out.println("Text read");

			/**close the pdf file*/
			decodePdf.closePdfFile();

		}
	}

	//////////////////////////////////////////////////////////////////////////
	/**
	 * main routine which checks for any files passed and runs the demo
	 */
	public static void main(String[] args) {


		if (showMessages)
			System.out.println("Simple demo to extract text objects");

		//set to default
		String file_name = testFile;

		//check user has passed us a filename
		if (args.length == 2) {
			file_name = args[0];
			output = args[1];
			System.out.println("File :" + file_name);
		} else {
			System.out.println("Please call with parameters :-");
			System.out.println("FileName");
			System.out.println("outputDir");
		}

		//check file exists
		File pdf_file = new File(file_name);

		//if file exists, open and get number of pages
		if (pdf_file.exists() == false) {
			System.out.println("File " + file_name + " not found");
		}
		long now = System.currentTimeMillis();
		ExtractStructuredText text1 = new ExtractStructuredText(file_name,
				output);
		long finished = System.currentTimeMillis();

		if (!isTest)
			System.out.println("Time taken=" + ((finished - now) / 1000));

	}
}
