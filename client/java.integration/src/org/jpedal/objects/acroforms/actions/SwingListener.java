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
* SwingListener.java
* ---------------
*/
package org.jpedal.objects.acroforms.actions;

import org.jpedal.objects.acroforms.formData.FormObject;
import org.jpedal.objects.acroforms.rendering.AcroRenderer;
import org.jpedal.objects.acroforms.utils.ConvertToString;

import javax.swing.*;
import javax.swing.text.JTextComponent;
import java.awt.event.*;
import java.awt.*;
import java.util.*;

public class SwingListener extends PDFListener implements MouseListener, KeyListener, FocusListener/*, ComponentListener, InputMethodListener, HierarchyListener*/ {
    /*
     * deciphering characteristics from formObject bit 1 is index 0 in []
     * 1 = invisible
     * 2 = hidden - dont display or print
     * 3 = print - print if set, dont if not
     * 4 = nozoom
     * 5= norotate
     * 6= noview
     * 7 = read only (ignored by wiget)
     * 8 = locked
     * 9 = togglenoview
     */

    public SwingListener(FormObject form, AcroRenderer acroRend, ActionHandler formsHandler) {

        super(form, acroRend, formsHandler);
    }

    public void mouseClicked(MouseEvent e) {
        super.mouseClicked(e);
    }

    public void mousePressed(MouseEvent e) {
        super.mousePressed(e);
    }

    public void mouseReleased(MouseEvent e) {
        super.mouseReleased(e);
    }

    public void mouseEntered(MouseEvent e) {

        handler.A(e, formObject, ActionHandler.MOUSEENTERED);
        handler.E(e, formObject);

        if (formObject.getCharacteristics()[8]) {//togglenoView
            ((JComponent) e.getSource()).setVisible(true);
            ((JComponent) e.getSource()).repaint();
        }/*else if(command.equals("comboEntry")){
			((JComboBox) e.getSource()).showPopup();
		}*/

    }

    public void mouseExited(MouseEvent e) {

        handler.A(e, formObject, ActionHandler.MOUSEEXITED);
        handler.X(e, formObject);

        if (formObject.getCharacteristics()[8]) {//togglenoView
            ((JComponent) e.getSource()).setVisible(false);
            ((JComponent) e.getSource()).repaint();
        }/*else if(command.equals("comboEntry")){
			((JComboBox) e.getSource()).hidePopup();
		}*/

    }

    public void keyTyped(KeyEvent e) { //before key added to data

        boolean keyIgnored=false;

        //set length
        int maxLength = formObject.getMaxTextLength();

        if(maxLength!=-1){

            char c=e.getKeyChar();
            
            if(c!=8 && c!=127){

                JTextComponent comp= ((JTextComponent) e.getSource());

                String text=comp.getText();
                
                int length=text.length();
                if(length>=maxLength){
                    e.consume();
                    keyIgnored=true;
                }

                if(length>maxLength)
                comp.setText(text.substring(0,maxLength));

            }
        }

        //if valid process further
        if(!keyIgnored){
        	
        	if(e.getKeyChar()=='\n')
        		acrorend.getCompData().loseFocus();

            int rejectKey=handler.K(e, formObject, ActionHandler.MOUSEPRESSED);

            if(rejectKey==ActionHandler.REJECTKEY)
            e.consume();

            handler.V(e,formObject, ActionHandler.MOUSEPRESSED);

        }
    }

    public void keyPressed(KeyEvent e) {
        //ignored at present
    }

    public void keyReleased(KeyEvent e) { //after key added to component value

        super.keyReleased(e);
    }

    public void focusGained(FocusEvent e) {

        super.focusGained(e);
    }

    public void focusLost(FocusEvent e) {

        super.focusLost(e);
    }
}
