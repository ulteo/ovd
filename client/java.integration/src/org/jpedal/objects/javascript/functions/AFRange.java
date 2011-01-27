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
* AFRange.java
* ---------------
*/
package org.jpedal.objects.javascript.functions;

import org.jpedal.objects.acroforms.actions.ActionHandler;
import org.jpedal.objects.acroforms.rendering.AcroRenderer;
import org.jpedal.constants.ErrorCodes;

public class AFRange extends JSFunction{

	public AFRange(AcroRenderer acro, String ref) {
		super(acro,ref);

		functionID=AFRange;
	}

	public void execute(String js, String[] args, int type, int event, char keyPressed) {
		
		if(args==null ){
			debug("Unknown implementation in "+js);

		}else if(args.length<1){
			debug("Values length is less than 1");
		}else{
			
			if(event==ActionHandler.FOCUS_EVENT && type==VALIDATE){

				String currentVal=(String)acro.getCompData().getValue(ref);
				
				//allow empty if not already set
				if(currentVal.length()==0){
					//Allow for no value and ignore validation
					acro.getCompData().setValue(ref,currentVal,true,false,false); //write back
					return;
				}
				
				if(isNotNumber(currentVal) ){
					currentVal=null;
				}else{
					
					//Convert currentVal into a format parseFloat can accept
					String newValue = currentVal;
					
					if(DECIMAL_IS_COMMA){
						newValue = newValue.replaceAll("\\.", "");
						newValue = newValue.replaceAll(",", "\\.");
					}else{
						newValue = newValue.replaceAll(",", "");
					}
					
					float numVal=Float.parseFloat(newValue);
					
					//Get Range if if range values are include
					boolean notEquals1 = Boolean.valueOf(args[1]).booleanValue();
					float min= Float.parseFloat(args[2]);
					boolean notEquals2 = Boolean.valueOf(args[3]).booleanValue();
					float max= Float.parseFloat(args[4]);
					
					//Ensure this float value in within the provided range else set currentVal to null
					if(notEquals1 && numVal<min){
						currentVal=null;
					}else if(!notEquals1 && numVal<=min)
						currentVal=null;
					
					if(notEquals2 && numVal>max){
						currentVal=null;
					}else if(!notEquals2 && numVal>=max)
						currentVal=null;
				}

				if(currentVal==null){//restore and flag error
					maskAlert(ErrorCodes.JSInvalidRangeFormat,args);
					execute(js, args, type, event, keyPressed);
				}else{
					//Set value as the original formated value
					acro.getCompData().setValue(ref,currentVal,true,false,false); //write back
				}
			}else
				debug("Unknown command "+args[0]+" in "+js);
		}
	}
}
