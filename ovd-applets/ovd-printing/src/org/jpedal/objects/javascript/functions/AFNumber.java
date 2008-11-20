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
* AFNumber.java
* ---------------
*/
package org.jpedal.objects.javascript.functions;

import org.jpedal.objects.acroforms.actions.ActionHandler;
import org.jpedal.objects.acroforms.rendering.AcroRenderer;
import org.jpedal.constants.ErrorCodes;
import org.jpedal.sun.PrintfFormat;

import javax.swing.text.JTextComponent;
import java.awt.*;

public class AFNumber extends JSFunction{

	public AFNumber(AcroRenderer acro, String ref) {
		super(acro,ref);

		functionID=AFNumber;
	}


	public int  execute(String js, String[] args, int type, int event, char keyPressed) {

		int messageCode=ActionHandler.NOMESSAGE;
		
		if(args==null ){
			debug("Unknown implementation in "+js);

		}else if(args.length<1){
			debug("Values length is less than 1");
		}else{

			//settings
			int decCount=Integer.parseInt(args[1]);
			int gapFormat=Integer.parseInt(args[2]);
			int minusFormat=Integer.parseInt(args[3]);
			int currentStyle=Integer.parseInt(args[4]);
			String currencyMask=stripQuotes(args[5]);
			boolean hasCurrencySymbol = Boolean.valueOf(args[6]).booleanValue();

			if(gapFormat==2){
				JSFunction.DECIMAL_IS_COMMA = true;
			}

			if(event==ActionHandler.MOUSEPRESSED){ //flag if key ignored
				
				String actualValue=(String) acro.getCompData().getValue(ref);
				
				boolean isValidForNumber=((keyPressed>='0' && keyPressed<='9') || 
						(keyPressed=='-' && actualValue.indexOf(keyPressed)==-1) || 
						(keyPressed=='.' && actualValue.indexOf(keyPressed)==-1)  && gapFormat!=2 || 
						(keyPressed==',' && actualValue.indexOf(keyPressed)==-1) && gapFormat==2);
				if(!isValidForNumber)
					messageCode=ActionHandler.REJECTKEY;

			}else
				messageCode=validateNumber(args, type, event, ErrorCodes.JSInvalidNumberFormat, decCount, gapFormat,
						minusFormat, currentStyle, currencyMask,hasCurrencySymbol,keyPressed);
		}

		return messageCode;
	}

	protected int validateNumber(String[] args, int type, int event, int errCode, int decCount,
			int gapFormat, int minusFormat, int currentStyle, String currencyMask,
			boolean hasCurrencySymbolAtFront,char keyPressed) {

		int messageCode=ActionHandler.NOMESSAGE;

		//System.out.println(gapFormat+" "+minusFormat+" "+currentStyle+" "+currencyMask);

		//current form value
		String currentVal=(String)acro.getCompData().getValue(ref);
		String processedVal="";
		
		if(type==KEYSTROKE){

			//massage data with regexp
			if(gapFormat>1){

				if(event== ActionHandler.FOCUS_EVENT)
					processedVal=applyRegexp(currentVal,
							new String[]{"[+-]?\\d+([.,]\\d+)?","[+-]?[.,]\\d+","[+-]?\\d+[.,]"});
				else if(event== ActionHandler.MOUSERELEASED)
					processedVal=applyRegexp(currentVal,new String[]{"[+-]?\\d*,?\\d*"});

			}else{
				if(event==ActionHandler.FOCUS_EVENT)
					processedVal=applyRegexp(currentVal,
							new String[]{"[+-]?\\d+(\\.\\d+)?","[+-]?\\.\\d+","[+-]?\\d+\\."});
				else if(event== ActionHandler.MOUSERELEASED)
					processedVal=applyRegexp(currentVal,new String[]{"[+-]?\\d*\\.?\\d*"});
			}
			
			//if its changed its not valid
			if(!processedVal.equals(currentVal) && currentVal.indexOf('-')<=0){
				//write back
				acro.getCompData().setValue(ref,acro.getCompData().getLastValidValue(ref),false,true,false);
				
			}else if(event==ActionHandler.FOCUS_EVENT){
				
				//If '-' is not at start remove it and continue
				if(currentVal.indexOf('-')>0) 
					currentVal=currentVal.charAt(0)+currentVal.substring(1,currentVal.length()).replaceAll("-", "");
				
				/**
				 * strip out number value or 0 for no value
				 */
				double number;

				if(currentVal!=null && currentVal.length()>-1){
					StringBuffer numValu = convertStringToNumber(currentVal);

					//reset if no number or validate
					if(numValu.length()==0){
						currentVal="";
					}else{
						number=Double.parseDouble(numValu.toString());

						boolean isNegative=number <0;
						
						if(currentVal.charAt(0)=='-' && number == 0){
							currentVal = currentVal.substring(1,currentVal.length());
							number = 0;
						}

						//System.err.println("minusFormat="+minusFormat+" currentStyle="+currentStyle+" number="+number+" currentVal="+currentVal);

						//setup mask
						String mask = "%" + gapFormat + '.' + decCount + 'f';

//						if(minusFormat!=0 || hasCurrencySymbolAtFront){
						if(number<0)
							number=-number;

//						}

						//apply mask
						currentVal = new PrintfFormat(mask).sprintf(number);

						String sep=",",decimal=".",decimalPart="";

						//add , after each 000
						if( gapFormat ==2){
							sep=".";
							decimal=",";
							decimalPart="";                	
						}

						//strip off decimal
						int dec=currentVal.indexOf('.');
						if(dec!=-1){
							decimalPart=currentVal.substring(dec+1,currentVal.length());
							currentVal=currentVal.substring(0,dec);
						}

						int length=currentVal.length();

						StringBuffer finalVal=new StringBuffer();

						//work out unmatched part and add
						int unMatchedChars=length-(length/3*3);
						finalVal.append(currentVal.substring(0,unMatchedChars));

						//add chars in groups of 3
						for(int cCount=unMatchedChars;cCount<length;cCount=cCount+3){

							if(finalVal.length()>0)
								finalVal.append(sep);

							finalVal.append(currentVal.substring(cCount,cCount+3));
						}

						if(decimalPart.length()>0){
							finalVal.append(decimal);
							finalVal.append(decimalPart);
						}

						currentVal=finalVal.toString();

						StringBuffer rawValue=new StringBuffer(currentVal);

						if(hasCurrencySymbolAtFront)
							rawValue.insert(0,currencyMask);

						if(isNegative && (minusFormat==2 || minusFormat==3))
							rawValue.insert(0,'(');

							if(!hasCurrencySymbolAtFront)
								rawValue.append(currencyMask);

							if(isNegative && (minusFormat==2 || minusFormat==3))
								rawValue.append(')');

						if(isNegative && minusFormat!=1 && minusFormat!=3)
							rawValue.insert(0,'-');


						currentVal=rawValue.toString();

						//add back sign for minus numbers
						//if(isNegative)
						//    currentVal="-"+currentVal;

						//set colour

						if(minusFormat==1 || minusFormat==3){

							JTextComponent comp=(JTextComponent) acro.getCompData().getWidget(ref);
							if(comp==null){ //avoid
							}else if(isNegative)
								comp.setForeground(Color.RED);
							else
								comp.setForeground(Color.BLACK);
						}
					}
					
					//write back
					acro.getCompData().setValue(ref,currentVal,false,true,false);
				}
			}
		}
		return messageCode;
	}

	static StringBuffer convertStringToNumber(String currentVal) {

		int charCount=currentVal.length();
		StringBuffer numValu=new StringBuffer();

		boolean hasDecPoint=false;
		for(int i=0;i<charCount;i++){
			char c=currentVal.charAt(i);

			if((i==0 && c=='-')||(!hasDecPoint && c=='.')||(c>='0' && c<='9')){
				numValu.append(c);

				//track decimal point
				if(c=='.')
					hasDecPoint=true;

			}
		}
		return numValu;
	}


}
