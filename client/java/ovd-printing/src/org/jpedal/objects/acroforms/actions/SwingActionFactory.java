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
* SwingActionFactory.java
* ---------------
*/
package org.jpedal.objects.acroforms.actions;

import org.jpedal.PdfDecoder;
import org.jpedal.io.PdfObjectReader;
import org.jpedal.exception.PdfException;
import org.jpedal.objects.acroforms.decoding.FormStream;
import org.jpedal.objects.acroforms.gui.Summary;
import org.jpedal.objects.acroforms.overridingImplementations.PdfSwingPopup;
import org.jpedal.objects.acroforms.rendering.AcroRenderer;
import org.jpedal.objects.acroforms.utils.ConvertToString;
import org.jpedal.objects.acroforms.formData.FormObject;
import org.jpedal.utils.BrowserLauncher;
import org.jpedal.utils.LogWriter;
import org.jpedal.utils.Messages;

import javax.swing.*;
import javax.swing.text.JTextComponent;
import java.awt.*;
import java.awt.event.MouseEvent;
import java.awt.event.MouseListener;
import java.awt.event.ComponentEvent;
import java.awt.event.KeyEvent;
import java.awt.print.PageFormat;
import java.awt.print.Paper;
import java.awt.print.PrinterException;
import java.awt.print.PrinterJob;
import java.io.IOException;
import java.util.Map;
import java.util.StringTokenizer;

public class SwingActionFactory implements ActionFactory {

    AcroRenderer acrorend;

    PdfDecoder decode_pdf=null;


    public void showMessageDialog(String s) {
        JOptionPane.showMessageDialog(decode_pdf, s);

    }

    /**
     * pick up key press or return ' '
     */
    public char getKeyPressed(Object raw) {

        ComponentEvent ex=(ComponentEvent)raw;

        if (ex instanceof KeyEvent)
            return ((KeyEvent) ex).getKeyChar();
        else
            return ' ';

    }

    public void setFieldVisibility(Map hideMap) {

        String[] fieldsToHide = (String[]) hideMap.get("fields");
        Boolean[] whetherToHide = (Boolean[]) hideMap.get("hide");

        if (fieldsToHide.length != whetherToHide.length) {
            LogWriter.writeFormLog("{custommouselistener} number of fields and nuber of hides or not the same", FormStream.debugUnimplemented);
            return;
        }

        for (int i = 0; i < fieldsToHide.length; i++) {

            Component[] checkObj = (Component[]) acrorend.getComponentsByName(fieldsToHide[i]);
            if (checkObj != null) {
                for (int j = 0; j < checkObj.length; j++) {
                    checkObj[j].setVisible(!whetherToHide[i].booleanValue());
                }
            }
        }
    }

    public void setPageandPossition(Object location) {


        //scroll to 'location'
		if (location != null)
			decode_pdf.scrollRectToVisible((Rectangle)location);
       
        decode_pdf.invalidate();
		decode_pdf.updateUI();
    }

    public void print() {

        //ask if user ok with printing and print if yes
        if (JOptionPane.showConfirmDialog(decode_pdf, Messages.getMessage("PdfViewerPrinting.ConfirmPrint"),
                Messages.getMessage("PdfViewerPrint.Printing"), JOptionPane.YES_NO_OPTION) == 0) {

            //setup print job and objects
            PrinterJob printJob = PrinterJob.getPrinterJob();
            PageFormat pf = printJob.defaultPage();

            // Set PageOrientation to best use page layout
            int orientation = decode_pdf.getPDFWidth() < decode_pdf
                    .getPDFHeight() ? PageFormat.PORTRAIT
                    : PageFormat.LANDSCAPE;

            pf.setOrientation(orientation);

            Paper paper = new Paper();
            paper.setSize(595, 842);
            paper.setImageableArea(43, 43, 509, 756);

            pf.setPaper(paper);
            //          allow user to edit settings and select printing
            printJob.setPrintable(decode_pdf, pf);

            try {
                printJob.print();
            } catch (PrinterException e1) {
            }
        }
    }

    public void reset() {
    	acrorend.getCompData().reset();

        String[] defaultValues = acrorend.getCompData().getDefaultValues();
        Component[] allFields = (Component[]) acrorend.getComponentsByName(null);

        for (int i = 0; i < allFields.length; i++) {
            if (allFields[i] != null) {// && defaultValues[i]!=null){

                if (allFields[i] instanceof AbstractButton) {
                    if (allFields[i] instanceof JCheckBox) {
                        //setSelectedItem(item)
                        if (defaultValues[i] == null) {
                            ((JCheckBox) allFields[i]).setSelected(false);
                        } else {
                            String fieldState = allFields[i].getName();
                            int ptr = fieldState.indexOf("-(");
                            /** NOTE if indexOf string changes change ptr+# to same length */
                            if (ptr != -1) {
                                fieldState = fieldState.substring(ptr + 2, fieldState.length() - 1);
                            }

                            if (fieldState.equals(defaultValues[i]))
                                ((JCheckBox) allFields[i]).setSelected(true);
                            else
                                ((JCheckBox) allFields[i]).setSelected(false);

                            LogWriter.writeFormLog("{renderer} resetform on mouse press " + allFields[i].getClass() + " - " + defaultValues[i] + " current=" + ((JCheckBox) allFields[i]).isSelected() + ' ' + ((JCheckBox) allFields[i]).getText(), FormStream.debugUnimplemented);
                        }

                    } else if (allFields[i] instanceof JButton) {
                        // ?
                        LogWriter.writeFormLog("{renderer{ resetform on mouse press " + allFields[i].getClass() + " - " + defaultValues[i] + " current=" + ((JButton) allFields[i]).isSelected() + ' ' + ((JButton) allFields[i]).getText(), FormStream.debugUnimplemented);

                    } else if (allFields[i] instanceof JRadioButton) {
                        //on/off
                        if (defaultValues[i] == null) {
                            ((JRadioButton) allFields[i]).setSelected(false);
                        } else {
                            String fieldState = allFields[i].getName();

                            int ptr = fieldState.indexOf("-(");
                            /** NOTE if indexOf string changes change ptr+# to same length */
                            if (ptr != -1) {
                                fieldState = fieldState.substring(ptr + 2, fieldState.length() - 1);
                            }

                            if (fieldState.equals(defaultValues[i]))
                                ((JRadioButton) allFields[i]).setSelected(true);
                            else
                                ((JRadioButton) allFields[i]).setSelected(false);

                        }
                    }
                } else if (allFields[i] instanceof JTextComponent) {

                    //changed 20070420 so resets saved last values as well
                    //text
                    //((JTextComponent) allFields[i]).setText(defaultValues[i]);

                    String fieldName = allFields[i].getName();

                    acrorend.getCompData().setValue(fieldName, defaultValues[i], true, true,true);

                } else if (allFields[i] instanceof JComboBox) {
                    // on/off
                    ((JComboBox) allFields[i]).setSelectedItem(defaultValues[i]);

                } else if (allFields[i] instanceof JList) {
                    ((JList) allFields[i]).setSelectedValue(defaultValues[i], true);
                }
                allFields[i].repaint();

            }
        }
        acrorend.getCompData().reset();
    }

    public void setPDF(PdfDecoder decode_pdf,AcroRenderer acrorend) {
        this.decode_pdf=decode_pdf;
        this.acrorend=acrorend;
    }

    public void setCursor(int eventType) {
    	
    	if(decode_pdf==null){
    		//do nothing
    	}else if (eventType == ActionHandler.MOUSEENTERED)
            decode_pdf.setCursor(new Cursor(Cursor.HAND_CURSOR));
        else if (eventType == ActionHandler.MOUSEEXITED)
            decode_pdf.setCursor(new Cursor(Cursor.DEFAULT_CURSOR));
    }

    public void showSig(Map sigObject) {

        JDialog frame = new JDialog((JFrame) null, "Signature Properties", true);

        Summary summary = new Summary(frame, sigObject);
        summary.setValues((String) sigObject.get("Name"), (String) sigObject.get("Reason"),
                (String) sigObject.get("M"), (String) sigObject.get("Location"));

        frame.getContentPane().add(summary);
        frame.setSize(550, 220);
        frame.setLocationRelativeTo(null);
        frame.setVisible(true);
    }


    public void submitURL(Map aDataMap, String submitURL) {

        if (submitURL != null) {
            Component[] compsToSubmit = new Component[0];
            if (aDataMap.containsKey("Fields")) {
                StringTokenizer fieldsTok = new StringTokenizer((String) aDataMap.get("Fields"), ",");
                String[] includeNameList = new String[0];
                String[] listOfNames = new String[fieldsTok.countTokens()];
                int i = 0;
                while (fieldsTok.hasMoreTokens()) {
                    listOfNames[i++] = fieldsTok.nextToken();
                }

                //	    	Flags see pdf spec v1.6 p662
                Object obj = aDataMap.get("Flags");
                int value = 0;
                if (obj instanceof String) {
                    value = Integer.parseInt((String) obj);
                } else if (obj instanceof Integer) {
                    value = ((Integer) obj).intValue();
                } else {
                    LogWriter.writeFormLog("(internal only) flags NON Sting = " + obj.getClass() + ' ' + obj, FormStream.debugUnimplemented);
                }

                if ((value & 1) == 1) {
                    //fields is an exclude list
                    try {
                        java.util.List tmplist = acrorend.getComponentNameList();
                        //					System.out.println("before="+ConvertToString.convertArrayToString(tmplist.toArray()));
                        if (tmplist != null) {
                            for (i = 0; i < listOfNames.length; i++) {
                                tmplist.remove(listOfNames[i]);
                            }
                            //						System.out.println("after="+ConvertToString.convertArrayToString(tmplist.toArray()));
                        }
                    } catch (PdfException e1) {
                        LogWriter.writeFormLog("SwingFormFactory.setupMouseListener() get component name list exception", FormStream.debugUnimplemented);
                    }
                } else {
                    //fields is an include list
                    includeNameList = listOfNames;
                }

                Component[] compsToAdd, tmp;
                for (i = 0; i < includeNameList.length; i++) {
                    compsToAdd = (Component[]) acrorend.getComponentsByName(includeNameList[i]);
                    
                    if(compsToAdd!=null){
	                    tmp = new Component[compsToSubmit.length + compsToAdd.length];
	                    if (compsToAdd.length > 1) {
	                        LogWriter.writeFormLog("(internal only) SubmitForm multipul components with same name", FormStream.debugUnimplemented);
	                    }
	                    for (i = 0; i < tmp.length; i++) {
	                        if (i < compsToSubmit.length) {
	                            tmp[i] = compsToSubmit[i];
	                        } else if (i - compsToSubmit.length < compsToAdd.length) {
	                            tmp[i] = compsToAdd[i - compsToSubmit.length];
	                        }
	                    }
	                    compsToSubmit = tmp;
                    }
                }
            } else {
                compsToSubmit = (Component[]) acrorend.getComponentsByName(null);
            }


            String text = "";
            for (int i = 0; i < compsToSubmit.length; i++) {
                if (compsToSubmit[i] instanceof JTextComponent) {
                    text += ((JTextComponent) compsToSubmit[i]).getText();
                } else if (compsToSubmit[i] instanceof AbstractButton) {
                    text += ((AbstractButton) compsToSubmit[i]).getText();
                } else {
                    LogWriter.writeFormLog("(internal only) SubmitForm field form type not accounted for", FormStream.debugUnimplemented);
                }
            }

            try {
                BrowserLauncher.openURL(submitURL + "?en&q=" + text);
            } catch (IOException e1) {
                showMessageDialog(Messages.getMessage("PdfViewer.ErrorWebsite"));
                e1.printStackTrace();
            }
        }
    }

    public Object getHoverCursor() {
        return new MouseListener(){
            public void mouseEntered(MouseEvent e) {
                setCursor(ActionHandler.MOUSEENTERED);
            }

            public void mouseExited(MouseEvent e) {
                setCursor(ActionHandler.MOUSEEXITED);
            }

            public void mouseClicked(MouseEvent e) {
            }

            public void mousePressed(MouseEvent e) {
            }

            public void mouseReleased(MouseEvent e) {
            }
        };
    }

    public void popup(Object raw, FormObject formObj, PdfObjectReader currentPdfFile, int pageHeight, int insetH) {

        if (((MouseEvent)raw).getClickCount() == 2) {

            //@chris - looks good
            // I have removed unused parameter passed in
            // Can we lose start-demo,etc to add it into main code?
            //I question below
        }
    }
}
