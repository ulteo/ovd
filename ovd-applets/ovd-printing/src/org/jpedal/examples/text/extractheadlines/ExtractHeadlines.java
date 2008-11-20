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
* ExtractHeadlines.java
* ---------------
*/
package org.jpedal.examples.text.extractheadlines;

import java.io.File;

import java.util.HashMap;
import java.util.Map;
import java.util.StringTokenizer;

import org.jpedal.PdfDecoder;
import org.jpedal.examples.text.ExtractTextInRectangle;
import org.jpedal.exception.PdfException;
import org.jpedal.exception.PdfSecurityException;
import org.jpedal.grouping.PdfGroupingAlgorithms;
import org.jpedal.objects.PdfPageData;
import org.jpedal.utils.LogWriter;

/**
 * This example was written to show extraction from a page location of
 * repetitive information (ie Section).
 * 
 */
public class ExtractHeadlines extends ExtractTextInRectangle{

	/**debug flag to exit if no section found*/
	static final private boolean debug=false;
	
	/**defines output so easy to alter to objc or whatever*/
	Output currentOutput=new Output();
	
	private String configDir="config"+System.getProperty("file.separator");
	
	/**holds configuration data*/
	HeadlineConfiguration config=new HeadlineConfiguration(configDir);
	
	/**target dir for PDF files*/
	private static String testFile="timesPDFS";
	
	/**default value*/
	String[] sectionTokens=null;
	
	private int[] x1,x2,y1,y2;
	
	/**
	 * @param args
	 */
	/**
	 * main routine which checks for any files passed and runs the demo
	 */
	public static void main(String[] args) {
		
		showMessages=false;
		
		if(showMessages)
		System.out.println("Simple demo to extract text objects");

		//set to default
		String file_name = testFile;
		
		//check user has passed us a filename
		if(args.length==1){
			file_name = args[0];
			System.out.println("File :" + file_name);	
		}else{
			System.out.println("Please call with Filename");
			
			System.exit(1);
		 	
		}

		//check file exists
		File pdf_file = new File(file_name);

		//if file exists, open and get number of pages
		if (pdf_file.exists() == false) {
			System.out.println("File " + file_name + " not found");
		}
		
		/**
		 * extract top line and remove section/page
		 */
		
		//extract line with section and page
		ExtractHeadlines text1 = new ExtractHeadlines(file_name);
		
	}

		/**
		 * extract section using tags
		 */
		private String extractSection(String extractedText) {
			
			if(showMessages)
				System.out.println(extractedText);
			
			if(extractedText==null)
				return null;
			
			Map sections=new HashMap();
	//		Map pages=new HashMap();
			int sectionTokenCount=sectionTokens.length;
	//		int pageTokenCount=pageTokens.length;
			for(int i=0;i<sectionTokenCount;i++)
				sections.put(sectionTokens[i],"x");
	//		for(int i=0;i<pageTokenCount;i++)
	//		pages.put(pageTokens[i],"x");
			
			String pageNumber=null,section=null,currentToken=null;
			
			//cycle through to get value
			StringTokenizer tokens=new StringTokenizer(extractedText,"<>");
			
			while(tokens.hasMoreTokens()){
				
				//exit if both found
				if((section!=null)&&(pageNumber!=null))
					break;
				currentToken=tokens.nextToken();
				
			
				//now look for match for page and section
				if((sections.get(currentToken)!=null)){
					String font = currentToken;
					currentToken=tokens.nextToken();
					
					//see if number and ignore if so
					boolean isNumber=false;
						
					if((!isNumber)&&(currentToken.length()>2)){
						StringBuffer sectionName=new StringBuffer();
						while(tokens.hasMoreTokens()&&(!currentToken.equals("/font"))){
							if(currentToken.indexOf("SpaceC")!=-1)
								sectionName.append(' ');
							else
								sectionName.append(currentToken);
							currentToken=tokens.nextToken();
						}
						section=sectionName.toString().trim();
						
						// (sb) if text is in this font then we want to take it first so
						// skip all other possible tokens. ie take "Racing" before getting to 
						// "Sport"
						if(font.equals("font face=\"TimesClassicDisplay\" style=\"font-size:16pt\""))
							break;
					}
				}
				
			}
			
			return section;
		}
	
	/**example method to open a file and extract the raw text*/
	public ExtractHeadlines(String file_name) {

		/**
		 * read XML tags to look for
		 */
		//get number of tags and init store
		int tagCount=Integer.parseInt(config.getValue("xmlCount"));
		sectionTokens=new String[tagCount];
		
		//read in xml tags
		for(int j=0;j<tagCount;j++){
			sectionTokens[j]=config.getValue("xmlTag_"+j);
			
			if(showMessages)
			System.out.println(sectionTokens[j]);
		}
		
		
		/**
		 * read location values
		 */
		//get number of tags and init store
		tagCount=Integer.parseInt(config.getValue("locationCount"));
		
		x1=new int[tagCount];
		x2=new int[tagCount];
		y1=new int[tagCount];
		y2=new int[tagCount];
		
		//read values
        String key="locTag";
        String[] coords={"x1","y1","x2","y2"};
        
        for(int i=0;i<tagCount;i++){

        	for(int coord=0;coord<4;coord++){
        		
	            String currentKey=key+ '_' +i+ '_' +coords[coord];
	            String value=config.getValue(currentKey);
	            int numberValue=Integer.parseInt(value);
	            
	            //set values
	            switch(coord){
	                case 0:
	                	x1[i]=numberValue;
	                break;
	                case 1:
	                	y1[i]=numberValue;
	                break;
	                case 2:
	                	x2[i]=numberValue;
	                break;
	                case 3:
	                	y2[i]=numberValue;
	                break;
	            }
        	}
        }
		
		/**/
		
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
			decodeFile("",file_name);
			
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
				if(showMessages)
				System.out.println(i + "/ " + fileCount + ' ' + files[i]);

				if (files[i].toLowerCase().endsWith(".pdf")) {
					if(showMessages)
					System.out.println(file_name + files[i]);

					decodeFile(file_name,files[i]);
					
				}
			}
		}
	}
	

	/**
	 * routine to decode a file
	 */
	protected void decodeFile(String path,String name) {
		
		String file_name=path+name;
		
		//get details from filename
		String paper=name.substring(0,3);
		String pageNumber=name.substring(3,5);
		String edition=name.substring(5,8);
		String date=name.substring(8,10);
		
		//setup output
		String outputDir="TimesSections/";
		File newDir=new File(outputDir);
		newDir.mkdir();
		currentOutput.open(outputDir+paper+ '.' +edition+ '.' +date+".txt");
		
		//section from page
		String section=null;
		
		//PdfDecoder returns a PdfException if there is a problem
		try {
			decodePdf = new PdfDecoder(false);
			decodePdf.setExtractionMode(PdfDecoder.TEXT); //extract just text
			decodePdf.init(true);
			//make sure widths in data CRITICAL if we want to split lines correctly!!
			decodePdf.openPdfFile(file_name);
			
			if(showMessages)
			System.out.println("file_name="+file_name);

		} catch (PdfSecurityException se) {
			System.err.println("Security Exception " + se + " in pdf code for text extraction on file "+decodePdf.getObjectStore().getCurrentFilename());
			//e.printStackTrace();
		} catch (PdfException se) {
			System.err.println("Pdf Exception " + se + " in pdf code for text extraction on file "+decodePdf.getObjectStore().getCurrentFilename());
			//e.printStackTrace();
		} catch (Exception e) {
			System.err.println("Exception " + e + " in pdf code for text extraction on file "+decodePdf.getObjectStore().getCurrentFilename());
			e.printStackTrace();
		}

		/**
		 * extract data from pdf (if allowed). 
		 */
		if ((decodePdf.isEncrypted()&&(!decodePdf.isPasswordSupplied())) && (!decodePdf.isExtractionAllowed())) {
			if(showMessages){
				System.out.println("Encrypted settings");
				System.out.println(
					"Please look at SimpleViewer for code sample to handle such files");
				System.out.println("Or get support/consultancy");
			}
		} else {
			//page range
			int start = 1, end = decodePdf.getPageCount();

			//number of possible values to match
			int possSetsCoordinates=x2.length;
			
			//reset section to null
			section=null;
			
			/**
			 * extract data from pdf
			 */
			try {
				for (int page = start; page < end + 1; page++) { //read pages

					//decode the page
					decodePdf.decodePage(page);
					
					/**
					 * scan possible page locations for section title
					 */
					for(int coordSet=0;coordSet<possSetsCoordinates;coordSet++){
						
						//SET co-ordinates
						int x1=this.x1[coordSet];
						int x2=this.x2[coordSet];
						int y1=this.y1[coordSet];
						int y2=this.y2[coordSet];
						
						if(showMessages)
						System.out.println("Using ("+x1+ ',' +y1+") ("+x2+ ',' +y2+ ')');
						
						/** create a grouping object to apply grouping to data*/
						PdfGroupingAlgorithms currentGrouping =decodePdf.getGroupingObject();
						
						/**The call to extract the text*/
						text =null;
						
						try{
							text =currentGrouping.extractTextInRectangle(x1,y1,x2,y2,page,false,true);
						} catch (PdfException e) {
							decodePdf.closePdfFile();
							System.err.println("Exception " + e.getMessage()+" in file "+decodePdf.getObjectStore().fullFileName);
							e.printStackTrace();
						}
						
						if (text == null) {
							if(showMessages)
								System.out.println("No text found");
						
						}else{
							section=extractSection(text);
						
							if(section!=null)
								coordSet=possSetsCoordinates;
						}
					}
					
					//remove data once written out
					decodePdf.flushObjectValues(false);
				
					/**
					 * are scanning all possible locations, 
					 * check for values and exit if not found
					 */
					if((section==null)){
						if(debug){
							System.out.println("section="+section);
							System.exit(1);
						}
					}else{
						if(showMessages)
						System.out.println("section="+section);
						
						currentOutput.outputSection(section,pageNumber,name);
						
					}
					
					if(showMessages)
					System.out.println("----");
				
				}
			} catch (Exception e) {
				decodePdf.closePdfFile();
				e.printStackTrace();
				System.out.println(decodePdf.getObjectStore().getCurrentFilename());
			}

			/**
			 * flush data structures - not strictly required but included
			 * as example
			 */
			decodePdf.flushObjectValues(true); //flush any text data read

			/**tell user*/
			if(showMessages)
			System.out.println("Text read");

		}

		//setup output
		currentOutput.close();
		
		/**close the pdf file*/
		decodePdf.closePdfFile();

	}



}
