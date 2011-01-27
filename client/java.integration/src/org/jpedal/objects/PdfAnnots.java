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
* PdfAnnots.java
* ---------------
*/
package org.jpedal.objects;

import org.jpedal.io.PdfObjectReader;
import org.jpedal.objects.raw.PdfObject;
import org.jpedal.objects.raw.PdfDictionary;
import org.jpedal.utils.LogWriter;
import org.jpedal.utils.Strip;

import java.awt.*;
import java.util.*;
import java.util.List;

/**
 * holds data from annotations and provides methods to
 * decode, store and retrieve PdfAnnotations.
 */
public class PdfAnnots {

	/**current annot number*/
	private int annotNumber=0;
	
	PdfObjectReader currentPdfFile=null;
	
	public static HashMap fields=new HashMap();

    private HashMap names=new HashMap(), objectsChecked=new HashMap();

    final private boolean debugAnnot=false;

    /**hold raw annot data*/
	private Map[] Annots;
	private String[] undecodedAnnots;

	/**hold raw annot shape data*/
	private Map annotArea=new Hashtable();

	//private Map annotType=new Hashtable();

    private Map annotCustomIcon=new HashMap();

	private String pageNumber=null;

    /** sets up annotation object to hold content -
	 * (This method is used internally to generate the annotations 
	 * and should not be called)
	 * 
	 * If pageNumber not needed, pass in null
	 */
	public PdfAnnots(PdfObjectReader currentPdfFile,String pageNumber) {
	    
	    this.currentPdfFile=currentPdfFile;

	    this.pageNumber=pageNumber;

        if(debugAnnot)
                System.out.println("Setup Annot for "+pageNumber+" "+this);

        //setup a list of fields which are string values
		fields.put("Contents","x");
		fields.put("T","x");
		fields.put("NM","x");
        fields.put("Subj","x");    
        fields.put("V","x");
        fields.put("CA","x");
        fields.put("DA","x");
        fields.put("DV", "x");
        fields.put("JS","x");
        fields.put("Dest","x");
	}
	
	private PdfAnnots(){}

    /**
	 * read the annotation from the page
	 */
	final public void readAnnots(byte[][] annotList){
		
		LogWriter.writeMethod("{pdf-readAnnots}", 1);

		/**allow for empty value*/
		if (annotList!=null) {
			try{
				/**work through values or process direct value*/	
				int count=annotList.length;

				Annots=new Map[count];
                undecodedAnnots=new String[count];
                
                for(int ii=0;ii<count;ii++)
					if(annotList[ii]!=null)
					readAnnot(new String(annotList[ii]));
				
			} catch (Exception e) {
				LogWriter.writeLog("Exception processing annots");
			}
		}
	}
	
	/**
	 * get number of annotations
	 */
	final public int getAnnotCount(){
		return annotNumber;
	}
	
	/**
	 * get raw PDF data for annotation - returns null if not in range
	 * (first annot is 0, not 1)
	 */
	final public Map getAnnotRawData(int i){
		
		return (Map) resolveAnnot(i);
	}
    
    /**
     * get all raw PDF data for annotation
     */
    final public List getAnnotRawDataList(){
        
    	//make sure all decoded
    	for(int aa=0;aa<this.annotNumber;aa++)
    		resolveAnnot(aa);
    	
    	if(Annots==null)
    		return null;
    	else{
    		ArrayList list=new ArrayList(annotNumber);
    		for(int ii=0;ii<annotNumber;ii++){
    			if(Annots[ii]!=null)
    			list.add(Annots[ii]);
    		}
    		return list;
    	}
    }

    /**
     * show if has own custom icon
     */
    final public boolean hasOwnIcon(int i){
    	
    	resolveAnnot(i);
    	
        return annotCustomIcon.get(new Integer(i))!=null;
    }

    /**
	 * get  area as it is stored in the PDF
	 */
	final public String getAnnotObjectArea(int i){
		
		resolveAnnot(i);
		
		return (String) annotArea.get(new Integer(i));
	}
	
	
	/**
	 * get  color for annotation
	 */
	final public Color getAnnotColor(int i){
		
	    Color annotColor=null;
	    
	    Map annot= resolveAnnot(i);
	    
	    String cols=(String)annot.get("C");
	    
	    if(cols!=null){
	        String rawCol=Strip.removeArrayDeleminators(currentPdfFile.getValue((cols)));
	        StringTokenizer colElements=new StringTokenizer(rawCol);
	       
	        if(colElements.countTokens()==3){
		        float r=Float.parseFloat(colElements.nextToken());
		        float g=Float.parseFloat(colElements.nextToken());
		        float b=Float.parseFloat(colElements.nextToken());
	        
		        annotColor=new Color(r,g,b);
	    		}
	    }
	    
		return annotColor;
	}
	
	/**
	 * get  stroke to use for border - return null if no border
	 */
	final public Stroke getBorderStroke(int i){
		
	    int width=0;
	    
	    //Map currentAnnot=(Map) Annots.get(i);
	    //String cols=(String)currentAnnot.get("Border");
	    Map annot= resolveAnnot(i);
	    
	    String cols=(String)annot.get("Border");
	    
	    if(cols!=null){
	        String rawCol=Strip.removeArrayDeleminators(currentPdfFile.getValue((cols)));
	        
	        StringTokenizer colElements=new StringTokenizer(rawCol);
	        
	        //allow for no additional values
	        if(colElements.countTokens()==1){
	        	 width=Integer.parseInt(rawCol);
	        }else if(colElements.countTokens()==3){
	        for(int j=0;j<2;j++)
	            colElements.nextToken();
	        
	        width=Integer.parseInt(colElements.nextToken());
	        }
	    }
	    
	    if(width==0)
	        return null;
	    else
	        return new BasicStroke(width);
	}
	
	
	/**
	 * get  area as it is stored in the PDF
	 */
	final public String getAnnotSubType(int i){
		
	    String subtype=null;
	    
	    Map currentAnnot=(Map) resolveAnnot(i);
	    subtype=(String)currentAnnot.get("Subtype");
	    
		//remove / from substype
		if(subtype!=null && subtype.startsWith("/"))
		    subtype=subtype.substring(1);
		
		return subtype;
	}

    

	/**
	 * read an annotation from the page
	 */
	final private void readAnnot(String annot) {

		LogWriter.writeMethod("{pdf-readAnnot: " + annot + '}', 1);

        if(debugAnnot)
            System.out.println("Read Annot "+annot+" into "+this);

        undecodedAnnots[annotNumber]=annot;
        //Annots.add(null);
        
       // generateAnnotMap(annot,annotNumber);
		
        //System.out.println(annotNumber+" "+annot+" "+undecodedAnnots.size()+" "+Annots.size());
        
        
		annotNumber++;
	}
	
	/**
	 * return if not already generated
	 */
	private Map resolveAnnot(int i){
	
		Map annotMap=null;
		
		if(i<Annots.length)
		annotMap=Annots[i];
		
		if(annotMap==null)
			annotMap=generateAnnotMap(undecodedAnnots[i],i);
		
		return annotMap;
	}

	private Map generateAnnotMap(String annot,int annotNumber) {
		
		
		Map annotationObject=currentPdfFile.readObject(new PdfObject(annot), annot,false, fields);

		//add ref
		annotationObject.put("obj",annot);

		//We have worked out full oldName of object as form but reread it here
		//so we need to resolve full oldName here AS well.....

		String name = (String) currentPdfFile.resolveToMapOrString("T", annotationObject.get("T"));
		String parentRef = (String) annotationObject.get("Parent");
		String parentName=null;
       
		/**
		 * scan tree if not already scanned
		 */
		String readName=(String) names.get(parentRef);
		if(readName==null && !objectsChecked.containsKey(parentRef)){
			//this.objectsChecked.put(parentRef, "x");
	        while (parentRef != null) {
	        	
	        	//read parent object
				Map parentObj = currentPdfFile.readObject(new PdfObject(parentRef), parentRef, false, fields);
	
				Object raw=parentObj.get("T");
	
	            if(raw==null)
					break;
	
				//get any oldName set there and append if not null. Try again
				parentName = (String) currentPdfFile.resolveToMapOrString("T", raw);
				if (parentName != null) {
	
					if(name ==null)
						name =parentName;
					else
						name = parentName + '.' + name;
	
					
					//carry on up tree
					parentRef = (String) parentObj.get("Parent");
	
				}
			}
		}else
			name=readName;
    
        if(name!=null)
            annotationObject.put("T", name);

		if(pageNumber!=null)
			annotationObject.put("PageNumber", pageNumber);

		if(annotationObject.get("AP")!=null)
			annotCustomIcon.put(new Integer(annotNumber),"x");

		/**
		 * extract the data we need to display
		 */
		String rectString=(String)annotationObject.get("Rect");

		if(rectString!=null){
			String rect=Strip.removeArrayDeleminators(currentPdfFile.getValue(rectString));
			annotArea.put(new Integer(annotNumber),rect);

			/**
			 * read certain values and substitute
			 */
			String[] keysToSubstitute={"A","FS"};
			substituteKeyValues(currentPdfFile, annotationObject, keysToSubstitute);

			/**
			 * save annotation and update count
			 */
			Annots[annotNumber]=annotationObject;
			
		}
		
		return annotationObject;
	}

	/**
     * replace all values with direct items
     */
    private void substituteKeyValues(PdfObjectReader currentPdfFile, Map annotationObject, String[] keysToSubstitute) {
        int count=keysToSubstitute.length;
		for(int j=0;j<count;j++){
			try{
			Object annotObjectData=annotationObject.get(keysToSubstitute[j]);
			if (annotObjectData != null) {
				
				Map actionData=null;
				if(annotObjectData instanceof String){
					actionData=currentPdfFile.readObject(new PdfObject((String) annotObjectData), (String) annotObjectData,false, null);
					
					if(actionData.containsKey("startStreamOnDisk")) //decompress any streams
					currentPdfFile.readStream((String)annotObjectData, false);
					/**/
					if(actionData.containsKey("Stream")){ //decompress any streams
					    byte[] decompressedData=currentPdfFile.readStream(actionData,(String)annotObjectData,true,false,false, false,false);
					    actionData.put("DecodedStream",decompressedData);
					}/**/
				}else
					actionData = (Map) annotObjectData;
				
				if(actionData.containsKey("EF")){
				    
				    Map fileData=(Map) actionData.get("EF");
				    String[] fileSpec={"F"};
				    substituteKeyValues(currentPdfFile, fileData, fileSpec);
				    
				}
				
				annotationObject.put(keysToSubstitute[j],actionData);
				
				
			}
			}catch(Exception e){
			   LogWriter.writeLog("Exception "+e);
			}
		}
    }

    
    /**
     * a text field
     */
    public String getField(int i,String field) {
        
        String returnValue=null;
        
        
        Object rawAnnot= resolveAnnot(i);
        
        Map currentAnnot=(Map)currentPdfFile.resolveToMapOrString(field,rawAnnot);
        
        byte[] title=currentPdfFile.getByteTextStringValue(currentAnnot.get(field),fields);
        if(title!=null)
			returnValue=currentPdfFile.getTextString(title);
		
        return returnValue;
    }

    /**
     * return color of border or black as default
     */
    public Color getBorderColor(int i) {
        
        Color annotColor=Color.black;
	    
        // String cols=(String)currentAnnot.get("Border");
	    
        Map annot= resolveAnnot(i);
	    
	    String cols=(String)annot.get("Border");
	    
	    if(cols!=null){
	        String rawCol=Strip.removeArrayDeleminators(currentPdfFile.getValue((cols)));
	        StringTokenizer colElements=new StringTokenizer(rawCol);
	       
	        if(colElements.countTokens()==3){
		        float r=Float.parseFloat(colElements.nextToken());
		        float g=Float.parseFloat(colElements.nextToken());
		        float b=Float.parseFloat(colElements.nextToken());
	        
		        annotColor=new Color(r,g,b);
	    		}
	    }
	    
		return annotColor;
    }
}
