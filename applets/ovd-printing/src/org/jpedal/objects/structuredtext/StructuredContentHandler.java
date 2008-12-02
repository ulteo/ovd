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
* StructuredContentHandler.java
* ---------------
*/
package org.jpedal.objects.structuredtext;

import java.util.HashMap;
import java.util.Iterator;
import java.util.Map;

import org.jpedal.io.PdfObjectReader;
import org.w3c.dom.Element;
import org.w3c.dom.Document;
import org.w3c.dom.Node;

/**
 * structured content
 */
public class StructuredContentHandler {

	/**flag to show if we add co-ordinates to merely tagged content*/
	private boolean addCoordinates=true;

    /**store entries from BMC*/
    private Map markedContentProperties;

    /**handle nested levels of marked content*/
    private int markedContentLevel = 0;

    /**stream of marked content*/
    private StringBuffer markedContentSequence;

    static final private boolean debug=false;

    private boolean contentExtracted=false;

    private boolean mapTags=true;

    private String currentKey;

    private Map keys,values,dictionaries;

    private StringBuffer finalXML;
    private Map lookupStructParents;

    PdfObjectReader currentPdfFile;
    private String imageName;

    boolean buildDirectly=false;

    Document doc;

    Element root;

	private float x1,y1,x2,y2;

    public StructuredContentHandler(Object markedContent) {

        //build either tree of lookuptable
        if(markedContent instanceof Map){
        	buildDirectly=false;
            values=(Map)markedContent;

        }else{
            buildDirectly=true;
            doc=(Document)markedContent;
            root = doc.createElement("TaggedPDF-doc");
		    doc.appendChild(root);
        }
        
        if(debug)
        	System.out.println("BuildDirectly="+buildDirectly);

        //this.currentPdfFile=currentPdfFile;

        markedContentProperties=new HashMap();
        markedContentLevel = 0;

        markedContentSequence = new StringBuffer();

        currentKey="";

        finalXML=new StringBuffer();

        keys=new HashMap();
        dictionaries=new HashMap();


        lookupStructParents=new HashMap();
    }


    public void MP() {

    }

    public void DP(Map dictionary) {

        if(debug){
            System.out.println("DP----------------------------------------------------------"+markedContentLevel);

            System.out.println(dictionary);

            System.out.println("Property list="+dictionary);

        }


    }

    public void BDC(Map dictionary) {

        //if start of sequence, reinitialise settings
        if (markedContentLevel == 0)
            markedContentSequence = new StringBuffer();

        markedContentLevel++;

		//get main key
        Iterator allKeys=dictionary.keySet().iterator();
        String mainKey=allKeys.next().toString();
        
        Map dict=(Map)dictionary.get(mainKey);
        
        //only used in direct mode and breaks non-direct code so remove
        if(buildDirectly)
        	dict.remove("MCID");
        
        String MCID=(String)dict.get("MCID");
        
        //save key
        if(MCID!=null){
        	keys.put(new Integer(markedContentLevel),MCID); 
        	dictionaries.put(String.valueOf(markedContentLevel),dict);
    	}else{ //try to use tags if no structure
    		dictionaries.put(String.valueOf(markedContentLevel),dictionary);
    	}
        
        if(debug){
            System.out.println("BDC----------------------------------------------------------"+markedContentLevel+" MCID="+MCID);
            System.out.println("Property list="+dictionary);
        }
        
        
    }


    public void BMC(String op) {

        //stip off /
        if(op.startsWith("/"))
            op=op.substring(1);

        //if start of sequence, reinitialise settings
        if (markedContentLevel == 0)
            markedContentSequence = new StringBuffer();

        markedContentProperties.put(new Integer(markedContentLevel),op);

        markedContentLevel++;

        if(debug)
            System.out.println("BMC----------------------------------------------------------level="+markedContentLevel+" raw op="+op);

        //save label and any dictionary
        keys.put(new Integer(markedContentLevel),op);

		if(buildDirectly){
			//read any dictionay work out type
			Map dict=(Map) dictionaries.get(currentKey);
			boolean isBMC=dict==null;

			//add node with name for BMC
			if(op!=null){
				//System.out.println(op+" "+root.getElementsByTagName(op));
				Element newRoot=(Element) root.getElementsByTagName(op).item(0);
				
				if(newRoot==null){
					newRoot=doc.createElement(op);
					root.appendChild(newRoot);
				}
				root=newRoot;
			}
        }
	}


    public void EMC() {

    	//set flag to show some content
    	contentExtracted=true;
    	
        /**
         * add current structre to tree
        **/
        currentKey=(String)keys.get(new Integer(markedContentLevel));

        //if no MCID use current level as key
        if(currentKey==null)
        	currentKey= String.valueOf(markedContentLevel);
        
        if(debug)
        	System.out.println("currentKey="+currentKey+ ' ' +keys);
        
        if(buildDirectly){
        	
			//read any dictionay work out type
			Map dict=(Map) dictionaries.get(currentKey);
			boolean isBMC=(dict==null);
			

			//if(debug)
				System.out.println(isBMC+" "+currentKey+ ' ' +dict+" markedContentSequence="+markedContentSequence);
			
			//add node with name for BMC
			if(isBMC){
				if(currentKey!=null){
					
					Node child=doc.createTextNode(stripEscapeChars(markedContentSequence.toString()));
					
					root.appendChild(child);
					
					if(addCoordinates){
						root.setAttribute("x1", String.valueOf((int) x1));
						root.setAttribute("y1", String.valueOf((int) y1));
						root.setAttribute("x2", String.valueOf((int) x2));
						root.setAttribute("y2", String.valueOf((int) y2));
					}	

					Node oldRoot=root.getParentNode();
					root=(Element) oldRoot;
				}
			}else{
				//get root key on dictionary (should only be 1)
				//and create node
				Iterator keys=dict.keySet().iterator();
				String S=(String) keys.next();
				
				Element tag = doc.createElement(S);
				root.appendChild(tag);
				
				//now add any attributes
				Map atts=(Map) dict.get(S);
				Iterator attribKeys=atts.keySet().iterator();
				while(attribKeys.hasNext()){
					String nextAtt=(String) attribKeys.next();
					tag.setAttribute(nextAtt,stripEscapeChars(atts.get(nextAtt)));
				}
				
				if(addCoordinates){
					tag.setAttribute("x1", String.valueOf((int) x1));
					tag.setAttribute("y1", String.valueOf((int) y1));
					tag.setAttribute("x2", String.valueOf((int) x2));
					tag.setAttribute("y2", String.valueOf((int) y2));
				}
				
				
				//add the text
				Node child=doc.createTextNode(markedContentSequence.toString());
				tag.appendChild(child);
            	//System.exit(1);
			}

			//reset
			markedContentSequence=new StringBuffer();


        }else{
        	String ContentSequence = markedContentSequence.toString();
        	
        	/*if(ContentSequence.indexOf("&amp;")!= -1){
        		ContentSequence = ContentSequence.replaceAll("&amp;","&");
        	}
        	
        	if(ContentSequence.indexOf("&lt;")!= -1){
        		ContentSequence = ContentSequence.replaceAll("&lt;","<");
        		//System.out.print(">>>>>>>>>>>> Temp =="+ContentSequence);
        	}
        	
        	if(ContentSequence.indexOf("&gt;")!= -1){
        		ContentSequence = ContentSequence.replaceAll("&gt;",">");
        	}

        	if(ContentSequence.indexOf("&#")!= -1){
        		//convert hex numbers to the char value
        	}*/
        	
        	//System.out.println(currentKey+" "+markedContentSequence);
            if(debug)
            	System.out.println("write out "+currentKey+" text="+markedContentSequence+ '<');
            Map dict=(Map) (dictionaries.get(String.valueOf(markedContentLevel)));
            
            //reset on MCID tag
            if(dict!=null && dict.containsKey("MCID")){
            	values.put(currentKey,ContentSequence);
                //testi.pdf wrong tags
            	markedContentSequence=new StringBuffer();
            }
            
            //remove used dictionary
            dictionaries.remove(String.valueOf(markedContentLevel));

        }

        if (markedContentLevel > 0)
            markedContentLevel--;

        if(debug)
        System.out.println("EMC----------------------------------------------------------"+markedContentLevel);

        
    }

    /**store the actual text in the stream*/
    public void setText(StringBuffer current_value,float x1,float y1,float x2,float y2) {

         markedContentSequence.append(current_value);
         this.x1=x1;
         this.y1=y1;
         this.x2=x2;
         this.y2=y2;

    }


    public void setImageName(String name) {
        this.imageName=name;

    }
    
    //delete escape chars such as \( but allow for \\
	private String stripEscapeChars(Object dict) {
		char c,lastC=' ';
		
		StringBuffer str=new StringBuffer((String) dict);
		int length=str.length();
		for(int ii=0;ii<length;ii++){
			c=str.charAt(ii);
			if(c=='\\' && lastC!='\\'){
				str.deleteCharAt(ii);
				length--;
			}
			lastC=c;
				
		}
		
		return str.toString();
		
	}

	public boolean hasContent() {
		return contentExtracted;
	}
}
