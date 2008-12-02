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
* Javascript.java
* ---------------
*/
package org.jpedal.objects;

//<start-os>
import org.jpedal.objects.acroforms.actions.ActionHandler;
import org.jpedal.objects.acroforms.rendering.AcroRenderer;
import org.jpedal.objects.acroforms.utils.ConvertToString;
import org.jpedal.objects.javascript.ExpressionEngine;
import org.jpedal.objects.javascript.DefaultParser;

import java.util.*;

//<end-os>

/**
 * general container for javascript - empty class in GPL version
 */
public class Javascript {

	//<start-os>
	
	/**
	 * local copy to access
	 */
	private AcroRenderer renderer;

    //user implementation
    private ExpressionEngine userExpressionEngine;

    final private String deliminator= String.valueOf((char) 65535);

    final public static boolean debugActionHandler=false;
    
    /**default to handle commands*/
    private ExpressionEngine jsParser= new DefaultParser();


    public void setRenderer(AcroRenderer acro){
    
    	this.renderer=acro;
    }
    
    public boolean isJavaScriptEnabled() {
        return enableJavaScript;
    }

    private boolean enableJavaScript = false;

    private Map javascriptCommands=new HashMap();
    private Map javascriptTypesUsed=new HashMap();
    private Map linkedjavascriptCommands=new HashMap();

    private boolean hasJavascript;

    public boolean hasJavascript() {
        return hasJavascript;
    }

    public void readJavascript() {
        hasJavascript = true;
    }

    public void reset() {
        hasJavascript = false;
    }


    /**
     * called to execute various action commands such as page opened
     * triggered by events not easily tracked with listeners
     */
    public  void executeAction(int pageNumber,int actionType){

        //will probably need to map page number onto ref so we can retrieve or use

        //page number and not object id to store - really needs an example

        
    }

    public int execute(String ref, int type,int eventType,char keyPressed) {
    	
        int returnCode=executeCommand(ref, type, eventType, keyPressed);

        
        if(eventType==ActionHandler.FOCUS_EVENT){

            //C action requires us to execute other objects code
            String linkedObj= (String) linkedjavascriptCommands.get(ref);

//            if(ref.indexOf("Text15")!=-1)
//                System.out.println(ref+" "+eventType+" linkedObj="+linkedObj);
                
            if(linkedObj!=null){
                StringTokenizer values=new StringTokenizer(linkedObj,deliminator);

                while(values.hasMoreTokens())
                    executeCommand(values.nextToken(), ActionHandler.C2, eventType, keyPressed);

            }else
            	executeCommand(ref, ActionHandler.C2, eventType, keyPressed);
        }

        return returnCode;
    }

    private int executeCommand(String ref, int type,int eventType,char keyPressed) {
    	
        int message=ActionHandler.NOMESSAGE;
        
        //get javascript
        Object js= javascriptCommands.get(ref+ '-' +type);
        
        if(js==null)
            return ActionHandler.NOMESSAGE;

        if(debugActionHandler)
                System.out.println("[Javascript] Action Handler is "+userExpressionEngine+" is Javascript object "+this);

        //allow user to add own expression engine
        if(userExpressionEngine!=null)
            message=userExpressionEngine.execute(ref, renderer, type, js, eventType , keyPressed);

        if(message!=ActionHandler.STOPPROCESSING)
            message=jsParser.execute(ref, renderer, type, js, eventType, keyPressed);

        return message;
    }

    /**
     * Return Javascript strings associated with object
     * @param ref
     */
    public List getRelatedJavascriptCommands(String ref) {


        List list=new ArrayList();

        Iterator types=javascriptTypesUsed.keySet().iterator();

        while(types.hasNext()){

        	int type=((Integer)types.next()).intValue();

        	//get javascript
        	//scan all possible values
        	String js=(String) javascriptCommands.get(ref+ '-' +type);

        	if(js!=null)
        		list.add(js);

        }

        return list;
    }


    /**
     * store and execute code from Names object
     */
    public void setCode(String nextKey, String value) {


        //@forms
        

    }

    public void closeFile() {

        javascriptTypesUsed.clear();
        javascriptCommands.clear();
        linkedjavascriptCommands.clear();


        if(userExpressionEngine!=null)
        userExpressionEngine.closeFile();

        jsParser.closeFile();
        

    }
    
    public void storeJavascript(String ref, Object code,int type) {

        javascriptCommands.put(ref+ '-' +type,code);

        javascriptTypesUsed.put(new Integer(type),"x");//track types used so we can recall

        //System.out.println(type+"Store "+ref+" = "+script);

        //log all values in "" as possible fields
        //(will include commands and spurious links as well)
        if(type==ActionHandler.C2){

            int ptr=0,start;
            String script = (String)code;

            while(true){

                //get start " but ignore \"
                int escapedptr=script.indexOf("\\\"",ptr);
                while(true){
                    ptr=script.indexOf('\"',ptr);
                    if(ptr==-1 || escapedptr==-1 || ptr-1>escapedptr)
                        break;

                }

                if(ptr==-1)
                    break;

                ptr++; //roll on
                start=ptr;

                //get end " but ignore \"
                escapedptr=script.indexOf("\\\"",ptr);
                while(true){
                    ptr=script.indexOf('\"',ptr);

                    if(ptr==-1 || escapedptr==-1 || ptr-1>escapedptr)
                        break;

                }

                if(ptr==-1)
                break;

                String obj=script.substring(start,ptr);

                if(obj!=null){
                    String existingList=(String) linkedjavascriptCommands.get(obj);

                    if(existingList==null)
                        existingList=ref;
                    else
                        existingList=existingList+deliminator+ref;

                    linkedjavascriptCommands.put(obj,existingList);
                }

                ptr++; //roll on

            }
        }
    }

    public void setUserExpressionEngine(ExpressionEngine expressionEngine) {
        this.userExpressionEngine=expressionEngine;

        if(debugActionHandler)
        System.out.println("Action Handler set to "+expressionEngine+" is Javascript object "+this);
    }

//  <end-os>

}
