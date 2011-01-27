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
* AFSimple.java
* ---------------
*/
package org.jpedal.objects.javascript.functions;

import org.jpedal.objects.acroforms.rendering.AcroRenderer;

public class AFSimple extends JSFunction{
    

    public AFSimple(AcroRenderer acro, String ref) {
        super(acro,ref);

        functionID=AFSimple;
    }

    public void execute(String js, String[] args, int type, int eventType, char keyPressed) {
    	
        if(args==null ){
            debug("Unknown implementation in "+js);

        }else if(args.length<1){
            debug("Values length is less than 1");
        }else{

            if(type==JSFunction.CALCULATE){

                String result="";

                int currentItem=1;

                //get first value which is command
                String nextValue=args[currentItem];

                int objType=convertToValue(nextValue);

                if(objType!=-1){

                    currentItem++;
                    nextValue=args[currentItem];

                    String rest="";
                    if(nextValue.startsWith("new Array")){

                        result = processArray(nextValue,objType);

                    }else
                        debug("Unknown params "+rest+" in "+js);

                }else
                    debug("Unknown command "+nextValue+" in "+js);


                //write back
                acro.getCompData().setValue(ref,result,true,false,false);

            }else
                debug("Unknown command "+args[0]+" in "+js);
        }
    }

    /**
     * get string name as int value if recognised
     * @param rawValue
     * @return
     */
    private static int convertToValue(String rawValue) {

        int value=-1; //default no match

        if(rawValue.equals("\"SUM\""))
            value=SUM;
        else if(rawValue.equals("\"AVG\""))
            value=AVG;
        else if(rawValue.equals("\"PRD\""))
            value=PRD;
        else if(rawValue.equals("\"MIN\""))
            value=MIN;
        else if(rawValue.equals("\"MAX\""))
            value=MAX;


        return value;
    }

}
