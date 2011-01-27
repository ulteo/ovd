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
* JSFunction.java
* ---------------
*/
package org.jpedal.objects.javascript.functions;

import org.jpedal.objects.acroforms.rendering.AcroRenderer;

import java.util.Calendar;
import java.util.GregorianCalendar;
import java.util.List;
import java.util.ArrayList;
import java.util.StringTokenizer;
import java.util.regex.Pattern;
import java.util.regex.Matcher;

/**
 * base class for functions with shared code
 */
public class JSFunction {

    private static boolean debugJavaScript=false;

    String ref;
    AcroRenderer acro;

    int functionID=0; //used in shared methods to distinguish which running

    public static final int AFDate = 1;
    public static final int AFNumber = 2;
    public static final int AFPercent = 3;
    public static final int AFRange = 4;
    public static final int AFSimple = 5;
    public static final int AFSpecial = 6;
    public static final int AFTime = 7;

    static final int AVG=1;
    static final int SUM=2;
    static final int PRD=3;
    static final int MIN=4;
    static final int MAX=5;

    public static final int UNKNOWN = -1;
    public static final int KEYSTROKE = 1;
    public static final int VALIDATE = 2;
    public static final int FORMAT = 3;
    public static final int CALCULATE = 4;
    
    public static boolean DECIMAL_IS_COMMA = false;


    public JSFunction(AcroRenderer acro, String ref) {
        
        this.acro=acro;
        this.ref=ref;

    }

    public static void debug(String str){

        if (debugJavaScript){
            System.out.println("[javascript] "+str);

        }
    }

    /**apply one of more matching patterns and return where a match*/
    protected static String applyRegexp(String text, String[] patterns ) {
        String matchedString="";

        int patternCount=patterns.length;

        for(int i = 0; i<patternCount; i++){

            Pattern pa=Pattern.compile(patterns[i]);
            Matcher m=pa.matcher(text);
            if(m.matches()){
                i=patternCount;
                int start=m.start();
                int end=m.end();
                matchedString=text.substring(start,end);

                //System.out.println(matchedString+" "+patterns[i]);
            }
        }

        return matchedString;
    }
    
    /**
     * general routine to handle array values
     */
    String processArray(String nextValue, int operation) {

        float result=0;
        boolean resultNotSet = true;

        boolean hasDec=false, hasData=false;

        String[] args2=convertToArray(nextValue);
        
        //add together except first item (which is new Array)
        float arrayCount=args2.length;
        
        for(int ii=1;ii<arrayCount;ii++){

            nextValue=stripQuotes(args2[ii]);

            String val=(String)acro.getCompData().getValue(nextValue);
            
            //if string is empty set value to 0 in calculations
            if(val.length()==0){
            	val="0";
            }
            	
                //flag if any values and if decimal
                if(val.indexOf('.')!=-1)
                	hasDec=true;

                hasData=true;

                boolean isNegative=val.startsWith("-");

                float nextVal=0;
                
                //replace a , with .
                int commaLoc=val.indexOf(',');
                if(commaLoc!=-1)
                	val=val.replace(',', '.');
                
                if(isNegative){
                    nextVal= -Float.parseFloat(val.substring(1));
                }else
                    nextVal= Float.parseFloat(val);
                
                switch(operation){
                    case AVG:
                        result=result+nextVal;
                        break;

                    case SUM:
                        result=result+nextVal;
                        break;

                    case PRD:
                    	if(resultNotSet){
                    		result = 1;
                    		resultNotSet = false;
                    	}
                        result=result*nextVal;
                        break;

                    case MIN:
                        if(ii==1)
                            result=nextVal;
                        else if(nextVal<result)
                            result=nextVal;
                        break;

                    case MAX:
                        if(ii==1)
                            result=nextVal;
                        else if(nextVal>result)
                            result=nextVal;
                        break;
                    
                    default:
                        debug("Unsupported op "+operation+" in processArray");
                         break;
                }
        }
        
        //post process
        if(operation==AVG)
            result=result/(arrayCount-1);

        if(hasDec)
            return String.valueOf(result);
        else if(!hasData)
            return "";
        else
            return String.valueOf((int) result);
    }

    /**
     * turn javascript string into values in an array
     */
    public static String[] convertToArray(String js) {

        String rawCommand=js;

        int ptr=js.indexOf('(');
        int items=0,count=0;
        String[] values=null;
        String finalValue="";

        List rawValues=new ArrayList();

        /**
         * first value is command
         */
        if(ptr!=-1){

            String com=js.substring(0,ptr);

            rawValues.add(com);

            items++;

            //remove
            js=js.substring(ptr,js.length()).trim();

            int charsAtEnd=1;

            //lose ; as well
            if(js.endsWith(";"))
                charsAtEnd++;

            //remove main brackets and possibly ;
            if(js.startsWith("(")) //strip brackets
                js=js.substring(1,js.length()-charsAtEnd);
            else
                debug("Unknown args in "+rawCommand);
        }

        /**
         * break into values allowing for nested values
         */
        StringTokenizer tokens=new StringTokenizer(js,"(,);",true);
        while(tokens.hasMoreTokens()){

            //get value
            String nextValue=tokens.nextToken();

            //allow for comma in brackets
            while(tokens.hasMoreTokens() && nextValue.startsWith("\"") && !nextValue.endsWith("\""))
                    nextValue=nextValue+tokens.nextToken();

            if(count==0 && nextValue.equals(",")){
                rawValues.add(finalValue);

                finalValue="";
                items++;
            }else{
                if(nextValue.equals("("))
                    count++;
                else if(nextValue.equals(")"))
                    count--;

                finalValue=finalValue+nextValue;
            }
        }

        //last value
        items++;
        rawValues.add(finalValue);


        //turn into String array to avoid casting later
        //(could be rewritten later to be cleaner if time/performance issue)
        values=new String[items];
        for(int ii=0;ii<items;ii++){
            values[ii]=((String)rawValues.get(ii)).trim();
            //System.out.println(ii+" >"+values[ii]+"<");
            }

        return values;
    }

    /**
     * ensure any empty slots at start filled
     */
    private static String padString(String rawVal, int maxLen) {

        int length= rawVal.length();
        
        if(maxLen ==length)
            return rawVal;
        else if(maxLen <length)
            return null;
        else{
            StringBuffer paddedString=new StringBuffer();

            int extraChars=maxLen-length;

            for(int jj=0;jj<extraChars;jj++)
                paddedString.append('0');

            paddedString.append(rawVal);

            return paddedString.toString();
        }
    }

    //alert user and reset to old value
    void maskAlert(int code,Object[] args) {

        //restore old value
        String validValue= (String) acro.getCompData().getLastValidValue(ref);
        if(validValue==null)
        validValue="";

        acro.getCompData().setValue(ref,validValue,true, true,false);
        
        //callback
        acro.reportError(code, args);

    }

    //apply formatting in mask to data
    //returns null if invalid
    String validateMask(String[] args,String separator,boolean useDefaultValues) {

    	final String[] months={"January","February","March","April","May","June","July","August","September","October","November","December"};
    	final int[] monthsCount={31,28,31,30,31,30,31,31,30,31,30,31};

    	int monthMod = 1;
    	int monthValue = 0;
    	int dayValue = 0;
    	
    	String validValue=null;

    	int count=args.length;
    	if(count!=2){
    		String list="";
    		for(int i=0;i<count;i++){

    			if(i==0)
    				list=args[i];
    			else
    				list=list+ ',' +args[i];

    		}

    		JSFunction.debug("Unexpected values items="+count+ '{' +list+ '}');

    	}else{

    		boolean isValid=true; //assume okay and disprove

    		String formData= (String) acro.getCompData().getValue(ref);

    		if(formData==null || formData.length()==0)
    			return "";

    		String endText=null;

    		//some values have additions such as PM/AM
    		int space=formData.lastIndexOf(' ');
    		if(space!=-1){
    			endText=formData.substring(space+1).toLowerCase().trim();

    			//must end am or pm  is does, strip it off
    			if(endText!=null){
    				if(endText.equals("am") || endText.equals("pm"))
    					formData=formData.substring(0,space);
//  				else
//  				return null;
    			}
    		}

    		String mask=stripQuotes(args[1]);

    		//Day must be "XX" not "X"
    		int d = mask.indexOf('d');
    		if(mask.charAt(d+1)!='d'){
    			mask = mask.replaceFirst("d", "dd");
    		}
    		
    		StringTokenizer maskValues=new StringTokenizer(mask,separator,true); //ie mm:dd:yyyy
    		StringTokenizer formValues=new StringTokenizer(formData,separator,true); //ie 01:01:2007
    		//match each part

    		StringBuffer finalValue=new StringBuffer();

    		String nextMask,nextVal,nextSep="", paddedValue="";

    		//get a time instance and defaults for all Date values here
    		GregorianCalendar gc = new GregorianCalendar();

    		//loop through and test each value
    		while(maskValues.hasMoreTokens()){

    			paddedValue="";

    			//get next mask and any next value (allowing for multiple separators)
    			while(true){
    				nextMask=maskValues.nextToken();
    				
    				if(separator.indexOf(nextMask)==-1 || !maskValues.hasMoreTokens())
    					break;

    				//its muliple separators so append and retry
    				finalValue.append(nextMask);

    			}
    			
    			while(true){
    				//get form if there is one
        			if(!formValues.hasMoreTokens())
        				nextVal=null; //run out of values
        			else
        				nextVal=formValues.nextToken();
        			
    				if(nextVal==null || separator.indexOf(nextVal)==-1 || !formValues.hasMoreTokens())
    					break;
    			}

    			if(maskValues.hasMoreTokens())
    				nextSep=maskValues.nextToken();
    			else
    				nextSep=null;
    			
    			if(nextVal!=null)
    				paddedValue=padString(nextVal,nextMask.length());
    			
    			if(nextMask.equals("h")){ //12 hour clock

    				//allow for null value in Date
    				if(useDefaultValues && nextVal==null)
    					paddedValue= String.valueOf(gc.get(Calendar.HOUR));
    				else 
    					paddedValue=padString(nextVal,2);
    				
					isValid = verifyNumberInRange(paddedValue,0,11);
    				

    			}else if(nextMask.equals("HH")){ //24 hours clock

    				//allow for null value in Date
    				if(useDefaultValues && nextVal==null){
    					paddedValue= String.valueOf(gc.get(Calendar.HOUR_OF_DAY));
    					paddedValue=padString(paddedValue,2);
    					isValid = verifyNumberInRange(paddedValue,0,23);
    				}else{
    					isValid = verifyNumberInRange(paddedValue,0,23);
    				}

    			}else if(nextMask.equals("MM")){

    				//allow for null value in Date
    				if(useDefaultValues && nextVal==null){
    					paddedValue= String.valueOf(gc.get(Calendar.MINUTE));
    					paddedValue=padString(paddedValue,2);
    					isValid = verifyNumberInRange(paddedValue,0,59);
    				}else{
    					isValid = verifyNumberInRange(paddedValue,0,59);
    				}

    			}else if(nextMask.equals("mm") || nextMask.equals("m")){

    				isValid = verifyNumberInRange(paddedValue,0,12);
    				if(isValid){
    					int idx = Integer.parseInt(paddedValue)-1;
    					if(idx==1 && monthMod>0)
    						monthMod=monthMod-1;
    				}
    			}else if(nextMask.equals("tt")){
    				if(useDefaultValues && nextVal==null)
    						paddedValue = "am";

   					isValid = (paddedValue.toLowerCase().equals("am") || paddedValue.toLowerCase().equals("pm"));
    			
    			}else if(nextMask.equals("ss")){

    				//allow for null value in Date
    				if(useDefaultValues && nextVal==null){
    					paddedValue= String.valueOf(gc.get(Calendar.SECOND));
    					paddedValue=padString(paddedValue,2);
    					isValid = verifyNumberInRange(paddedValue,0,59);
    				}else{
    					isValid = verifyNumberInRange(paddedValue,0,59);
    				}

    			}else if(nextMask.equals("dd") || nextMask.equals("d")){
    				isValid = verifyNumberInRange(paddedValue,0,31);
    				if(isValid)
    					dayValue = Integer.parseInt(paddedValue);
    				
    				
    			}else if(nextMask.equals("yyyy") || nextMask.equals("yy")){

    				//get a time instance and defaults for all Date values here
    				//add this check to all values except day and month

    				//allow for null value in Date
    				if(useDefaultValues && nextVal==null){
    					nextVal= String.valueOf(gc.get(Calendar.YEAR));
    					isValid = verifyNumberInRange(nextVal,0,9999);

    				}else{
    					//cannot pad year
    					if(nextMask.length()!=nextVal.length())
    						isValid=false;
    					else{
    						//07  becomes 2007
    						if(nextVal.length()==2){

    							int year=Integer.parseInt(nextVal);
    							if(year<50)
    								nextVal="20"+nextVal;
    							else
    								nextVal="19"+nextVal;
    						}
    						//note year is not padded out
    						isValid = verifyNumberInRange(nextVal,0,9999);

    					}
    				}
    				
    				if(isValid && Integer.parseInt(nextVal)%4!=0 && monthMod>0)
    					monthMod=monthMod-1;
    				//stop padded value over-writing underneath
    				paddedValue=nextVal;

    			}else if(nextMask.equals("mmm") || nextMask.equals("mmmm")){

    				//this needs to handle april apr 4 and 04 -if invalid it uses default (ie May)
    				int idx = -1;
    				//if chars used instead of month number only check first 3 chars
    				if(nextVal.length()>=3)
    					for(int i=0;i!=months.length;i++){
    						nextVal = nextVal.toLowerCase();
    						int length = 3;

    						nextVal = nextVal.substring(0, length).toLowerCase();
    						String month = months[i].substring(0, length).toLowerCase();

    						if(nextVal.equals(month))
    							idx = i;
    					}
    				if(idx==-1){
    					try{
    						idx = Integer.parseInt(nextVal)-1;
    						if(idx<12)
    							paddedValue = months[idx];
    					}catch(Exception e){
    						paddedValue = null;
    						isValid=false;
    					}
    				}else{
    					paddedValue = months[idx];
    				}
    				if(idx!=1 && monthMod>0)
    					monthMod=monthMod-1;

    				//Check valid month index
    				if(idx>11)
    					isValid=false;
    				else
    					monthValue = idx;
    			}else{

    				JSFunction.debug("Mask value >"+nextMask+"< not implemented");
    				isValid=false;
    			}

    			if(!isValid)
    				break;

    			//if passed, add on to result
    			finalValue.append(paddedValue);
    			if(nextSep!=null) //not on last one
    				finalValue.append(nextSep);


    		}
    		
    		if(monthValue<0 || monthValue>monthsCount.length || dayValue>monthsCount[monthValue]+monthMod)
    			isValid=false;
    		
    		if(isValid)
    			validValue=finalValue.toString();

    	}

    	return validValue;
    }

    //must line in range min-max (inclusive so range 0-24 will pass values 0 and 24 and 1 and 23)
    private static boolean verifyNumberInRange(String nextVal, int min,int max) {
    	
        boolean valid=true;
        
        if(nextVal==null || isNotNumber(nextVal)){ //too long or invalid
            valid =false;
        }else{
            int number =Integer.parseInt(nextVal);

            if(number<min || number>max)
            valid =false;
        }
        return valid;
    }

    //remove double quotes
    protected static String stripQuotes(String arg) {

        //lose quotes
        if(arg.startsWith("\""))
            arg=arg.substring(1,arg.length()-1);

        //allow for \\u00xx
        if(arg.startsWith("\\u")){
            String unicodeVal=arg.substring(2);
            arg= String.valueOf((char) Integer.parseInt(unicodeVal, 16));
        }else if(arg.startsWith("\\")){ //and octal
            String unicodeVal=arg.substring(1);
            arg= String.valueOf((char) Integer.parseInt(unicodeVal, 8));
        }

        return arg;
    }

    //check it is a number
    protected static boolean isNotNumber(String nextVal) {
    	
        //allow for empty string
        if(nextVal.length()==0)
                return true;

        //assume false and disprove
        boolean notNumber =false;

        char[] chars=nextVal.toCharArray();
        int count=chars.length;

        //exit on first char not 0-9
        for(int ii=0;ii<count;ii++){
            if(chars[ii]=='.' || chars[ii]=='-' || chars[ii]==','){

            }else if(chars[ii]<48 || chars[ii]>57){
                ii=count;
                notNumber =true;
            }
        }
        
        return notNumber;

    }
}
