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
* OutlineData.java
* ---------------
*/
package org.jpedal.objects.outlines;

import org.jpedal.io.PdfObjectReader;
import org.jpedal.objects.PageLookup;
import org.jpedal.objects.raw.PdfObject;
import org.jpedal.utils.LogWriter;
import org.jpedal.utils.Strip;
import org.w3c.dom.Document;
import org.w3c.dom.Element;

import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;
import java.awt.*;
import java.util.*;

/**
 * encapsulate the Outline data
 */
public class OutlineData {
	
	private Document OutlineDataXML;
	
	/**locations of top target*/
	private float[] pagesTop;
	
	private Map pointLookupTable;
	
	/**locations of top and bottom target*/
	private float[] pagesBottom;
	
	/**lookup for converting page to ref*/
	private String[] refTop;
	
	/**lookup for converting page to ref*/
	private String[] refBottom;
	
	/**final table*/
	private String[] lookup;
	
	private Map fields=new Hashtable();
	
	private Map keysUsedTable=new Hashtable();
	
	private OutlineData(){}
	
	/**create list when object initialised*/
	public OutlineData(int pageCount){
		
		DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
		try {
			OutlineDataXML=factory.newDocumentBuilder().newDocument();
		} catch (ParserConfigurationException e) {
			System.err.println("Exception "+e+" generating XML document");
		}
		
		//setup a list of fields which are string values
		fields.put("Title","x");
		fields.put("Dest","x");
		
		 String[] keysUsed={"Title","Next","Last"};
		 
		 for(int i=0;i<keysUsed.length;i++)
		 		keysUsedTable.put(keysUsed[i],"x");
		
		 //increment so arrays correct size
		 pageCount++;
		 
		 /**locations of top target*/
		 pagesTop=new float[pageCount];
			
		 /**locations of top and bottom target*/
		 pagesBottom=new float[pageCount];
		 
		 /**lookup for converting page to ref*/
		 refTop=new String[pageCount];
		 
		 /**lookup for converting page to ref*/
		 refBottom=new String[pageCount];
		 
		 /**final table*/
		 lookup=new String[pageCount];
		 
		 /**points*/
		 pointLookupTable=new HashMap();
		 
		
	}
	
	/**return the list*/
	public Document getList(){
		return OutlineDataXML;
	}
	
	/**
	 * read the outline data
	 */
	public int readOutlineFileMetadata(Object outlineObject,PdfObjectReader currentPdfFile,PageLookup pageLookup) {
		
		LogWriter.writeMethod("{readOutlineFileMetadata "+outlineObject+ '}',0);
		
		int count=0;
		String startObj,nextObj,endObj,rawDest,title;
		Map values;
		
		/**read the main object and extract the count and start,end*/
		if(outlineObject instanceof String)
			values = currentPdfFile.readObject(new PdfObject((String)outlineObject), (String)outlineObject,false, null);
		else
			values=(Map) outlineObject;
		
		//count value	
		Object rawNumber=values.get("Count");
		if(rawNumber!=null)
		count = Integer.parseInt(currentPdfFile.getValue((String)rawNumber));
		
		//if(count>0){
			
			//start,end values
			startObj = (String) values.get("First");

			if(startObj!=null){
			
				Element root=OutlineDataXML.createElement("root");
				
				OutlineDataXML.appendChild(root);
				
				int level=0;
				readOutlineLevel(root,currentPdfFile, pageLookup, startObj,level);
				
			}
		//}
			
			/**
		//build lookup table
		int pageCount=this.refTop.length;
		String lastLink=null,currentBottom;
		for(int i=1;i<pageCount;i++){
		    
		    //if page has link use bottom
		    //otherwise last top
		    String link=this.refTop[i];
		    
		    if(link!=null){
		        lookup[i]=link;
		    }else
		        lookup[i]=lastLink;
		    
		    //System.out.println("Default for page "+i+" = "+lookup[i]+" "+refBottom[i]+" "+refTop[i]);
		    //track last top link
		    String top=this.refBottom[i];
		    if(top!=null){
		        lastLink=top;
		    }
		    
		}
		
		//System.exit(1);
		/***/
		return count;
	}
	
	/**
	 * returns default bookmark to select for each page
	 * - not part of API and not live
	 *
	public Map getPointsForPage(){
	    return this.pointLookupTable;
	}*/
	
	/**
	 * returns default bookmark to select for each page
	 * - not part of API and not live
	 */
	public String[] getDefaultBookmarksForPage(){
	    return lookup;
	}

	/**
	 * read a level
	 */
	private void readOutlineLevel(Element root,PdfObjectReader currentPdfFile, PageLookup pageLookup, String startObj,int level) {
		
		String nextObj;
		String endObj;
		String rawDest;
		String convertedTitle="";
		Object anchor;
		float coord=0;
		byte[] title;
		
		Map values;
		Element child=OutlineDataXML.createElement("title");
		
		while(true){
			
			//read the first object
			values = currentPdfFile.readObject(new PdfObject(startObj), startObj,false, fields);
			
			String ID=startObj;
			//set to -1 as default
			coord=-1;
			
			/**
			 * process and move onto next value
			 */
			nextObj=(String) values.get("Next");
			endObj=(String) values.get("Last");
			startObj = (String) values.get("First");
			
			//read the destination
			Object destStream=values.get("Dest");
			if(destStream!=null){
				if(destStream instanceof byte[]){
					rawDest=currentPdfFile.getTextString((byte[]) destStream);
				}else{
					rawDest=(String)destStream;
				}
			}else
				rawDest=null;
			
			//dest in name
			if((rawDest!=null)&&(rawDest.startsWith("("))){
				rawDest=rawDest.substring(1,rawDest.length()-1);
				
				rawDest=currentPdfFile.convertNameToRef(rawDest);
				
				if(rawDest.indexOf('[')==-1){
					Map parentObject= currentPdfFile.readObject(new PdfObject(rawDest), rawDest,false, fields);
					rawDest=(String) parentObject.get("D");
				}
			}
			
			//dest in anchor
			anchor=(values.get("A"));
			if(anchor!=null){
			    Map anchorObj;
			    if(anchor instanceof String){
			        anchorObj = currentPdfFile.readObject(new PdfObject((String)anchor), (String)anchor,false, fields);
			    }else{
			        anchorObj=(Map) anchor;
			    }
	            rawDest=(String)anchorObj.get("D");
	            
	            //if text, reread object and process correctly
	            if(rawDest!=null && rawDest.startsWith("(")){
	            	Map DField=new HashMap();
	            	DField.put("D","x");
	            	currentPdfFile.flushObjectCache();
	            	anchorObj = currentPdfFile.readObject(new PdfObject((String)anchor), (String)anchor,false, DField);
	            	
	            	//System.out.println("nEW MAP="+anchorObj);
	            	byte[] newD=currentPdfFile.getByteTextStringValue(anchorObj.get("D"),DField);
	            	rawDest=currentPdfFile.getTextString(newD);
	            }
	            
	            //System.out.println(rawDest+"<>"+anchorObj);
				//System.exit(1);
			}
			
			
			/**read title allowing for indirect object*/
			//read the title and remove ()
			title= currentPdfFile.getByteTextStringValue(values.get("Title"),fields);
			
			if(title!=null){
				convertedTitle=currentPdfFile.getTextString(title);
				//System.out.println(convertedTitle);	
				//add node
				child=OutlineDataXML.createElement("title");
				root.appendChild(child);
				child.setAttribute("title",convertedTitle);
				
				/**
				 * add any general values
				 */
				Iterator keyList=values.keySet().iterator();
				while(keyList.hasNext()){
					String currentKey=keyList.next().toString();
					//System.out.println(currentKey);
					if(!keysUsedTable.containsKey(currentKey)){
						Object keyValue=values.get(currentKey);
						if((keyValue!=null)&&(keyValue instanceof String))
								child.setAttribute(currentKey,(String)keyValue);	
					}
				}
			}
			
			/**
			 * add destination if any as a page number
			 */
			if((rawDest!=null)&&(rawDest.startsWith("("))){ //convert any indirect ref first
		        rawDest=currentPdfFile.convertNameToRef(rawDest);
		        if(rawDest!=null){
		            Map destObj=currentPdfFile.readObject(new PdfObject(rawDest), rawDest,false,null);
		            if(destObj!=null)
		            rawDest=(String)destObj.get("D");
		        }
		        
		    }else if(rawDest!=null){
		        String name=currentPdfFile.convertNameToRef(rawDest);
		        
		        if(name!=null){//handle indirect ref
		            rawDest=name;
		            if((rawDest!=null)&&(rawDest.endsWith("R"))){
		            	
			            Map destObj=currentPdfFile.readObject(new PdfObject(rawDest), rawDest,false,null);
			            if(destObj!=null)
			            rawDest=(String)destObj.get("D");
			        }
		        }else if(rawDest.endsWith(" R")){
		        	Object dData=currentPdfFile.readObject(new PdfObject(rawDest), rawDest,false,null);
		        	
		        	
		        	if(dData!=null && dData instanceof Map){
		        		Object indirectD=((Map)dData).get("D");
		        		if(indirectD!=null && indirectD instanceof String)
		        			rawDest=(String)indirectD;
		        	}
		        		
		        }
		    }
			
			if((rawDest!=null)){
			   
			    String ref="";
			    int page=-1;
			    if(rawDest.startsWith("[")){
			        
			        StringTokenizer destValues=new StringTokenizer(rawDest,"[]/ ");
			        
			        if(destValues.countTokens()>3)
			            ref=destValues.nextToken()+ ' ' +destValues.nextToken()+ ' ' +destValues.nextToken();
			        
			    }else
			        ref=rawDest;
			    
			    page=pageLookup.convertObjectToPageNumber(ref);
			    
			    //check for indirect ref
			    if(page==-1){
			        Map newValue=currentPdfFile.readObject(new PdfObject(ref), ref,false,null);
			        String rawValue=(String) newValue.get("rawValue");
			        if(rawValue!=null){
			            rawValue=Strip.removeArrayDeleminators(rawValue);
			            int p=rawValue.indexOf(" R");
			            if(p!=-1){
			                ref=rawValue.substring(0,p+2);
			                page=pageLookup.convertObjectToPageNumber(ref);
						   
						   //get location as well
						   p=rawValue.indexOf("/FitH");
						   if(p!=-1){
						       String value=rawValue.substring(p+5).trim();
						       coord=Float.parseFloat(value);
						       //child.setAttribute("top",value);
						   }
			            }
			            
			            //System.out.println(rawValue);
			        }
			    }
			    
			    if(page==-1){
			    	//	System.out.println("No such page "+rawDest+" with ref"+ref+" "+convertedTitle);
			      
			    }else{
			        child.setAttribute("page", String.valueOf(page));
			        child.setAttribute("level", String.valueOf(level));
			        child.setAttribute("objectRef",ID);
			          
			        //track whether we have a link for gotopage
			        Integer pageInt=new Integer(page);
			        
			        /**set location*/
			        /**create the point lookup table*/
					if((rawDest!=null)&&(rawDest.indexOf("/XYZ")!=-1)){ //$NON-NLS-1$
						
						rawDest=rawDest.substring(rawDest.indexOf("/XYZ")+4); //$NON-NLS-1$
						
						StringTokenizer destValues=new StringTokenizer(rawDest,"[] "); //$NON-NLS-1$
						
						//ignore the first, read next 2
						//values.nextToken();
						String x=destValues.nextToken();
						if(x.equals("null")) //$NON-NLS-1$
							x="0"; //$NON-NLS-1$
						String y=destValues.nextToken();
						if(y.equals("null")) //$NON-NLS-1$
							y="0"; //$NON-NLS-1$
						
						pointLookupTable.put(title,new Point((int) Float.parseFloat(x),(int) Float.parseFloat(y)));
					}
					
			        //set defaults
					if(refTop[page]==null){
					    pagesTop[page]=coord;
        		        		refTop[page]=ID;
        		        		pagesBottom[page]=coord;
    		        		    refBottom[page]=ID;
					}else{
					
				        //set top point
				        String lastRef=refTop[page];
				        float last=pagesTop[page];
				        if((last>coord)&&(last!=-1)){
		        		        pagesTop[page]=coord;
		        		        refTop[page]=ID;
		        		    }
			        		
			        		//set bottom point
			        		lastRef=refBottom[page];
				        last=pagesBottom[page];
			        		if((last<coord)&&(last!=-1)){
			        		    pagesBottom[page]=coord;
			        		    refBottom[page]=ID;
			        		}   
		        		}
			    }
			}
			
			if(startObj!=null)
				readOutlineLevel(child,currentPdfFile, pageLookup, startObj,level+1);
			
			if(nextObj==null)
				break;
			
			startObj=nextObj;
			
		}
	}
}
