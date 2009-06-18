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
* JPedalBorderFactory.java
* ---------------
*/
package org.jpedal.objects.acroforms.creation;

import java.awt.Color;
import java.util.HashMap;
import java.util.Map;

import javax.swing.BorderFactory;
import javax.swing.border.BevelBorder;
import javax.swing.border.Border;

import org.jpedal.objects.acroforms.utils.ConvertToString;
import org.jpedal.utils.LogWriter;
import org.jpedal.utils.Strip;

public class JPedalBorderFactory {
	
	private final static boolean printouts = false;
	private final static boolean debugUnimplemented = false;

	/**
     * setup the border style
     */
    public static Border createBorderStyle
            (Object
                    borderObj, Color
                    borderColor, Color
                    borderBackgroundColor) {
        /**Type must be Border
         * W width in points (if 0 no border, default =1)
         * S style - (default =S)
         * 	S=solid, D=dashed (pattern specified by D entry below), B=beveled(embossed appears to above page),
         * 	I=inset(engraved appeared to be below page), U=underline ( single line at bottom of boundingbox)
         * D array phase - e.g. [a b] c means:-  a=on blocks,b=off blocks(if not present default to a),
         * 		c=start of off block preseded index is on block.
         * 	i.e. [4] 6 :- 4blocks on 4blocks off, block[6] if off - 1=off 2=on 3=on 4=on 5=on 6=off 7=off 8=off 9=off etc...
         *
         */
        if (printouts) {
            System.out.println("createBorderStyle() color=" + borderColor + " background color=" + borderBackgroundColor + "\n\tfield=" + borderObj);
        }

        Map borderStream = new HashMap();
        if (borderObj == null) {
            borderStream.put("S", "/S");
            borderStream.put("W", "1");
        } else {
            if (borderObj instanceof Map) {
                borderStream = (Map) borderObj;
            } else {
                LogWriter.writeFormLog("{SwingFormFactory.createBorderStyle} border stream is String SwingFormFactory.createBorderStyle", debugUnimplemented);
            }
        }

        if (borderBackgroundColor == null) {
//		    borderBackgroundColor = new Color(0,0,0,0);
            if (printouts)
                System.out.println("background border color null");
        }
        if (borderColor == null) {
//		    borderColor = new Color(0,0,0,0);
            if (printouts)
                System.out.println("border color null");
            return null;
        }

        Border newBorder = null;

        //set border width or default of 1 if no value
        String width = ((String) borderStream.get("W"));
        int w=1;
        if(width!=null)
        w= Integer.parseInt(width);

        if (printouts)
            System.out.println("width=" + width);

        String style = ((String) borderStream.get("S"));

        if(style==null)
            style="S"; //set default if null
        else
            style = Strip.checkRemoveLeadingSlach(style);

        if (printouts)
            System.out.println("style=" + style);

        if (style.equals("U")) {
            if (printouts)
                System.out.println("FormStream.createBorderStyle() U CHECK=" + ConvertToString.convertMapToString(borderStream, null));
            newBorder = BorderFactory.createMatteBorder(0, 0, w, 0, borderColor);//underline field

        } else if (style.equals("I")) {
            if (printouts)
                System.out.println("FormStream.createBorderStyle() I CHECK=" + ConvertToString.convertMapToString(borderStream, null));
            newBorder = BorderFactory.createEtchedBorder(borderColor, borderBackgroundColor);//inset below page

        } else if (style.equals("B")) {
            if (printouts)
                System.out.println("FormStream.createBorderStyle() B CHECK=" + ConvertToString.convertMapToString(borderStream, null));
            newBorder = BorderFactory.createBevelBorder(BevelBorder.LOWERED, borderColor, borderBackgroundColor);//beveled above page

        } else if (style.equals("S")) {
            if (printouts)
                System.out.println("FormStream.createBorderStyle() S CHECK=" + ConvertToString.convertMapToString(borderStream, null));
            newBorder = BorderFactory.createLineBorder(borderColor, w);//solid

        } else if (style.equals("D")) {
            Object dashPattern = borderStream.get("D");

            if (dashPattern instanceof String) {
                if (debugUnimplemented)
                    System.out.println("createBorderStyle D pattern is String=" + (dashPattern));
            } else if (dashPattern instanceof Map) {
                if (debugUnimplemented)
                    System.out.println("createBorderStyle D pattern is Map=" + ConvertToString.convertMapToString(((Map) dashPattern), null));
            } else {
                if (debugUnimplemented)
                    System.out.println("createBorderStyle D pattern is UNKNOWN=" + dashPattern);
            }

            //setup dash pattern TODO MARK dashline
//	        new DashedBorder(Color.magenta,width,height);
//	    (new BasicStroke(3/scaling, BasicStroke.CAP_ROUND,
//	            BasicStroke.JOIN_ROUND, 0, new float[]{0,6/scaling,0,6/scaling}, 0));

        } else {
            if (debugUnimplemented)
                System.out.println("NOT IMPLEMENTED BS - border stream=" + ConvertToString.convertMapToString(borderStream, null));
        }

        return newBorder;
    }
	
}
