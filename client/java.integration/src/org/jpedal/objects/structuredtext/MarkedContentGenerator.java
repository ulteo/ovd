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
* MarkedContentGenerator.java
* ---------------
*/
package org.jpedal.objects.structuredtext;

import org.jpedal.PdfDecoder;
import org.jpedal.io.PdfObjectReader;
import org.jpedal.objects.PageLookup;
import org.jpedal.objects.PdfAnnots;
import org.jpedal.objects.raw.PdfObject;
import org.jpedal.utils.Strip;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.Text;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Map;
import java.util.StringTokenizer;

/**
 * extract as marked content
 */
public class MarkedContentGenerator {
	
	Map markInfo,structTreeRoot,ClassMap;
	static final boolean XMLdebugFlag = false;
	private PdfObjectReader currentPdfFile;
	
	DocumentBuilder db=null;
	
	Document doc;
	
	Element root;
	
	PdfDecoder decode_pdf;
	
	static Map fields=new HashMap();
	
	Map pageStreams=new HashMap();
	
	static{
		
		//tell JPedal which are text feilds
		fields.put("ID","x");
		fields.put("T","x");
		fields.put("Lang","x");
		fields.put("Alt","x");
		fields.put("E","x");
		fields.put("ActualText","x");
	}
	
	/**
	 * main entry paint
	 */
	public Document getMarkedContentTree(PdfObjectReader currentPdfFile,PdfDecoder decode_pdf, PageLookup pageLookup) {
		
		this.currentPdfFile=currentPdfFile;
		this.decode_pdf=decode_pdf;
		
		/**
		 * create the XMLtree and root
		 **/
		setupTree();
		
		/**
		 * really cool tree
		 */
		if(structTreeRoot!=null && structTreeRoot.get("ParentTree")!=null){
			
			if(XMLdebugFlag){
				System.out.println("Has structured hierarchy");
				System.out.println(markInfo);
				System.out.println(structTreeRoot);
			}
			
			/**
			 * scan PDF and add nodes
			 */
			buildTree();
			
			//flush all objects
			pageStreams.clear();
			
		}else{ //from the page stream
			
			System.out.println("assume single page to start with!!!!");
			Map pageStream=new HashMap();
			try {
				decode_pdf.decodePageForMarkedContent("1",doc);
				
			} catch (Exception e) {
				e.printStackTrace();  //To change body of catch statement use File | Settings | File Templates.
			}
		}
		
		return doc;
	}
	
	/**
	 * create a blank XML structure and a root. Add comment to say created by JPedal
	 */
	private void setupTree() {
		
		
		try {
			DocumentBuilderFactory dbf = DocumentBuilderFactory.newInstance();
			db = dbf.newDocumentBuilder();
		} catch (ParserConfigurationException e) {
			e.printStackTrace();
		}
		
		doc =  db.newDocument();
		
		/**add Adobe comment*/
		doc.appendChild(doc.createComment(" Created from JPedal "));
		doc.appendChild(doc.createComment(" http://www.jpedal.org "));
		
		
		
	}
	
	/**
	 * scan down PDF struct object, creating XML tree
	 */
	private void buildTree() {
		
		/**create root and attach*/
		root = doc.createElement("TaggedPDF-doc");
		doc.appendChild(root);
		
		/**
		 * read the main values from the structTreeRoot
		 **/
		Map RoleMap=getObjectAsMap("RoleMap",structTreeRoot);
		String IDTree=currentPdfFile.getValue((String)structTreeRoot.get("IDTree"));
		
		/*
		 * read ParentTree and handle indirect ref
		 */
		String ParentTree =(String) structTreeRoot.get("ParentTree");
		if(ParentTree.endsWith("R")){
			Map value=currentPdfFile.readObject(new PdfObject(ParentTree), ParentTree,false,null);
			ParentTree=(String) value.get("Nums");
			
			
		}
		
		
		Object K=structTreeRoot.get("K");
		
		ClassMap=getObjectAsMap("ClassMap",structTreeRoot);
		
		if(XMLdebugFlag){
			System.out.println("==========Reading root==============");
			System.out.println("structTreeRoot="+structTreeRoot);
			System.out.println("RoleMap="+RoleMap);
			System.out.println("IDTree="+IDTree);
			System.out.println("ParentTree="+ParentTree);
			System.out.println("K="+K);
			System.out.println("ClassMap="+ClassMap);
			System.out.println("====================================");
		}
		
		/**
		 * read struct K value and decide what type
		 */
		if(K!=null){
			
			if(XMLdebugFlag)
				System.out.println("Reading K for Root");
			
			if(K instanceof String){
				String value = (String) K;
				if(value.endsWith(" R") && !value.startsWith("/")){
					Map dict=currentPdfFile.readObject(new PdfObject(value), value,false,fields);
					readChildNode(dict,root);
				}else if(value.startsWith("[")){ //indirect ie [ 28 0 R ]
					String possRef=Strip.removeArrayDeleminators(value);
					
					StringTokenizer count=new StringTokenizer(possRef);
					while(count.hasMoreTokens()){
						String obj;
						
						StringBuffer ref=new StringBuffer();
						for(int i=0;i<3;i++){
							ref.append(count.nextToken());
							if(i!=2)
							ref.append(' ');
						}
						
						obj=ref.toString();
						
						Map dict=currentPdfFile.readObject(new PdfObject(obj), obj,false,fields);
						
						readChildNode(dict,root);
					}
					
				}
			}else
				readChildNode(K,root);
			
		}
	}
	
	private void readChildNode(Object K,Element root) {
		
		//if(XMLdebugFlag)
			//System.out.println("readChildNode K value="+K+" parent="+root.getParentNode());
		
		
		boolean processed=false;
		
		if(K instanceof Map){
			Map dict=(Map)K;
			//replace any named values first
			if(XMLdebugFlag)
				System.out.println("remap Values for "+dict);
			
			Iterator keys=dict.keySet().iterator();
			while(keys.hasNext()){
				Object nextKey=keys.next();
				if(!nextKey.equals("S")) //do not remap S
					K = checkClassMap(nextKey,dict);
			}

			//allow for indirect ref to K value
			if(dict.get("S")==null && dict.get("rawValue")!=null){
				K=dict.get("rawValue");
			}else{ //genuine child
				processed=true;
				
				try {
					root=createChildNode(dict,root);
				} catch (Exception e) {
				}
			}
		}
		if(processed){ //no more to do
		}else if(K instanceof String){ //its really a link to further items
			
			String value=(String)K;
			
			//dictionary
			if(value.endsWith(" R") && !value.startsWith("/")){
				Map dict=currentPdfFile.readObject(new PdfObject(value), value,false,fields);
				readChildNode(dict,root);
			}else if(value.startsWith("[")){ //indirect ie [ 28 0 R ]
				String possRef=Strip.removeArrayDeleminators(value);
				
				StringTokenizer count=new StringTokenizer(possRef);
				if(count.countTokens()==3){
					Map dict=currentPdfFile.readObject(new PdfObject(possRef), possRef,false,fields);
					readChildNode(dict,root);
				}
				
			}
			
		}else{
		}
	}
	
	/**
	 * add in the data to the tree
	 */
	private Element createChildNode(Map mapK,Element root) throws Exception {
		
		/**type, Set XML tag*/
		String S=currentPdfFile.getValue((String) mapK.get("S"));
		mapK.remove("S");
		if(S!=null && S.startsWith("/"))
			S=S.substring(1);
		
		//exit with no S
		if(S==null)
			return null;
		
		//make sure S is valid XML
		StringBuffer cleanedS=new StringBuffer();
		int length=S.length();
		for(int i=0;i<length;i++){
			char c=S.charAt(i);
			
			//translate any hex values ( #xx ) into chars
			if(c=='#'){ //assume 2 byte
				StringBuffer num=new StringBuffer(2);
				for(int j=0;j<2;j++){//read number
					i++;
					num.append(S.charAt(i));
				}
				//Convert from hex value as string into ASCI char
				c=(char)Integer.parseInt(num.toString(),16);
				
				//hard-coded based on Adobe output
				if(!Character.isLetterOrDigit(c))
					c='-';
				
			}
			
			//remap spaces
			if(c==' ')
				cleanedS.append('-');
			else if(c=='-') // hard-coded
				cleanedS.append(c);
			else if(Character.isLetterOrDigit(c)) //reject non-valid
				cleanedS.append(c);
		}
		
		S=cleanedS.toString();
		
		//read any content streams
		String Pg=(String) mapK.get("Pg");
		Map pageStream=null;
		if(Pg!=null){
			
			//see if cached and decode if not
			pageStream=(Map)pageStreams.get(Pg);
			
			if(pageStream==null){
				
				pageStream=new HashMap();
				try {
					decode_pdf.decodePageForMarkedContent(Pg,pageStream);
					pageStreams.put(Pg,pageStream);
					
				} catch (Exception e) {
					e.printStackTrace();  //To change body of catch statement use File | Settings | File Templates.
				}
			}
		}
		
		/**
		 * add to tree or include in current if ingored tag
		 */
		Element newRoot=null;
		
		
		newRoot=root;
		if(!S.equals("Span"))
		{
			//see if any mapping for this value
			if(ClassMap!=null){
				
				String newValue=(String) ClassMap.get(S);
				if(newValue!=null){
					//	S=newValue;
					if(XMLdebugFlag){
						System.out.println(S+ ' ' +newValue);
						System.out.println(">"+currentPdfFile.readObject(new PdfObject(newValue), newValue,false,null));
						System.out.println(S);
					}
				}
			}
			  /**/
			if(mapK.get("K")!=null){
				newRoot=doc.createElement(S);
				root.appendChild(newRoot);
			}
			
			
		}
		
		/**
		 * add attributes
		 */
		
		//add remaining attritbutes
		Iterator keys=mapK.keySet().iterator();
		
		
		while(keys.hasNext()){
			String nextKey=keys.next().toString();
			
			if(nextKey.equals("S")){ //had to be done first so ignore
			}else if(nextKey.equals("P")){ //ignore
			}else if(nextKey.equals("A")){ //ignore
			}else if(nextKey.equals("T")){ //show
				//System.out.println("T="+currentPdfFile.getTextString((byte[]) mapK.get("T"))+"<");
			}else if(nextKey.equals("C")){ //ignore
			}else if(nextKey.equals("Pg")){ //ignore
			}else if(nextKey.equals("K")){
				
				String Kvalue=(String)mapK.get("K");
				if(Kvalue.indexOf(' ')==-1){// single number
					int mcid=Integer.parseInt(Kvalue);
					
					//System.out.println(mcid+" "+pageStream);
					String text=(String)pageStream.get(Kvalue);
					if(text!=null && text.length()>0){
						Text textNode=doc.createTextNode(text);
						newRoot.appendChild(textNode);
						
						if(XMLdebugFlag)
							System.out.println("text="+text+" Kvalue="+Kvalue);
						
					}
				}else if(Kvalue.indexOf('[')!=-1){// several numbers or objects
					
					//get count
					final String deliminators="<[ ]>";
					StringTokenizer values=new StringTokenizer(Kvalue,deliminators,true);
					int Kcount=values.countTokens();
					
					//put all values into a array so we can determine if object or number
					String[] Kvalues=new String[Kcount];
					
					//put into array
					int currentItem=0;
					while(values.hasMoreTokens()){
						Kvalues[currentItem]=values.nextToken();
						currentItem++;
					}
					
					//scan all items in turn and parse
					currentItem=0;
					while(currentItem<Kcount){
						if(Kvalues[currentItem].equals("<")){ //handle object embedded
								
							//skip next <
							currentItem++;
							currentItem++;
							
							int mcid=-1;
							String ref="";
							//loop to read values
							while(!Kvalues[currentItem].equals(">")){
								
								if(Kvalues[currentItem].equals("/MCID")){ //read mcid as next item
									
									currentItem++;
									currentItem++;
									mcid=Integer.parseInt(Kvalues[currentItem]);
									
								}else if(Kvalues[currentItem].equals("/Pg")){ //read ref
									currentItem=currentItem+2;
									StringBuffer value=new StringBuffer();
									for(int i=0;i<5;i++){
										value.append(Kvalues[currentItem]);
										currentItem++;
									}
									
									ref=value.toString();
									
								}else if(Kvalues[currentItem].equals("/Obj")){ //read ref
									currentItem=currentItem+2;
									StringBuffer value=new StringBuffer();
									for(int i=0;i<5;i++){
										value.append(Kvalues[currentItem]);
										currentItem++;
									}
									
									Map obj=currentPdfFile.readObject(new PdfObject(value.toString()), value.toString(),false,null);
									String type = null;
									if(obj.get("Type")!=null)
										type=(String) obj.get("Type");
									
									if(obj.get("Subtype")!=null)
										type=(String) obj.get("Subtype");
									
									if(type.equals("/Annot")){
										PdfAnnots annot=decode_pdf.getPdfAnnotsData(null);
										//annot.readAnnots()
									}
									if(type.equals("/Link")){
										
									}else{
									}
								}
								currentItem++;
							}
								
							
							/**
							 * process embedded object
							 */				
							
							if(mcid!=-1){
								
								//see if stream cached and decode if not
								pageStream=(Map)pageStreams.get(ref);
								if(pageStream==null){
									
									pageStream=new HashMap();
									try {
										decode_pdf.decodePageForMarkedContent(ref,pageStream);
										pageStreams.put(Pg,pageStream);
										
									} catch (Exception e) {
										e.printStackTrace();  //To change body of catch statement use File | Settings | File Templates.
									}
								}
								
								if(XMLdebugFlag)
									System.out.println("Set PG for "+Pg+ ' ' +pageStream);
								
								//create node
								String text=(String)pageStream.get(String.valueOf(mcid));
								Text textNode=doc.createTextNode(text);
								newRoot.appendChild(textNode);
								
								//if(XMLdebugFlag)
								//	System.out.println("Text="+text+" mcid="+mcid);
								
							}
							//skip next >
							currentItem++;
							
						}else if(deliminators.indexOf(Kvalues[currentItem])!=-1){//ignore deliminators OTHER THAN < 
							
						}else{
							boolean isRef=(Kcount-currentItem>4 && Kvalues[currentItem+4].equals("R"));
							//lookup item referenced

							if(!isRef){
								//read the number
								int mcid=Integer.parseInt(Kvalues[currentItem]);
								String text=(String)pageStream.get(String.valueOf(mcid));
								
								Text textNode=doc.createTextNode(text);
								newRoot.appendChild(textNode);
								
								//if(XMLdebugFlag)
								//	System.out.println("Noref Text="+text+" mcid="+mcid);
							}else if(isRef){ //its a reference so read
								StringBuffer value=new StringBuffer();
								for(int i=0;i<5;i++){
									value.append(Kvalues[currentItem]);
									currentItem++;
								}
								
								Map objData=currentPdfFile.readObject(new PdfObject(value.toString()), value.toString(),false, fields);
								readChildNode(objData,newRoot);
							}else{
								/*
								 * MCR dictionary (according to the spec)
								 * Need to add handling into here.
								 * REFNo3503
								 */
								
								
							}
						}
						
						currentItem++;
					}
					
					//if(Kvalue.indexOf("R")!=-1)
					//System.exit(1);
				}else if(Kvalue.indexOf('R')!=-1){
					// handle child elements
					readChildNode(Kvalue,newRoot);
					
				}else{
					if(XMLdebugFlag){
						System.out.println("KK value unsupported >"+Kvalue+ '<');
						System.exit(1);
				}
				}
			}else{ //generic
				if(XMLdebugFlag)
					System.out.println("Used generic code for nextKey="+nextKey);
				
				Object nextValue=mapK.get(nextKey);
				if(nextValue instanceof String){
					newRoot.setAttribute(nextKey,((String)nextValue));	
				}
				else if(nextValue instanceof byte[]){
					String string=currentPdfFile.getTextString((byte[])nextValue);
					newRoot.setAttribute("xml:"+nextKey.toLowerCase(),string);
				}
				
			}
		}
		
		return newRoot;
	}
	

	/**
	 * if its actually a reference to another object replace with object
	 * @param key
	 * @param values
	 */
	private Object checkClassMap(Object key,Map values) {
		Object value=values.get(key);
		if(value==null)
			return values;
		
		if(value instanceof String && ClassMap != null){
			String name=((String)value).substring(1);
			Object realValue=ClassMap.get(name);
			
			if(realValue!=null){
				values.put(key,realValue);
			}
		}
		return values;
	}
	
	private Map getObjectAsMap(String ID, Map map){
		
		Map ClassMap=null;
		
		Object raw=structTreeRoot.get(ID);
		
		if (raw instanceof String)
			ClassMap = currentPdfFile.readObject(new PdfObject((String) raw), (String) raw,false, fields);
		else
			ClassMap = (Map) raw;
		
		return ClassMap;
		
	}
	
	public void setRootValues(Map structTreeRoot, Map markInfo) {
		
		this.structTreeRoot=structTreeRoot;
		this.markInfo=markInfo;
	}
}
