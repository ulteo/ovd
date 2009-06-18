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
* PdfSwingPopup.java
* ---------------
*/
package org.jpedal.objects.acroforms.overridingImplementations;

import org.jpedal.objects.acroforms.formData.FormObject;
import org.jpedal.utils.Strip;

import javax.swing.*;
import java.util.StringTokenizer;
import java.awt.*;

/**
 * provide PDF poup for Annotations
 */
public class PdfSwingPopup extends JTextField {
    public PdfSwingPopup(FormObject formObject, int pageHeight, int insetH,String rectShape,boolean isVisible) {

        super();

        //System.out.println("height="+pageHeight);
        //Component popupcomp = null;

        String popupTitle;
        if(formObject.getPopupTitle()==null)
            popupTitle = "TEST popupTitle not set in formobject";
        else
            popupTitle = formObject.getPopupTitle();
        // popupcomp = new JTextField(popupTitle);

        this.setText(popupTitle);
        Rectangle rect = createBoundsRectangle(rectShape,null);
        rect.y = pageHeight-rect.y-rect.height+insetH;
        this.setBounds(rect);


        this.setVisible(isVisible);

    }

    private Rectangle createBoundsRectangle(String rect,FormObject formObject) {

        rect = Strip.removeArrayDeleminators(rect);
        StringTokenizer tok = new StringTokenizer(rect);

        double x1=Float.parseFloat(tok.nextToken()),y1=Float.parseFloat(tok.nextToken()),
                x2=Float.parseFloat(tok.nextToken()),y2=Float.parseFloat(tok.nextToken());

        if(x1>x2){
            double tmp = x1;
            x1 = x2;
            x2 = tmp;
        }
        if(y1>y2){
            double tmp = y1;
            y1 = y2;
            y2 = tmp;
        }

        Rectangle bBox = new Rectangle((int)x1,(int)y1,(int) (x2-x1),(int) (y2-y1));

        if(formObject!=null)
            formObject.setBoundingRectangle(bBox);
        else
            return bBox;

        return null;
    }

}
