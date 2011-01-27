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
* AFDate.java
* ---------------
*/
package org.jpedal.objects.javascript.functions;

import org.jpedal.objects.acroforms.actions.ActionHandler;
import org.jpedal.objects.acroforms.rendering.AcroRenderer;
import org.jpedal.constants.ErrorCodes;

public class AFDate extends JSFunction{


    public AFDate(AcroRenderer acro, String ref) {
        super(acro,ref);

        functionID=AFDate;
    }

    public void execute(String js, String[] args, int type, int event, char keyPressed) {

        if(event!=ActionHandler.FOCUS_EVENT){
            JSFunction.debug("Not called on key event "+js);
            return ;
        }

        Object[] errArgs=new Object[2];
        errArgs[0]=ref;
        errArgs[1]=stripQuotes(args[1]);
        
        String validatedValue= validateMask(args,":.,/ -",true);

        if(validatedValue!=null && validatedValue.length()>0){ //with date we also test its valid date

            //of course mask is not a perfect match :-(
            //(ie MM is month, not minutes)
           // String dateMask = getJavaDateMask(args);

            //we need to bounce into date and out to string
            //and compare as Date happily excepts 32nd Feb 2006, and other invalid values
           // SimpleDateFormat testDate =new SimpleDateFormat(dateMask);

//            try {
//                Date convertedDate =testDate.parse(validatedValue);
//                System.out.println(validatedValue+" - "+testDate.format(convertedDate).toString());
//                //30th feb would become March so no match
//                if(!validatedValue.equals(testDate.format(convertedDate).toString()))
//                validatedValue=null;
//                
//            } catch (ParseException e) {
//
//                //will force reset and error message in setNewValidValue
//                validatedValue=null;
//            }
            
        }

        //will reset if null
        if(validatedValue==null){
            maskAlert(ErrorCodes.JSInvalidDateFormat,errArgs);
            execute(js, args, type, event, keyPressed);
        }else
            acro.getCompData().setValue(ref,validatedValue,true,true,false);
    }

//    private String getJavaDateMask(String[] args) {
//        String dateMask=stripQuotes(args[1]);
//        StringBuffer mappedMask=new StringBuffer();
//        StringTokenizer dateValue=new StringTokenizer(dateMask,".");
//        while(dateValue.hasMoreTokens()){
//
//            String val=dateValue.nextToken();
//
//            if(val.equals("mm"))
//            val="MM";
//
//            if(mappedMask.length()>0)
//            mappedMask.append('.');
//
//            mappedMask.append(val);
//
//        }
//
//        dateMask=mappedMask.toString();
//        return dateMask;
//    }
}
