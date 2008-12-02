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
* AFSpecial.java
* ---------------
*/
package org.jpedal.objects.javascript.functions;

import org.jpedal.objects.acroforms.actions.ActionHandler;
import org.jpedal.objects.acroforms.rendering.AcroRenderer;
import org.jpedal.constants.ErrorCodes;
import org.jpedal.sun.PrintfFormat;

public class AFSpecial extends JSFunction{

    public AFSpecial(AcroRenderer acro, String ref) {
        super(acro,ref);

        functionID=AFSpecial;
    }

    public void execute(String js, String[] args, int type, int event, char keyPressed) {

        if(args==null ){
            debug("Unknown implementation in "+js);

        }else if(args.length<1){
            debug("Values length is less than 1");
        }else{

            //settings - if no value will default to special
            int specialID=-1;
            char c=args[1].charAt(0);

            if(args[1].length()==1 && c>='0' && c<='3' ) //ignore if special as would throw exception
                specialID=Integer.parseInt(args[1]);

            boolean isExecuted=true; //asume true and reset if not

            //current form value
            String currentVal=(String)(acro.getCompData().getValue(ref));
            String processedVal="";

            if(type==KEYSTROKE){

                //massage data with regexp
                switch (specialID){

                    case 0:  //zip

                        if(event== ActionHandler.FOCUS_EVENT)
                            processedVal=applyRegexp(currentVal, new String[]{"\\d{5}"});
                        else if(event== ActionHandler.MOUSERELEASED)
                            processedVal=applyRegexp(currentVal,new String[]{"\\d{0,5}"});

                        break;

                    case 1:  //extended zip

                        if(event== ActionHandler.FOCUS_EVENT)
                            processedVal=applyRegexp(currentVal, new String[]{"\\d{5}(\\.|[- ])?\\d{4}"});
                        else if(event== ActionHandler.MOUSERELEASED)
                            processedVal=applyRegexp(currentVal, new String[]{"\\d{0,5}(\\.|[- ])?\\d{0,4}"});


                        break;

                    case 2:  //phone

                        if(event== ActionHandler.FOCUS_EVENT)
                            processedVal=applyRegexp(currentVal,
                                    new String[]{"\\d{3}(\\.|[- ])?\\d{4}","\\d{3}(\\.|[- ])?\\d{3}(\\.|[- ])?\\d{4}",
                                            "\\(\\d{3}\\)(\\.|[- ])?\\d{3}(\\.|[- ])?\\d{4}","011(\\.|[- \\d])*"});
                        else if(event== ActionHandler.MOUSERELEASED)
                            processedVal=applyRegexp(currentVal,
                                    new String[]{"\\d{0,3}(\\.|[- ])?\\d{0,3}(\\.|[- ])?\\d{0,4}",
                                            "\\(\\d{0,3}","\\(\\d{0,3}\\)(\\.|[- ])?\\d{0,3}(\\.|[- ])?\\d{0,4}",
                                            "\\(\\d{0,3}(\\.|[- ])?\\d{0,3}(\\.|[- ])?\\d{0,4}",
                                            "\\d{0,3}\\)(\\.|[- ])?\\d{0,3}(\\.|[- ])?\\d{0,4}", "011(\\.|[- \\d])*"});

                        break;

                    case 3:  //SSN

                        if(event== ActionHandler.FOCUS_EVENT)
                            processedVal=applyRegexp(currentVal,
                                    new String[]{"\\d{3}(\\.|[- ])?\\d{2}(\\.|[- ])?\\d{4}"});
                        else if(event== ActionHandler.MOUSERELEASED)
                            processedVal=applyRegexp(currentVal,
                                    new String[]{"\\d{0,3}(\\.|[- ])?\\d{0,2}(\\.|[- ])?\\d{0,4}"});

                        break;

                    default:  //special

                        if(event== ActionHandler.FOCUS_EVENT || event== ActionHandler.MOUSERELEASED)
                            processedVal=applyRegexp(currentVal,new String[]{args[1]});

                        break;
                }

                //if its changed its not valid
                if(event==ActionHandler.FOCUS_EVENT){

                    if(!processedVal.equals(currentVal)){
                        maskAlert(ErrorCodes.JSInvalidSpecialFormat,args);
                        execute(js, args, type, event, keyPressed);
                    }else
                        acro.getCompData().setValue(ref,processedVal,true,true,false);  //write back

                }else if(event==ActionHandler.MOUSEPRESSED || event ==ActionHandler.MOUSERELEASED){ //we do not check on keystrokes

                }else
                    isExecuted=false;

            }else if(type==FORMAT){

                /**
                 * strip out number value or 0 for no value
                 */
                float number=0;

                String mask="";

                if(currentVal!=null && currentVal.length()>0){

                    //massage data with regexp
                    switch (specialID){

                        case 0:  //zip

                            mask="99999";

                            break;

                        case 1:  //extended zip

                            mask = "99999-9999";
                            break;

                        case 2:  //phone

                            //count digits and choose if 'local' or area
                            int digitCount=countDigits(currentVal);

                            if (digitCount >9 )
                                mask = "(999) 999-9999";
                            else
                                mask= "999-9999";

                            break;

                        case 3:  //SSN

                            mask = "999-99-9999";

                            break;

                        default:
                            isExecuted=false;
                            break;
                    }

                    //apply mask
                    if(isExecuted)
                        currentVal = new PrintfFormat(mask).sprintf(number);

                    acro.getCompData().setValue(ref,currentVal,true,true,false);  //write back

                }
            }else
                isExecuted=false;



            if(!isExecuted)
                debug("Unknown setting or command "+args[0]+" in "+js);
        }
    }

    //count numbers in string
    private static int countDigits(String currentVal) {

        int count=0;
        int len=currentVal.length();
        for(int i=0;i<len;i++){
            char c=currentVal.charAt(i);
            if(c>='0' && c<='9')
            count++;
        }

        return count;
    }

    protected boolean validateNumber(String[] args, int type, int event, int errCode, int decCount,
                                     int gapFormat, int minusFormat, int currentStyle, String currencyMask,
                                     boolean hasCurrencySymbol) {

        boolean isExecuted=true; //asume true and reset if not

        //current form value
        String currentVal=(String)acro.getCompData().getValue(ref);
        String processedVal="";

        if(type==KEYSTROKE){

            //massage data with regexp
            if(gapFormat>1){

                if(event== ActionHandler.FOCUS_EVENT){
                    processedVal=applyRegexp(currentVal,
                            new String[]{"[+-]?\\d+([.,]\\d+)?","[+-]?[.,]\\d+","[+-]?\\d+[.,]"});
                }else if(event== ActionHandler.MOUSERELEASED)
                    processedVal=applyRegexp(currentVal,new String[]{"[+-]?\\d*,?\\d*"});

            }else{

                if(event==ActionHandler.FOCUS_EVENT){
                    processedVal=applyRegexp(currentVal,
                            new String[]{"[+-]?\\d+(\\.\\d+)?","[+-]?\\.\\d+","[+-]?\\d+\\."});
                }else if(event== ActionHandler.MOUSERELEASED)
                    processedVal=applyRegexp(currentVal,new String[]{"[+-]?\\d*\\.?\\d*"});
            }


            //if its changed its not valid
            if(event==ActionHandler.FOCUS_EVENT){

                if(!processedVal.equals(currentVal))
                    maskAlert(errCode,args);
                else
                    acro.getCompData().setValue(ref,currentVal,true,true,false); //write back

            }else //assume keystroke
                acro.getCompData().setValue(ref,currentVal,true,true,false); //write back

        }else
            isExecuted=false;




        return isExecuted;
    }

}
