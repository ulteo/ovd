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
* PdfFormData.java
* ---------------
*/
package org.jpedal.objects;

import java.io.InputStream;
import java.io.ByteArrayInputStream;
import java.util.*;

import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.Transformer;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamSource;
import javax.xml.transform.stream.StreamResult;

import org.jpedal.exception.PdfException;
import org.jpedal.utils.Strip;
import org.w3c.dom.Document;

/**
 * Added as a repository to store form data in so that it can be reused. 
 * Data stored in a List - each element is a Map
 */
public class PdfFormData
{
	
	/**
	 * list of elements - each element is a form object from the pdf
	 */
	private java.util.List formObjects=new ArrayList();
	
	final public static int XFA_TEMPLATE=0;
	
	final public static int XFA_DATASET=1;
	
	final public static int XFA_CONFIG=2;
	
	private Document xfaTemplate=null;
	private Document xfaDataset=null;
	private Document xfaConfig=null;
	
	private boolean hasXFA = false;
   
	/**numbwer of elements*/
	private int formCount=0, totalCount=0;
	
	/**flag to show if we truncate*/
	private boolean isDemo=false;

	private static boolean failed;

	/**used to debug*/
	static final private boolean showXML=false;

	public PdfFormData(boolean isDemo){
		this.isDemo=isDemo;
	}
	
	/**
	 * flag to show if XFA (not yet implemented)
	 */
	public boolean hasXFAFormData(){
		return hasXFA;
	}
	
	/**
	 * (NOT LIVE)
	 * @throws PdfException
	 */
	public Document getXFAFormData(int type) throws PdfException{
	    
	    if(type==XFA_TEMPLATE)
	        return xfaTemplate;
	    else  if(type==XFA_DATASET)
	    	return xfaDataset;
	    else  if(type==XFA_CONFIG)
		    return xfaConfig;
	    else
	        throw new PdfException("Unknown type for XFA");
	        
	}
	
	/**
	 * (NOT LIVE)
	 * @throws PdfException
	 */
	public void setXFAFormData(int type,byte[] xmlString) throws PdfException{
//		NodeList nodes;
//		Element currentElement;


		DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
		Document content = null;
		try{
			content = factory.newDocumentBuilder().parse(new ByteArrayInputStream(xmlString));
			
//			/**
//			 * get the print values and extract info
//			 */
//			nodes = doc.getElementsByTagName("print");
//				
//			currentElement = (Element) nodes.item(0);
		}catch(Exception e){
			content = null;
		}

		/**
		 * SHOW Data
		 */
		if(showXML==true){

			if(type==XFA_TEMPLATE){
			   System.out.println("xfaTemplate=================");
			}else  if(type==XFA_DATASET){
				System.out.println("XFA_DATASET=================");
			}else  if(type==XFA_CONFIG){
				System.out.println("xfaConfig=================");
			}


			InputStream stylesheet = this.getClass().getResourceAsStream("/org/jpedal/examples/text/xmlstyle.xslt");

			TransformerFactory transformerFactory = TransformerFactory.newInstance();

			/**output tree*/
			try {
				Transformer transformer = transformerFactory.newTransformer(new StreamSource(stylesheet));

				//useful for debugging
				transformer.transform(new DOMSource(content), new StreamResult(System.out));

				System.out.println("/n==========================================");

			} catch (Exception e) {
				e.printStackTrace();
			}

		}

		if(type==XFA_TEMPLATE){
	       xfaTemplate=content;
	       hasXFA = true;
	    }else  if(type==XFA_DATASET){
	        xfaDataset=content;
	        hasXFA = true;
	    }else  if(type==XFA_CONFIG){
	        xfaConfig=content;
	        hasXFA = true;
	    }else{
	        throw new PdfException("Unknown type for XFA");
	    }
	        
	}
	
	/**<p>Used internally to add a form object as it is extracted from the pdf stream. 
	 * Should not need to be called in normal usage.
	*/
	public void addFormElement(Map currentForm){
		
		String value;
		
		/**
		 * remove any / on keys to simplify life
		 */
		Iterator iter = currentForm.keySet().iterator();
		while(iter.hasNext()){
		    Object localKey = iter.next();
		    Object localValue = currentForm.get(localKey);
		    if(localValue instanceof String){
			    value = (String) localValue;
			    if(value!=null){
			        if(value.startsWith("/"))
			            value=value.substring(1);
			        currentForm.put(localKey,value);
			    }
		    }
		}
		
		//add rectangle
		value=(String) currentForm.get("Rect");
		if(value!=null)
			currentForm.put("Rect",Strip.removeArrayDeleminators(value));
		
		/**and put into data object*/
		formObjects.add(formCount,currentForm);
		formCount++;
		
	}
	
	/**add in demo limit*/
	private String makeDemo(String value){
		
		/**alter data in demo version*/
		if (isDemo & (value != null)) {
			StringBuffer textData=new StringBuffer(value);
			int len = textData.length();
			for (int ii = 0; ii < len; ii = ii + 4) {
				
				textData.replace(ii, ii + 1, "1");
				
			}
			value=textData.toString();
		}
		return value;
	}
	
	/**
	 * get all data as list of items with each Form item stored in a Map
	 */
	final public List getFormData()
	{
		
		return formObjects;
	}

	/**increase number of forms items. Some forms items can contain
	 * several objects (ie ButtonGroup)
	*/
	public void incrementCount(int kidCount) {
		totalCount=totalCount+kidCount;
		
	}

	/**total number of Form objects to be displayed. This may not be
	 * the same as the number of Form objects as 1 button group could
	 * contain 6 buttons.
	 */
	public int getTotalCount() {
		return totalCount;
	}
	
}
