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
* HeadlineConfiguration.java
* ---------------
*/
package org.jpedal.examples.text.extractheadlines;

import java.io.InputStream;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;
import javax.xml.transform.stream.StreamSource;

import org.jpedal.storypad.Configuration;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.Node;

public class HeadlineConfiguration extends Configuration {

	/**default value*/
	String[] sectionTokens={
	"font face=\"TimesClassicDisplay\" style=\"font-size:16pt\"",
	"font face=\"TimesClassicDisplayBold\" style=\"font-size:16pt\"",
	"font face=\"ClassicFranklinNeoBeta\" style=\"font-size:16pt\"",
	"font face=\"ClassicFranklinNeoBeta\" style=\"font-size:18pt\"",
	"font face=\"ClassicFranklinNeoExt-Bold\" style=\"font-size:18pt\"",};
	//"font face=\"TimesClassicDisplay\" style=\"font-size:94pt\"",
	//"font face=\"ClassicFranklinNeoBeta\" style=\"font-size:120pt\"",
	//"font face=\"ClassicFranklinLightBetaOne\" style=\"font-size:93pt\""};
//	String[] pageTokens={"font face=\"TimesClassicDisplay\" style=\"font-size:16pt\"",
//	"font face=\"ClassicFranklinBookBetaOne\" style=\"font-size:16pt\""};
	
	private int[] x1={-10,180,10};
	private int[] x2={105,316,1537};
	private int[] y1={930,930,970};
	private int[] y2={908,900,946};
	
	private HeadlineConfiguration(){}
	
	/**initialise category and load or create config file*/
	public HeadlineConfiguration(String configDir){
	    
	    /**loadConfig if present*/
	    boolean fileExists=loadValues(configDir);
	    
	    if(!fileExists){
	        saveValues();
	        loadValues(configDir);
	    }
	}
	
	/**
     * write out a config file
     */
    public boolean saveValues() {
        
        try{
            //create doc and set root
        	DocumentBuilderFactory dbf = DocumentBuilderFactory.newInstance();
			DocumentBuilder db = dbf.newDocumentBuilder();
			Document doc = db.newDocument();
			
            Node root=doc.createElement(sectionName);
           
            //add comments
            Node creation=doc.createComment("Created "+org.jpedal.utils.TimeNow.getShortTimeNow());
            
            doc.appendChild(creation);
            
            doc.appendChild(root);
            
            /**
             * add the XML tags we look for
             */
            int count=sectionTokens.length;
            //write out tag count
            Element tagCount=doc.createElement("xmlCount");
            tagCount.setAttribute("value", String.valueOf(count));
            root.appendChild(tagCount);
            
            //write out values to XML
            String key="xmlTag";
            for(int i=0;i<count;i++){

                String currentKey=key+ '_' +i;
                Element section=doc.createElement(currentKey);
                
                //add values
                section.setAttribute("value", sectionTokens[i]);
                root.appendChild(section);
          
            }
            
            /**
             * add the locations we scan
             */
            int locCount=this.x1.length;
            //write out tag count
            Element loc=doc.createElement("locationCount");
            loc.setAttribute("value", String.valueOf(locCount));
            root.appendChild(loc);
            
            //write out values to XML
            key="locTag";
            String[] coords={"x1","y1","x2","y2"};
            
            for(int i=0;i<locCount;i++){

            	for(int coord=0;coord<4;coord++){
            		String currentKey=key+ '_' +i+ '_' +coords[coord];
            		Element section=doc.createElement(currentKey);
            		
            		//add values
            		switch(coord){
            		case 0:
            			section.setAttribute("value", String.valueOf(x1[i]));
            			break;
            		case 1:
            			section.setAttribute("value", String.valueOf(y1[i]));
            			break;
            		case 2:
            			section.setAttribute("value", String.valueOf(x2[i]));
            			break;
            		case 3:
            			section.setAttribute("value", String.valueOf(y2[i]));
            			break;
            		}
            		root.appendChild(section);
            	}
          
            }
            
            //write out
            //use System.out for FileOutputStream to see on screen
	        InputStream stylesheet = this.getClass().getResourceAsStream("/org/jpedal/examples/simpleviewer/res/xmlstyle.xslt");
			
			TransformerFactory transformerFactory = TransformerFactory.newInstance();
			Transformer transformer = transformerFactory.newTransformer(new StreamSource(stylesheet));
			transformer.transform(new DOMSource(doc), new StreamResult(configDir+sectionName+".xml"));
	                    
            System.out.println("Created "+configDir+sectionName+".xml");
            
        }catch(Exception e){
            e.printStackTrace();
        } 
        
        return true;
    }
    
	

}
