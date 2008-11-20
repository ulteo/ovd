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
* AFTime.java
* ---------------
*/
package org.jpedal.objects.javascript.functions;

import org.jpedal.objects.acroforms.actions.ActionHandler;
import org.jpedal.objects.acroforms.rendering.AcroRenderer;
import org.jpedal.constants.ErrorCodes;

public class AFTime extends JSFunction {
    
    public AFTime(AcroRenderer acro, String ref) {

        super(acro,ref);

        functionID=AFTime;
    }

    public void execute(String js, String[] args,int type,int event, char keyPressed) {

        if(event== ActionHandler.FOCUS_EVENT && type==KEYSTROKE){

            JSFunction.debug("AFTime(format)="+js);

            String validatedValue= validateMask(args,":",false);

            if(validatedValue==null){

                Object[] errArgs=new Object[1];
                errArgs[0]=ref;

                maskAlert(ErrorCodes.JSInvalidFormat,errArgs);
                execute(js, args, type, event, keyPressed);
            }else
                acro.getCompData().setValue(ref,validatedValue,true,true,false);


        }else if(type==KEYSTROKE){ //just ignore and process on focus lost

            JSFunction.debug("AFTime(keystroke)="+js);
        
        }else
            JSFunction.debug("Unknown command "+js);

    }
}
