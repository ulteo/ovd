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
* ExtractSection.java
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
import org.jpedal.utils.LogWriter;

/**
 * This example was written to show extraction from a page location of
 * repetitive information (ie Section).
 * 
 */
public class ExtractSection extends ExtractTextInRectangle{
	
	/**debug flag to exit if no section found*/
	static final private boolean debug=false;
	
	/**holds configuration data*/
	SectionConfiguration sectionConfig;
	
	/**default value*/
	String[] sectionTokens=null;
	
	private int[] section_x1,section_x2,section_y1,section_y2;
	
	private String section=null;
	
	public String getSection() {
		return section;
	}

	/**
	 * extract section using tags
	 */
	private String extractSection(String extractedText) {
		
		String pageNumber, section = null, currentToken;
		try {
			if(showMessages)
				System.out.println(extractedText);
			
			if(extractedText==null)
				return null;
			
			Map sections=new HashMap();
			
			int sectionTokenCount=sectionTokens.length;
			for(int i=0;i<sectionTokenCount;i++)
				sections.put(sectionTokens[i],"x");
			
			pageNumber = null;
			section = null;
			currentToken = null;
			
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
		} catch (RuntimeException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
		
		return section;
	}
	
	/**example method to open a file and extract the raw text*/
	public ExtractSection(String file_name, String configDir) {
		
		try{
			showMessages=false;

            initSection(configDir);
			
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
		}catch(Exception e){
			e.printStackTrace();
		}
	}

    private void initSection(String configDir) {
        sectionConfig =new SectionConfiguration(configDir);

        /**
			 * read XML tags to look for
         */

        section= sectionConfig.getValue("default_section");

        //get number of tags and init store
        int tagCount=Integer.parseInt(sectionConfig.getValue("xmlCount"));
        sectionTokens=new String[tagCount];

        //read in xml tags
        for(int j=0;j<tagCount;j++){
            sectionTokens[j]= sectionConfig.getValue("xmlTag_"+j);

            if(showMessages)
                System.out.println(sectionTokens[j]);
        }

        /**
			 * read location values
         */
        //get number of tags and init store
        tagCount=Integer.parseInt(sectionConfig.getValue("locationCount"));

        section_x1=new int[tagCount];
        section_x2=new int[tagCount];
        section_y1=new int[tagCount];
        section_y2=new int[tagCount];

        //read values
        String key="locTag";
        String[] coords={"x1","y1","x2","y2"};

        for(int i=0;i<tagCount;i++){

            for(int coord=0;coord<4;coord++){

                String currentKey=key+ '_' +i+ '_' +coords[coord];
                String value= sectionConfig.getValue(currentKey);
                int numberValue=Integer.parseInt(value);

                //set values
                switch(coord){
                case 0:
                    section_x1[i]=numberValue;
                    break;
                case 1:
                    section_y1[i]=numberValue;
                    break;
                case 2:
                    section_x2[i]=numberValue;
                    break;
                case 3:
                    section_y2[i]=numberValue;
                    break;
                }
            }
        }
    }


    /**
	 * routine to decode a file
	 */
	protected void decodeFile(String path,String name) {
		
		try {
			String file_name=path+name;
			
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
				int possSetsCoordinates=section_x2.length;

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

                            /** create a grouping object to apply grouping to data*/
							PdfGroupingAlgorithms currentGrouping =new PdfGroupingAlgorithms(decodePdf.getPdfData());

                            //SET co-ordinates
							int x1=section_x1[coordSet];
							int x2=section_x2[coordSet];
							int y1=section_y1[coordSet];
							int y2=section_y2[coordSet];
							
							if(showMessages)
								System.out.println("Using ("+x1+ ',' +y1+") ("+x2+ ',' +y2+ ')');
							

							try{
								text =currentGrouping.extractTextInRectangle(x1,y1,x2,y2,page,false,true);

                                if (text != null) {

                                    section=extractSection(text);

                                    //exit loop
                                    if(section!=null)
                                        coordSet=possSetsCoordinates;
                                }
                            } catch (PdfException e) {
                                text =null;
                                decodePdf.closePdfFile();
								System.err.println("Exception " + e.getMessage()+" in file "+decodePdf.getObjectStore().fullFileName);
								e.printStackTrace();
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
							
							this.section = section;
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
			
			/**close the pdf file*/
			decodePdf.closePdfFile();
		} catch (RuntimeException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
		
	}
}
