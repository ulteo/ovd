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
* GUI.java
* ---------------
*/

package org.jpedal.examples.simpleviewer.gui;

import java.awt.Color;
import java.awt.Graphics2D;
import java.awt.Image;
import java.awt.Rectangle;
import java.awt.image.BufferedImage;
import java.util.HashMap;

import javax.swing.JOptionPane;

import org.jpedal.Display;
import org.jpedal.PdfDecoder;
import org.jpedal.objects.PdfAnnots;
import org.jpedal.utils.Messages;
import org.jpedal.examples.simpleviewer.Values;
import org.jpedal.examples.simpleviewer.gui.generic.GUIButton;
import org.jpedal.examples.simpleviewer.gui.generic.GUICombo;
import org.jpedal.examples.simpleviewer.gui.generic.GUIOutline;
import org.jpedal.examples.simpleviewer.gui.generic.GUIThumbnailPanel;
import org.jpedal.examples.simpleviewer.gui.swing.SwingOutline;
import org.jpedal.examples.simpleviewer.utils.PropertiesFile;

/**any shared GUI code - generic and AWT*/
public class GUI {
	
	/**nav buttons - global so accessible to ContentExtractor*/
	public GUIButton first,fback,back,forward,fforward,end;
	
	public GUIButton singleButton,continuousButton,continuousFacingButton,facingButton;
	
	/**list for types - assumes present in org/jpedal/examples/simpleviewer/annots*
	 * "OTHER" MUST BE FIRST ITEM
	 * Try adding Link to the list to see links
	 */
	private final String[] annotTypes={"Other","Text","FileAttachment"};

	private final Color[] annotColors={Color.RED,Color.BLUE,Color.BLUE};
	
	public void setPreferences(int dpi, int search, int border, boolean scroll, int pageMode, boolean updateDefaultValue, int maxNoOfMultiViewers, boolean showDownloadWindow){
		
		//Set border config value and repaint
		PdfDecoder.CURRENT_BORDER_STYLE = border;
		properties.setValue("borderType", String.valueOf(border));
		
		//Set autoScroll default and add to properties file
		allowScrolling = scroll;
		properties.setValue("autoScroll", String.valueOf(scroll));
		
		//Dpi is taken into effect when zoom is called
		this.dpi = dpi;
		properties.setValue("DPI", String.valueOf(dpi));
		
		//@kieran Ensure valid value if not recognised
		if(pageMode<Display.SINGLE_PAGE || pageMode>Display.CONTINUOUS_FACING)
			pageMode = Display.SINGLE_PAGE;
		
		//Default Page Layout
		decode_pdf.setPageMode(pageMode);
		properties.setValue("pageMode", String.valueOf(pageMode));
		
		decode_pdf.repaint();
		
		//Set the search window
		if(!properties.getValue("searchWindowType").equals(String.valueOf(search)))
			JOptionPane.showMessageDialog(null, Messages.getMessage("PageLayoutViewMenu.ResetSearch"));
		
		properties.setValue("searchWindowType", String.valueOf(search));

		properties.setValue("automaticupdate", String.valueOf(updateDefaultValue));
		
		commonValues.setMaxMiltiViewers(maxNoOfMultiViewers);
		properties.setValue("maxmultiviewers", String.valueOf(maxNoOfMultiViewers));
		
		decode_pdf.setUseDownloadWindow(showDownloadWindow);
		properties.setValue("showDownloadWindow", String.valueOf(showDownloadWindow));
		
	}
	
	private int dpi = 96;
	
	public float convertToCorrectedScaling(float rawScaling) {

		//Allow for dpi value in scaling
		return rawScaling * (dpi/72);
	}
	
	/**handle for internal use*/
	protected PdfDecoder decode_pdf;
	
	/** location for divider with thumbnails turned on */
	protected static final int thumbLocation=200;
	
	/** minimum screen width to ensure menu buttons are visible */
	protected static final int minimumScreenWidth=700;
	
	/**track pages decoded once already*/
	protected HashMap pagesDecoded=new HashMap();

	/**allows user to toggle on/off text/image snapshot*/
	protected  GUIButton snapshotButton;
	
	/**cursorBox to draw onscreen*/
	private Rectangle currentRectangle =null;
	
	
	public int cropX;

	public int cropW;

	public int cropH;

	/**crop offset if present*/
	protected int mediaX,mediaY;

	public int mediaW;

	public int cropY;

	public int mediaH;
	
	/**show if outlines drawn*/
	protected boolean hasOutlinesDrawn=false;
	
	/**XML structure of bookmarks*/
	protected GUIOutline tree=new SwingOutline();
	
	/**stops autoscrolling at screen edge*/
	private boolean allowScrolling=true;
	
	/** location for the divider when bookmarks are displayed */
	protected int divLocation=170;
	
	/**flag to switch bookmarks on or off*/
	protected boolean showOutlines=true;
	
	/**scaling values as floats to save conversion*/
	protected float[] scalingFloatValues={1.0f,1.0f,1.0f,.25f,.5f,.75f,1.0f,1.25f,1.5f,2.0f,2.5f,5.0f,7.5f,10.0f};
	
	/**page scaling to use 1=100%*/
	protected float scaling = 1;
	
	/** padding so that the pdf is not right at the edge */
	protected static final int inset=25;
	
	/**store page rotation*/
	protected int rotation=0;
	
	/**scaling values as floats to save conversion*/
	protected final String[] rotationValues={"0","90","180","270"};
	
	/**scaling factors on the page*/
	protected GUICombo rotationBox;

	//<start-os>
	/**allows user to set quality of images*/
	protected GUICombo qualityBox;
	//<end-os>
	
	/**scaling factors on the page*/
	protected GUICombo scalingBox;
	
	/**default scaling on the combobox scalingValues*/
	protected static final int defaultSelection=0;

	/**title message on top if you want to over-ride JPedal default*/
	protected String titleMessage=null;
	
	protected Values commonValues;
	
	protected GUIThumbnailPanel thumbnails;
	
	protected PropertiesFile properties;
	
	/* (non-Javadoc)
	 * @see org.jpedal.examples.simpleviewer.gui.swing.GUIFactory#allowScrolling()
	 */
	public boolean allowScrolling() {
		return allowScrolling;
	}
	
	/* (non-Javadoc)
	 * @see org.jpedal.examples.simpleviewer.gui.swing.GUIFactory#getAnnotTypes()
	 */
	public String[] getAnnotTypes() {
		
		return this.annotTypes;
	}
	
	/* (non-Javadoc)
	 * @see org.jpedal.examples.simpleviewer.gui.swing.GUIFactory#setNoPagesDecoded()
	 */
	public void setNoPagesDecoded() {
		pagesDecoded.clear();
		
	}
	
	/* (non-Javadoc)
	 * @see org.jpedal.examples.simpleviewer.gui.swing.GUIFactory#setScalingToDefault()
	 */
	public void setScalingToDefault(){
		if(PdfDecoder.isRunningOnWindows){
			/** Adobe have different scaling factors for Adobe Reader 7/8 in windows but not in OS X */
			//scaling ==1.0f; //Actual Scaling of File
			//scaling = 1.335f; //Scaling factor in Adobe Reader 7
			scaling = 1.53f; //Scaling factor in Adobe Reader 8
		}else
			scaling = 1.0f;
		scalingBox.setSelectedIndex(defaultSelection); 
	}
	
	/* (non-Javadoc)
	 * @see org.jpedal.examples.simpleviewer.gui.swing.GUIFactory#setRectangle(java.awt.Rectangle)
	 */
	public void setRectangle(Rectangle newRect) {
		currentRectangle=newRect;
	}
	
	/* (non-Javadoc)
	 * @see org.jpedal.examples.simpleviewer.gui.swing.GUIFactory#getRectangle()
	 */
	public Rectangle getRectangle() {
		return currentRectangle;
	}
	
	/* (non-Javadoc)
	 * @see org.jpedal.examples.simpleviewer.gui.swing.GUIFactory#setAutoScrolling(boolean allowScrolling)
	 */
	public void setAutoScrolling(boolean allowScrolling) {
		this.allowScrolling=allowScrolling;
		
	}
	
	/* (non-Javadoc)
	 * @see org.jpedal.examples.simpleviewer.gui.swing.GUIFactory#toogleAutoScrolling()
	 */
	public void  toogleAutoScrolling(){
		allowScrolling=!allowScrolling;
	}
	
	/* (non-Javadoc)
	 * @see org.jpedal.examples.simpleviewer.gui.swing.GUIFactory#getRotation()
	 */
	public int getRotation() {
		return rotation;
	}
	
	/* (non-Javadoc)
	 * @see org.jpedal.examples.simpleviewer.gui.swing.GUIFactory#getScaling()
	 */
	public float getScaling() {
		return scaling;
	}
	
	public void setScaling(float s){
		scaling = s;
		scalingBox.setSelectedIndex((int)scaling);
	}
	
	/* (non-Javadoc)
	 * @see org.jpedal.examples.simpleviewer.gui.swing.GUIFactory#getPDFDisplayInset()
	 */
	public int getPDFDisplayInset() {
		return inset;
	}

	//<link><a name="createUniqueAnnotationIcons" />
	/** example code which sets up an individual icon for each annotation to display - only use
	 * if you require each annotation to have its own icon<p>
	 * To use this you ideally need to parse the annotations first -there is a method allowing you to
	 * extract just the annotations from the data.
	 */
	public void createUniqueAnnotationIcons() {

		int p=commonValues.getCurrentPage();

		PdfAnnots annotsData=decode_pdf.getPdfAnnotsData(null);

		final int size=16; //pixel size

		if(annotsData!=null){

			//actual size
			int max=annotsData.getAnnotCount();

			for(int j=0;j<annotTypes.length;j++){ //build a set of icons for each fileType you wish to supportby including in AnnotTypes

				//if(!annotsData.getAnnotSubType(j).equals("add type here")) //just 1 type please
				//next;

				//number icons needed
				int iconsForPage=0;

				//code to count number for selected type - just use max for all icons
				for (int i = 0; i < max; i++) {

					if(annotsData.getAnnotSubType(i).equals(annotTypes[j])) //count number of icons
						iconsForPage++;

				}

				//initialise to required size
				Image[] annotIcons = new Image[iconsForPage];


				//and create icons
				for (int i = 0; i < iconsForPage; i++) {

					//create a unique graphic
					annotIcons[i] = new BufferedImage(size, size,BufferedImage.TYPE_INT_ARGB);
					Graphics2D g2 = (Graphics2D) annotIcons[i].getGraphics();
					g2.setColor(annotColors[j]);
					g2.fill(new Rectangle(0, 0, size, size));
					g2.setColor(Color.BLACK);
					g2.draw(new Rectangle(0, 0, size-1, size-1));
					g2.setColor(Color.white);
					g2.drawString(((i+1) + " "),2, 12);

				}

				//add set of icons to display
				if(iconsForPage>0)
				decode_pdf.addUserIconsForAnnotations(p,annotTypes[j],annotIcons);
			}
		}
	}

	public int getDpi() {
		return dpi;
	}

	public void setDpi(int dpi) {
		this.dpi = dpi;
	}
}
