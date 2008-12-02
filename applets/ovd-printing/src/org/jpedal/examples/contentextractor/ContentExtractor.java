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
* ContentExtractor.java
* ---------------
*/
package org.jpedal.examples.contentextractor;

import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Dimension;
import java.awt.Font;
import java.awt.Rectangle;
import java.awt.Shape;

import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.geom.Rectangle2D;
import java.awt.image.BufferedImage;
import java.io.ByteArrayInputStream;
import java.io.ByteArrayOutputStream;
import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStreamWriter;
import java.util.HashMap;
import java.util.Map;

import javax.swing.Box;
import javax.swing.JButton;
import javax.swing.JFileChooser;

import javax.swing.AbstractButton;
import javax.swing.JDialog;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.JTabbedPane;
import javax.swing.JTextPane;
import javax.swing.JToolBar;
import javax.swing.UIManager;

import javax.swing.text.BadLocationException;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.FactoryConfigurationError;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;
import javax.xml.transform.stream.StreamSource;

import org.jpedal.PdfPanel;
import org.jpedal.examples.simpleviewer.Commands;
import org.jpedal.examples.simpleviewer.SimpleViewer;
import org.jpedal.examples.simpleviewer.gui.generic.GUIButton;
import org.jpedal.examples.simpleviewer.gui.swing.SwingButton;
import org.jpedal.examples.simpleviewer.utils.IconiseImage;
import org.jpedal.exception.PdfException;

import org.jpedal.grouping.PdfGroupingAlgorithms;

import org.jpedal.io.JAIHelper;import org.jpedal.io.ObjectStore;

import org.jpedal.utils.LogWriter;
import org.jpedal.utils.Messages;
import org.jpedal.utils.Strip;
import org.jpedal.utils.repositories.Vector_Int;
import org.jpedal.utils.repositories.Vector_Object;
import org.jpedal.utils.repositories.Vector_String;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

/**
 * Example client application to extract text and image from any rectangular area
 * on page using JPedal (for example ad extraction)
 * (Assumes content is in single column)
 *
 * Notes: currentGUI.getFrame() example uses some "unofficial" classes inside the library which may alter in function
 */
public class ContentExtractor extends SimpleViewer{

	/**co-ordinate of extracted object*/
	private Vector_Int itemSelectedX1=new Vector_Int(10);

	/**co-ordinate of extracted object*/
	private Vector_Int itemSelectedY1=new Vector_Int(10);

	/**co-ordinate of extracted object*/
	private Vector_Int itemSelectedWidth=new Vector_Int(10);

	/**co-ordinate of extracted object*/
	private Vector_Int itemSelectedHeight=new Vector_Int(10);

	/**co-ordinate of extracted object*/
	private Vector_Int pageUsed=new Vector_Int(10);

	/**where image file stored on disk*/
	private Vector_String imagesStored=new Vector_String(10);

	/**cache of thumbnails to speed things up*/
	Vector_Object thumbnailsStored=new Vector_Object(10);

	/**map of images for speed*/
	private Map imagesUsed=new HashMap();

	/**where text file stored on disk*/
	private Vector_String textStored=new Vector_String(10);

	/**where XML file stored on disk*/
	private Vector_String xmlStored=new Vector_String(10);

	/**items extractd +1*/
	private int itemSelectedCount=1;

	/**flag to show if extracted items visible*/
	private boolean showExtractedItems;

	/**allows view of extracted items if any*/
	GUIButton showItemsExtracted=new SwingButton();

	/**allows save of extracted items if any*/
	GUIButton saveItemsExtracted=new SwingButton();

	/**image type to save*/
	static final private String type="TIFF";

	public ContentExtractor() {

        JAIHelper.useJAI(false);

        JAIHelper.confirmJAIOnClasspath();

        commonValues.setContentExtractor(true);

       // alignment= Display.DISPLAY_LEFT_ALIGNED;
    }

	/** main method to run the software */
	public static void main(String[] args) {

		/**
		 * set the look and feel for the GUI components to be the
		 * default for the system it is running on
		 */
		try {
			UIManager.setLookAndFeel( UIManager.getSystemLookAndFeelClassName() );
		}catch (Exception e) {
			LogWriter.writeLog("Exception " + e + " setting look and feel");
		}

		/** Run the software */
		ContentExtractor current = new ContentExtractor();
		current.setupViewer();

	}

	/**
	 * setup the display and pop-up a viewer window
	 */
	public void setupViewer() {

		/**
	     * initialise client
	     */
	    try{

	        init(null);

	        /**ensure high quality images*/
	        //broken on Louise page
	       // decode_pdf.useHiResScreenDisplay(true);
	       // switchModes=false;

	        /**create the icons and menus for program*/
			setupExtractorGUI();

	    }catch(Exception e){
	        e.printStackTrace();
	        System.out.println("Exception on initialisation"); //$NON-NLS-1$
	    }
	}

	/**
	 * build tailored version of general GUI
	 */
	private void setupExtractorGUI() {

		currentGUI.first=new SwingButton();
		currentGUI.fback=new SwingButton();
		currentGUI.back=new SwingButton();
		currentGUI.forward=new SwingButton();
		currentGUI.fforward=new SwingButton();
		currentGUI.end=new SwingButton();

		/**
		/**
		 * forward, backward etc and add additional listeners
		 * to make sure it resets extracted items to none
		 **/
		((AbstractButton) currentGUI.first).addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent e) {
				createOnscreenOutlines();
			}
		});
		((AbstractButton) currentGUI.fback).addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent e) {
				createOnscreenOutlines();
			}
		});
		((AbstractButton) currentGUI.back).addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent e) {
				createOnscreenOutlines();
			}
		});
		((AbstractButton) currentGUI.forward).addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent e) {
				createOnscreenOutlines();
			}
		});
		((AbstractButton) currentGUI.fforward).addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent e) {
				createOnscreenOutlines();
			}
		});
		((AbstractButton) currentGUI.end).addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent e) {
				createOnscreenOutlines();
			}
		});
		currentGUI.pageCounter2.addActionListener(new ActionListener(){
			public void actionPerformed(ActionEvent e) {
				createOnscreenOutlines();
			}
		});

		/**
		 * general setup for GUI
		 */
		currentGUI.init(scalingValues,currentCommands,currentPrinter);

		/**
		 * add a title
		 */
		currentGUI.setViewerTitle(null);

		if(!thumbnails.isShownOnscreen())
			currentGUI.setPDFOutlineVisible(false);

		/**
		 * track and display screen co-ordinates and onscreen rectangle
		 */
		mouseHandler.setupExtractor();

		/**
		 * combo boxes on toolbar
		 * */
		currentGUI.addCombo(Messages.getMessage("PdfViewerToolbarScaling.text"), Messages.getMessage("PdfViewerToolbarTooltip.zoomin"), Commands.SCALING);

		currentGUI.addCombo(Messages.getMessage("PdfViewerToolbarRotation.text"), Messages.getMessage("PdfViewerToolbarTooltip.rotation"), Commands.ROTATION);

		//<start-os>
		/**image quality option - allow user to choose between images downsampled
		 * (low memory usage 72 dpi) image hires (high memory usage no downsampling)*/
		currentGUI.addCombo(Messages.getMessage("PdfViewerToolbarImageOp.text")," ",Commands.QUALITY);
		//<end-os>
		
		/**status object on toolbar showing 0 -100 % completion */
		currentGUI.initStatus();

		/**
		 *setup menu options
		 */
		createSwingMenu(false);

		/**
		 * shortcut buttons to press
		 */
		createExtractorButtons(currentGUI.getTopButtonBar());

		/**
		 * zoom,scale,rotation, status,cursor
		 */
		currentGUI.addCursor();

	}

	/**
	 * sets up the buttons on the tool bar which user can click
	 */
	private void createExtractorButtons(JToolBar currentBar1) {

		openButton(currentBar1);

		currentBar1.add(Box.createHorizontalGlue());

		/**snap to grid function*/
		GUIButton snapToGridButton = new SwingButton();
		snapToGridButton.init("/org/jpedal/examples/contentextractor/snapgrid.gif", -1,"Click to snap onto outlines");

		currentBar1.add((AbstractButton) snapToGridButton);

		((AbstractButton) snapToGridButton).addActionListener(new ActionListener() {
		    public void actionPerformed(ActionEvent e) {
		    	mouseHandler.updateRectangle();
		    }
		});

		currentBar1.add(Box.createHorizontalGlue());

		/**snapshot screen function*/
		GUIButton snapshotButton = new SwingButton();
		snapshotButton.init("/org/jpedal/examples/simpleviewer/res/snapshot.gif", -1,"Click to extract selected rectangle");

		currentBar1.add((AbstractButton) snapshotButton);

		((AbstractButton) snapshotButton).addActionListener(new ActionListener() {
		    public void actionPerformed(ActionEvent e) {
		    		if(commonValues.getSelectedFile()!=null && !commonValues.isProcessing() && currentGUI.getRectangle()!=null){
		    			extractContent();
		    			
			    		if(currentGUI.isPDFOutlineVisible())
			    			createThumbnails();
		    		
				}
		    }
		});

		currentBar1.add(Box.createHorizontalGlue());
		currentBar1.add(Box.createHorizontalGlue());

		/**icon to show extracted items*/
		String tooltip="Show extracted items";
		if(showExtractedItems)
			tooltip="Hide extracted items";
		showItemsExtracted.init("/org/jpedal/examples/contentextractor/list.gif", -1,tooltip);
         showItemsExtracted.setVisible(false);

		currentBar1.add((AbstractButton) showItemsExtracted);
		((AbstractButton) showItemsExtracted).addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent e) {//toggle nav bar on/off
			    boolean current=!currentGUI.isPDFOutlineVisible();

			    currentGUI.setPDFOutlineVisible(current);
			    showExtractedItems=!showExtractedItems;

			    if(current){
			    		createThumbnails();
			    }else{
			    	showExtractedItems=false;
			    }
			    //mainFrame.repaint();
			}
		});

		currentBar1.add(Box.createHorizontalGlue());

		/**icon to save extracted items*/
		saveItemsExtracted.init("/org/jpedal/examples/simpleviewer/res/save.gif", -1,"Save extracted items");
		saveItemsExtracted.setVisible(false);

		currentBar1.add((AbstractButton) saveItemsExtracted);
		((AbstractButton) saveItemsExtracted).addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent e) {//toggle nav bar on/off
				boolean saved=saveThumbnails();

				if(saved)
				resetToEmpty(false);

				decode_pdf.setFoundTextAreas(null);

			}
		});

		currentBar1.add(Box.createHorizontalGlue());
	}

	/**save all thumbnails*/
	private boolean saveThumbnails(){

		boolean wasSaved=false;

		//Create a file chooser
		final JFileChooser fc = new JFileChooser(System.getProperty( "user.dir" ) );
		fc.setFileSelectionMode(JFileChooser.DIRECTORIES_ONLY); //directories only

		//In response to a button click:
		int returnVal = fc.showSaveDialog(fc);

		if (returnVal == JFileChooser.APPROVE_OPTION) {

			File file = fc.getSelectedFile();

			String newTarget=file.getAbsolutePath();

			//check it does not exist and tell user if so
			if(file.exists()){
				currentGUI.showMessageDialog("Directory already exists - please use another name");
			}else{ //copy in the files

				file.mkdirs();

				//get contents
				String target=commonValues.getTarget();
				String separator=commonValues.getSeparator();
				if(target!=null){
					File temp_files = new File(target);
					String[] file_list = temp_files.list();
					if (file_list != null) {
						for (int ii = 0; ii < file_list.length; ii++) {

							//if image only copy if in list
							//System.out.println(file_list[ii]+" "+imagesUsed.get(file_list[ii])+" "+imagesUsed);
							if(!(file_list[ii].endsWith("TIFF"))|(imagesUsed.get(file_list[ii])!=null)){
								File delete_file = new File(target + separator+file_list[ii]);
								//System.out.println(ii+" copying "+target + separator+file_list[ii]);
								ObjectStore.copy(target + separator+file_list[ii],newTarget + separator+file_list[ii]);
								delete_file.deleteOnExit();
								delete_file.delete();
								/**File test=new File(newTarget + separator+file_list[ii]);
								if(!test.exists())
									System.out.println("Failed");*/
							}
						}
					}

					wasSaved=true;
				}
			}
		}

		if(wasSaved)
			showExtractedItems=false;

		return wasSaved;
	}	

	/**
	 * open button function (resets highlighted areas as well)
	 */
	private void openButton(JToolBar currentBar1) {
		/**open icon*/
		GUIButton open = new SwingButton();
		open.init("/org/jpedal/examples/simpleviewer/res/open.gif", -1,Messages.getMessage("PdfViewerFileMenuTooltip.open"));

		currentBar1.add((AbstractButton) open);
		((AbstractButton) open).addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent e) {

				commonValues.setProcessing(false);

				boolean dataWillBeOverwritten=true;

				if(itemSelectedCount==1){ //no data
					dataWillBeOverwritten=false;
				}else{ //warn user and allow to stop
					int choice=currentGUI.showConfirmDialog(" ARE YOU SURE you wish to lose the currently selected items?","Extracted Content WILL be overwritten",JOptionPane.YES_NO_OPTION);

					if(choice==JOptionPane.YES_OPTION)
						dataWillBeOverwritten=false;
				}

				/**stop user accidentally trashing items already extracted*/
				if(!dataWillBeOverwritten){
					resetToEmpty(false);

                    currentGUI.setPDFOutlineVisible(false);

                    currentGUI.zoom(false);
                    currentCommands.selectFile();

				}

			}
		});
		currentBar1.add(Box.createHorizontalGlue());
	}

	/**
	 * remove all items and deselect all
	 */
	private void resetToEmpty(boolean saveFirst) {

		if((saveFirst)&&(itemSelectedCount>1)){

			int choice=currentGUI.showConfirmDialog("Do you wish to save it first?","Extracted Content WILL be overwritten",JOptionPane.YES_NO_OPTION);

			if(choice==JOptionPane.YES_OPTION){
				//allow for unsuccessful save
				while(!saveThumbnails()){}
			}
		}

		/** clean up GUI*/
		showExtractedItems=false;

	    showItemsExtracted.setVisible(false);
	    saveItemsExtracted.setVisible(false);

		/**reset all values*/
		itemSelectedX1=new Vector_Int(10);
		itemSelectedY1=new Vector_Int(10);
		itemSelectedWidth=new Vector_Int(10);
		itemSelectedHeight=new Vector_Int(10);
		itemSelectedCount=1;
		pageUsed=new Vector_Int(10);
		imagesStored=new Vector_String(10);
		imagesUsed=new HashMap();
		thumbnailsStored=new Vector_Object(10);
		textStored=new Vector_String(10);
		xmlStored=new Vector_String(10);

		//flush any objects on disk
	 	currentCommands.flush();

		//flush any highlight
        decode_pdf.removeHiglightedObject();
        decode_pdf.setHighlightedZones(0,null,null,null,null,null,null,null,null,null,null,null,null,null);
        decode_pdf.updateCursorBoxOnScreen(null,null);

        thumbnails.generateOtherThumbnails(imagesStored.get(),thumbnailsStored);

        currentGUI.setSplitDividerLocation(0);
        decode_pdf.repaint();

	}

	/**
	 * extract text from selected rectangle, popup onscreen and store for use
	 */
	private void extractContent() {

		BufferedImage snapShot=null;

		/**get the xml*/
		String xmlText=null,text=null;

		/** extraction code */
        try {

			PdfGroupingAlgorithms currentGrouping =decode_pdf.getGroupingObject();
			 /**ensure co-ords in right order*/

	        int t_x1=commonValues.m_x1;
	        int t_x2=commonValues.m_x2;
	        int t_y1=commonValues.m_y1;
	        int t_y2=commonValues.m_y2;

	        if(t_y1<t_y2){
	            t_y2=commonValues.m_y1;
	            t_y1=commonValues.m_y2;
	        }

	        if(t_x1>t_x2){
	            t_x2=commonValues.m_x1;
	            t_x1=commonValues.m_x2;
	        }

			//get XML
	        xmlText = currentGrouping.extractTextInRectangle(t_x1-3, t_y1+3, t_x2+6, t_y2-6, commonValues.getCurrentPage(), false,true);

	        // get text
	        if(xmlText!=null)
	        		text=Strip.stripXML(xmlText).toString();

		} catch (PdfException e) {
			e.printStackTrace();
		}

        //get the image
		snapShot=extractSelectedScreenAsImage();


		popupDisplay(snapShot, xmlText, text,itemSelectedCount,true,false);
	}

    /**
	 * popup window with content so user can accept and edit
	 */
	private void popupDisplay(final BufferedImage snapShot, String xmlText, String text,final int id,final boolean calculateLocations,boolean isResave) {

		JTabbedPane display=new JTabbedPane();
		final JTextPane textPane=new JTextPane();
		final JTextPane xmlPane=new JTextPane();

		/**
         * get image and put image in panel
         */
		JPanel image_display = new JPanel();
		image_display.setLayout( new BorderLayout() );

		//wrap image so we can display
		if( snapShot != null )//add image if there is one
			image_display.add( new JLabel( new IconiseImage( snapShot ) ), BorderLayout.CENTER );
		else
			return;

		//display in scroll pane so size controlled
		JScrollPane imageScroll = new JScrollPane();
		imageScroll.setVerticalScrollBarPolicy(JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED);
		imageScroll.setHorizontalScrollBarPolicy(JScrollPane.HORIZONTAL_SCROLLBAR_AS_NEEDED);
		imageScroll.getVerticalScrollBar().setUnitIncrement(80);
		imageScroll.getHorizontalScrollBar().setUnitIncrement(80);
		imageScroll.getViewport().add( image_display );
		imageScroll.setPreferredSize(new Dimension(400,400));

		/**pop-up tab with content*/
		display.addTab("Image",imageScroll);

		/**
		 * display the text
		 */
		if(xmlText!=null){
	        JScrollPane scroll=new JScrollPane();
            try {
                scroll = currentGUI.createPane(xmlPane,xmlText,  true);
            } catch (BadLocationException e1) {
                e1.printStackTrace();
            }
            scroll.setHorizontalScrollBarPolicy(JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
            scroll.setVerticalScrollBarPolicy(JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED);
            scroll.setPreferredSize(new Dimension(400,400));

            display.addTab("XML",scroll);
        }

		if(text!=null){

	        JScrollPane scroll1=new JScrollPane();
	        try {
	            scroll1 = currentGUI.createPane(textPane,text,  true);
	        } catch (BadLocationException e1) {
	            e1.printStackTrace();
	        }

	        scroll1.setHorizontalScrollBarPolicy(JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
	        scroll1.setVerticalScrollBarPolicy(JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED);
	        scroll1.setPreferredSize(new Dimension(400,400));

	        display.addTab("Text",scroll1);
		}

		String message="Extracted Content - would you like to accept?";
		if(isResave)
			message="Would you like to save any changes you have made to the current item?";

		/**resizeable pop-up for content*/
		final JDialog displayFrame=new JDialog(currentGUI.getFrame(),true);

		displayFrame.setLocationRelativeTo(null);
		displayFrame.setLocation(currentGUI.getFrame().getLocationOnScreen().x+10,currentGUI.getFrame().getLocationOnScreen().y+10);
		displayFrame.setSize(450,450);
		displayFrame.setTitle(message);
		displayFrame.getContentPane().setLayout(new BorderLayout());
		displayFrame.getContentPane().add(display,BorderLayout.CENTER);

		JPanel buttonBar=new JPanel();
		buttonBar.setLayout(new BorderLayout());
		displayFrame.getContentPane().add(buttonBar,BorderLayout.SOUTH);

		/**
		 * yes option allows user to save content
		 */
		JButton yes=new JButton(Messages.getMessage("PdfMessage.Yes"));
		yes.setFont(new Font("SansSerif", Font.PLAIN, 12));
		buttonBar.add(yes,BorderLayout.WEST);
		yes.addActionListener(new ActionListener(){

			public void actionPerformed(ActionEvent e) {

				String extractedtext="",finalxmlText="";
//				store text internally so can be edited again without reload
				extractedtext=textPane.getText();
				finalxmlText=xmlPane.getText();

				if(calculateLocations){
					textStored.addElement(extractedtext);
		        		xmlStored.addElement(finalxmlText);
				}else{
		        		textStored.setElementAt(extractedtext,id-1);
		        		xmlStored.setElementAt(finalxmlText,id-1);
				}

				//store on disk so we can write out at end

		        saveExtractedContent(snapShot,finalxmlText,extractedtext,id,calculateLocations);


				//switch on button
				if(itemSelectedCount==1){
					showItemsExtracted.setVisible(true);
					saveItemsExtracted.setVisible(true);
				}

				/**save co-ords*/
				if(calculateLocations){
					itemSelectedCount++;
					itemSelectedX1.addElement(commonValues.m_x1);
					itemSelectedY1.addElement(commonValues.m_y1);
					itemSelectedWidth.addElement(commonValues.m_x2);
					itemSelectedHeight.addElement(commonValues.m_y2);
				}

				/**
				 * setup variables
				 */
				if(calculateLocations)
					createOnscreenOutlines();

				displayFrame.dispose();
			}
		});

		/**
		 * no option just removes display
		 */
		JButton no=new JButton(Messages.getMessage("PdfMessage.No"));
		no.setFont(new Font("SansSerif", Font.PLAIN, 12));
		buttonBar.add(no,BorderLayout.EAST);
		no.addActionListener(new ActionListener(){

			public void actionPerformed(ActionEvent e) {
				displayFrame.dispose();
			}});

		/**show the popup*/
		displayFrame.setVisible(true);

	}


	/**
	 *setup and apply outlines for currentGUI.getFrame() page
	 */
	private void createOnscreenOutlines() {

		int[] x1=itemSelectedX1.get();
		int[] y1=itemSelectedY1.get();
		int[] x2=itemSelectedWidth.get();
		int[] y2=itemSelectedHeight.get();

        int[] order=new int[itemSelectedCount];
		boolean[] highlightedZonesSelected=new boolean[itemSelectedCount];

        //graphical info on regexp used
        int[] processedByRegularExpression=new int[itemSelectedCount];

        Rectangle2D[] outlineZone=new Rectangle2D[itemSelectedCount];
		Shape[] fragmentShapes=new Shape[itemSelectedCount];
		Color[] fragmentColorCoding=new Color[itemSelectedCount];

		int[] plotNumberX=new int[itemSelectedCount];
		int[] plotNumberY=new int[itemSelectedCount];

		for (int i = 0; i < itemSelectedCount; i++) {

			order[i]=i;

			highlightedZonesSelected[i]=true;

			//outlines
			outlineZone[i]=new Rectangle(x1[i],y2[i],(x2[i]-x1[i]),(y1[i]-y2[i]));

			/**story co-ords*/
			plotNumberX[i]=(int)((outlineZone[i].getBounds().getMinX()+outlineZone[i].getBounds().getMaxX())/2);
			plotNumberY[i]=(int)((outlineZone[i].getBounds().getMinY()+outlineZone[i].getBounds().getMaxY())/2)-12;

			if(pageUsed.elementAt(i)==commonValues.getCurrentPage())
			fragmentShapes[i]=outlineZone[i];

			fragmentColorCoding[i]=Color.BLUE;

		}

		currentGUI.setRectangle(null);

		decode_pdf.setHighlightedZones(PdfPanel.SHOW_OBJECTS,plotNumberX,plotNumberY,fragmentShapes,
				null,null,null,null,
				outlineZone,highlightedZonesSelected,null,
				fragmentColorCoding,order,processedByRegularExpression);
		decode_pdf.repaint();
	}

	/**
	 * store extracted content on disk until needed
	 */
	private void saveExtractedContent(BufferedImage snapShot,String xml,String text,int id,boolean isSave) {

		String target=commonValues.getTarget();
		String separator=commonValues.getSeparator();
		/**
		 * setup local store
		 */
		if(target==null){
			File tmp;
			try {
				tmp = File.createTempFile("jpedal","ads");
				File dir=new File(tmp.getAbsolutePath()+"-files");
				dir.mkdirs();
				dir.deleteOnExit();
				target=dir.getAbsolutePath();
				commonValues.setTarget(target);
				tmp.delete();
			} catch (IOException e) {
				e.printStackTrace();
			}
		}

		/**
		 * Do the actual save of image
		 */
		if((target!=null)){
			if(!isSave){
			//	;
			}else if(snapShot!=null){

				String name="page_"+commonValues.getCurrentPage()+"_id"+id+ '.' +type;
				String fileLocation=target+separator+name;
				javax.media.jai.JAI.create("filestore", snapShot, fileLocation, type);
				thumbnailsStored.addElement(snapShot);
				imagesStored.addElement(fileLocation);
				imagesUsed.put(name,"x");
			}else{
				imagesStored.addElement(null);
				thumbnailsStored.addElement(null);
			}
		}else
			currentGUI.showInputDialog("Problem accessing drive - unable to save images");


		int page=commonValues.getCurrentPage();
		if(!isSave){
			page=pageUsed.elementAt(id-1);
		}else{
			/**save page*/
			pageUsed.addElement(page);
		}

		/**
		 * save the text
		 */
	    	try{
			writeText(text,target,id,page);
		} catch (Exception e) {
			e.printStackTrace();
			currentGUI.showInputDialog("Problem saving text");
		}

		/**
	     * save the XML
	     */
		try{
			writeXML(xml,target,id,page);
		} catch (Exception e) {
			e.printStackTrace();
			currentGUI.showMessageDialog("Problem saving xml");
		}
	}

	/**
	 * save text into file on drive
	 * @throws FileNotFoundException
	  */
	private void writeText(String text,String target,int id,int currentPage) throws Exception{

		OutputStreamWriter output_stream =
			new OutputStreamWriter(
				new FileOutputStream(target+commonValues.getSeparator()+"page_"+currentPage+"_id"+id + ".txt"));

		output_stream.write(text); //write actual data
		output_stream.close();

	}

	/**
     * write out a story to disk. Set textData to null if you are in automated
     * mode.
     */
    public void writeXML(String xml,String target,int id,int currentPage) throws Exception {

        /**
         * text output
         */
        try{
	        //create doc and set root
        	DocumentBuilderFactory dbf = DocumentBuilderFactory.newInstance();
			DocumentBuilder db = dbf.newDocumentBuilder();
			Document doc = db.newDocument();

	        Element root = doc.createElement("content");
	        doc.appendChild(root);

	        //add comments
	        Node creation=doc.createComment("Created "+org.jpedal.utils.TimeNow.getShortTimeNow());
	        Node version=doc.createComment("Extracted via JPedal");
	        Node source=doc.createComment("SourceFile "+decode_pdf.getObjectStore().getCurrentFilename());
	        root.appendChild(creation);
	        root.appendChild(version);
	        root.appendChild(source);

	        Element currentElement=addTextAsXML(xml, doc,root);
	        Node textNode=doc.importNode(currentElement,true); //needed to 'detach' and reattach
	        root.appendChild(textNode);

	        //@use System.out for FileOutputStream to see on screen
	        InputStream stylesheet =decode_pdf.getClass().getResourceAsStream("/org/jpedal/examples/simpleviewer/res/xmlstyle.xslt");

			TransformerFactory transformerFactory = TransformerFactory.newInstance();
			Transformer transformer = transformerFactory.newTransformer(new StreamSource(stylesheet));
			transformer.transform(new DOMSource(doc), new StreamResult(target+commonValues.getSeparator()+"page_"+currentPage+"_id" +id+".xml"));

        }catch(Exception e){
        		e.printStackTrace();
        }

    }

    /**
	 * convert text fragment to XML so tokens correctly encoded
	 */
	private Element addTextAsXML(String textData, Document doc, Node root) throws FactoryConfigurationError {

		Element currentElement=null;

		try{
			/**
			 * convert text to XML fragment in UTF8
			 */

			//make it UTF8 'safe'
			ByteArrayOutputStream bos=new ByteArrayOutputStream();
			OutputStreamWriter out = new OutputStreamWriter(bos, "UTF-8");
			out.write("<?xml version=\"1.0\"?><text>");

			//strip out null characters and convert illegal characters
			int ll=textData.length();
			for(int i=0;i<ll;i++){
				char c=textData.charAt(i);
				if(c=='&'){ //see if html escape char
					boolean isTag=false;
					for(int j=i+1;j<ll;j++){
						char nextC=textData.charAt(i);
						if(nextC==';'){
							j=ll;
							isTag=true;
						}else if((nextC==' ')|(nextC=='\n')){
							j=ll;
						}
					}

					if(isTag)
						out.write(c);
					else
						out.write("&amp;");
				}else if(c>0)
					out.write(c);

			}
			out.write("</text>");

			out.close();
			bos.close();

			//parse
			ByteArrayInputStream bos2=new ByteArrayInputStream(bos.toByteArray());
			DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
			org.w3c.dom.Document dataDoc = factory.newDocumentBuilder().parse(bos2);

			//get node and add to output tree
			NodeList nodes = dataDoc.getElementsByTagName("text");
			currentElement = (Element) nodes.item(0);
			bos.close();
		}catch(Exception e){
			LogWriter.writeLog("Exception "+e+" in writing text \n"+textData);

		}

		return currentElement;
	}

	/**
	 * extract selected area as a rectangle and show onscreen
	 */
	private BufferedImage extractSelectedScreenAsImage() {

		/**ensure co-ords in right order*/
        int t_x1=commonValues.m_x1;
        int t_x2=commonValues.m_x2;
        int t_y1=commonValues.m_y1;
        int t_y2=commonValues.m_y2;

        if(t_y1<t_y2){
            t_y2=commonValues.m_y1;
            t_y1=commonValues.m_y2;
        }

        if(t_x1>t_x2){
            t_x2=commonValues.m_x1;
            t_x1=commonValues.m_x2;
        }

        return decode_pdf.getSelectedRectangleOnscreen(t_x1,t_y1,t_x2,t_y2,100);

	}
	/**
	 * if setup, put up a list onscreen of thumbnails to click on
	 */
	private void createThumbnails() {

        //thumbnails.
		currentGUI.initThumbnails(itemSelectedCount,pageUsed);
		thumbnails.generateOtherThumbnails(imagesStored.get(),thumbnailsStored);

		//add the listeners
		Object[] buttons=thumbnails.getButtons();
		for(int i=0;i<itemSelectedCount-1;i++){
			JButton eachThumb=(JButton)buttons[i];
			eachThumb.addActionListener(new ButtonPopup(i));
		}
	}


	/**
	 * popup the ads with image and text when icon clicked
	 */
	public class ButtonPopup implements ActionListener {

		int id;

		JButton[] buttons;

		/**
		 * store id
		 */
		public ButtonPopup(int i) {

			id=i;
		}

		public void actionPerformed(ActionEvent arg0) {
			//if(((JButton)arg0).isSelected())

			thumbnails.resetHighlightedThumbnail(id);
			thumbnails.refreshDisplay();
			BufferedImage img=javax.media.jai.JAI.create("fileload",imagesStored.elementAt(id)).getAsBufferedImage();
			popupDisplay(img, xmlStored.elementAt(id),textStored.elementAt(id), id+1,false,true);			
		}
	}
}
