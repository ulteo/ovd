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
* ExtractDate.java
* ---------------
*/
package org.jpedal.examples.text.extractheadlines;

import java.util.HashMap;
import java.util.Map;
import java.util.StringTokenizer;

import org.jpedal.examples.text.ExtractTextInRectangle;
import org.jpedal.exception.PdfException;
import org.jpedal.grouping.PdfGroupingAlgorithms;
import org.jpedal.objects.PdfData;
import org.jpedal.utils.Strip;

/**
 * This example was written to show extraction from a page location of
 * repetitive information (ie Section).
 * 
 */
public class ExtractDate extends ExtractTextInRectangle{
	
	/**debug flag to exit if no section found*/
	static final private boolean debug=false;
	
	/**holds configuration data*/
	DateConfiguration configDate;
	
	/**default value*/
	String[] dateTokens=null;

	private int[] date_x1,date_x2,date_y1,date_y2;
	
	private String day,date,month,year;

    /**holds configuration data*/
	SectionConfiguration sectionConfig;

	/**default value*/
	String[] sectionTokens=null;

    boolean isDate=false;

    private int[] section_x1,section_x2,section_y1,section_y2;

	private String section=null;

    private String folio=null;

    public String getFolio() {
		return folio;
	}

    public String getSection() {
		return section;
	}

    public String getDay() {
		return day;
	}

	public String getDate() {
		return date;
	}

	public String getMonth() {
		return month;
	}

	public String getYear() {
		return year;
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



    /**
	 * extract date using tags
	 */
	private String extractDate(String extractedText) {

		String pageNumber, date = "", currentToken;
		try {

            if(showMessages)
				System.out.println(extractedText);

			if(extractedText==null)
				return null;

			Map dates=new HashMap();

			int dateTokenCount=dateTokens.length;
			for(int i=0;i<dateTokenCount;i++)
				dates.put(dateTokens[i],"x");

			pageNumber = null;
			currentToken = null;

			//cycle through to get value
			StringTokenizer tokens=new StringTokenizer(extractedText,"<>");

			while(tokens.hasMoreTokens()){

				//exit if both found
				if((date!=null)&&(pageNumber!=null))
					break;
				currentToken=tokens.nextToken();


				//now look for match for page and date
				if((dates.get(currentToken)!=null)){
					String font = currentToken;
					currentToken=tokens.nextToken();

					//see if number and ignore if so
					boolean isNumber=false;

					if((!isNumber)&&(currentToken.length()>2)){
						StringBuffer dateName=new StringBuffer();
						while(tokens.hasMoreTokens()&&(!currentToken.equals("/font"))){
							if(currentToken.indexOf("SpaceC")!=-1)
								dateName.append(' ');
							else
								dateName.append(currentToken);
							currentToken=tokens.nextToken();
						}

						date=date+ ' ' +dateName.toString().trim();

					}
				}

			}
		} catch (RuntimeException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}

		return date;
	}


    /**example method to open a file and extract the raw text*/
    public ExtractDate(String file_name, String configDir,PdfData pdf_data) {

        //adjust for date format
        boolean hasDay=(file_name.indexOf("TIM")!=-1);

            try{
                showMessages=false;

                initDate(configDir);
                initSection(configDir);

                extractValues(pdf_data, 1, hasDay);

            }catch(Exception e){
                e.printStackTrace();
            }
        }

    private void initDate(String configDir) {

        configDate =new DateConfiguration(configDir);

        /**
         * read XML tags to look for
         */

        date= configDate.getValue("default_date");

        //get number of tags and init store
        int tagCount=Integer.parseInt(configDate.getValue("xmlCount"));
        dateTokens=new String[tagCount];

        //read in xml tags
        for(int j=0;j<tagCount;j++){
            dateTokens[j]= configDate.getValue("xmlTag_"+j);

            if(showMessages)
                System.out.println(dateTokens[j]);
        }

        /**
         * read location values
         */
        //get number of tags and init store
        tagCount=Integer.parseInt(configDate.getValue("locationCount"));

        date_x1=new int[tagCount];
        date_x2=new int[tagCount];
        date_y1=new int[tagCount];
        date_y2=new int[tagCount];

        //read values
        String key="locTag";
        String[] coords={"x1","y1","x2","y2"};

        for(int i=0;i<tagCount;i++){

            for(int coord=0;coord<4;coord++){

                String currentKey=key+ '_' +i+ '_' +coords[coord];
                String value= configDate.getValue(currentKey);
                int numberValue=Integer.parseInt(value);

                //set values
                switch(coord){
                    case 0:
                        date_x1[i]=numberValue;
                        break;
                    case 1:
                        date_y1[i]=numberValue;
                        break;
                    case 2:
                        date_x2[i]=numberValue;
                        break;
                    case 3:
                        date_y2[i]=numberValue;
                        break;
                }
            }
        }
    }

    private void extractValues(PdfData pdfdata, int page, boolean hasDay) {

        PdfGroupingAlgorithms currentGrouping =new PdfGroupingAlgorithms(pdfdata);

        //reset to null
        date=null;

        int x1,x2,y1,y2;

        /**
		 * scan possible page locations for section title
         */
        int possSetsCoordinates=date_x2.length;

        for(int coordSet=0;coordSet<possSetsCoordinates;coordSet++){

            //SET co-ordinates
            x1=date_x1[coordSet];
            x2=date_x2[coordSet];
            y1=date_y1[coordSet];
            y2=date_y2[coordSet];

            try{
            /**The call to extract the text*/
            text =currentGrouping.extractTextInRectangle(x1,y1,x2,y2,page,false,true);

            if(showMessages)
            System.out.println("Using ("+x1+ ',' +y1+") ("+x2+ ',' +y2+") text="+text);

            if (text != null) {

                String rawDate=extractDate(text);

                if(showMessages)
                    System.out.println("Date="+rawDate+ '<');

                if(rawDate != null && !rawDate.equals(configDate.getValue("default_date"))){
                    StringTokenizer st = new StringTokenizer(rawDate,", ");

                    int count=st.countTokens();

                    if(count>=3 || (hasDay && count>=4)){

                        if(hasDay)
                            this.day = st.nextToken();

                        this.month= st.nextToken();
                        this.date = st.nextToken();
                        this.year = st.nextToken();

                        //reduce errors by checking valid values
                        if(!isString(month) || !isNumber(this.date) || !isNumber(year)){
                            month=null;
                            rawDate=null;
                            date=null;
                            year=null;
                        }
                    }
                }

                //exit if we have values
                if(month!=null && rawDate!=null && year!=null)
                    coordSet=possSetsCoordinates;
            }
            }catch(Exception ee){
                System.out.println(ee);
                month=null;
                date=null;
                year=null;
            }


        }

        /**
		 * scan possible page locations for section title
        */
        int possSetSectionCoordinates=section_x2.length;
        String section=null;
        for(int coordSet=0;coordSet<possSetSectionCoordinates;coordSet++){

            //SET co-ordinates
            x1=section_x1[coordSet];
            x2=section_x2[coordSet];
            y1=section_y1[coordSet];
            y2=section_y2[coordSet];

            if(showMessages)
                System.out.println("Using ("+x1+ ',' +y1+") ("+x2+ ',' +y2+ ')');

            try{
                text =currentGrouping.extractTextInRectangle(x1,y1,x2,y2,page,false,true);

                if (text != null) {

                    this.folio= Strip.stripXML(text).toString();
                            
                    section=extractSection(text);

                    //exit loop and set value
                    if(section!=null) {
                        coordSet=possSetSectionCoordinates;

                        if(showMessages)
                        System.out.println("section="+section);

                        this.section = section;
                    }
                }
            } catch (PdfException e) {
                text =null;
                System.err.println("Exception " + e.getMessage()+" in file "+decodePdf.getObjectStore().fullFileName);
                e.printStackTrace();
            }
        }

        // check for value and exit if not found in debug mode
        if(section==null && debug){
                System.out.println("section="+section);
                System.exit(1);
        }
    }

    private boolean isString(String chars) {

		//assume true and disprove
		boolean isString=true;

		int count=chars.length();

		for(int ii=0;ii<count;ii++){
			char c=chars.charAt(ii);

			//if wrong flag and exit
			if(!Character.isLetter(c)){
				isString=false;
				ii=count;

			}
		}

		return isString;
	}

	private boolean isNumber(String chars) {

		//assume true and disprove
		boolean isNumber=true;

		int count=chars.length();

		for(int ii=0;ii<count;ii++){
			char c=chars.charAt(ii);

			//if wrong flag and exit
			if(!Character.isDigit(c)){
				isNumber=false;
				ii=count;
			}
		}

		return isNumber;
	}
}
