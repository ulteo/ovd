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
* TableZoner.java
* ---------------
*/
package org.jpedal.examples.tablezoning;

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
import java.io.File;
import java.io.IOException;
import java.io.InputStream;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import javax.swing.Box;
import javax.swing.JButton;
import javax.swing.JFileChooser;

import javax.swing.AbstractButton;
import javax.swing.ButtonGroup;
import javax.swing.JDialog;
import javax.swing.JFrame;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JRadioButton;
import javax.swing.JScrollPane;
import javax.swing.JTabbedPane;
import javax.swing.JTextPane;
import javax.swing.JToolBar;
import javax.swing.UIManager;

import javax.swing.text.BadLocationException;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;
import javax.xml.transform.stream.StreamSource;

import org.jpedal.PdfPanel;
import org.jpedal.examples.simpleviewer.Commands;
import org.jpedal.examples.simpleviewer.SimpleViewer;
import org.jpedal.examples.simpleviewer.Values;
import org.jpedal.examples.simpleviewer.gui.generic.GUIButton;
import org.jpedal.examples.simpleviewer.gui.swing.SwingButton;
import org.jpedal.examples.simpleviewer.utils.FileFilterer;
import org.jpedal.examples.tablezoning.ExtractTextTableFromZones;
import org.jpedal.exception.PdfException;

import org.jpedal.grouping.PdfGroupingAlgorithms;

import org.jpedal.io.JAIHelper;import org.jpedal.io.ObjectStore;

import org.jpedal.utils.LogWriter;
import org.jpedal.utils.Messages;
import org.jpedal.utils.repositories.Vector_Int;
import org.jpedal.utils.repositories.Vector_Object;
import org.jpedal.utils.repositories.Vector_String;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;


/**
 * example client application to set up areas where tables can be extracted from positions
 * on page using JPedal (for example table extraction).
 * (Assumes table has equal width columns)
 * Program can extract tables as .csv files. User will require a program to manipulate .csv
 * files if they wish to use the tables further.
 *
 * Notes: currentGUI.getFrame() example uses some "unofficial" classes inside the library which may alter in function
 */
public class TableZoner extends SimpleViewer{

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

	//Create xml / data buttons to alter what is opened
	private final JRadioButton xmlButton = new JRadioButton("Xml");
	private final JRadioButton dataButton = new JRadioButton("Data");

	public boolean showSaveFunction= false;

	private String xml_file = "";

	private String pdf_file = "";
	
	private boolean xmlSaveRequired = false;
	
	private boolean xmlFileChanged = false;

	private static String OutputDir = System.getProperty("user.home")+System.getProperty("file.separator")+"tables";
	
	public TableZoner() {

		JAIHelper.useJAI(true);

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
		TableZoner current = new TableZoner();
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
			setupOutput();
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

		ButtonGroup group = new ButtonGroup();
		group.add(xmlButton);
		group.add(dataButton);
		xmlButton.setEnabled(false);
		dataButton.setSelected(true);

		currentGUI.getTopButtonBar().add(xmlButton);
		currentGUI.getTopButtonBar().add(dataButton);

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

				//if(saved)
				//resetToEmpty(false);

				decode_pdf.setFoundTextAreas(null);

			}
		});

		currentBar1.add(Box.createHorizontalGlue());
	}

	/**save all thumbnails*/
	private boolean saveThumbnails(){

		boolean wasSaved=false;
		
		if((xml_file.length() == 0 && dataButton.isSelected()) || xmlFileChanged)
			xmlSaveRequired = true;
		
		if(!dataButton.isSelected() || xmlSaveRequired){

			//Create a file chooser
			final JFileChooser fc = new JFileChooser(System.getProperty( "user.dir" ) );
			fc.setFileSelectionMode(JFileChooser.FILES_ONLY);

			String[] xml = new String[] { "xml" };
			fc.addChoosableFileFilter(new FileFilterer(xml, "Xml (*.xml)"));		

			//In response to a button click:
			int returnVal = fc.showSaveDialog(fc);

			if (returnVal == JFileChooser.APPROVE_OPTION) {

				File file = fc.getSelectedFile();

				String newTarget=file.getAbsolutePath();

				int result=0;
				//check it does not exist and tell user if so
				if(file.exists())
					result = currentGUI.showConfirmDialog("Are you sure you wish to replace this file?", "Confirm Overwrite", 1);

				if(result==0){ //copy in the files

					if(!file.getAbsoluteFile().toString().endsWith(".xml")){
						file = new File(file.getAbsoluteFile().toString()+".xml");
					}

					try {
						file.createNewFile();
						

						//get contents
						String target=commonValues.getTarget();
						String separator=commonValues.getSeparator();
						if(target!=null){
							File temp_files = new File(target);
							String[] file_list = temp_files.list();
							if (file_list != null) {
								for (int ii = 0; ii < file_list.length; ii++) {

									File delete_file = new File(target + separator+file_list[ii]);
									//System.out.println(ii+" copying "+target + separator+file_list[ii]);
									ObjectStore.copy(target + separator+"TablesExtracted.xml",file.getAbsolutePath());
									delete_file.deleteOnExit();
									delete_file.delete();
								}
							}
							xml_file = file.getAbsolutePath();
							wasSaved=true;
							xmlFileChanged = false;
						}
					} catch (IOException e) {
						// TODO Auto-generated catch block
						e.printStackTrace();
					}
				}
			}
			if(xmlSaveRequired==true){
				xmlSaveRequired = false;
				saveThumbnails();
			}
		}else{
			ExtractTextTableFromZones zones = new ExtractTextTableFromZones();
			if(xml_file.length() != 0){
				
				final JFileChooser fc = new JFileChooser(System.getProperty( "user.dir" ) );
				fc.setFileSelectionMode(JFileChooser.DIRECTORIES_ONLY);

				//In response to a button click:
				int returnVal = fc.showSaveDialog(fc);

				if (returnVal == JFileChooser.APPROVE_OPTION) {
					File file = fc.getSelectedFile();
					OutputDir = file.getAbsolutePath();
				}
				
				
				String[] args = {pdf_file,xml_file,OutputDir};
				ExtractTextTableFromZones.main(args);
				JOptionPane.showMessageDialog(new JFrame(), "All Tables Extracted to :: "+OutputDir);
			}else{
				xmlSaveRequired = true;
				saveThumbnails();
			}

		}
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

				if(dataButton.isSelected()){
					xmlButton.enable();
					commonValues.setProcessing(false);

					boolean dataWillBeOverwritten=true;

					if(itemSelectedCount==1){ //no data
						dataWillBeOverwritten=false;
					}else{ //warn user and allow to stop
						int choice=currentGUI.showConfirmDialog("ARE YOU SURE you wish to lose the currently selected items?","Extracted Content WILL be overwritten",JOptionPane.YES_NO_OPTION);

						if(choice==JOptionPane.YES_OPTION)
							dataWillBeOverwritten=false;
					}

					/**stop user accidentally trashing items already extracted*/
					if(!dataWillBeOverwritten){
						resetToEmpty(false);

						currentGUI.setPDFOutlineVisible(false);

						currentGUI.zoom(false);
						currentCommands.selectFile();
						xmlButton.setEnabled(true);
						xmlButton.setSelected(true);
						pdf_file = commonValues.getSelectedFile();
					}
				}else{

					boolean tableData = false;
					int[] itemSelectedPage = new int[1];
					final JFileChooser chooser = new JFileChooser(commonValues.getInputDir());
					if(commonValues.getSelectedFile()!=null)
						chooser.setSelectedFile(new File(commonValues.getSelectedFile()));
					chooser.setFileSelectionMode(JFileChooser.FILES_ONLY);

					String[] xml = new String[] { "xml" };
					chooser.addChoosableFileFilter(new FileFilterer(xml, "Xml (*.xml)"));

					chooser.showOpenDialog(currentGUI.getFrame());
					final File file = chooser.getSelectedFile();

					if(file!=null && file.exists()){
						setupOutput();
						String  target = commonValues.getTarget();
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
							}catch(Exception ex){

                                LogWriter.writeLog("[PDF] Error deleting file");

                            }
						}
						try{
							DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
							Document doc = factory.newDocumentBuilder().parse(file);

							/**
							 * get the values and extract info
							 */
							NodeList Allnodes=doc.getChildNodes();
							NodeList nodes=doc.getElementsByTagName("TablePositions");
							Element currentElement = (Element) nodes.item(0);
							if(currentElement!=null){

								NodeList catNodes=currentElement.getChildNodes();

								List catValues = getChildValues(catNodes);

								/**use to set values*/
								int size=catValues.size();

								itemSelectedCount=0;
								itemSelectedX1.clear();
								itemSelectedY1.clear();
								itemSelectedWidth.clear();
								itemSelectedHeight.clear();

								int i=0;

								Element next=(Element) catValues.get(i);
								String key=next.getNodeName();
								String value=next.getAttribute("value");
								if(key.endsWith("Count")){
									itemSelectedCount = Integer.parseInt(value);
									itemSelectedPage = new int[itemSelectedCount];
								}
								int pages = 0;
								for(i=0;i<size;i++){
									next=(Element) catValues.get(i);
									key=next.getNodeName();
									value=next.getAttribute("value");

									if(key.endsWith("page")){
										itemSelectedPage[pages] = Integer.parseInt(value);
										pages++;
									}
									if(key.endsWith("x1"))
										itemSelectedX1.addElement(Integer.parseInt(value));
									if(key.endsWith("y1"))
										itemSelectedY1.addElement(Integer.parseInt(value));
									if(key.endsWith("x2"))
										itemSelectedWidth.addElement(Integer.parseInt(value));
									if(key.endsWith("y2"))
										itemSelectedHeight.addElement(Integer.parseInt(value));
								}
								int[][] openValues = new int[itemSelectedCount][5];
								i=0;
								int j=0;
								int[] x1 = itemSelectedX1.get();
								int[] x2 = itemSelectedWidth.get();
								int[] y1 = itemSelectedY1.get();
								int[] y2 = itemSelectedHeight.get();
								while(i<itemSelectedCount){

									openValues[i][0]=itemSelectedPage[i];
									openValues[i][1]=x1[i];
									openValues[i][2]=x2[i];
									openValues[i][3]=y1[i];
									openValues[i][4]=y2[i];
									values = openValues[i];

									BufferedImage snapShot=extractSelectedScreenAsImage();

									if((commonValues.getTarget()!=null)){
										if(snapShot!=null){
											String name="page_"+commonValues.getCurrentPage()+"_id_"+i+type;
											String fileLocation=commonValues.getTarget()+System.getProperty("separator")+name;
											javax.media.jai.JAI.create("filestore", snapShot, fileLocation, type);
											imagesStored.addElement(fileLocation);
											imagesUsed.put(name,"x");
										}else{
											imagesStored.addElement(null);
											thumbnailsStored.addElement(null);
										}
									}else
										currentGUI.showInputDialog("Problem accessing drive - unable to save images");



									writeXML(target);
									i++;
								}
								tableData = true;
							}
						}catch(Exception ex){
							ex.printStackTrace();
						}
						if(tableData){
							int i =0;
							int currentPage=0;
							currentPage = commonValues.getCurrentPage();
							while(i<itemSelectedCount){
								commonValues.setCurrentPage(itemSelectedPage[i]);
								int page = commonValues.getCurrentPage();
								if(page==0)
									page=1;
								pageUsed.addElement(page);
								i++;
							}
							commonValues.setCurrentPage(currentPage);
							showItemsExtracted.setVisible(true);
							saveItemsExtracted.setVisible(true);
							itemSelectedCount++;
							createOnscreenOutlines();
						}else{
							JOptionPane jop = new JOptionPane();
							JOptionPane.showMessageDialog(new JFrame(),"This file does not contain Table Position Data.");
						}
						xml_file = file.getAbsolutePath();
					}else{
						decode_pdf.repaint();
						currentGUI.showMessageDialog( Messages.getMessage("PdfViewerMessage.NoSelection"));
					}
				}
			}

		});
		currentBar1.add(Box.createHorizontalGlue());
	}

	protected List getChildValues(NodeList catNodes) {
		/**
		 * get all valid elements into list
		 */
		List catValues=new ArrayList();
		int items=catNodes.getLength();
		for(int i=0;i<items;i++){
			Node next=catNodes.item(i);

			if(next instanceof Element)
				catValues.add(next);
		}
		return catValues;
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
		String xmlText=null;

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

		} catch (PdfException e) {
			e.printStackTrace();
		}

		//get the image
		snapShot=extractSelectedScreenAsImage();

		popupDisplay(snapShot, xmlText, itemSelectedCount,true,false);
	}

	public int values[] = new int[5];
	public String key[] ={"page","x1","x2","y1","y2"};// new String[5];

	/**
	 * popup window with content so user can accept and edit
	 */
	private void popupDisplay(final BufferedImage snapShot,String xmlText,final int id,final boolean calculateLocations,boolean isResave) {

		//Convert XML to table
		//rest to default in case text option selected
		boolean isXML=true;
		PdfGroupingAlgorithms currentGrouping = null;
		try {
			currentGrouping = decode_pdf.getGroupingObject();
		} catch (PdfException e3) {
			// TODO Auto-generated catch block
			e3.printStackTrace();
		}

		Map content = null;

		/**
		 * find out if xml or text - as we need to turn xml off before
		 * extraction. So we assume xml and strip out. This is obviously
		 */
		int useCSV=currentGUI.showConfirmDialog(Messages.getMessage("PdfViewerXHTML.message"),
				Messages.getMessage("PdfViewerOutputFormat.message"),
				JOptionPane.YES_NO_OPTION);


		try {
			if(useCSV!=0)
				content = currentGrouping.extractTextAsTable(commonValues.m_x1,
						commonValues.m_y1, commonValues.m_x2, commonValues.m_y2, commonValues.getCurrentPage(), true, false,
						false, false, 0, false);
			else
				content = currentGrouping.extractTextAsTable(commonValues.m_x1,
						commonValues.m_y1, commonValues.m_x2, commonValues.m_y2, commonValues.getCurrentPage(), false, true,
						true, false, 1, false);
		} catch (PdfException e2) {
			// TODO Auto-generated catch block
			e2.printStackTrace();
		}


		xmlText = (String) content.get("content");


		JTabbedPane display=new JTabbedPane();
		final JTextPane textPane=new JTextPane();
		final JTextPane xmlPane=new JTextPane();
		final JPanel buttonBar ;//= new JPanel();
		final JDialog displayFrame;// = new JDialog();;

		if (xmlText != null) {
			JScrollPane scroll=new JScrollPane();
			try {
				JTextPane text_pane=new JTextPane();
				scroll = currentGUI.createPane(text_pane,xmlText,  true);
			} catch (BadLocationException e1) {
				e1.printStackTrace();
			}
			scroll.setHorizontalScrollBarPolicy(JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
			scroll.setVerticalScrollBarPolicy(JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED);
			scroll.setPreferredSize(new Dimension(400,400));

			/**resizeable pop-up for content*/
			displayFrame =  new JDialog(currentGUI.getFrame(),true);
			if(commonValues.getModeOfOperation()!=Values.RUNNING_APPLET){
				JFrame frame = currentGUI.getFrame();
				displayFrame.setLocation(frame.getLocationOnScreen().x+10,frame.getLocationOnScreen().y+10);
			}

			displayFrame.setSize(450,450);

			displayFrame.setTitle(Messages.getMessage("PdfViewerExtractedText.menu"));
			displayFrame.getContentPane().setLayout(new BorderLayout());
			displayFrame.getContentPane().add(scroll,BorderLayout.CENTER);
			buttonBar=new JPanel();
			buttonBar.setLayout(new BorderLayout());
			displayFrame.getContentPane().add(buttonBar,BorderLayout.SOUTH);



			String message="Extracted Content - would you like to accept?";
			if(isResave)
				message="Would you like to save any changes you have made to the current item?";

			/**
			 * yes option allows user to save content
			 */
			JButton yes=new JButton(Messages.getMessage("PdfMessage.Yes"));
			yes.setFont(new Font("SansSerif", Font.PLAIN, 12));
			buttonBar.add(yes,BorderLayout.WEST);
			yes.addActionListener(new ActionListener(){

				public void actionPerformed(ActionEvent e) {

					String extractedtext="",finalxmlText="";
//					store text internally so can be edited again without reload
					extractedtext=textPane.getText();
					finalxmlText=xmlPane.getText();

					/**Print out Values for co-ords and page*/
					values[0] = commonValues.getCurrentPage();
					values[1] = commonValues.m_x1;
					values[2] = commonValues.m_x2;
					values[3] = commonValues.m_y1;
					values[4] = commonValues.m_y2;

					if(calculateLocations){
						textStored.addElement(extractedtext);
						xmlStored.addElement(finalxmlText);
					}else{
						textStored.setElementAt(extractedtext,id-1);
						xmlStored.setElementAt(finalxmlText,id-1);
					}

					//store on disk so we can write out at end
					saveExtractedContent(snapShot,finalxmlText,id,calculateLocations);

					//switch on button
					if(itemSelectedCount==1){
						showItemsExtracted.setVisible(true);
						saveItemsExtracted.setVisible(true);
					}

					/**save co-ords*/
					if(calculateLocations){
						itemSelectedCount++;
						itemSelectedX1.addElement(commonValues.m_x1);
						itemSelectedWidth.addElement(commonValues.m_x2);
						itemSelectedY1.addElement(commonValues.m_y1);
						itemSelectedHeight.addElement(commonValues.m_y2);
					}

					/**
					 * setup variables
					 */
					if(calculateLocations)
						createOnscreenOutlines();
					xmlFileChanged = true;
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
				}
			});

			/**show the popup*/
			displayFrame.setVisible(true);
		}
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
	private void saveExtractedContent(BufferedImage snapShot,String xml,int id,boolean isSave) {

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
				imagesStored.addElement(fileLocation);
				imagesUsed.put(name,"x");
				thumbnailsStored.addElement(snapShot);
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

		try{
			writeText(text,target,id,page);
		} catch (Exception e) {
			e.printStackTrace();
			currentGUI.showInputDialog("Problem saving text");
		}*/

		/**
		 * save the XML
		 */
		try{
			writeXML(target);
		} catch (Exception e) {
			e.printStackTrace();
			currentGUI.showMessageDialog("Problem saving xml");
		}
	}

	/**
	 * write out a story to disk. Set textData to null if you are in automated
	 * mode.
	 */

	public DocumentBuilderFactory dbf;
	public DocumentBuilder db;
	public Document doc;
	public Element root;
	public Element count;

	public void setupOutput(){

		try {
//			create doc and set root
			dbf = DocumentBuilderFactory.newInstance();
			db = dbf.newDocumentBuilder();
			doc = db.newDocument();

			//add comments
			Node creation=doc.createComment("Created "+org.jpedal.utils.TimeNow.getShortTimeNow());
			Node version=doc.createComment("Extracted via JPedal");
			Node source=doc.createComment("SourceFile "+decode_pdf.getObjectStore().getCurrentFilename());
			doc.appendChild(creation);
			doc.appendChild(version);
			doc.appendChild(source);

			root = doc.createElement("TablePositions");
			doc.appendChild(root);

			count=doc.createElement("Count");
			count.setAttribute("value", String.valueOf(itemSelectedCount));
			root.appendChild(count);

		} catch (ParserConfigurationException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}

	}

	public int coords = 0;

	public void writeXML(String target) throws Exception {

		/**
		 * text output
		 */
		try{
			int i=0;
			count.setAttribute("value", String.valueOf(itemSelectedCount));

			while(i<values.length){
				Element currentElement=doc.createElement("loc_"+coords+ '_' +key[i]);
				currentElement.setAttribute("value", String.valueOf(values[i]));
				Node textNode=doc.importNode(currentElement,true); //needed to 'detach' and reattach
				root.appendChild(textNode);
				i++;
			}
			coords++;
			//@use System.out for FileOutputStream to see on screen
			InputStream stylesheet =decode_pdf.getClass().getResourceAsStream("/org/jpedal/examples/simpleviewer/res/xmlstyle.xslt");

			TransformerFactory transformerFactory = TransformerFactory.newInstance();
			Transformer transformer = transformerFactory.newTransformer(new StreamSource(stylesheet));
			transformer.transform(new DOMSource(doc), new StreamResult(target+commonValues.getSeparator()+"TablesExtracted.xml"));

		}catch(Exception e){
			e.printStackTrace();
		}

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
			popupDisplay(img,xmlStored.elementAt(id), id+1,false,true);			
		}
	}
}
