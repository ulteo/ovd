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
* DefaultParser.java
* ---------------
*/
package org.jpedal.objects.javascript;

import java.util.Iterator;
import java.util.Map;

import org.jpedal.objects.acroforms.actions.ActionHandler;
import org.jpedal.objects.acroforms.rendering.AcroRenderer;
import org.jpedal.objects.acroforms.utils.ConvertToString;
import org.jpedal.objects.javascript.functions.*;

public class DefaultParser implements ExpressionEngine
{

    //rhino parser for when we need it
    private JSParser parser=null;

    private String ref;
    private AcroRenderer acro;
    private ExpressionEngine userExpressionEngine;

    /**
     * execute javascript and reset forms values
     */
    public int execute(String ref, AcroRenderer acro, int type, Object code, int eventType, char keyPressed) {

        int messageCode=ActionHandler.NOMESSAGE;
        
        if(code instanceof String){
        	String js = (String) code;
        	
	        //convert into args array
	        String[] args=JSFunction.convertToArray(js);
	
	        String command=args[0];
	
	        //make global so we don't have to pass in
	        this.acro=acro;
	        this.ref=ref;
	
	        if(command.startsWith("AF"))
	            messageCode=handleAFCommands(command,js, args, eventType, keyPressed);
	        else
	            JSFunction.debug("Unknown command "+js);
        }

        return messageCode;
    }

    private int handleAFCommands(String command,String js, String[] args, int eventType, char keyPressed) {

        int messageCode=ActionHandler.NOMESSAGE;

        //Workout type
        int type=JSFunction.UNKNOWN;

        if(js.indexOf("_Keystroke")!=-1){
            type=JSFunction.KEYSTROKE;
        }else if(js.indexOf("_Validate")!=-1){
            type=JSFunction.VALIDATE;
        }else if(js.indexOf("_Format")!=-1){
            type=JSFunction.FORMAT;
        }else if(js.indexOf("_Calculate")!=-1){
            type=JSFunction.CALCULATE;
        }

        if(eventType!=ActionHandler.FOCUS_EVENT && (type==JSFunction.VALIDATE || type==JSFunction.FORMAT)){
            JSFunction.debug("Not called on key event "+js);
            return messageCode;
        }


        if(js.startsWith("AFSpecial_"))
            new AFSpecial(acro,ref).execute(js, args, type, eventType, keyPressed);
        else if(command.startsWith("AFPercent_"))
            new AFPercent(acro,ref).execute(js, args, type, eventType, keyPressed);
        else if(command.startsWith("AFSimple_"))
            new AFSimple(acro,ref).execute(js, args, type, eventType, keyPressed);
        else if(command.startsWith("AFDate_"))
            new AFDate(acro,ref).execute(js, args, type, eventType, keyPressed);
        else if(js.startsWith("AFNumber_"))
            messageCode=new AFNumber(acro,ref).execute(js, args, type, eventType, keyPressed);
        else if(js.startsWith("AFRange_"))
            new AFRange(acro,ref).execute(js, args, type, eventType, keyPressed);
        else if(js.startsWith("AFTime_"))
            new AFTime(acro,ref).execute(js, args, type, eventType, keyPressed);
        else
            JSFunction.debug("Unknown command "+js);

        return messageCode;
    }

    public void closeFile() {
        
        //flush rhino parser
        if(parser!=null)
            parser.flush();

    }

    public boolean reportError(int code, Object[] args) {
        //does nothing in default
        return false;
    }

}
