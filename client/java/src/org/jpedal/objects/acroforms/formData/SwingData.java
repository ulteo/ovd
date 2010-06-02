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
* SwingData.java
* ---------------
*/
package org.jpedal.objects.acroforms.formData;

import org.jpedal.PdfDecoder;
import org.jpedal.constants.ErrorCodes;
import org.jpedal.objects.acroforms.overridingImplementations.FixImageIcon;
import org.jpedal.objects.acroforms.utils.ConvertToString;
import org.jpedal.objects.acroforms.utils.FormUtils;
import org.jpedal.utils.LogWriter;
import org.jpedal.utils.Strip;

import javax.swing.*;
import javax.swing.text.JTextComponent;
import java.awt.*;
import java.awt.geom.AffineTransform;
import java.util.*;
import java.util.List;

/**
 * Swing specific implementation of Widget data
 * (all non-Swing variables defined in ComponentData)
 *
 */
public class SwingData extends ComponentData implements GUIData{
     
	/**
     * array to hold components
     */
    private Component[] allFields;
    
    /**
     * flag to show if checked
     */
    private boolean[] testedForDuplicates;
    
    /**
     * possible wrapper for some components such as JList which do not have proper scrolling
     */
    private JScrollPane[] scroll;
    
    /**
     * used for radio and checkboxes where multiple connected values
     */
    private ButtonGroup bg = new ButtonGroup();
	
    /**
     * panel components attached to
     */
    private JPanel panel;

    /** we store Buttongroups in this */
    private Map annotBgs=new HashMap();


    /**
     * generic call used for triggering repaint and actions
     */
    public void loseFocus(){
    	
    	if(panel!=null){
	    	if (SwingUtilities.isEventDispatchThread())
	    		panel.grabFocus();
	    	else {
				final Runnable doPaintComponent = new Runnable() {
					public void run() {
						panel.grabFocus();
					}
				};
				SwingUtilities.invokeLater(doPaintComponent);
			}
    	}
    }
    
    /**
     * set the type of object for each Form created
     * @param fieldName
     * @param type
     */
	public void setFormType(String fieldName, Integer type) {
		
		typeValues.put(fieldName, type);
		
	}
	
	/**
     * return components which match object name
     * @return
     */
    private Object[] getComponentsByName(String objectName,Object checkObj) {

    	//allow for duplicates
        String duplicateComponents = (String) duplicates.get(objectName);

        int index = ((Integer) checkObj).intValue();

        boolean moreToProcess = true;
        int firstIndex = index;
        while (moreToProcess) {
            if (index + 1 < allFields.length && allFields[index + 1] != null){ 
            	
            	String name=allFields[index + 1].getName();
            	if(name==null){	//we now pass Annots through so need to allow for no name
	            	moreToProcess = false;
	            }else if(FormUtils.removeStateToCheck(name, false).equals(objectName)) {
	               index += 1;
	            } else {
	                moreToProcess = false;
	            }
            }else
                moreToProcess = false;
        }

        int size = (index + 1) - firstIndex;
        Component[] compsToRet = new Component[size];

        for (int i = 0; i < size; i++, firstIndex++) {
            compsToRet[i] = allFields[firstIndex];
            if (firstIndex == index)
                break;
        }

        //recreate list and add in any duplicates
        if (duplicateComponents != null && duplicateComponents.indexOf(',') != -1) {

            StringTokenizer additionalComponents = new StringTokenizer(duplicateComponents, ",");

            int count = additionalComponents.countTokens();

            Component[] origComponentList = compsToRet;
            compsToRet = new Component[size + count];

            //add in original components
            System.arraycopy(origComponentList, 0, compsToRet, 0, size);

            //and duplicates
            for (int i = 0; i < count; i++){
                int ii=Integer.parseInt(additionalComponents.nextToken());

                //System.out.println(ii+" "+count);
                compsToRet[i + size] = allFields[ii];
            }
        }

        return compsToRet;
    }
    
    /**
     * get vale using objectName as ref
     * @param objectName
     * @return
     */
    public Object getValue(Object objectName) {

        if (objectName == null)
            return "";

        Object retValue="";
        Object checkObj = nameToCompIndex.get(objectName);

        retValue = getFormValue(checkObj);

        return retValue;

    }
    
    /**valid flag used by Javascript to allow rollback*/
    public void setValue(String name,Object value, boolean isValid,boolean isFormatted,boolean reset) {

        //track so we can reset if needed
        if(!reset && isValid){
            //System.out.println("valid="+value);
            lastValidValue.put(name,value);
        }
        //save raw version before we overwrite
        if(!reset && isFormatted){

            //System.out.println("1formatted="+getValue(name));

            lastUnformattedValue.put(name,getValue(name));
        }

        Object checkObj = nameToCompIndex.get(name);

        setFormValue(value, checkObj);
    }
    
    public String getComponentName(int currentComp, ArrayList nameList, String lastName) {

        String currentName;

        Component currentField=allFields[currentComp];

        if (currentField != null) {
            //ensure following fields don't get added if (e.g they are a group)

            currentName = FormUtils.removeStateToCheck(currentField.getName(), false);
            if (currentName != null && !lastName.equals(currentName)) {
            	
            	if(!testedForDuplicates[currentComp]){
            		//stop multiple matches
            		testedForDuplicates[currentComp]=true;

            		//track duplicates
            		String previous = (String) duplicates.get(currentName);
            		if (previous != null)
            			duplicates.put(currentName, previous + ',' + currentComp);
            		else
            			duplicates.put(currentName, String.valueOf(currentComp));
            	}
            	
                //add to list
                nameList.add(currentName);
                lastName = currentName;
            }
        }
        return lastName;
    }
    
    public Object[] getComponentsByName(String objectName) {
		
    	if (objectName == null)
            return allFields;

        Object checkObj = nameToCompIndex.get(objectName);
        if (checkObj == null)
            return null;

        if (checkObj instanceof Integer) {

            //return allFields[index];
            return getComponentsByName(objectName, checkObj);
        } else {
            LogWriter.writeLog("{stream} ERROR DefaultAcroRenderer.getComponentByName() Object NOT Integer and NOT null");

            return null;
        }
	}
	
	public Object getFormValue(Object checkObj) {

        Object retValue="";

        if(checkObj!=null){
            int index = ((Integer) checkObj).intValue();

            if(allFields[index] instanceof JCheckBox)
                retValue=Boolean.valueOf(((JCheckBox) allFields[index]).isSelected());
            else if(allFields[index] instanceof JComboBox)
                retValue=((JComboBox) allFields[index]).getSelectedItem();
            else if(allFields[index] instanceof JList)
                retValue=((JList) allFields[index]).getSelectedValues();
            else if(allFields[index] instanceof JRadioButton)
                retValue=Boolean.valueOf(((JRadioButton) allFields[index]).isSelected());
            else if(allFields[index] instanceof JTextComponent)
                retValue=((JTextComponent) allFields[index]).getText();
            else{

                retValue="";
            }
        }
        return retValue;
    }
	
	public void setFormValue(Object value, Object checkObj) {

        if(checkObj!=null){
            int index = ((Integer) checkObj).intValue();

            if(allFields[index] instanceof JCheckBox)
                ((JCheckBox) allFields[index]).setSelected(Boolean.valueOf((String) value).booleanValue());
            else if(allFields[index] instanceof JComboBox)
                ((JComboBox) allFields[index]).setSelectedItem(value);
            else if(allFields[index] instanceof JList)
                ((JList) allFields[index]).setSelectedValue(value,false);
            else if(allFields[index] instanceof JRadioButton)
                ((JRadioButton) allFields[index]).setText((String)value);
            else if(allFields[index] instanceof JTextComponent)
                ((JTextComponent) allFields[index]).setText((String)value);
            else{
            }
        }
    }
	
	public void showForms() {
        
        if (allFields != null) {
            for (int i = 0; i < allFields.length; i++) {
                if (allFields[i] != null) {
                    if(allFields[i] instanceof JButton){
                    	allFields[i].setBackground(Color.blue);
                    }else if(allFields[i] instanceof JTextComponent){
                    	allFields[i].setBackground(Color.red);
                    }else {
                    	allFields[i].setBackground(Color.green);
                    }
                    
                	allFields[i].setForeground(Color.lightGray);
                    allFields[i].setVisible(true);
                    allFields[i].setEnabled(true);
                    ((JComponent) allFields[i]).setOpaque(true);
                    if (allFields[i] instanceof AbstractButton) {
                        if (!(allFields[i] instanceof JRadioButton)) {
                            ((AbstractButton) allFields[i]).setIcon(null);
                        }
                    } else if (allFields[i] instanceof JComboBox) {
                        ((JComboBox) allFields[i]).setEditable(false);
                    }
                }
            }
        }
    }
	
	/**
     * get actual widget using objectName as ref or null if none
     * @param objectName
     * @return
     */
    public Object getWidget(Object objectName) {

        if (objectName == null)
            return null;
        else{

            Object checkObj = nameToCompIndex.get(objectName);

            if(checkObj==null)
                return null;
            else{
                int index = ((Integer) checkObj).intValue();

                return allFields[index];
            }
        }
    }
	
	/**
     * render component onto G2 for print of image creation
     */
    private void renderComponent(Graphics2D g2, int currentComp, Component comp, int rotation, int accDisplay) {
        //if (showMethods)
        //    System.out.println("DefaultAcroRenderer.renderComponent()");

        if (comp != null) {

            boolean editable = false;

            if (comp instanceof JComboBox) {
                if (((JComboBox) comp).isEditable()) {
                    editable = true;
                    ((JComboBox) comp).setEditable(false);
                }

                /**fix for odd bug in Java when using Windows l & f - might need refining*/
                if (!UIManager.getLookAndFeel().isNativeLookAndFeel()) {

                    if (((JComboBox) comp).getComponentCount() > 0)
                        renderComponent(g2, currentComp, ((JComboBox) comp).getComponent(0), rotation, accDisplay);
                }else if(PdfDecoder.isRunningOnMac){ //hack for OS X (where have we heard that before)
                    if (((JComboBox) comp).getComponentCount() > 0){

                        JComboBox combo=((JComboBox)comp);

                        Object selected=combo.getSelectedItem();
                        if(selected!=null){

                            JTextField text=new JTextField();
                                                    
                            text.setText(combo.getSelectedItem().toString());

                            text.setBackground(combo.getBackground());
                            text.setForeground(combo.getForeground());
                            text.setFont(combo.getFont());

                            text.setBorder(null);

                            renderComponent(g2, currentComp, text, rotation, accDisplay);
                        }else
                           renderComponent(g2, currentComp, ((JComboBox) comp).getComponent(0), rotation, accDisplay);
                    }
                }

            }

            scaleComponent(currentPage, 1, rotation, currentComp, comp, false);

            AffineTransform ax = g2.getTransform();
            g2.translate(comp.getBounds().x - insetW, comp.getBounds().y + cropOtherY[currentPage]);
            comp.paint(g2);
            g2.setTransform(ax);
            
            if (editable /*&& comp instanceof JComboBox*/) {
                ((JComboBox) comp).setEditable(true);
            }
        }
    }

    boolean renderFormsWithJPedalFontRenderer=false;
	
    /**
     * draw the forms onto display for print of image. Note different routine to
     * handle forms also displayed at present
     */
    public void renderFormsOntoG2(Object raw, int pageIndex, float scaling, int rotation, int accDisplay) {

    	Graphics2D g2=(Graphics2D) raw;

        AffineTransform defaultAf = g2.getTransform();

        //setup scaling
        AffineTransform aff = g2.getTransform();
        aff.scale(1, -1);
        aff.translate(0, -pageHeight - insetH);
        g2.setTransform(aff);

        //remove so don't appear rescaled on screen
//		if((currentPage==pageIndex)&&(panel!=null))
//			this.removeDisplayComponentsFromScreen(panel);//removed to stop forms disappearing on printing

        try {

        	//get start number
        	int currentComp = trackPagesRendered[pageIndex];
        
            //not displayed so we can manipulate at will

            Component[] formComps = allFields;

            if (formComps != null && currentComp != -1) {

                /**needs to go onto a panel to be drawn*/
                JPanel dummyPanel = new JPanel();

                //disable indent while we print
                int tempIndent = indent;
                indent = 0;

                //just put on page, allowing for no values (last one alsways empty as array 1 too big
                //first test stops problem with empty forms
                while (pageMap.length>currentComp && pageMap[currentComp] == pageIndex) {

                    Component comp = formComps[currentComp];
                    if (comp != null && comp.isVisible()) {
                        //wrap JList in ScrollPane to ensure displayed if size set to smaller than list
                        //(ie like ComboBox)
                        //@note - fixed by moving the selected item to the top of the list. 
                    	//this works for the file acro_func_baseline1.pdf
                    	//and now works on file fieldertest2.pdf and on formsmixed.pdf
                    	//but does not render correct in tests i THINK, UNCONFIRMED
                    	//leaves grayed out boxes in renderer.
                    	
                    	float boundHeight = boundingBoxs[currentComp][3]-boundingBoxs[currentComp][1];
                        int swingHeight = comp.getPreferredSize().height;
                        /**
                         * check if the component is a jlist, and if it is, then is their a selected item, 
                         * if their is then is the bounding box smaller than the jlist actual size
                         * then and only then do we need to change the way its printed
                         */
                        if(renderFormsWithJPedalFontRenderer){

                            //get correct key to lookup form data
                            String ref=this.convertIDtoRef(currentComp);

                            
                            //System.out.println(currentComp+" "+comp.getLocation()+" "+comp);
                            Object rawForm= this.getRawForm(ref);

                            if(rawForm instanceof FormObject){
                                FormObject form=(FormObject)rawForm;
                                System.out.println(ref+" "+form.getTextFont()+" "+form.getTextString());
                            }
                        }else if(comp instanceof JList && ((JList)comp).getSelectedIndex()!=-1 && boundHeight<swingHeight){

                            JList comp2=(JList)comp;

                            dummyPanel.add(comp);
                            
//                        	JList tmp = comp2;//((JList)((JScrollPane)comp).getViewport().getComponent(0));
                        	ListModel model = comp2.getModel();
                        	Object[] array = new Object[model.getSize()];
                        	
                        	int selectedIndex = comp2.getSelectedIndex();
                        	int c = 0;
                        	array[c++] = model.getElementAt(selectedIndex);
                        	
                        	for(int i=0;i<array.length;i++){
                    			if(i!=selectedIndex)
                    				array[c++] = model.getElementAt(i);
                        	}
                        	
                        	comp2.setListData(array);
                        	comp2.setSelectedIndex(0);
                        	
                        	try {
                        		renderComponent(g2, currentComp,comp2,rotation,accDisplay);
                              	dummyPanel.remove(comp2);
                            } catch (Exception cc) {

                            }
                        }else {
                        	dummyPanel.add(comp);
                        	
                        	try {
                        		renderComponent(g2, currentComp,comp,rotation,accDisplay);
                              	dummyPanel.remove(comp);
                            } catch (Exception cc) {

                            }
                        }
                    }
                    currentComp++;

                    if (currentComp == pageMap.length)
                        break;
                }
                indent = tempIndent; //put back indent
            }
        } catch (Exception e) {
            e.printStackTrace();
        }

        g2.setTransform(defaultAf);

        //put componenents back
        if (currentPage == pageIndex && panel != null) {
//			createDisplayComponentsForPage(pageIndex,this.panel,this.displayScaling,this.rotation);
//			panel.invalidate();
//			panel.repaint();
        	
            resetScaledLocation(displayScaling, rotation, indent);
        }

    }

	private void setField(Component nextComp,int formPage,float scaling, int rotation) {
		
		//add fieldname to map for action events
        String curCompName = FormUtils.removeStateToCheck(nextComp.getName(), false);

        if (curCompName != null && !lastNameAdded.equals(curCompName)) {
        	nameToCompIndex.put(curCompName, new Integer(nextFreeField));
            lastNameAdded = curCompName;
        }

        //setup and add component to selection
        if (nextComp != null) {
        	
	        //set location and size
	        Rectangle rect = nextComp.getBounds();
	        if (rect != null) {
	            
	            boundingBoxs[nextFreeField][0] = rect.x;
	            boundingBoxs[nextFreeField][1] = rect.y;
	            boundingBoxs[nextFreeField][2] = rect.width + rect.x;
	            boundingBoxs[nextFreeField][3] = rect.height + rect.y;
	
	        }
	            
			allFields[nextFreeField] = nextComp;
	        scroll[nextFreeField]=null;
	
	        fontSize[nextFreeField] = fontSizes[nextFreeField];

            //flag as unused
	        firstTimeDisplayed[nextFreeField]=true;

	        //make visible
	        scaleComponent(formPage, scaling, rotation, nextFreeField, nextComp, true);
			
        }
        
        pageMap[nextFreeField] = formPage;
        
		nextFreeField++;
        
	}

	
	
	/**
     * alter font and size to match scaling. Note we pass in compoent so we can
     * have multiple copies (needed if printing page displayed).
     */
    private void scaleComponent(int currentPage, float scaling, int rotation, int i, Component comp, boolean repaint) {
        
//    	if (showMethods)
//            System.out.println("DefaultAcroRenderer.scaleComponent()");

        if (comp == null)
            return;

        int x = 0, y = 0, w = 0, h = 0;

        int cropOtherX = (pageData.getMediaBoxWidth(currentPage) - pageData.getCropBoxWidth(currentPage) - pageData.getCropBoxX(currentPage));

        if (rotation == 0) {

            //old working routine
//		    int x = (int)((boundingBoxs[i][0])*scaling)+insetW-pageData.getCropBoxX(currentPage);
//		    int y = (int)((pageData.getMediaBoxHeight(currentPage)-boundingBoxs[i][3]-cropOtherY)*scaling)+insetH;
//		    int w = (int)((boundingBoxs[i][2]-boundingBoxs[i][0])*scaling);
//		    int h = (int)((boundingBoxs[i][3]-boundingBoxs[i][1])*scaling);

            int crx = pageData.getCropBoxX(currentPage);
            //new hopefully more accurate routine
            float x100 = (boundingBoxs[i][0]) - (crx) + insetW;
            
            /**
             * if we are drawing the forms to "extract image" or "print",
             * we don't translate g2 by insets we translate by crop x,y
             * so add on crop values
             * we should also only be using 0 rotation
             */
            if (!repaint)
                x100 += crx;
            
            float y100 = (pageData.getMediaBoxHeight(currentPage) - boundingBoxs[i][3] - cropOtherY[currentPage]) + insetH;
            float w100 = (boundingBoxs[i][2] - boundingBoxs[i][0]);
            float h100 = (boundingBoxs[i][3] - boundingBoxs[i][1]);

            x = (int) (((x100 - insetW) * scaling) + insetW);
            y = (int) (((y100 - insetH) * scaling) + insetH);
            w = (int) (w100 * scaling);
            h = (int) (h100 * scaling);
      
        } else if (rotation == 90) {

            //old working routine
//		    int x = (int)((boundingBoxs[i][1]-pageData.getCropBoxY(currentPage))*scaling)+insetW;
//			int y = (int)((boundingBoxs[i][0])*scaling)+insetH;
//			int w = (int)((boundingBoxs[i][3]-boundingBoxs[i][1])*scaling);
//			int h = (int)((boundingBoxs[i][2]-boundingBoxs[i][0])*scaling);

            //new hopefully better routine
            float x100 = (boundingBoxs[i][1] - pageData.getCropBoxY(currentPage)) + insetW;
            float y100 = (boundingBoxs[i][0] - pageData.getCropBoxX(currentPage)) + insetH;
            float w100 = (boundingBoxs[i][3] - boundingBoxs[i][1]);
            float h100 = (boundingBoxs[i][2] - boundingBoxs[i][0]);

            x = (int) (((x100 - insetH) * scaling) + insetH);
            y = (int) (((y100 - insetW) * scaling) + insetW);
            w = (int) (w100 * scaling);
            h = (int) (h100 * scaling);

        } else if (rotation == 180) {
            //old working routine
//		    int x = (int)((pageData.getMediaBoxWidth(currentPage)-boundingBoxs[i][2]-cropOtherX)*scaling)+insetW;
//			int y = (int)((boundingBoxs[i][1]-pageData.getCropBoxY(currentPage))*scaling)+insetH;
//			int w = (int)((boundingBoxs[i][2]-boundingBoxs[i][0])*scaling);
//			int h = (int)((boundingBoxs[i][3]-boundingBoxs[i][1])*scaling);

            //new hopefully better routine
            int x100 = (int) (pageData.getMediaBoxWidth(currentPage) - boundingBoxs[i][2] - cropOtherX) + insetW;
            int y100 = (int) (boundingBoxs[i][1] - pageData.getCropBoxY(currentPage)) + insetH;
            int w100 = (int) (boundingBoxs[i][2] - boundingBoxs[i][0]);
            int h100 = (int) (boundingBoxs[i][3] - boundingBoxs[i][1]);

            x = (int) (((x100 - insetW) * scaling) + insetW);
            y = (int) (((y100 - insetH) * scaling) + insetH);
            w = (int) (w100 * scaling);
            h = (int) (h100 * scaling);

        } else if (rotation == 270) {

            //old working routine
//		    int x = (int)((pageData.getMediaBoxHeight(currentPage)-boundingBoxs[i][3]-cropOtherY)*scaling)+insetW;
//			int y = (int)((pageData.getMediaBoxWidth(currentPage)-boundingBoxs[i][2]-cropOtherX)*scaling)+insetH;
//			int w = (int)((boundingBoxs[i][3]-boundingBoxs[i][1])*scaling);
//			int h = (int)((boundingBoxs[i][2]-boundingBoxs[i][0])*scaling);

            //new hopefully improved routine
            float x100 = (pageData.getMediaBoxHeight(currentPage) - boundingBoxs[i][3] - cropOtherY[currentPage]) + insetW;
            float y100 = (pageData.getMediaBoxWidth(currentPage) - boundingBoxs[i][2] - cropOtherX) + insetH;
            float w100 = (boundingBoxs[i][3] - boundingBoxs[i][1]);
            float h100 = (boundingBoxs[i][2] - boundingBoxs[i][0]);

            x = (int) (((x100 - insetH) * scaling) + insetH);
            y = (int) (((y100 - insetW) * scaling) + insetW);
            w = (int) (w100 * scaling);
            h = (int) (h100 * scaling);

        }

        /**
         * rescale the font size
         */
//        if (debug)
//            System.out.println("check font size=" + comp);

        Font resetFont = comp.getFont();
        if (resetFont != null) {
            int rawSize = fontSize[i];

            if (rawSize == -1)
                rawSize = 8;

            if (rawSize == 0) {//best fit

                //work out best size for bounding box of object
                int height = (int) (boundingBoxs[i][3] - boundingBoxs[i][1]);
                int width = (int) (boundingBoxs[i][2] - boundingBoxs[i][0]);
                if(rotation==90 || rotation==270){
                	int tmp = height;
                	height = width;
                	width = tmp;
                }

                height *= 0.85;

                rawSize = height;

                if (comp instanceof JTextComponent) {
                	int len = ((JTextComponent) comp).getText().length();
                    if ((len * height) / 2 > width) {
                        if(len>0)
                        	width /= len;
                    	rawSize = width;
                    }
                } else if (comp instanceof JButton) {
                    String text = ((JButton) comp).getText();
                    if(text!=null){
                    	int len = text.length();
	                    if((len * height) / 2 > width) {
	                        if (len > 0)
	                            width /= len;
	                        rawSize = width;
	                    }
                    }
                } else {
//					System.out.println("else="+width);
                }

//				rawSize = height;
            }

            int size = (int) (rawSize * scaling);
            if (size < 1) {
                size = 1;
            }

//            if (debug)
//                System.out.println(size + "<<<<<<resetfont=" + resetFont);

            Font newFont = new Font(resetFont.getFontName(), resetFont.getStyle(), size);
            //resetFont.getAttributes().put(java.awt.font.TextAttribute.SIZE,size);
//            if (debug)
//                System.out.println("newfont=" + newFont);

            comp.setFont(newFont);
            
            //factor in offset if multiple pages displayed
            if ((xReached != null)) {
                x = x + xReached[currentPage];
                y = y + yReached[currentPage];
            }

            comp.setBounds(indent + x, y, w, h);

        }

        /**
         * rescale the icons if any
         */
        if (comp != null && comp instanceof AbstractButton) {
            AbstractButton but = ((AbstractButton) comp);

            Icon curIcon = but.getIcon();
            if (curIcon instanceof FixImageIcon) {
                ((FixImageIcon) curIcon).setWH(comp.getWidth(), comp.getHeight());

                setIconProperties(currentPage, rotation, curIcon);
            }

            curIcon = but.getPressedIcon();
            if (curIcon instanceof FixImageIcon) {
                ((FixImageIcon) curIcon).setWH(comp.getWidth(), comp.getHeight());
                
                setIconProperties(currentPage, rotation, curIcon);
            }

            curIcon = but.getSelectedIcon();
            if (curIcon instanceof FixImageIcon) {
                ((FixImageIcon) curIcon).setWH(comp.getWidth(), comp.getHeight());
                
                setIconProperties(currentPage, rotation, curIcon);
            }

            curIcon = but.getRolloverIcon();
            if (curIcon instanceof FixImageIcon) {
                ((FixImageIcon) curIcon).setWH(comp.getWidth(), comp.getHeight());
                
                setIconProperties(currentPage, rotation, curIcon);
            }

            curIcon = but.getRolloverSelectedIcon();
            if (curIcon instanceof FixImageIcon) {
                ((FixImageIcon) curIcon).setWH(comp.getWidth(), comp.getHeight());
                
                setIconProperties(currentPage, rotation, curIcon);
            }
        }

        if (repaint) {
            //  comp.invalidate();
            //  comp.repaint();
        }
    }

	private void setIconProperties(int currentPage, int rotation, Icon curIcon) {
		int pageRotation = pageData.getRotation(currentPage);
		((FixImageIcon) curIcon).setPageRotation(pageRotation);
		((FixImageIcon) curIcon).setRotation(rotation);
	}

    

	
	
	/**
	 * used to flush/resize data structures on new document/page
	 * @param formCount
	 * @param pageCount
	 * @param keepValues
	 */
	public void resetComponents(int formCount,int pageCount,boolean keepValues) {

		//System.out.println("count="+formCount);
		
		super.resetComponents(formCount, pageCount, keepValues);

        if(!keepValues){
            scroll= new JScrollPane[formCount +1];    
            allFields = new Component[formCount + 1];          
            testedForDuplicates = new boolean[formCount + 1];          
        }else if(pageMap!=null){
        	JScrollPane[] tmpScroll = scroll;
            Component[] tmpFields = allFields;
            boolean[] tmptestedForDuplicates = testedForDuplicates;
            
            allFields = new Component[formCount + 1];
            testedForDuplicates = new boolean[formCount + 1];
           
            scroll=new JScrollPane[formCount +1];

            int origSize=tmpFields.length;

            //populate
            for (int i = 0; i < formCount+1; i++) {

                if(i==origSize)
                        break;
                
                allFields[i] = tmpFields[i];
                testedForDuplicates[i] = tmptestedForDuplicates[i];
                scroll[i]=tmpScroll[i];
            }
        }

        //clean out store of buttonGroups
        annotBgs.clear();
    }

	/**
	 * used to remove all components from display
	 */
	public void removeAllComponentsFromScreen() {
		
		if(panel!=null){
			if (SwingUtilities.isEventDispatchThread())
				panel.removeAll();
	    	else {
				final Runnable doPaintComponent = new Runnable() {
					public void run() {
						panel.removeAll();
					}
				};
				SwingUtilities.invokeLater(doPaintComponent);
	    	}
    	}
		
	}

	/**
	 * pass in object components drawn onto
	 * @param rootComp
	 */
	public void setRootDisplayComponent(final Object rootComp) {
		
		
		if (SwingUtilities.isEventDispatchThread())
			panel=(JPanel)rootComp;
    	else {
			final Runnable doPaintComponent = new Runnable() {
				public void run() {
					panel=(JPanel)rootComp;
				}
			};
			SwingUtilities.invokeLater(doPaintComponent);
		}
		
	}

	/**
	 * used to add any additional radio/checkboxes on decode
	 * @param page
	 */
	public void completeFields(int page) {
		
		/**
         * handles composite objects (ie group of radio buttons) which may be split across pages
         */
        if (additionFieldsMap.get(String.valueOf(page)) != null) {
            ArrayList list = (ArrayList) additionFieldsMap.get(String.valueOf(page));
            Iterator iter = list.iterator();
            while (iter.hasNext()) {
                //put into array
				setField((Component) iter.next(), page,scaling,rotation);	
        	}
        }
        
	}
	
	/**
	 * store and complete setup of component
	 * @param formObject
	 * @param formNum
	 * @param isChildObject
	 * @param formType
	 * @param rawField
	 */
	public void completeField(final FormObject formObject,
			int formNum, boolean isChildObject, Integer formType, Object rawField) {
		
		if(rawField==null)
			return;

        final int formPage=formObject.getPageNumber();
		        
        //cast back to ULC or Swing or SWT
		//and change this class to suit
		Component retComponent=(Component)rawField;
		
		String fieldName=formObject.getFieldName();
        String parent=formObject.getParentRef();


        //if no name, or parent has one recursively scan tree for one in Parent
		while(parent!=null){
			FormObject parentObj=(FormObject) rawFormData.get(parent);

            //stop if we have run out of tree or reached kid level (in whcih case name duplicate)
            if(parentObj==null || parentObj.getKidData()!=null)
				break;

            String newName=parentObj.getFieldName();
            if(fieldName==null && newName!=null)
                fieldName=newName;
            else if(newName!=null)
                fieldName=fieldName+"."+newName;

            if(newName==null)
            break;
            
            parent=parentObj.getParentRef();

		}

		/**
		 * set values for Component
		 */
		
		//append state to name so we can retrieve later if needed
		String name = fieldName;
		if (name != null) {//we have some empty values as well as null
			String stateToCheck = formObject.getStateTocheck();
		    if (stateToCheck != null && stateToCheck.length()>0)
		        name = name + "-(" + stateToCheck + ')';
		    retComponent.setName(name);
		}
		
		Rectangle rect = formObject.getBoundingRectangle();
		if (rect != null)
		    retComponent.setBounds(rect);
		
		String defaultValue=formObject.getDefaultValue();
		if (formObject.getValuesMap() != null)
		    defaultValue = (String) formObject.getValuesMap().get(Strip.checkRemoveLeadingSlach(defaultValue));
		else
		    defaultValue = Strip.checkRemoveLeadingSlach(defaultValue);

		fontSizes[formNum] = formObject.getTextSize();
		defaultValues[formNum]=defaultValue;

        convertFormIDtoRef.put(new Integer(nextFreeField),formObject.getPDFRef());

        //set the type
        if(formType.equals(org.jpedal.objects.acroforms.creation.FormFactory.UNKNOWN))
			typeValues.put(fieldName, org.jpedal.objects.acroforms.creation.FormFactory.ANNOTATION);
        else
            typeValues.put(fieldName,formType);

      if(isChildObject){
		try {
		    AbstractButton but = (AbstractButton) retComponent;
		    but.setBounds(formObject.getBoundingRectangle());
		    but.setText(String.valueOf(formObject.getPageNumber()));
		    bg.add(but);  // Add to button group
		} catch (ClassCastException cc) {
		}
      }else{
		if (bg.getButtonCount() > 1) {
		    AbstractButton[] sortedButtons = FormUtils.sortGroupSmallestFirst(bg);
		
		    for (int j = 0; j < bg.getButtonCount(); j++) {
		        if (sortedButtons[j].getLabel().equals(String.valueOf(formPage))) {
		        	String currentState = formObject.getCurrentState();
		        	String onState = formObject.getOnState();
		            if ((currentState != null && currentState.equals(FormUtils.removeStateToCheck(sortedButtons[j].getName(), true)))
		                    || (onState != null && onState.equals(FormUtils.removeStateToCheck(sortedButtons[j].getName(), true)))) {
		                sortedButtons[j].setSelected(true);
		            }
					
		            //put into array
					setField(sortedButtons[j], formPage,scaling,rotation);
					
		        } else {
		            if (additionFieldsMap.get(sortedButtons[j].getLabel()) != null) {
		                ArrayList list = (ArrayList) additionFieldsMap.get(sortedButtons[j].getLabel());
		                list.add(sortedButtons[j]);
		                additionFieldsMap.put(sortedButtons[j].getLabel(), list);
		            } else {
		                ArrayList list = new ArrayList();
		                list.add(sortedButtons[j]);
		                additionFieldsMap.put(sortedButtons[j].getLabel(), list);
		            }
		        }
		    }
		} else if (retComponent != null) { //other form objects
		    if (formObject.getFieldFlags()[FormObject.NOTOGGLETOOFF]) {
		        if (retComponent instanceof AbstractButton) {
		            AbstractButton but = (AbstractButton) retComponent;
		            but.setBounds(formObject.getBoundingRectangle());
		            but.setText(String.valueOf(formObject.getPageNumber()));
		            new ButtonGroup().add(but);  // Add to button group
		        } else {
		        }
		    }

            //allow for kids set for Annots in Acroform
            //On first we create the button and then link in later items
            //no sorting at present - may need refining
            String annotLink=formObject.getAnnotParent();
            if(annotLink!=null){

                //see if one set ad if not set
                ButtonGroup annotBg;
                Object currentBg=annotBgs.get(annotLink);
                if(currentBg==null){ //first item in Group so create new ButtonGroup
                    annotBg=new ButtonGroup();
                    annotBgs.put(annotLink,annotBg);
                }else{ //use existing
                    
                    annotBg=(ButtonGroup)currentBg;
                }

                try{
                    if (retComponent instanceof AbstractButton) {
                        AbstractButton but = (AbstractButton) retComponent;
                        but.setBounds(formObject.getBoundingRectangle());
                        but.setText(String.valueOf(formObject.getPageNumber()));
                        annotBg.add(but);  // Add to button group
                    }

                }catch(Exception e){
                }
            }

            //put into array
			setField(retComponent, formPage, scaling, rotation);
			
		}
      }
	}
	
	/**
     * alter location and bounds so form objects show correctly scaled
     */
    public void resetScaledLocation(float scaling, int rotation, int indent) {

        //if (showMethods)
        //    System.out.println("resetScaledLocation scaling=" + scaling + " indent=" + indent + " rotation=" + rotation);

        this.indent = indent;
        this.displayScaling = scaling;
        this.rotation = rotation;

        //System.out.println("Reset scaling "+scaling+" "+lastScaling+" "+startID+" "+this.pageHeight);
        /**
         debug=true;
         /**/

        //we get a spurious call in linux resulting in an exception
        if (trackPagesRendered == null)
            return;

        //only if necessary
        if (scaling != lastScaling || rotation != oldRotation || indent != oldIndent) {

        	oldRotation = rotation;
        	lastScaling = scaling;
        	oldIndent = indent;

            int currentComp;

            //fix rescale issue on Testfeld
            if (startPage <
            		trackPagesRendered.length) {
                currentComp =trackPagesRendered[startPage];//startID;
            } else {
                currentComp = 0;
            }

            //reset all locations
            if ((allFields != null) && (currentPage > 0) && (currentComp != -1) && (pageMap.length > currentComp)) {

                //just put on page, allowing for no values (last one alsways empty as array 1 too big
                //while(pageMap[currentComp]==currentPage){
                while (currentComp<pageMap.length && currentComp>-1 &&  
                		((pageMap[currentComp] >= startPage) && (pageMap[currentComp] < endPage) 
                			&& (allFields[currentComp] != null))) {

                    //System.out.println("added"+currentComp);
                    //while(currentComp<pageMap.length){//potential fix to help rotation
                    if (panel != null){// && !(allFields[currentComp] instanceof JList))

                    	if (SwingUtilities.isEventDispatchThread()){
                    		if(scroll[currentComp]==null)
                            	panel.remove(allFields[currentComp]);
                            else
                            	panel.remove(scroll[currentComp]);
                    		
                    		scaleComponent(pageMap[currentComp], scaling, rotation, currentComp, allFields[currentComp], true);

                    	}else {
                			final int id=currentComp;
                			final float s=scaling;
                			final int r=rotation;
                			final Runnable doPaintComponent = new Runnable() {
                				public void run() {
                					if(scroll[id]==null)
                                    	panel.remove(allFields[id]);
                                    else
                                    	panel.remove(scroll[id]);
                					
                					scaleComponent(pageMap[id], s, r, id, allFields[id], true);


                				}
                			};
                			SwingUtilities.invokeLater(doPaintComponent);
                		}
                    }
                    
                    if (panel != null){

                    	/** possible problem with rotation files, 
                    	 * just test if rotated 90 or 270 and get appropriate height or width, 
                    	 * that would represent the height when viewed at correct orientation
                    	 */
                    	float boundHeight = boundingBoxs[currentComp][3]-boundingBoxs[currentComp][1];
                        int swingHeight = allFields[currentComp].getPreferredSize().height;
                        
                        if(allFields[currentComp] instanceof JList && boundHeight<swingHeight){

                            JList comp=(JList)allFields[currentComp];

                            if(scroll[currentComp]!=null)
                            	scroll[currentComp].remove(comp);

                            scroll[currentComp]=new JScrollPane(comp);

                            scroll[currentComp].setVerticalScrollBarPolicy(JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED);
                            scroll[currentComp].setHorizontalScrollBarPolicy(JScrollPane.HORIZONTAL_SCROLLBAR_AS_NEEDED);

                            
                            scroll[currentComp].setLocation(comp.getLocation());

                            scroll[currentComp].setPreferredSize(comp.getPreferredSize());
                            scroll[currentComp].setSize(comp.getSize());

                            //ensure visible (do it before we add)
                            int index=comp.getSelectedIndex();
                            if(index>-1)
                                comp.ensureIndexIsVisible(index);

                            if (SwingUtilities.isEventDispatchThread())
                            	panel.add(scroll[currentComp]);
                        	else {
                    			final int id=currentComp;
                    			final Runnable doPaintComponent = new Runnable() {
                    				public void run() {
                    					panel.add(scroll[id]);
                    				}
                    			};
                    			SwingUtilities.invokeLater(doPaintComponent);
                    		}
                            

                        }else{
                        	
                        	if (SwingUtilities.isEventDispatchThread())
                        		panel.add(allFields[currentComp]);
                        	else {
                    			final int id=currentComp;
                    			final Runnable doPaintComponent = new Runnable() {
                    				public void run() {
                    					panel.add(allFields[id]);
                    				}
                    			};
                    			SwingUtilities.invokeLater(doPaintComponent);
                    		}
                        }
                    }
                    currentComp++;
                }
            }
        }
    }

    

	

	/**
	 * put components onto screen display
	 * @param startPage
	 * @param endPage
	 */
	public void displayComponents(int startPage,int endPage) {
	
		if (panel == null)
        	return ;
        
        this.startPage = startPage;
        this.endPage = endPage;

        /**    MIGHT be needed for multi display
         boolean multiPageDisplay=(startPage!=endPage);

         //remove all invisible forms
         if(multiPageDisplay){

         int start=1;
         int end=startPage;
         //from start to first page
         //removePageRangeFromDisplay(start, end, panel); //end not included in range

         //from end to last page
         int last=1+trackPagesRendered.length;
         //removePageRangeFromDisplay(end, last, panel);
         }
         /**/
        
        for (int page = startPage; page < endPage; page++) {

            int currentComp = getStartComponentCountForPage(page);
            //just put on page, allowing for no values (last one always empty as array 1 too big)

            //allow for empty form
            if(pageMap==null || pageMap.length<=currentComp)
            return;
            
            //display components
            if (currentComp!=-1 && currentComp != -999 && startPage>0 && endPage>0) {
                while (pageMap[currentComp] >= startPage && pageMap[currentComp] < endPage) {
                    if (allFields[currentComp] != null) {
                    	if (SwingUtilities.isEventDispatchThread()){
        	        		scaleComponent(pageMap[currentComp], scaling, rotation, currentComp, allFields[currentComp], true);              
        	        		panel.add(allFields[currentComp]);
        	        		
        	        	}else {
        	    			final int id=currentComp;
        	    			final Runnable doPaintComponent = new Runnable() {
        	    				public void run() {
        	    					scaleComponent(pageMap[id], scaling, rotation, id, allFields[id], true);                       
        	    					panel.add(allFields[id]);
        	    				}
        	    			};
        	    			SwingUtilities.invokeLater(doPaintComponent);
        	    		}
        	            
        	            String currentName = FormUtils.removeStateToCheck(allFields[currentComp].getName(), false);
        	
        	            //<start-os>
        	            //throws exception if repeated and not needed after first show
        	            if(firstTimeDisplayed[currentComp])
        	            javascript.execute(currentName,
        	            		org.jpedal.objects.acroforms.actions.ActionHandler.K,
        	            		org.jpedal.objects.acroforms.actions.ActionHandler.FOCUS_EVENT,' ');
        	            //<end-os>
        	
        	            firstTimeDisplayed[currentComp]=false;
                    }
                    
                    currentComp++;
                    
                    if (currentComp == pageMap.length)
                        break;
                }
            }
            
        }
	}

	/**
	 * tell user about Javascript validation error
	 * @param code
	 * @param args
	 */
	public void reportError(int code, Object[] args) {
		
		//tell user
		if(code==ErrorCodes.JSInvalidFormat){
			JOptionPane.showMessageDialog(panel,"The values entered does not match the format of the field ["+args[0]+" ]",
					"Warning: Javascript Window",JOptionPane.INFORMATION_MESSAGE);
		}else if(code==ErrorCodes.JSInvalidDateFormat)
			JOptionPane.showMessageDialog(panel,"Invalid date/time: please ensure that the date/time exists. Field ["+args[0]+" ] should match format "+args[1],
					"Warning: Javascript Window",JOptionPane.INFORMATION_MESSAGE);
		else if(code==ErrorCodes.JSInvalidRangeFormat){
			StringBuffer message=new StringBuffer("Invalid value: must be greater than ");
			if(args[1].equals("true"))
				message.append("or equal to ");

			message.append(args[2]);
			message.append("\nand less than ");

			if((args[3]).equals("true"))
				message.append("or equal to ");

			message.append(args[4]);

			message.append('.');
			JOptionPane.showMessageDialog(panel,message.toString(),
					"Warning: Javascript Window",JOptionPane.INFORMATION_MESSAGE);
		}else
			JOptionPane.showMessageDialog(panel,"The values entered does not match the format of the field",
					"Warning: Javascript Window",JOptionPane.INFORMATION_MESSAGE);
		
	}

	/**
	 * reset composite used for radio buttons/check boxes
     *
     * Note we may have a set of buttonGroups in Annots defined in forms - this is done
     * in other code
	 */
	public void resetButtonGroup() {
		
		bg = new ButtonGroup();

    }

	

	

	/**
	 * return list of form names for page
	 * @param pageNumber
	 * @return
	 */
	public List getComponentNameList(int pageNumber) {
		
		if (trackPagesRendered == null)
			return null;

		if ((pageNumber != -1) && (trackPagesRendered[pageNumber] == -1))
			return null; //now we can interrupt decode page this is more appropriate
		//  throw new PdfException("[PDF] Page "+pageNumber+" not decoded");

		int currentComp;
		if (pageNumber == -1)
			currentComp = 0;
		else
			currentComp =trackPagesRendered[pageNumber];

		ArrayList nameList = new ArrayList();

		//go through all fields on page and add to list
		String lastName = "";
		String currentName = "";
		while ((pageNumber == -1) || (pageMap[currentComp] == pageNumber)) {
			lastName = getComponentName(currentComp, nameList, lastName);
			currentComp++;
			if (currentComp == pageMap.length)
				break;
		}

		return nameList;
	}

	public void execute(Map codeMap) {
    	
    	if(((String)codeMap.get("S")).indexOf("Hide")!=-1){
    		String fieldName = (String) codeMap.get("T");
    		String hideVal = (String)codeMap.get("H");
    		boolean show;
    		if(hideVal==null){
    			show = false;
    		}else {
    			show = !(Boolean.valueOf(hideVal).booleanValue());
    		}
    		
    		Component[] fields = (Component[]) getComponentsByName(fieldName);
    		for(int i=0;i<fields.length;i++){
    			fields[i].setVisible(show);
    		}
    	}
	}

	public Map getRawFormData() {
		return rawFormData;
	}

    /**
     * not used by Swing
     * @param offset
     */
    public void setOffset(int offset) {

    }
}
