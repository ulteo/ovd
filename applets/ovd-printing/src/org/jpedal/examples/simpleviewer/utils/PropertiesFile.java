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
* PropertiesFile.java
* ---------------
*/
package org.jpedal.examples.simpleviewer.utils;

import java.io.File;
import java.io.InputStream;
import java.util.ArrayList;
import java.util.Collections;
import java.util.List;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;
import javax.xml.transform.stream.StreamSource;

import org.jpedal.Display;
import org.jpedal.examples.simpleviewer.Commands;
import org.jpedal.utils.LogWriter;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NamedNodeMap;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
                                      
/**holds values stored in XML file on disk*/
public class PropertiesFile {
	
	private String separator=System.getProperty( "file.separator" );
	private String userDir=System.getProperty("user.dir");
	
	final private String configFile=userDir+separator+".properties.xml";
	
	private Document doc;
	
	private int noOfRecentDocs = 6;
	
	public PropertiesFile(){
		
		try{
			DocumentBuilderFactory dbf = DocumentBuilderFactory.newInstance();
			DocumentBuilder db = dbf.newDocumentBuilder();

			if(new File(configFile).exists()){
				try{
                    doc =  db.parse(new File(configFile));
				}catch(Exception e){
					doc =  db.newDocument();
				}
			}else
				doc =  db.newDocument();

			boolean hasAllElements=checkAllElementsPresent();

            //only write out if needed
            if(!hasAllElements)
            writeDoc();
			
		}catch(Exception e){
			LogWriter.writeLog("Exception " + e + " generating properties file");
		}
	}
	
	public String[] getRecentDocuments(){
		String[] recentDocuments=new String[noOfRecentDocs];
		
		try{
			NodeList nl =doc.getElementsByTagName("recentfiles");
			List fileNames = new ArrayList();
			
			if(nl != null && nl.getLength() > 0) {
				NodeList allRecentDocs = ((Element) nl.item(0)).getElementsByTagName("*");
				
				for(int i=0;i<allRecentDocs.getLength();i++){
					Node item = allRecentDocs.item(i);
					NamedNodeMap attrs = item.getAttributes();
					fileNames.add(attrs.getNamedItem("name").getNodeValue());
				}
			}
			
			//prune unwanted entries
			while(fileNames.size() > noOfRecentDocs){
				fileNames.remove(0);
			}
			
			Collections.reverse(fileNames);
			
			recentDocuments = (String[]) fileNames.toArray(new String[noOfRecentDocs]);
		}catch(Exception e){
			LogWriter.writeLog("Exception " + e + " getting recent documents");
        	return null;
		}
		
		return recentDocuments;
	}
	
	public void addRecentDocument(String file){
		try{
			Element recentElement = (Element) doc.getElementsByTagName("recentfiles").item(0);
			
			checkExists(file, recentElement);
			
			Element elementToAdd=doc.createElement("file");
			elementToAdd.setAttribute("name",file);
			
			recentElement.appendChild(elementToAdd);
			
			removeOldFiles(recentElement);
			
			//writeDoc();
		}catch(Exception e){
			LogWriter.writeLog("Exception " + e + " adding recent document to properties file");
		}
	}
	
	public void setValue(String elementName, String newValue) {
		try {
			NodeList nl =doc.getElementsByTagName(elementName);
			Element element=(Element) nl.item(0);
			element.setAttribute("value",newValue);
			
			writeDoc();
		}catch(Exception e){
			LogWriter.writeLog("Exception " + e + " setting value in properties file");
		}
	}
	
	public String getValue(String elementName){
		NamedNodeMap attrs = null;
		try {
			NodeList nl =doc.getElementsByTagName(elementName);
			Element element=(Element) nl.item(0);
			if(element==null)
				return "";
			attrs = element.getAttributes();
			
		}catch(Exception e){
			LogWriter.writeLog("Exception " + e + " generating properties file");
			return "";
		}

		return attrs.getNamedItem("value").getNodeValue();
	}
	
	private void removeOldFiles(Element recentElement) throws Exception{
		NodeList allRecentDocs = recentElement.getElementsByTagName("*");
		
		while(allRecentDocs.getLength() > noOfRecentDocs){
			recentElement.removeChild(allRecentDocs.item(0));
		}	
	}
	
	private void checkExists(String file, Element recentElement) throws Exception{
		NodeList allRecentDocs = recentElement.getElementsByTagName("*");
		
		for(int i=0;i<allRecentDocs.getLength();i++){
			Node item = allRecentDocs.item(i);
			NamedNodeMap attrs = item.getAttributes();
			String value = attrs.getNamedItem("name").getNodeValue();
			
			if(value.equals(file))
				recentElement.removeChild(item);
		}
	}

    //
    public void writeDoc() throws Exception{
		
		InputStream stylesheet = this.getClass().getResourceAsStream("/org/jpedal/examples/simpleviewer/res/xmlstyle.xslt");
	
		TransformerFactory transformerFactory = TransformerFactory.newInstance();
		Transformer transformer = transformerFactory.newTransformer(new StreamSource(stylesheet));
		transformer.transform(new DOMSource(doc), new StreamResult(configFile));

	}
	
	private boolean checkAllElementsPresent() throws Exception{

        //assume true and set to false if wrong
        boolean hasAllElements=true;

        NodeList allElements = doc.getElementsByTagName("*");
		List elementsInTree=new ArrayList(allElements.getLength());
		
		for(int i=0;i<allElements.getLength();i++)
			elementsInTree.add(allElements.item(i).getNodeName());
		
		Element propertiesElement = null;
		
		if(elementsInTree.contains("properties")){
			propertiesElement = (Element) doc.getElementsByTagName("properties").item(0);
		}else{
			DocumentBuilderFactory dbf = DocumentBuilderFactory.newInstance();
			DocumentBuilder db = dbf.newDocumentBuilder();
			
			doc =  db.newDocument();	
			
			propertiesElement = doc.createElement("properties");
			doc.appendChild(propertiesElement);
			
			allElements = doc.getElementsByTagName("*");
			elementsInTree=new ArrayList(allElements.getLength());
			
			for(int i=0;i<allElements.getLength();i++)
				elementsInTree.add(allElements.item(i).getNodeName());

            hasAllElements=false;
        }
		
		if(!elementsInTree.contains("recentfiles")){
			Element recent = doc.createElement("recentfiles");
			propertiesElement.appendChild(recent);

            hasAllElements=false;
        }
		
		if(!elementsInTree.contains("showfirsttimepopup")){
			Element showFormsSaveMessage = doc.createElement("showfirsttimepopup");
			showFormsSaveMessage.setAttribute("value","true");
			propertiesElement.appendChild(showFormsSaveMessage);

            hasAllElements=false;
        }
		
		if(!elementsInTree.contains("showsaveformsmessage")){
			Element showFormsSaveMessage = doc.createElement("showsaveformsmessage");
			showFormsSaveMessage.setAttribute("value","true");
			propertiesElement.appendChild(showFormsSaveMessage);

            hasAllElements=false;
        }
		
		if(!elementsInTree.contains("showitextmessage")){
			Element showItextMessage = doc.createElement("showitextmessage");
			showItextMessage.setAttribute("value","true");
			propertiesElement.appendChild(showItextMessage);

            hasAllElements=false;
        }
		
		if(!elementsInTree.contains("showddmessage")){
			Element showDDMessage = doc.createElement("showddmessage");
			showDDMessage.setAttribute("value","true");
			propertiesElement.appendChild(showDDMessage);

            hasAllElements=false;
        }
		
		if(!elementsInTree.contains("searchWindowType")){
			Element showDDMessage = doc.createElement("searchWindowType");
			showDDMessage.setAttribute("value","0");
			propertiesElement.appendChild(showDDMessage);

            hasAllElements=false;
        }
		
		if(!elementsInTree.contains("borderType")){
			Element showDDMessage = doc.createElement("borderType");
			showDDMessage.setAttribute("value","1");
			propertiesElement.appendChild(showDDMessage);

            hasAllElements=false;
        }
		
		if(!elementsInTree.contains("showDownloadWindow")){
			Element showDDMessage = doc.createElement("showDownloadWindow");
			showDDMessage.setAttribute("value","true");
			propertiesElement.appendChild(showDDMessage);

            hasAllElements=false;
        }
		
		if(!elementsInTree.contains("DPI")){
			Element showDDMessage = doc.createElement("DPI");
			showDDMessage.setAttribute("value","96");
			propertiesElement.appendChild(showDDMessage);

            hasAllElements=false;
        }
		
		if(!elementsInTree.contains("autoScroll")){
			Element showDDMessage = doc.createElement("autoScroll");
			showDDMessage.setAttribute("value","true");
			propertiesElement.appendChild(showDDMessage);

            hasAllElements=false;
        }
		
		if(!elementsInTree.contains("pageMode")){
			Element showDDMessage = doc.createElement("pageMode");
			showDDMessage.setAttribute("value",String.valueOf(Display.SINGLE_PAGE));
			propertiesElement.appendChild(showDDMessage);

            hasAllElements=false;
        }
		
		if(!elementsInTree.contains("displaytipsonstartup")){
			Element showDDMessage = doc.createElement("displaytipsonstartup");
			showDDMessage.setAttribute("value","true");
			propertiesElement.appendChild(showDDMessage);

            hasAllElements=false;
        }
		
		if(!elementsInTree.contains("automaticupdate")){
			Element showDDMessage = doc.createElement("automaticupdate");
			showDDMessage.setAttribute("value","true");
			propertiesElement.appendChild(showDDMessage);

            hasAllElements=false;
        }
		
		if(!elementsInTree.contains("currentversion")){
			Element showDDMessage = doc.createElement("currentversion");
			showDDMessage.setAttribute("value","");
			propertiesElement.appendChild(showDDMessage);

            hasAllElements=false;
        }
		
		if(!elementsInTree.contains("showtiffmessage")){
			Element showDDMessage = doc.createElement("showtiffmessage");
			showDDMessage.setAttribute("value","true");
			propertiesElement.appendChild(showDDMessage);

            hasAllElements=false;
        }
		
		if(!elementsInTree.contains("maxmultiviewers")){
			Element showDDMessage = doc.createElement("maxmultiviewers");
			showDDMessage.setAttribute("value","20");
			propertiesElement.appendChild(showDDMessage);

            hasAllElements=false;
        }

        return hasAllElements;
    }
	
	public int getNoRecentDocumentsToDisplay() {
		return this.noOfRecentDocs;
	}
}
