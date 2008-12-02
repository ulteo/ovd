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
* AFPercent.java
* ---------------
*/
package org.jpedal.objects.javascript.functions;

import org.jpedal.objects.acroforms.actions.ActionHandler;
import org.jpedal.objects.acroforms.rendering.AcroRenderer;
import org.jpedal.constants.ErrorCodes;
import org.jpedal.sun.PrintfFormat;

public class AFPercent extends AFNumber{

    public AFPercent(AcroRenderer acro, String ref) {
        super(acro,ref);

        functionID=AFPercent;
    }


    public int execute(String js, String[] args, int type, int event, char keyPressed) {

        int messageCode= ActionHandler.NOMESSAGE;

        if(args==null ){
            debug("Unknown implementation in "+js);

        }else if(args.length<1){
            debug("Values length is less than 1");
        }else{

            //settings
            int decCount=Integer.parseInt(args[1]);
            int gapFormat=Integer.parseInt(args[2]);

            if(type==KEYSTROKE){

                messageCode=validateNumber(args, type, event, ErrorCodes.JSInvalidPercentFormat,
                        decCount, gapFormat, 0, 0, "",true, keyPressed);

            }else if(type==FORMAT){

                //current form value
                String currentVal=(String)acro.getCompData().getValue(ref);

                /**
                 * get value, format and add %
                 */
                float number=0;
                String mask="";

                if(currentVal!=null && currentVal.length()>0){
                    StringBuffer numValu = convertStringToNumber(currentVal);

                    //reset if no number or validate
                    if(numValu.length()>0)
                        number=Float.parseFloat(numValu.toString())*100;


                    mask = mask + '%' + gapFormat + '.' + decCount + 'f';

                    //apply mask and add % to end
                    currentVal = new PrintfFormat(mask).sprintf(number)+ '%';
                    
                }else
                    currentVal="";

                //write back
                acro.getCompData().setValue(ref,currentVal,true,true,false);

            }else
                debug("Unknown type "+args[0]+" in "+js);
        }

        return messageCode;
    }
}
