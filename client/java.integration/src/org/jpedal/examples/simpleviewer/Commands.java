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
 * Commands.java
 * ---------------
 */
package org.jpedal.examples.simpleviewer;

import org.jpedal.Display;
import org.jpedal.PdfDecoder;
import org.jpedal.examples.simpleviewer.gui.SwingGUI;
import org.jpedal.examples.simpleviewer.gui.generic.GUISearchWindow;
import org.jpedal.examples.simpleviewer.gui.generic.GUIThumbnailPanel;
import org.jpedal.examples.simpleviewer.gui.popups.*;
import org.jpedal.examples.simpleviewer.gui.swing.SearchList;
import org.jpedal.examples.simpleviewer.utils.*;
import org.jpedal.exception.PdfException;
import org.jpedal.grouping.PdfGroupingAlgorithms;
import org.jpedal.grouping.SearchType;
import org.jpedal.io.JAIHelper;
import org.jpedal.io.JAITiffHelper;
import org.jpedal.objects.PdfFileInformation;
import org.jpedal.objects.PdfPageData;
import org.jpedal.utils.*;
import org.jpedal.utils.SwingWorker;
import javax.imageio.ImageIO;
import javax.swing.*;
import java.awt.*;
import javax.swing.event.HyperlinkEvent;
import javax.swing.event.HyperlinkListener;
import javax.swing.text.BadLocationException;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.ComponentEvent;
import java.awt.event.ComponentListener;
import java.awt.geom.AffineTransform;
import java.awt.image.BufferedImage;
import java.beans.PropertyVetoException;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.net.URL;
import java.net.URLConnection;
import java.util.*;
import java.util.List;

/**code to execute the actual commands*/
public class Commands {

	public static final int INFO = 1;
	public static final int BITMAP = 2;
	public static final int IMAGES = 3;
	public static final int TEXT = 4;
	public static final int SAVE = 5;
	public static final int PRINT = 6;
	public static final int EXIT = 7;
	public static final int AUTOSCROLL = 8;
	public static final int DOCINFO = 9;
	public static final int OPENFILE = 10;
	//public static final int BOOKMARK = 11;
	public static final int FIND = 12;
	public static final int SNAPSHOT = 13;
	public static final int OPENURL = 14;
	public static final int VISITWEBSITE = 15;
	public static final int PREVIOUSDOCUMENT = 16;
	public static final int NEXTDOCUMENT = 17;
	public static final int PREVIOUSRESULT = 18;
	public static final int NEXTRESULT = 19;
	public static final int TIP = 20;
	public static final int CASCADE = 21;
	public static final int TILE = 22;
	public static final int UPDATE = 23;
	public static final int PREFERENCES = 24;
	

	public static final int FIRSTPAGE = 50;
	public static final int FBACKPAGE = 51;
	public static final int BACKPAGE = 52;
	public static final int FORWARDPAGE = 53;
	public static final int FFORWARDPAGE = 54;
	public static final int LASTPAGE = 55;
	public static final int GOTO = 56;

	public static final int SINGLE = 57;
	public static final int CONTINUOUS = 58;
	public static final int CONTINUOUS_FACING = 59;
	public static final int FACING = 60;

	public static final int FULLSCREEN=61;

	/**combo boxes start at 250*/
	public static final int QUALITY = 250;
	public static final int ROTATION = 251;
	public static final int SCALING = 252;

	//<link><a name="constants" />
	/**
	 * external/itext menu options start at 500 - add your own CONSTANT here
	 * and refer to action using name at ALL times
	 */
	public static final int SAVEFORM = 500;
	public static final int PDF = 501;
	public static final int ROTATE=502;
	public static final int DELETE=503;
	public static final int ADD=504;
	public static final int SECURITY=505;
	public static final int ADDHEADERFOOTER=506;
	public static final int STAMPTEXT=507;
	public static final int STAMPIMAGE=508;
	public static final int SETCROP=509;
	public static final int NUP = 510;
	public static final int HANDOUTS = 511;
	//public static final int NEWFUNCTION = 512;


	private Values commonValues;
	private SwingGUI currentGUI;
	private PdfDecoder decode_pdf;

	private GUIThumbnailPanel thumbnails;

	/**window for full screen mode*/
	Window win;


	/**Multi page tiff image loading*/
	private int tiffImageToLoad = 0;
	
	/**Objects required to load Tiff using JAI*/
    private JAITiffHelper tiffHelper=null;


	/**image if file tiff or png or jpg*/
	private BufferedImage img=null;

	private int noOfRecentDocs;
	private RecentDocuments recent;

	private JMenuItem[] recentDocuments;

	private final Font headFont=new Font("SansSerif",Font.BOLD,14);

	/**flag used for text popup display to show if menu disappears*/
	private boolean display=true;

	private PropertiesFile properties;

	final private GUISearchWindow searchFrame;

	private SearchList results;

	private Printer currentPrinter;

	/**atomic lock for open thread*/
	private boolean isOpening;
	private boolean fileIsURL;

	//Track Last search index
	private int lastSearchIndex = 0;

	private boolean openingTransferedFile;
	
	public Commands(Values commonValues,SwingGUI currentGUI,PdfDecoder decode_pdf,GUIThumbnailPanel thumbnails,
			PropertiesFile properties , GUISearchWindow searchFrame,Printer currentPrinter) {
		this.commonValues=commonValues;
		this.currentGUI=currentGUI;
		this.decode_pdf=decode_pdf;
		this.thumbnails=thumbnails;
		this.properties=properties;
		this.currentPrinter=currentPrinter;

		this.noOfRecentDocs=properties.getNoRecentDocumentsToDisplay();
		recentDocuments = new JMenuItem[noOfRecentDocs];
		this.recent=new RecentDocuments(noOfRecentDocs,properties);

		this.searchFrame=searchFrame;
	}

	/**
	 * main routine which executes code for current command
	 */
	public void executeCommand(int ID) {

		String fileToOpen;
		switch(ID){

		case INFO:
			currentGUI.getInfoBox();
			break;

		case BITMAP:
			if(commonValues.getSelectedFile()==null){
				JOptionPane.showMessageDialog(currentGUI.getFrame(),Messages.getMessage("PdfViewer.OpenFile"));
			}else{
				//get values from user
				SaveBitmap current_selection = new SaveBitmap(commonValues.getInputDir(), commonValues.getPageCount(),commonValues.getCurrentPage());
				int userChoice=current_selection.display(currentGUI.getFrame(),Messages.getMessage("PdfViewer.SaveAsBitmap"));


				//get parameters and call if YES
				if(fileIsURL){
					currentGUI.showMessageDialog(Messages.getMessage("PdfViewerMessage.CannotExportFromURL"));
				}else if (userChoice == JOptionPane.OK_OPTION){
					Exporter exporter=new Exporter(currentGUI,commonValues.getSelectedFile(),decode_pdf);
					exporter.extractPagesAsImages(current_selection);
				}
			}
			break;

		case IMAGES:
			if(commonValues.getSelectedFile()==null){
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.NoFile"));
			}else{
				//get values from user

				SaveImage current_selection = new SaveImage(commonValues.getInputDir(), commonValues.getPageCount(), commonValues.getCurrentPage());
				int userChoice=current_selection.display(currentGUI.getFrame(),Messages.getMessage("PdfViewerTitle.SaveImagesFromPageRange"));

				//get parameters and call if YES
				if(fileIsURL){
					currentGUI.showMessageDialog(Messages.getMessage("PdfViewerMessage.CannotExportFromURL"));
				}else if (userChoice == JOptionPane.OK_OPTION){
					Exporter exporter=new Exporter(currentGUI,commonValues.getSelectedFile(),decode_pdf);
					exporter.extractImagesOnPages(current_selection);
				}
			}
			break;

		case TEXT:

			if(commonValues.getSelectedFile()==null){
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.NoFile"));
			}else if(!decode_pdf.isExtractionAllowed()){
				currentGUI.showMessageDialog("Not allowed");
			}else{
				//get values from user
				SaveText current_selection = new SaveText(commonValues.getInputDir(), commonValues.getPageCount(), commonValues.getCurrentPage());
				int userChoice=current_selection.display(currentGUI.getFrame(),Messages.getMessage("PdfViewerTitle.SaveTextFromPageRange"));

				//get parameters and call if YES
				if(fileIsURL){
					currentGUI.showMessageDialog(Messages.getMessage("PdfViewerMessage.CannotExportFromURL"));
				}else if (userChoice == JOptionPane.OK_OPTION){
					Exporter exporter=new Exporter(currentGUI,commonValues.getSelectedFile(),decode_pdf);
					exporter.extractTextOnPages(current_selection);
				}
			}
			break;

		case SAVE:
			saveFile();
			break;

		case PRINT:

			if(commonValues.getSelectedFile()!=null){
				if(!currentPrinter.isPrinting()){
					if(!commonValues.isPDF()){
						currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.ImagePrinting"));
					}else{
						currentPrinter.printPDF(decode_pdf,currentGUI);

						//needed for Mac to put forms back
						if(PdfDecoder.isRunningOnMac)
							currentGUI.getFrame().invalidate();
					}
				}else
					currentGUI.showMessageDialog(Messages.getMessage("PdfViewerPrintFinish.message"));
			}else
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewerNoFile.message"));
			break;

		case EXIT:
			if(currentPrinter.isPrinting())
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewerStillPrinting.text"));
			else
				exit();
			break;

		case AUTOSCROLL:
			currentGUI.toogleAutoScrolling();
			break;

		case DOCINFO:
			
			if(!commonValues.isPDF())
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.ImageSearch"));
			else
				currentGUI.showDocumentProperties(commonValues.getSelectedFile(), commonValues.getInputDir(), commonValues.getFileSize(), commonValues.getPageCount(), commonValues.getCurrentPage());
			break;

		case OPENFILE:

			
			
			/**
			 * warn user on forms
			 */
			handleUnsaveForms();


			if(currentPrinter.isPrinting())
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewerPrintWait.message"));
			else if(commonValues.isProcessing() || isOpening)
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewerDecodeWait.message"));
			else{
				if(commonValues.isContentExtractor())
					currentGUI.setPDFOutlineVisible(false);

				selectFile();

				fileIsURL=false;
			}
			break;

		case SNAPSHOT:

			if(decode_pdf.getDisplayView()!=Display.SINGLE_PAGE){
				currentGUI.showMessageDialog(Messages.getMessage("PageLayoutMessage.SinglePageOnly"));
			}else{
				commonValues.toggleExtractImageOnSelection();

				currentGUI.toggleSnapshotButton();
			}

			break;

		case FIND:
			if(commonValues.getSelectedFile()==null){
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.NoFile"));
			}else if(!commonValues.isPDF()){
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.ImageSearch"));
			}else if(decode_pdf.getDisplayView()!=Display.SINGLE_PAGE){
				currentGUI.showMessageDialog(Messages.getMessage("PageLayoutMessage.SinglePageOnly"));
			}else if((!searchFrame.isSearchVisible())){
				searchFrame.find(decode_pdf, commonValues);
			} else
				searchFrame.grabFocusInInput();

			break;

		case OPENURL:

			/**
			 * warn user on forms
			 */
			handleUnsaveForms();

			if(currentPrinter.isPrinting())
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewerPrintWait.message"));
			else if(commonValues.isProcessing() || isOpening)
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewerDecodeWait.message"));
			else{

				currentGUI.resetNavBar();
				String newFile=selectURL();
				if(newFile!=null){
					commonValues.setSelectedFile(newFile);
					fileIsURL=true;
				}
			}

			break;

		case VISITWEBSITE:
			try {
				BrowserLauncher.openURL(Messages.getMessage("PdfViewer.VisitWebsite"));
			} catch (IOException e1) {
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.ErrorWebsite"));
			}
			break;
		case TIP:
			TipOfTheDay tipOfTheDay = new TipOfTheDay(currentGUI.getFrame(), "/org/jpedal/examples/simpleviewer/res/tips", properties);
			tipOfTheDay.setVisible(true);
			break;
		case CASCADE:
			//<start-os>
			cascade();
			//<end-os>
			break;
		case TILE:
			//<start-os>
			tile();
			//<end-os>
			break;
		case UPDATE:
			checkForUpdates(true);
			break;
		case PREFERENCES:
			currentGUI.showPreferencesDialog();
			
			break;
		case PREVIOUSDOCUMENT:
			if(currentPrinter.isPrinting())
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewerPrintWait.message"));
			else if(commonValues.isProcessing() || isOpening)
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewerDecodeWait.message"));
			else{
				fileToOpen=recent.getPreviousDocument();
				if(fileToOpen!=null)
					open(fileToOpen);
			}
			break;

		case NEXTDOCUMENT:
			if(currentPrinter.isPrinting())
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewerPrintWait.message"));
			else if(commonValues.isProcessing() || isOpening)
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewerDecodeWait.message"));
			else{
				fileToOpen=recent.getNextDocument();
				if(fileToOpen!=null)
					open(fileToOpen);
			}
			break;

		case PREVIOUSRESULT:
			results = searchFrame.getResults();
			results.setSelectedIndex(results.getSelectedIndex()-1);

			//When looping to end of Doc, find end of list
			if(results.getSelectedIndex()==lastSearchIndex){
				int index = results.getLength();
				results.setSelectedIndex(index);
				while(results.getSelectedIndex()==0){
					index--;
					results.setSelectedIndex(index);
					if(index==0){
						try {
							throw new Exception("Error on Previous Loop");
						} catch (Exception e) {
							e.printStackTrace();
						}
					}

				}
			}

			lastSearchIndex = results.getSelectedIndex();


			if(!commonValues.isProcessing()){//{if (!event.getValueIsAdjusting()) {

				float scaling=currentGUI.getScaling();
				int inset=currentGUI.getPDFDisplayInset();

				int id=results.getSelectedIndex();

				decode_pdf.setFoundTextAreas(null);

				if(id!=-1){

					Integer key=new Integer(id);
					Object newPage=results.getTextPages().get(key);

					if(newPage!=null){
						int nextPage=((Integer)newPage).intValue();
						Rectangle highlight=(Rectangle) searchFrame.getTextRectangles().get(key);

						//move to new page
						if(commonValues.getCurrentPage()!=nextPage){
							commonValues.setCurrentPage(nextPage);

							currentGUI.resetStatusMessage(Messages.getMessage("PdfViewer.LoadingPage")+ ' ' +commonValues.getCurrentPage());

							/**reset as rotation may change!*/
							decode_pdf.setPageParameters(scaling, commonValues.getCurrentPage());

							//decode the page
							currentGUI.decodePage(false);

							decode_pdf.invalidate();
						}

						//draw rectangle
						int scrollInterval = decode_pdf.getScrollInterval();
						//previous one to revert back to but other more accurate
//						decode_pdf.scrollRectToVisible(new Rectangle((int)((highlight.x*scaling)+scrollInterval),(int)(mediaGUI.cropH-((highlight.y-currentGUI.cropY)*scaling)-scrollInterval*2),scrollInterval*4,scrollInterval*6));

						int x = (int)((highlight.x-currentGUI.cropX)*scaling)+inset;
						int y = (int)((currentGUI.cropH-(highlight.y-currentGUI.cropY))*scaling)+inset;
						int w = (int)(highlight.width*scaling);
						int h = (int)(highlight.height*scaling);

						Rectangle scrollto = new Rectangle(x-scrollInterval,y-h-scrollInterval,w+scrollInterval*2,h+scrollInterval*2);
						decode_pdf.scrollRectToVisible(scrollto);
						decode_pdf.setFoundTextArea(highlight);

						decode_pdf.invalidate();
						decode_pdf.repaint();

					}
				}
			}
			break;

		case NEXTRESULT:
			results = searchFrame.getResults();
			results.setSelectedIndex(results.getSelectedIndex()+1);

			if(results.getSelectedIndex()==lastSearchIndex)
				results.setSelectedIndex(0);

			lastSearchIndex = results.getSelectedIndex();


			if(!commonValues.isProcessing()){//{if (!event.getValueIsAdjusting()) {

				float scaling=currentGUI.getScaling();
				int inset=currentGUI.getPDFDisplayInset();

				int id=results.getSelectedIndex();

				decode_pdf.setFoundTextAreas(null);

				if(id!=-1){

					Integer key=new Integer(id);
					Object newPage=results.getTextPages().get(key);

					if(newPage!=null){
						int nextPage=((Integer)newPage).intValue();
						Rectangle highlight=(Rectangle) searchFrame.getTextRectangles().get(key);

						//move to new page
						if(commonValues.getCurrentPage()!=nextPage){
							commonValues.setCurrentPage(nextPage);

							currentGUI.resetStatusMessage(Messages.getMessage("PdfViewer.LoadingPage")+ ' ' +commonValues.getCurrentPage());

							/**reset as rotation may change!*/
							decode_pdf.setPageParameters(scaling, commonValues.getCurrentPage());

							//decode the page
							currentGUI.decodePage(false);

							decode_pdf.invalidate();
						}

						//draw rectangle
						int scrollInterval = decode_pdf.getScrollInterval();
						//previous one to revert back to but other more accurate
//						decode_pdf.scrollRectToVisible(new Rectangle((int)((highlight.x*scaling)+scrollInterval),(int)(mediaGUI.cropH-((highlight.y-currentGUI.cropY)*scaling)-scrollInterval*2),scrollInterval*4,scrollInterval*6));

						int x = (int)((highlight.x-currentGUI.cropX)*scaling)+inset;
						int y = (int)((currentGUI.cropH-(highlight.y-currentGUI.cropY))*scaling)+inset;
						int w = (int)(highlight.width*scaling);
						int h = (int)(highlight.height*scaling);

						Rectangle scrollto = new Rectangle(x-scrollInterval,y-h-scrollInterval,w+scrollInterval*2,h+scrollInterval*2);
						decode_pdf.scrollRectToVisible(scrollto);
						decode_pdf.setFoundTextArea(highlight);

						decode_pdf.invalidate();
						decode_pdf.repaint();

					}
				}
			}
			break;

		case FIRSTPAGE:
			if((commonValues.getSelectedFile()!=null)&&(commonValues.getPageCount()>1)&&(commonValues.getCurrentPage()!=1))
				back(commonValues.getCurrentPage()-1);
			break;

		case FBACKPAGE:
			if(commonValues.getSelectedFile()!=null)
				if(commonValues.getCurrentPage()<10)
					back(commonValues.getCurrentPage()-1);
				else
					back(10);
			break;

		case BACKPAGE:
			if(commonValues.getSelectedFile()!=null)
				back(1);
			break;

		case FORWARDPAGE:
			if(commonValues.getSelectedFile()!=null)
				forward(1);
			break;

		case FFORWARDPAGE:
			if(commonValues.getSelectedFile()!=null)
				if(commonValues.getPageCount()<commonValues.getCurrentPage()+10)
					forward(commonValues.getPageCount()-commonValues.getCurrentPage());
				else
					forward(10);
			break;

		case LASTPAGE:
			if((commonValues.getSelectedFile()!=null)&&(commonValues.getPageCount()>1)&&(commonValues.getPageCount()-commonValues.getCurrentPage()>0))
				forward(commonValues.getPageCount()-commonValues.getCurrentPage());
			break;

		case GOTO:
			String page = currentGUI.showInputDialog(Messages.getMessage("PdfViewer.EnterPageNumber"), Messages.getMessage("PdfViewer.GotoPage"), JOptionPane.QUESTION_MESSAGE);
			if(page != null)
				gotoPage(page);
			hideRedundentNavButtons();
			break;

			//start-os>
		case SINGLE:
			currentGUI.alignLayoutMenuOption(Display.SINGLE_PAGE);
			decode_pdf.setDisplayView(Display.SINGLE_PAGE, Display.DISPLAY_CENTERED);
			currentGUI.resetRotationBox();
			break;
		case CONTINUOUS:
			currentGUI.alignLayoutMenuOption(Display.CONTINUOUS);
			decode_pdf.setDisplayView(Display.CONTINUOUS, Display.DISPLAY_CENTERED);
			currentGUI.setSelectedComboIndex(Commands.ROTATION, 0);
			break;
		case CONTINUOUS_FACING:
			currentGUI.alignLayoutMenuOption(Display.CONTINUOUS_FACING);
			decode_pdf.setDisplayView(Display.CONTINUOUS_FACING, Display.DISPLAY_CENTERED);
			currentGUI.setSelectedComboIndex(Commands.ROTATION, 0);
			break;
		case FACING:
			currentGUI.alignLayoutMenuOption(Display.FACING);
			decode_pdf.setDisplayView(Display.FACING, Display.DISPLAY_CENTERED);
			currentGUI.decodePage(false);//ensure all pages appear
			currentGUI.setSelectedComboIndex(Commands.ROTATION, 0);
			break;


		case FULLSCREEN:
			// Determine if full-screen mode is supported directly
			GraphicsEnvironment ge = GraphicsEnvironment.getLocalGraphicsEnvironment();
			GraphicsDevice gs = ge.getDefaultScreenDevice();
			if (gs.isFullScreenSupported()) {
				// Full-screen mode is supported
			} else {
				// Full-screen mode will be simulated
			}

			// Create a window for full-screen mode; add a button to leave full-screen mode
			if(win==null){
				Frame frame = new Frame(gs.getDefaultConfiguration());
				win = new Window(frame);
			}

			currentGUI.getFrame().getContentPane().remove(currentGUI.getDisplayPane());
			win.add(currentGUI.getDisplayPane(),BorderLayout.CENTER);

			// Create a button that leaves full-screen mode
			Button btn = new Button("Return");
			win.add(btn, BorderLayout.NORTH);

			btn.addActionListener(new ActionListener() {
				public void actionPerformed(ActionEvent evt) {


					// Return to normal windowed mode
					GraphicsEnvironment ge = GraphicsEnvironment.getLocalGraphicsEnvironment();
					GraphicsDevice gs = ge.getDefaultScreenDevice();
					gs.setFullScreenWindow(null);

					win.remove(currentGUI.getDisplayPane());
					currentGUI.getFrame().getContentPane().add(currentGUI.getDisplayPane(), BorderLayout.CENTER);
					currentGUI.getDisplayPane().invalidate();
					currentGUI.getDisplayPane().updateUI();
					currentGUI.getFrame().getContentPane().validate();

					win.dispose();
				}
			});


			try {
				// Enter full-screen mode
				gs.setFullScreenWindow(win);
				win.validate();
			}catch(Error e){
				currentGUI.showMessageDialog("Full screen mode not supported on this machine.\n" +
				"JPedal will now exit");

				this.exit();
				// ...
			}// finally {
			// Exit full-screen mode
			//	gs.setFullScreenWindow(null);
			//}
			break;



		case QUALITY:
			if(!commonValues.isProcessing()){
				boolean useHiresImage=true;
				if(currentGUI.getSelectedComboIndex(Commands.QUALITY)==0)
					useHiresImage = false;

				if(commonValues.getSelectedFile()!=null){

					decode_pdf.unsetScaling();
					//tell user page will be redrawn
					currentGUI.showMessageDialog(Messages.getMessage("PdfViewerReparseWait.message"));

					//reset flag and re-decode page
					decode_pdf.useHiResScreenDisplay(useHiresImage);
					commonValues.setUseHiresImage(useHiresImage);

					try {
						currentGUI.decodePage(false);
					} catch (Exception e1) {
						System.err.println("Exception"+ ' ' + e1 + "decoding page after image quality changes");
						e1.printStackTrace();
					}
					//decode_pdf.updateUI();
				}
			}

			break;

			//<end-os>

		case SCALING:
			if(!commonValues.isProcessing()){
				if(commonValues.getSelectedFile()!=null)
					currentGUI.zoom(false);
			}
			break;

		case ROTATION:
			if(commonValues.getSelectedFile()!=null)
				currentGUI.rotate();
			//currentGUI.getDisplayPane();
			break;

			/**
			 * external/itext menu options start at 500 - add your own code here
			 */
		case SAVEFORM:
			saveChangedForm();
			break;

			//<start-13>

		case PDF:
			if(commonValues.getSelectedFile()==null){
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.NoFile"));
			}else{
				//get values from user
				SavePDF current_selection = new SavePDF(commonValues.getInputDir(), commonValues.getPageCount(), commonValues.getCurrentPage());
				int userChoice=current_selection.display(currentGUI.getFrame(),Messages.getMessage("PdfViewerTitle.SavePagesAsPdf"));

				//get parameters and call if YES
				if (userChoice == JOptionPane.OK_OPTION){
					ItextFunctions itextFunctions=new ItextFunctions(currentGUI,commonValues.getSelectedFile(),decode_pdf);
					itextFunctions.extractPagesToNewPDF(current_selection);
				}
			}
			break;

		case ROTATE:
			if(commonValues.getSelectedFile()==null){
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.NoFile"));
			}else{
				//get values from user
				RotatePDFPages current_selection = new RotatePDFPages(commonValues.getInputDir(), commonValues.getPageCount(), commonValues.getCurrentPage());
				int userChoice=current_selection.display(currentGUI.getFrame(),Messages.getMessage("PdfViewerRotation.text"));

				//get parameters and call if YES
				if (userChoice == JOptionPane.OK_OPTION){

					PdfPageData currentPageData=decode_pdf.getPdfPageData();

					decode_pdf.closePdfFile();
					ItextFunctions itextFunctions=new ItextFunctions(currentGUI,commonValues.getSelectedFile(),decode_pdf);
					itextFunctions.rotate(commonValues.getPageCount(),currentPageData,current_selection);
					open(commonValues.getSelectedFile());
				}

			}

			break;

		case SETCROP:
			if(commonValues.getSelectedFile()==null){
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.NoFile"));
			}else{
				//get values from user
				CropPDFPages cropPage = new CropPDFPages(commonValues.getInputDir(), commonValues.getPageCount(), commonValues.getCurrentPage());
				int cropPageChoice=cropPage.display(currentGUI.getFrame(),Messages.getMessage("PdfViewerTooltip.PDFCropPages"));

				//get parameters and call if YES
				if (cropPageChoice == JOptionPane.OK_OPTION){

					PdfPageData currentPageData=decode_pdf.getPdfPageData();

					decode_pdf.closePdfFile();
					ItextFunctions itextFunctions=new ItextFunctions(currentGUI,commonValues.getSelectedFile(),decode_pdf);
					itextFunctions.setCrop(commonValues.getPageCount(),currentPageData,cropPage);
					open(commonValues.getSelectedFile());
				}
			}

			break;

		case NUP:

			if(commonValues.getSelectedFile()==null){
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.NoFile"));
			}else{
//				get values from user
				ExtractPDFPagesNup nup = new ExtractPDFPagesNup(commonValues.getInputDir(), commonValues.getPageCount(), commonValues.getCurrentPage());
				int nupChoice=nup.display(currentGUI.getFrame(),Messages.getMessage("PdfViewerNUP.titlebar"));

				//get parameters and call if YES
				if (nupChoice == JOptionPane.OK_OPTION){

					PdfPageData currentPageData=decode_pdf.getPdfPageData();

					//decode_pdf.closePdfFile();
					ItextFunctions itextFunctions=new ItextFunctions(currentGUI,commonValues.getSelectedFile(),decode_pdf);
					itextFunctions.nup(commonValues.getPageCount(),currentPageData,nup);
					//open(commonValues.getSelectedFile());
				}
			}

			break;

		case HANDOUTS:
			if(fileIsURL)
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewerMessage.CannotExportFromURL"));

			if(commonValues.getSelectedFile()==null){
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.NoFile"));
			}else{
				if(!fileIsURL){//ensure file choose not displayed if opened from URL
					JFileChooser chooser1 = new JFileChooser();
					chooser1.setFileSelectionMode(JFileChooser.FILES_ONLY);

					int approved1=chooser1.showSaveDialog(null);
					if(approved1==JFileChooser.APPROVE_OPTION){

						File file = chooser1.getSelectedFile();

						ItextFunctions itextFunctions=new ItextFunctions(currentGUI,commonValues.getSelectedFile(),decode_pdf);
						itextFunctions.handouts(file.getAbsolutePath());
					}
				}
			}

			break;

		case DELETE:

			if(commonValues.getSelectedFile()==null){
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.NoFile"));
			}else{
				//get values from user
				DeletePDFPages deletedPages = new DeletePDFPages(commonValues.getInputDir(), commonValues.getPageCount(), commonValues.getCurrentPage());
				int deletedPagesChoice=deletedPages.display(currentGUI.getFrame(),Messages.getMessage("PdfViewerDelete.text"));

				//get parameters and call if YES
				if (deletedPagesChoice == JOptionPane.OK_OPTION){

					PdfPageData currentPageData=decode_pdf.getPdfPageData();

					decode_pdf.closePdfFile();
					ItextFunctions itextFunctions=new ItextFunctions(currentGUI,commonValues.getSelectedFile(),decode_pdf);
					itextFunctions.delete(commonValues.getPageCount(),currentPageData,deletedPages);
					open(commonValues.getSelectedFile());
				}
			}

			break;

		case ADDHEADERFOOTER:

			if(commonValues.getSelectedFile()==null){
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.NoFile"));
			}else{

				//get values from user
				AddHeaderFooterToPDFPages addHeaderFooter = new AddHeaderFooterToPDFPages(commonValues.getInputDir(), commonValues.getPageCount(), commonValues.getCurrentPage());
				int headerFooterPagesChoice=addHeaderFooter.display(currentGUI.getFrame(),Messages.getMessage("PdfViewerTitle.AddHeaderAndFooters"));

				//get parameters and call if YES
				if (headerFooterPagesChoice == JOptionPane.OK_OPTION){

					PdfPageData currentPageData=decode_pdf.getPdfPageData();

					decode_pdf.closePdfFile();
					ItextFunctions itextFunctions=new ItextFunctions(currentGUI,commonValues.getSelectedFile(),decode_pdf);
					itextFunctions.addHeaderFooter(commonValues.getPageCount(),currentPageData,addHeaderFooter);
					open(commonValues.getSelectedFile());
				}
			}

			break;

		case STAMPTEXT:

			if(commonValues.getSelectedFile()==null){
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.NoFile"));
			}else{
				//get values from user
				StampTextToPDFPages stampText = new StampTextToPDFPages(commonValues.getInputDir(), commonValues.getPageCount(), commonValues.getCurrentPage());
				int stampTextChoice=stampText.display(currentGUI.getFrame(),Messages.getMessage("PdfViewerStampText.text"));

				//get parameters and call if YES
				if (stampTextChoice == JOptionPane.OK_OPTION){

					PdfPageData currentPageData=decode_pdf.getPdfPageData();

					decode_pdf.closePdfFile();
					ItextFunctions itextFunctions=new ItextFunctions(currentGUI,commonValues.getSelectedFile(),decode_pdf);
					itextFunctions.stampText(commonValues.getPageCount(),currentPageData,stampText);
					open(commonValues.getSelectedFile());
				}
			}

			break;

		case STAMPIMAGE:

			if(commonValues.getSelectedFile()==null){
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.NoFile"));
			}else{

				//get values from user
				StampImageToPDFPages stampImage = new StampImageToPDFPages(commonValues.getInputDir(), commonValues.getPageCount(), commonValues.getCurrentPage());
				int stampImageChoice=stampImage.display(currentGUI.getFrame(),Messages.getMessage("PdfViewerStampImage.text"));

				//get parameters and call if YES
				if (stampImageChoice == JOptionPane.OK_OPTION){

					PdfPageData currentPageData=decode_pdf.getPdfPageData();

					decode_pdf.closePdfFile();
					ItextFunctions itextFunctions=new ItextFunctions(currentGUI,commonValues.getSelectedFile(),decode_pdf);
					itextFunctions.stampImage(commonValues.getPageCount(),currentPageData,stampImage);
					open(commonValues.getSelectedFile());
				}
			}

			break;

		case ADD:

			if(commonValues.getSelectedFile()==null){
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.NoFile"));
			}else{
				//get values from user
				InsertBlankPDFPage addPage = new InsertBlankPDFPage(commonValues.getInputDir(), commonValues.getPageCount(), commonValues.getCurrentPage());
				int positionToAdd=addPage.display(currentGUI.getFrame(),Messages.getMessage("PdfViewer.BlankPage"));

				//get parameters and call if YES
				if (positionToAdd == JOptionPane.OK_OPTION){

					PdfPageData currentPageData=decode_pdf.getPdfPageData();

					decode_pdf.closePdfFile();
					ItextFunctions itextFunctions=new ItextFunctions(currentGUI,commonValues.getSelectedFile(),decode_pdf);
					itextFunctions.add(commonValues.getPageCount(),currentPageData,addPage);
					open(commonValues.getSelectedFile());
				}
			}

			break;

		case SECURITY:

			if(commonValues.getSelectedFile()==null){
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.NoFile"));
			}else{
				//get values from user
				EncryptPDFDocument encryptPage = new EncryptPDFDocument(commonValues.getInputDir(), commonValues.getPageCount(), commonValues.getCurrentPage());
				int encrypt=encryptPage.display(currentGUI.getFrame(),"Standard Security");

				//get parameters and call if YES
				if (encrypt == JOptionPane.OK_OPTION){

					PdfPageData currentPageData=decode_pdf.getPdfPageData();

					decode_pdf.closePdfFile();
					ItextFunctions itextFunctions=new ItextFunctions(currentGUI,commonValues.getSelectedFile(),decode_pdf);
					itextFunctions.encrypt(commonValues.getPageCount(),currentPageData,encryptPage);
					open(commonValues.getSelectedFile());
				}
			}

			break;
			//<end-13>

			//<link><a name="link" />

			//case MYFUNCTION:
			/**add your code here. We recommend you put it in an external class such as
			 *
			 * MyFactory.newFunction(parameters);
			 *
			 * or
			 *
			 * ItextFunctions itextFunctions=new ItextFunctions(currentGUI.getFrame(),commonValues.getSelectedFile(),decode_pdf);
			 * itextFunctions.newFeature(parameters);
			 */
			//break;

		default:
			System.out.println("No menu item set");
		}
	}


	//<start-os>

	private void cascade() {
		JDesktopPane desktopPane = currentGUI.getMultiViewerFrames();

		JInternalFrame[] frames = desktopPane.getAllFrames();

		/** 
		 * reverse the order of these frames, so when they are cascaded they will 
		 * maintain the order they were to start with 
		 */
		for (int left = 0, right = frames.length - 1; left < right; left++, right--) {
			// exchange the first and last
			JInternalFrame temp = frames[left];
			frames[left] = frames[right];
			frames[right] = temp;
		}

		int x = 0;
		int y = 0;
		int width = desktopPane.getWidth() / 2;
		int height = desktopPane.getHeight() / 2;

		for (int i = 0; i < frames.length; i++) {
			if (!frames[i].isIcon()) { // if its minimized leave it there
				try {
					frames[i].setMaximum(false);
					frames[i].reshape(x, y, width, height);
					frames[i].setSelected(true);

					x += 25;
					y += 25;
					// wrap around at the desktop edge
					if (x + width > desktopPane.getWidth())
						x = 0;
					if (y + height > desktopPane.getHeight())
						y = 0;
				} catch (PropertyVetoException e) {
				}
			}
		}
	}

	private void tile() {

		JDesktopPane desktopPane = currentGUI.getMultiViewerFrames();

		JInternalFrame[] frames = desktopPane.getAllFrames();

		// count frames that aren't iconized
		int frameCount = 0;
		for (int i = 0; i < frames.length; i++) {
			if (!frames[i].isIcon())
				frameCount++;
		}

		int rows = (int) Math.sqrt(frameCount);
		int cols = frameCount / rows;
		int extra = frameCount % rows;
		// number of columns with an extra row

		int width = desktopPane.getWidth() / cols;
		int height = desktopPane.getHeight() / rows;
		int r = 0;
		int c = 0;
		for (int i = 0; i < frames.length; i++) {
			if (!frames[i].isIcon()) {
				try {
					frames[i].setMaximum(false);
					frames[i].reshape(c * width, r * height, width, height);
					r++;
					if (r == rows) {
						r = 0;
						c++;
						if (c == cols - extra) { // start adding an extra row
							rows++;
							height = desktopPane.getHeight() / rows;
						}
					}
				} catch (PropertyVetoException e) {
				}
			}
		}
	}

	int startX=0,startY=0;


	private boolean isFirstPageOpened=true;
	private MultiViewListener multiViewListener;
	
	/**
	 * this is the code which does all the work
	 * @return
	 */
	private PdfDecoder openNewMultiplePage(String fileName){

		JDesktopPane desktopPane = currentGUI.getMultiViewerFrames();

		/**
		 * setup PDF object
		 */
		final PdfDecoder localPdf=new PdfDecoder(true);
//		System.out.println("new pdf = "+localPdf.getClass().getName() + "@" + Integer.toHexString(localPdf.hashCode()));
		
		decode_pdf=localPdf;
		
		currentGUI.setPdfDecoder(decode_pdf);

		decode_pdf.setDisplayView(Display.SINGLE_PAGE,Display.DISPLAY_CENTERED);

		//decode_pdf.addExternalHandler(this, Options.MultiPageUpdate);
		decode_pdf.init(true);
		decode_pdf.setExtractionMode(0,72,1); //values extraction mode,dpi of images, dpi of page as a factor of 72

		int inset=currentGUI.getPDFDisplayInset();
		decode_pdf.setInset(inset,inset);
		decode_pdf.setBackground(new Color(190,190,190));
		decode_pdf.setPageParameters(currentGUI.getScaling(), commonValues.getCurrentPage(),currentGUI.getRotation());

		/**
		 * setup Internal frame to hold PDF
		 **/
		JInternalFrame pdf=new JInternalFrame(fileName, true, true, true, true);
		String s = String.valueOf(startX);
		pdf.setName(s);
		pdf.setSize(250,250);
		pdf.setVisible(true);
		pdf.setLocation(startX,startY);

		startX=startX+25;
		startY=startY+25;

		/**
		 * listener to switch to this object if window selected
		 */
		multiViewListener = new MultiViewListener(decode_pdf, currentGUI, commonValues, this);
		pdf.addInternalFrameListener(multiViewListener);
		
		pdf.addComponentListener(new ComponentListener(){
			public void componentHidden(ComponentEvent e) {}

			//Prevent window from becoming unselectable
			public void componentMoved(ComponentEvent e) {
				Component c = e.getComponent();
				Component parent = c.getParent();

				if(c.getLocation().y<0){
					//Prevent internal window from leaving off the top of the panel
					c.setLocation(c.getLocation().x, 0);

				}else if((c.getLocation().y + c.getSize().height) > (parent.getSize().height + (c.getSize().height/2))){
					//Don't go too far off the bottom of the screen (half internal window size)
					c.setLocation(c.getLocation().x, (parent.getSize().height - (c.getSize().height/2)));
				}

				if(c.getLocation().x < -(c.getSize().width/2)){
					//Don't go too far off the left of the screen (half internal window size)
					c.setLocation(-(c.getSize().width/2) , c.getLocation().y);

				}else if((c.getLocation().x + c.getSize().width) > (parent.getSize().width + (c.getSize().width/2))){
					//Don't go too far off the right of the screen (half internal window size)
					c.setLocation((parent.getSize().width - (c.getSize().width/2)),c.getLocation().y);
				}
			}
			public void componentResized(ComponentEvent e) {
				if(decode_pdf.getParent()!=null && currentGUI.getSelectedComboIndex(Commands.SCALING)<3)
					currentGUI.zoom(false);
			}
			public void componentShown(ComponentEvent e) {}
		});

		/**
		 * add the pdf display to show page
		 **/
		JScrollPane scrollPane=new JScrollPane();
		scrollPane.getViewport().add(localPdf);
		scrollPane.setVerticalScrollBarPolicy(JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED);
		scrollPane.setHorizontalScrollBarPolicy(JScrollPane.HORIZONTAL_SCROLLBAR_AS_NEEDED);
		scrollPane.getVerticalScrollBar().setUnitIncrement(80);
		scrollPane.getHorizontalScrollBar().setUnitIncrement(80);

		pdf.getContentPane().add(scrollPane);

		desktopPane.add(fileName,pdf);

		/**
		 * ensure at front and active
		 */
		try {
			pdf.setSelected(true);
		} catch (PropertyVetoException e1) {
			e1.printStackTrace();
		}
		pdf.toFront();
		pdf.grabFocus();
		
		return decode_pdf;

	}
	
	
	//<end-os>

	public void openTransferedFile(final String file) throws PdfException {
		
		while(openingTransferedFile || commonValues.isProcessing()){
			try {
				Thread.sleep(250);
			} catch (InterruptedException e) {
				e.printStackTrace();
			}
		}
		openingTransferedFile = true;
		
		currentGUI.resetNavBar();

		boolean isURL = file.startsWith("http:") || file.startsWith("file:");
		try {

			if(!isURL){
				fileIsURL=false;
				commonValues.setFileSize(new File(file).length() >> 10);
			}else
				fileIsURL=true;

			commonValues.setSelectedFile(file);
			
			currentGUI.setViewerTitle(null);
		} catch (Exception e) {
			LogWriter.writeLog("Exception " + e + " getting paths");
		}

		/** check file exists */
		File testFile = new File(commonValues.getSelectedFile());
		if (!isURL && !testFile.exists()) {
			currentGUI.showMessageDialog(Messages.getMessage("PdfViewerFile.text") + commonValues.getSelectedFile() + Messages.getMessage("PdfViewerNotExist"));

			/** open the file*/
		} else if ((commonValues.getSelectedFile() != null) && (commonValues.isProcessing() == false)) {
			
			//<start-os>
			if(currentGUI.isSingle()){
				//<end-os>
			
				decode_pdf.flushObjectValues(true);

				//<start-os>
			}else{
				decode_pdf=openNewMultiplePage(commonValues.getSelectedFile());
			}
			//<end-os>
			
			//reset the viewableArea before opening a new file
			decode_pdf.resetViewableArea();
			/**/
			
			try {
				openFile(commonValues.getSelectedFile());
			} catch (PdfException e) {
				openingTransferedFile = false;
				throw e;
			}
			
			if(commonValues.isPDF())
				openingTransferedFile=false;
			
//			SwingWorker worker = new SwingWorker() {
//				public Object construct() {
//					
//					openFile(commonValues.getSelectedFile());
//					
//					/** 
//					 * if it is an image being decoded then opening isn't complete until the thread
//					 * decodeImage is finished
//					 */
//					if(commonValues.isPDF())
//						openingTransferedFile=false;
//					
//					return null;
//				}
//			};
//			worker.start();
		}
	}
	
	public boolean openingTransferedFiles(){
		return openingTransferedFile;
	}


	/**add listeners to forms to track changes - could also do other tasks like send data to
	 * database server
	 */
	private void saveChangedForm() {
		org.jpedal.objects.acroforms.rendering.AcroRenderer formRenderer=decode_pdf.getFormRenderer();

		if(formRenderer==null)
			return;

		List names=null;

		try {
			names = formRenderer.getComponentNameList();
		} catch (PdfException e1) {
			e1.printStackTrace();
		}

		if(names==null){
			currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.NoFields"));
		}else{
			/**
			 * create the file chooser to select the file
			 */
			File file=null;
			String fileToSave="";
			boolean finished=false;
			while(!finished){
				JFileChooser chooser = new JFileChooser(commonValues.getInputDir());
				chooser.setSelectedFile(new File(commonValues.getInputDir()+ '/' +commonValues.getSelectedFile()));
				chooser.addChoosableFileFilter(new FileFilterer(new String[]{"pdf"}, "Pdf (*.pdf)"));
				chooser.addChoosableFileFilter(new FileFilterer(new String[]{"fdf"}, "fdf (*.fdf)"));
				chooser.setFileSelectionMode(JFileChooser.FILES_ONLY);
				//set default name to current file name
				int approved=chooser.showSaveDialog(null);
				if(approved==JFileChooser.APPROVE_OPTION){
					file = chooser.getSelectedFile();
					fileToSave=file.getAbsolutePath();

					if(!fileToSave.endsWith(".pdf")){
						fileToSave += ".pdf";
						file=new File(fileToSave);
					}

					if(fileToSave.equals(commonValues.getSelectedFile())){
						currentGUI.showMessageDialog(Messages.getMessage("PdfViewerMessage.SaveError"));
						continue;
					}

					if(file.exists()){
						int n=currentGUI.showConfirmDialog(fileToSave+ '\n' +
								Messages.getMessage("PdfViewerMessage.FileAlreadyExists")+".\n" +
								Messages.getMessage("PdfViewerMessage.ConfirmResave"),
								Messages.getMessage("PdfViewerMessage.Resave")
								,JOptionPane.YES_NO_OPTION);
						if(n==1)
							continue;
					}
					finished=true;
				}else{
					return;
				}
			}

			//<start-13>
			ItextFunctions itextFunctions=new ItextFunctions(currentGUI,commonValues.getSelectedFile(),decode_pdf);
			itextFunctions.saveFormsData(fileToSave);
			//<end-13>

			/**
			 * reset flag and graphical clue
			 */
			commonValues.setFormsChanged(false);
			currentGUI.setViewerTitle(null);

		}
	}

	/**
	 * warns user forms unsaved and offers save option
	 */
	public void handleUnsaveForms() {
		if((commonValues.isFormsChanged())&&(commonValues.isItextOnClasspath())){
			int n = currentGUI.showConfirmDialog(Messages.getMessage("PdfViewerFormsUnsavedOptions.message"),Messages.getMessage("PdfViewerFormsUnsavedWarning.message"), JOptionPane.YES_NO_OPTION);

			if(n==JOptionPane.YES_OPTION)
				saveChangedForm();
		}
		commonValues.setFormsChanged(false);
	}

	/**
	 * extract selected area as a rectangle and show onscreen
	 */
	public void extractSelectedScreenAsImage() {

		/**ensure co-ords in right order*/
		int t_x1=commonValues.m_x1;
		int t_x2=commonValues.m_x2;
		int t_y1=commonValues.m_y1;
		int t_y2=commonValues.m_y2;

		if(commonValues.m_y1<commonValues.m_y2){
			t_y2=commonValues.m_y1;
			t_y1=commonValues.m_y2;
		}

		if(commonValues.m_x1>commonValues.m_x2){
			t_x2=commonValues.m_x1;
			t_x1=commonValues.m_x2;
		}
		float scaling = 100;

		if(PdfDecoder.isRunningOnWindows)
			scaling = 100*currentGUI.getScaling();


		final BufferedImage snapShot=decode_pdf.getSelectedRectangleOnscreen(t_x1,t_y1,t_x2,t_y2,scaling);

		/**
		 * put in panel
		 */
		//if(temp!=null){
		JPanel image_display = new JPanel();
		image_display.setLayout( new BorderLayout() );

		//wrap image so we can display
		if( snapShot != null ){
			IconiseImage icon_image = new IconiseImage( snapShot );

			//add image if there is one
			image_display.add( new JLabel( icon_image ), BorderLayout.CENTER );
		}else{
			return;
		}

		final JScrollPane image_scroll = new JScrollPane();
		image_scroll.getViewport().add( image_display );

		//set image size
		int imgSize=snapShot.getWidth();
		if(imgSize<snapShot.getHeight())
			imgSize=snapShot.getHeight();
		imgSize=imgSize+50;
		if(imgSize>450)
			imgSize=450;

		/**resizeable pop-up for content*/
		JFrame frame = currentGUI.getFrame();
		final JDialog displayFrame =  new JDialog(frame,true);
		if(commonValues.getModeOfOperation()!=Values.RUNNING_APPLET){
			displayFrame.setLocationRelativeTo(null);
			displayFrame.setLocation(frame.getLocationOnScreen().x+10,frame.getLocationOnScreen().y+10);
		}

		displayFrame.setSize(imgSize,imgSize);
		displayFrame.setTitle(Messages.getMessage("PdfViewerMessage.SaveImage"));
		displayFrame.getContentPane().setLayout(new BorderLayout());
		displayFrame.getContentPane().add(image_scroll,BorderLayout.CENTER);

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

				displayFrame.setVisible(false);

				File file=null;
				String fileToSave="";
				boolean finished=false;
				while(!finished){
					final JFileChooser chooser = new JFileChooser(System.getProperty( "user.dir" ) );

					chooser.addChoosableFileFilter( new FileFilterer( new String[] { "tif", "tiff" }, "TIFF" ) );
					chooser.addChoosableFileFilter( new FileFilterer( new String[] { "jpg","jpeg" }, "JPEG" ) );

					int approved = chooser.showSaveDialog(image_scroll);

					if(approved==JFileChooser.APPROVE_OPTION){

						file = chooser.getSelectedFile();
						fileToSave=file.getAbsolutePath();

						String format=chooser.getFileFilter().getDescription();

						if(format.equals("All Files"))
							format="TIFF";

						if(!fileToSave.toLowerCase().endsWith(('.' +format).toLowerCase())){
							fileToSave += '.' +format;
							file=new File(fileToSave);
						}

						if(file.exists()){

							int n=currentGUI.showConfirmDialog(fileToSave+ '\n' +
									Messages.getMessage("PdfViewerMessage.FileAlreadyExists")+".\n" +
									Messages.getMessage("PdfViewerMessage.ConfirmResave"),
									Messages.getMessage("PdfViewerMessage.Resave"),JOptionPane.YES_NO_OPTION);
							if(n==1)
								continue;
						}

						if(JAIHelper.isJAIused())
							JAIHelper.confirmJAIOnClasspath();


						//Do the actual save
						if(snapShot!=null) {
							if(JAIHelper.isJAIused())
								javax.media.jai.JAI.create("filestore", snapShot, fileToSave, format);
							else if(format.toLowerCase().startsWith("tif"))
								currentGUI.showMessageDialog("Please setup JAI library for Tiffs");
							else{
								try {
									ImageIO.write(snapShot,format,new File(fileToSave));
								} catch (IOException e1) {
									e1.printStackTrace();
								}
							}
						}
						finished=true;
					}else{
						return;
					}
				}

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
		//}

	}

	/**
	 * routine to link GUI into text extraction functions
	 */
	public void extractSelectedText() {

		if(!decode_pdf.isExtractionAllowed()){
			currentGUI.showMessageDialog("Not allowed");
			return ;
		}

		/**ensure co-ords in right order*/
		int t_x1=commonValues.m_x1;
		int t_x2=commonValues.m_x2;
		int t_y1=commonValues.m_y1;
		int t_y2=commonValues.m_y2;

		if(t_y1<t_y2){
			int temp = t_y2;
			t_y2=t_y1;
			t_y1=temp;
		}

		if(t_x1>t_x2){
			int temp = t_x2;
			t_x2=t_x1;
			t_x1=temp;
		}

		if(t_x1<currentGUI.cropX)
			t_x1 = currentGUI.cropX;
		if(t_x1>currentGUI.mediaW-currentGUI.cropX)
			t_x1 = currentGUI.mediaW-currentGUI.cropX;

		if(t_x2<currentGUI.cropX)
			t_x2 = currentGUI.cropX;
		if(t_x2>currentGUI.mediaW-currentGUI.cropX)
			t_x2 = currentGUI.mediaW-currentGUI.cropX;

		if(t_y1<currentGUI.cropY)
			t_y1 = currentGUI.cropY;
		if(t_y1>currentGUI.mediaH-currentGUI.cropY)
			t_y1 = currentGUI.mediaH-currentGUI.cropY;

		if(t_y2<currentGUI.cropY)
			t_y2 = currentGUI.cropY;
		if(t_y2>currentGUI.mediaH-currentGUI.cropY)
			t_y2 = currentGUI.mediaH-currentGUI.cropY;

		//methods available and description
		String[] groupings = {Messages.getMessage("PdfViewerRect.label"),
				Messages.getMessage("PdfViewerRect.message"),
				Messages.getMessage("PdfViewerTable.label"),
				Messages.getMessage("PdfViewerTable.message"),
				Messages.getMessage("PdfViewerWordList.label"),
				Messages.getMessage("PdfViewerWordList.message"),
				Messages.getMessage("PdfViewerFind.label"),
				Messages.getMessage("PdfViewerFind.message")};

		//flag to show if text or XML
		boolean isXML=true;

		/**
		 * build and show a display
		 */
		JPanel display_value = new JPanel();
		display_value.setLayout(new BoxLayout(display_value,BoxLayout.Y_AXIS));


		JLabel region = new JLabel(Messages.getMessage("PdfViewerCoords.message")+
				' ' + commonValues.m_x1
				+ " , " + commonValues.m_y1 + " , " + commonValues.m_x2 + " , " + commonValues.m_y2);
		region.setFont(headFont);

		display_value.add(region);
		display_value.add(new JLabel(" "));

		/**
		 * provide list of groupings available and brief description
		 */
		int groupingCount = groupings.length / 2;
		Object[] options = new Object[groupingCount + 2];

		for (int i = 0; i < groupingCount; i++) {
			options[i + 2] = groupings[(i * 2)];

			JLabel groupingTitle = new JLabel(groupings[i * 2]);
			groupingTitle.setForeground(Color.blue);
			groupingTitle.setFont(headFont); //$NON-NLS-1$
			display_value.add(groupingTitle);

			JLabel groupingInfo = new JLabel(groupings[(i * 2) + 1]);
			groupingInfo.setFont(headFont); //$NON-NLS-1$
			display_value.add(groupingInfo);
			display_value.add(new JLabel(" ")); //$NON-NLS-1$

		}

		//add other buttons
		options[0] = Messages.getMessage("PdfViewerCancel.text");
		options[1] = Messages.getMessage("PdfViewerHelpMenu.text");

		/**
		 * bringup display and process user requests
		 */
		display = true;

		while (display) {
			int n = currentGUI.showOptionDialog(display_value,
					Messages.getMessage("PdfViewerGroupingOptions.message"),
					JOptionPane.OK_OPTION,
					JOptionPane.INFORMATION_MESSAGE, null, options,
					options[0]);

			String extractedText = null;

			try {
				/** common extraction code */
				PdfGroupingAlgorithms currentGrouping = null;

				if (n > 1) {

					/** create a grouping object to apply grouping to data */
					currentGrouping = decode_pdf.getGroupingObject();

				}

				//switch off display - pops up again if help selected
				display = false;

				//make choice
				switch (n) {
				case 1: //help

					JTextArea info = new JTextArea(Messages.getMessage("PdfViewerGroupingInfo.message"));

					currentGUI.showMessageDialog(info);
					display = true;
					break;

				case 2: //text extraction

					/**get the text*/
					extractedText = currentGrouping.extractTextInRectangle(
							t_x1, t_y1, t_x2, t_y2, commonValues.getCurrentPage(), false,
							true);

					/**
					 * find out if xml or text - as we need to turn xml off before
					 * extraction. So we assume xml and strip out. This is obviously
					 */
					int useXml=currentGUI.showConfirmDialog(Messages.getMessage("PdfViewerXmlMessage.message"),
							Messages.getMessage("PdfViewerOutputFormat.message"),JOptionPane.YES_NO_OPTION);

					if(extractedText==null)
						currentGUI.showMessageDialog(Messages.getMessage("PdfViewerMessage.NoTextFound"));
					else if(useXml!=0){
						extractedText=Strip.stripXML(extractedText).toString();

						isXML=false;
					}else
						isXML=true;

					break;

				case 3: //table

					//rest to default in case text option selected
					isXML=true;

					Map content;

					/**
					 * find out if xml or text - as we need to turn xml off before
					 * extraction. So we assume xml and strip out. This is obviously
					 */
					int useCSV=currentGUI.showConfirmDialog(Messages.getMessage("PdfViewerXHTML.message"),
							Messages.getMessage("PdfViewerOutputFormat.message"),
							JOptionPane.YES_NO_OPTION);

					if(useCSV!=0)
						content = currentGrouping.extractTextAsTable(t_x1,
								t_y1, t_x2, t_y2, commonValues.getCurrentPage(), true, false,
								false, false, 0, false);
					else
						content = currentGrouping.extractTextAsTable(t_x1,
								t_y1, t_x2, t_y2, commonValues.getCurrentPage(), false, true,
								true, false, 1, false);

					extractedText = (String) content.get("content");
					break;

				case 4: //text wordlist extraction


					//always reset to use unaltered co-ords
					PdfGroupingAlgorithms.useUnrotatedCoords=true;

					//page data so we can choose portrait or landscape
					PdfPageData pageData=decode_pdf.getPdfPageData();
					int rotation=pageData.getRotation(commonValues.getCurrentPage());
					if(rotation!=0){
						int alterCoords=currentGUI.showConfirmDialog(Messages.getMessage("PdfViewerRotatedCoords.message"),
								Messages.getMessage("PdfViewerOutputFormat.message"),
								JOptionPane.YES_NO_OPTION);

						if(alterCoords==0)
							PdfGroupingAlgorithms.useUnrotatedCoords=false;
					}


					/**get the text*/
					Vector words = currentGrouping.extractTextAsWordlist(
							t_x1,
							t_y1,
							t_x2,
							t_y2,
							commonValues.getCurrentPage(),
							false,
							true,"()!;.,\\/\"\"\'\'");

					/**
					 * find out if xml or text - as we need to turn xml off before
					 * extraction. So we assume xml and strip out. This is obviously
					 */
					useXml=currentGUI.showConfirmDialog(Messages.getMessage("PdfViewerXmlMessage.message"),
							Messages.getMessage("PdfViewerOutputFormat.message"),JOptionPane.YES_NO_OPTION);

					if(words==null)
						currentGUI.showMessageDialog(Messages.getMessage("PdfViewerMessage.NoTextFound"));
					else if(useXml!=0){
						isXML=false;
					}else
						isXML=true;

					if(words!=null){
						//put words into list
						StringBuffer textOutput=new StringBuffer();
						Iterator wordIterator=words.iterator();

						while(wordIterator.hasNext()){

							String currentWord=(String) wordIterator.next();

							/**remove the XML formatting if present - not needed for pure text*/
							if(!isXML)
								currentWord=Strip.convertToText(currentWord);

							int wx1=(int)Float.parseFloat((String) wordIterator.next());
							int wy1=(int)Float.parseFloat((String) wordIterator.next());
							int wx2=(int)Float.parseFloat((String) wordIterator.next());
							int wy2=(int)Float.parseFloat((String) wordIterator.next());

							/**this could be inserting into a database instead*/
							textOutput.append(currentWord).append(',').append(wx1).append(',').append(wy1).append(',').append(wx2).append(',').append(wy2).append('\n');

						}

						extractedText=textOutput.toString();
					}

					break;

				case 5: //find word in text

					String textToFind=currentGUI.showInputDialog(Messages.getMessage("PdfViewerMessage.GetUserInput"));

					//if cancel return to menu.
					if(textToFind==null || textToFind.length()<1){
						display=true;
						break;
					}

					//<start-demo>
					/**<end-demo>
	                    JOptionPane.showMessageDialog(currentGUI.getFrame(),Messages.getMessage("PdfViewerMessage.FindDemo"));
	                    textToFind=null;
	                    /**/

					int searchType = SearchType.DEFAULT;

					int caseSensitiveOption=currentGUI.showConfirmDialog(Messages.getMessage("PdfViewercase.message"),
							null,	JOptionPane.YES_NO_OPTION);

					if(caseSensitiveOption==0)
						searchType |= SearchType.CASE_SENSITIVE;

					int findAllOption=currentGUI.showConfirmDialog(Messages.getMessage("PdfViewerfindAll.message"),
							null,	JOptionPane.YES_NO_OPTION);

					if(findAllOption!=0)
						searchType |= SearchType.FIND_FIRST_OCCURANCE_ONLY;


					if(textToFind!=null){
						float[] co_ords = currentGrouping.findTextInRectangle(
								t_x1,
								t_y1,
								t_x2,
								t_y2,
								commonValues.getCurrentPage(),
								textToFind,
								searchType);

						if(co_ords!=null){
							if(co_ords.length<3)
								currentGUI.showMessageDialog(Messages.getMessage("PdfViewerMessage.Found")+ ' ' +co_ords[0]+ ',' +co_ords[1]);
							else{
								StringBuffer displayCoords = new StringBuffer();
								for(int i=0;i<co_ords.length/2;i++){
									displayCoords.append(Messages.getMessage("PdfViewerMessage.FoundAt")).append(' ');
									System.out.println("coord.length="+co_ords.length+" i="+i);
									displayCoords.append(co_ords[i*2]);
									displayCoords.append(',');
									displayCoords.append(co_ords[i*2+1]);
									displayCoords.append('\n');
								}
								currentGUI.showMessageDialog(displayCoords.toString());
							}
						}else
							currentGUI.showMessageDialog(Messages.getMessage("PdfViewerMessage.NotFound"));
					}

					break;

				default: //just in case user edits code and to handle cancel
					break;

				}

				if (extractedText != null) {

					JScrollPane scroll=new JScrollPane();
					try {
						JTextPane text_pane=new JTextPane();
						scroll = currentGUI.createPane(text_pane,extractedText,  isXML);
					} catch (BadLocationException e1) {
						e1.printStackTrace();
					}
					scroll.setHorizontalScrollBarPolicy(JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
					scroll.setVerticalScrollBarPolicy(JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED);
					scroll.setPreferredSize(new Dimension(400,400));

					/**resizeable pop-up for content*/
					final JDialog displayFrame =  new JDialog(currentGUI.getFrame(),true);
					if(commonValues.getModeOfOperation()!=Values.RUNNING_APPLET){
						JFrame frame = currentGUI.getFrame();
						displayFrame.setLocation(frame.getLocationOnScreen().x+10,frame.getLocationOnScreen().y+10);
					}

					displayFrame.setSize(450,450);

					displayFrame.setTitle(Messages.getMessage("PdfViewerExtractedText.menu"));
					displayFrame.getContentPane().setLayout(new BorderLayout());
					displayFrame.getContentPane().add(scroll,BorderLayout.CENTER);

					JPanel buttonBar=new JPanel();
					buttonBar.setLayout(new BorderLayout());
					displayFrame.getContentPane().add(buttonBar,BorderLayout.SOUTH);

					/**
					 * yes option allows user to save content
					 */
					JButton yes=new JButton(Messages.getMessage("PdfViewerMenu.return"));
					yes.setFont(new Font("SansSerif", Font.PLAIN, 12));
					buttonBar.add(yes,BorderLayout.WEST);
					yes.addActionListener(new ActionListener(){

						public void actionPerformed(ActionEvent e) {
							display=true;
							displayFrame.dispose();

						}
					});

					/**
					 * no option just removes display
					 */
					JButton no=new JButton(Messages.getMessage("PdfViewerFileMenuExit.text"));
					no.setFont(new Font("SansSerif", Font.PLAIN, 12));
					buttonBar.add(no,BorderLayout.EAST);
					no.addActionListener(new ActionListener(){

						public void actionPerformed(ActionEvent e) {

							displayFrame.dispose();
						}});

					/**show the popup*/
					displayFrame.setVisible(true);

				}

			} catch (PdfException e) {
				System.err.println("Exception " + e.getMessage()
						+ " in file " + commonValues.getSelectedFile());
				e.printStackTrace();
			}
		}
	}

	/**
	 * called by nav functions to decode next page
	 */
	private void decodeImage(final boolean resizePanel) {
		
        //remove any search highlight
		decode_pdf.setFoundTextArea(null);
		decode_pdf.setFoundTextAreas(null);

		currentGUI.setRectangle(null);

		//stop user changing scaling while decode in progress
		currentGUI.resetComboBoxes(false);

		//<start-os>
		currentGUI.setPageLayoutButtonsEnabled(false);
		//<end-os>

        /** flush any previous pages */
		decode_pdf.getDynamicRenderer().flush();

        decode_pdf.clearScreen();

		/** if running terminate first */
		thumbnails.terminateDrawing();

		commonValues.setProcessing(true);

		SwingWorker worker = new SwingWorker() {
			public Object construct() {

				try {

					currentGUI.updateStatusMessage(Messages.getMessage("PdfViewerDecoding.page"));

					if (img != null)
						decode_pdf.addImage(img);

					PdfPageData page_data = decode_pdf.getPdfPageData();
					
					if (img != null)
						page_data.setMediaBox(new float[]{0,0,img.getWidth(),img.getHeight()});
					
					page_data.checkSizeSet(1);
					currentGUI.resetRotationBox();

					/**
					 * make sure screen fits display nicely
					 */
					if ((resizePanel) && (thumbnails.isShownOnscreen()))
						currentGUI.zoom(false);

					if (Thread.interrupted())
						throw new InterruptedException();
					
					currentGUI.setPageNumber();

					// <start-13>
					currentGUI.setViewerTitle(null); // restore title
					// <end-13>

				} catch (Exception e) {
					//<start-13>
					currentGUI.setViewerTitle(null); //restore title
					//<end-13>
				}

				currentGUI.setStatusProgress(100);

				//reanable user changing scaling
				currentGUI.resetComboBoxes(true);
				
				//ensure drawn
				decode_pdf.repaint();

				openingTransferedFile=false;
				
				return null;
			}
		};

		worker.start();
	}

	/**
	 *  initial method called to open a new PDF
	 * @throws PdfException 
	 */
	protected boolean openUpFile(String selectedFile) throws PdfException {

		commonValues.maxViewY=0;// rensure reset for any viewport
		
		//Turn MultiPageTiff flag off to ensure no mistakes
		commonValues.setMultiTiff(false);
		
		boolean fileCanBeOpened = true;
		
		//<start-os>
		if(currentGUI.isSingle()){
			//<end-os>

			decode_pdf.closePdfFile();

			/** reset default values */
			currentGUI.setScalingToDefault();

			//<start-os>
		}
		//<end-os>

		/** ensure all data flushed from PdfDecoder before we decode the file */
		//decode_pdf.flushObjectValues(true);
		decode_pdf.markAllPagesAsUnread();

		try {
			//System.out.println("commonValues.isPDF() = "+commonValues.isPDF()+" <<<");
			/** opens the pdf and reads metadata */
			if(commonValues.isPDF()){
				if(selectedFile.startsWith("http:") || selectedFile.startsWith("file:")){
					try{
						decode_pdf.setDownloadWindomPosition(currentGUI.getFrame().getLocationOnScreen().x+(currentGUI.getFrame().getWidth()/2), currentGUI.getFrame().getLocationOnScreen().y+(currentGUI.getFrame().getHeight()/2));
						decode_pdf.openPdfFileFromURL(commonValues.getSelectedFile());
					}catch(Exception e){
						currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.UrlError"));
						selectedFile=null;
						fileCanBeOpened=false;
					}
				}else{
					decode_pdf.openPdfFile(commonValues.getSelectedFile());

					//<start-os>
					if(decode_pdf.getPageCount()>1 && !commonValues.isContentExtractor())
						currentGUI.setPageLayoutButtonsEnabled(true);
					hideRedundentNavButtons();
					//<end-os>
				}


				//reset thumbnails
				currentGUI.reinitThumbnails();


			}else{

				//set values for page display
				decode_pdf.resetForNonPDFPage();
				
				decode_pdf.setlastPageDecoded(1);

				boolean isTiff=selectedFile.toLowerCase().indexOf(".tif")!=-1;

				//decode image
				if(JAIHelper.isJAIused())
					JAIHelper.confirmJAIOnClasspath();

				boolean isURL = selectedFile.startsWith("http:") || selectedFile.startsWith("file:");

                if(isTiff && JAIHelper.isJAIused()){
					try {

                        tiffHelper=new JAITiffHelper(commonValues.getSelectedFile());

						int pageCount=tiffHelper.getTiffPageCount();

                        //Default to first page
						tiffImageToLoad = 0;
						
						//Multiple pages held within Tiff
                         if(pageCount>1){
							//Set page count (not with Pdf_Decoder as file not pdf)
							decode_pdf.setPageCount(pageCount);
							commonValues.setPageCount(pageCount);
							decode_pdf.setlastPageDecoded(1);
								//Flag to show this is a Tiff with multiple pages
							commonValues.setMultiTiff(true);
							decode_pdf.setMultiPageTiff(true);
							
						}

						drawMultiPageTiff();

					} catch (Exception e) {
                        e.printStackTrace();
                        LogWriter.writeLog("Exception " + e + Messages.getMessage("PdfViewerError.Loading") + commonValues.getSelectedFile());
					}
				}else{

					boolean showTiffMessage = isTiff && properties.getValue("showtiffmessage").equals("true");

					if (showTiffMessage) {
						JPanel panel = new JPanel();
						panel.setLayout(new GridBagLayout());

						final GridBagConstraints p = new GridBagConstraints();
						p.anchor = GridBagConstraints.WEST;
						p.gridx = 0;
						p.gridy = 0;

						String str = "<html>Some Tiff images do not display correctly without JAI support turned on.  "
							+ "<br>See <a href=\"http://support.idrsolutions.com/default.asp?W19\"> http://support.idrsolutions.com/default.asp?W19"
							+ "</a> for information on enabling JAI.";

						JCheckBox cb = new JCheckBox();
						cb.setText(Messages.getMessage("PdfViewerFormsWarning.CheckBox"));

						Font font = cb.getFont();
						JEditorPane ta = new JEditorPane();
						ta.addHyperlinkListener(new HyperlinkListener() {
							public void hyperlinkUpdate(HyperlinkEvent e) {
								if (e.getEventType() == HyperlinkEvent.EventType.ACTIVATED) {
									try {
										BrowserLauncher.openURL(e.getURL()
												.toExternalForm());
									} catch (IOException e1) {
										e1.printStackTrace();
									}
								}
							}
						});

						ta.setEditable(false);
						ta.setContentType("text/html");
						ta.setText(str);
						ta.setOpaque(false);
						ta.setFont(font);

						p.ipady = 20;
						panel.add(ta, p);
						p.ipady = 0;
						p.gridy = 1;
						panel.add(cb, p);

						JOptionPane.showMessageDialog(currentGUI.getFrame(), panel);
						if (cb.isSelected())
							properties.setValue("showtiffmessage", "false");
					}
					try {
						// Load the source image from a file.
						if (isURL)
							img = ImageIO.read(new URL(selectedFile));
						else{
							img = ImageIO.read(new File(selectedFile));
						}
					} catch (Exception e) {
						LogWriter.writeLog("Exception " + e + "loading " + commonValues.getSelectedFile());
					}

				}
				hideRedundentNavButtons();
			}
			//<<>>
			currentGUI.updateStatusMessage("opening file");

			/** popup window if needed */
			if ((fileCanBeOpened)&&(decode_pdf.isEncrypted()) && (!decode_pdf.isFileViewable())) {
				fileCanBeOpened = false;
				//<start-13>
				/**
				 * //<end-13>JOptionPane.showMessageDialog(currentGUI.frame,"Please
				 * use Java 1.4 to display encrypted files"); //<start-13>
				 */

				String password = currentGUI.showInputDialog(Messages.getMessage("PdfViewerPassword.message")); //$NON-NLS-1$

				/** try and reopen with new password */
				if (password != null) {
					decode_pdf.setEncryptionPassword(password);
					//decode_pdf.verifyAccess();

					if (decode_pdf.isFileViewable())
						fileCanBeOpened = true;

				}

				if(!fileCanBeOpened)
					currentGUI.showMessageDialog(Messages.getMessage("PdfViewerPasswordRequired.message"));
				//<end-13>
			}

//			currentGUI.reinitialiseTabs();

			if (fileCanBeOpened) {

				if(!commonValues.isContentExtractor()){
					properties.addRecentDocument(commonValues.getSelectedFile());
					updateRecentDocuments(properties.getRecentDocuments());
				}

				recent.addToFileList(commonValues.getSelectedFile());

				/** reset values */
				commonValues.setCurrentPage(1);
			}


		} catch (PdfException e) {
			System.err.println(("Exception " + e + " opening file"));

			//<start-os>
			if(currentGUI.isSingle()) {
				//<end-os>
				ErrorDialog.showError(e,Messages.getMessage("PdfViewerOpenerror"),currentGUI.getFrame(),commonValues.getSelectedFile());
				if(SimpleViewer.exitOnClose)
                    System.exit(1);
                else{
                    currentGUI.getFrame().setVisible(false);
                    currentGUI.getFrame().dispose();
                }
                //<start-os>
			}
			//<end-os>
			
			throw e;
		}

		if(!decode_pdf.isOpen() && commonValues.isPDF())
			return false;
		else
			return fileCanBeOpened;
	}

	/**
	 *  checks file can be opened (permission)
	 * @throws PdfException 
	 */
	public void openFile(String selectedFile) throws PdfException {
		
		decode_pdf.setPDF(false);
		decode_pdf.setMultiPageTiff(false);
		
		//get any user set dpi
		String hiresFlag = System.getProperty("org.jpedal.hires");
		if(PdfDecoder.hires || hiresFlag != null)
			commonValues.setUseHiresImage(true);

		//get any user set dpi
		String memFlag=System.getProperty("org.jpedal.memory");
		if(memFlag!=null){
			commonValues.setUseHiresImage(false);
		}

		//reset flag
		thumbnails.resetToDefault();

		//flush forms list
		currentGUI.setNoPagesDecoded();

		//remove search frame if visible
		if(searchFrame!=null)
			searchFrame.removeSearchWindow(false);

		commonValues.maxViewY=0;// rensure reset for any viewport
		String ending=selectedFile.toLowerCase().trim();
		commonValues.setPDF(ending.endsWith(".pdf")||ending.endsWith(".fdf"));
		decode_pdf.setPDF(ending.endsWith(".pdf")||ending.endsWith(".fdf"));

		//<start-os>
		//switch off continous mode for images
		if(!commonValues.isPDF())
			decode_pdf.setDisplayView(Display.SINGLE_PAGE, Display.DISPLAY_CENTERED);
		//<end-os>

		currentGUI.setQualityBoxVisible(commonValues.isPDF());

		commonValues.setCurrentPage(1);

		boolean fileCanBeOpened=openUpFile(commonValues.getSelectedFile());

        try{
			if(fileCanBeOpened)
				processPage();
			else{
				currentGUI.setViewerTitle(Messages.getMessage("PdfViewer.NoFile"));
				decode_pdf.clearScreen();
				this.currentGUI.zoom(false);
				commonValues.setPageCount(1);
				commonValues.setCurrentPage(1);
			}
		}catch(Exception e){
			System.err.println(Messages.getMessage("PdfViewerError.Exception")+ ' ' + e + ' ' + Messages.getMessage("PdfViewerError.DecodeFile"));

		}

		//commonValues.setProcessing(false);
	}


	/**
	 * decode and display selected page
	 */
	protected void processPage() {

		if (commonValues.isPDF() && ((decode_pdf.isOpen() || !commonValues.isPDF()))) {
			/**
			 * get PRODUCER and if OCR disable text printing
			 */
			PdfFileInformation currentFileInformation=decode_pdf.getFileInformationData();

			/**switch all on by default*/
			decode_pdf.setRenderMode(PdfDecoder.RENDERIMAGES+PdfDecoder.RENDERTEXT);

			String[] values=currentFileInformation.getFieldValues();
			String[] fields=currentFileInformation.getFieldNames();

			/** holding all creators that produce OCR pdf's */
			String[] ocr = {"TeleForm","dgn2pdf"};

			for(int i=0;i<fields.length;i++){

				if((fields[i].equals("Creator"))|(fields[i].equals("Producer"))){

					for(int j=0;j<ocr.length;j++){

						if(values[i].equals(ocr[j])){

							decode_pdf.setRenderMode(PdfDecoder.RENDERIMAGES);

							/**
							 * if we want to use java 13 JPEG conversion
							 */
							decode_pdf.setEnableLegacyJPEGConversion(true);

						}
					}
				}

				boolean currentProcessingStatus=commonValues.isProcessing();
				commonValues.setProcessing(true);	//stops listeners processing spurious event

				if(commonValues.isUseHiresImage()){
					decode_pdf.useHiResScreenDisplay(true);
					currentGUI.setSelectedComboIndex(Commands.QUALITY,1);
				}else{
					decode_pdf.useHiResScreenDisplay(false);
					currentGUI.setSelectedComboIndex(Commands.QUALITY,0);
				}
				commonValues.setProcessing(currentProcessingStatus);

			}
		}

		/**special customisations for images*/
		if(commonValues.isPDF())
			commonValues.setPageCount(decode_pdf.getPageCount());
		else if(!commonValues.isMultiTiff()){
			commonValues.setPageCount(1);
			decode_pdf.useHiResScreenDisplay(true);
        }


		if(commonValues.getPageCount()<commonValues.getCurrentPage()){
			commonValues.setCurrentPage(commonValues.getPageCount());
			System.err.println(commonValues.getCurrentPage()+ " out of range. Opening on last page");
			LogWriter.writeLog(commonValues.getCurrentPage()+ " out of range. Opening on last page");
		}


		//values extraction mode,dpi of images, dpi of page as a factor of 72

		decode_pdf.setExtractionMode(PdfDecoder.TEXT, 72, currentGUI.getScaling());

		/**
		 * update the display, including any rotation
		 */
		currentGUI.setPageNumber();

		currentGUI.resetRotationBox();

        if(commonValues.isPDF()){
			currentGUI.messageShown=false;
			currentGUI.decodePage(true);
		}else{
			//resize (ensure at least certain size)
			currentGUI.zoom(false);

			//add a border
			decode_pdf.setPDFBorder(BorderFactory.createLineBorder(Color.black, 1));

			/** turn off border in printing */
			decode_pdf.disableBorderForPrinting();

			decodeImage(true);

			commonValues.setProcessing(false);
		}
	}

	/** opens a pdf file and calls the display/decode routines */
	public void selectFile() {
		
		//remove search frame if visible
		if(searchFrame!=null)
			searchFrame.removeSearchWindow(false);

		currentGUI.resetNavBar();

		/**
		 * create the file chooser to select the file
		 */
		final JFileChooser chooser= new JFileChooser(commonValues.getInputDir());
		chooser.setName("chooser");
		if(commonValues.getSelectedFile()!=null)
			chooser.setSelectedFile(new File(commonValues.getSelectedFile()));
		chooser.setFileSelectionMode(JFileChooser.FILES_ONLY);

		String[] pdf = new String[] { "pdf" };
		String[] fdf = new String[] { "fdf" };
		String[] png = new String[] { "png","tif","tiff","jpg","jpeg" };
		chooser.addChoosableFileFilter(new FileFilterer(png, "Images (Tiff, Jpeg,Png)"));
		chooser.addChoosableFileFilter(new FileFilterer(fdf, "fdf (*.fdf)"));
		chooser.addChoosableFileFilter(new FileFilterer(pdf, "Pdf (*.pdf)"));

		final int state = chooser.showOpenDialog(currentGUI.getFrame());

		//ensure immediate redraw of blank screen
		//decode_pdf.invalidate();
		//decode_pdf.repaint();

		final File file = chooser.getSelectedFile();

        /**
		 * decode
		 */
		if (file != null && state == JFileChooser.APPROVE_OPTION) {

			String ext=file.getName().toLowerCase();
			boolean isValid=((ext.endsWith(".pdf"))||(ext.endsWith(".fdf"))||
					(ext.endsWith(".tif"))||(ext.endsWith(".tiff"))||
					(ext.endsWith(".png"))||
					(ext.endsWith(".jpg"))||(ext.endsWith(".jpeg")));

            if(isValid){
				/** save path so we reopen her for later selections */
				try {
					commonValues.setInputDir(chooser.getCurrentDirectory().getCanonicalPath());
					open(file.getAbsolutePath());

				} catch (IOException e1) {
					e1.printStackTrace();
				}
			}else{
				decode_pdf.repaint();
				currentGUI.showMessageDialog( Messages.getMessage("PdfViewer.NotValidPdfWarning"));
			}

		} else { //no file selected so redisplay old
			decode_pdf.repaint();
			currentGUI.showMessageDialog( Messages.getMessage("PdfViewerMessage.NoSelection"));
		}
	}

	private String selectURL() {

		String selectedFile = currentGUI.showInputDialog(Messages.getMessage("PdfViewerMessage.RequestURL"));

		//lose any spaces
		if(selectedFile!=null)
			selectedFile=selectedFile.trim();

		if ((selectedFile != null) && !selectedFile.trim().startsWith("http://") && !selectedFile.trim().startsWith("file:/")) {
			currentGUI.showMessageDialog(Messages.getMessage("PdfViewerMessage.URLMustContain"));
			selectedFile = null;
		}

		if(selectedFile!=null){
			boolean isValid = ((selectedFile.endsWith(".pdf"))
					|| (selectedFile.endsWith(".fdf")) || (selectedFile.endsWith(".tif"))
					|| (selectedFile.endsWith(".tiff")) || (selectedFile.endsWith(".png"))
					|| (selectedFile.endsWith(".jpg")) || (selectedFile.endsWith(".jpeg")));


			if (!isValid) {
				currentGUI.showMessageDialog(Messages.getMessage("PdfViewer.NotValidPdfWarning"));
				selectedFile=null;
			}
		}

		if(selectedFile!=null){

			commonValues.setSelectedFile(selectedFile);

			boolean failed=false;
			try {
				URL testExists=new URL(selectedFile);
				URLConnection conn=testExists.openConnection();

				if(conn.getContent()==null)
					failed=true;
			} catch (Exception e) {
				failed=true;
			}

			if(failed){
				selectedFile=null;
				currentGUI.showMessageDialog("URL "+selectedFile+ ' ' +Messages.getMessage("PdfViewerError.DoesNotExist"));
			}
		}

		//ensure immediate redraw of blank screen
		//decode_pdf.invalidate();
		//decode_pdf.repaint();

		/**
		 * decode
		 */
		if (selectedFile != null ) {
			try {

				commonValues.setFileSize(0);

				/** save path so we reopen her for later selections */
				//commonValues.setInputDir(new URL(commonValues.getSelectedFile()).getPath());

				currentGUI.setViewerTitle(null);

			} catch (Exception e) {
				System.err.println(Messages.getMessage("PdfViewerError.Exception")+ ' ' + e + ' ' +Messages.getMessage("PdfViewerError.GettingPaths"));
			}

			/**
			 * open the file
			 */
			if ((selectedFile != null) && (commonValues.isProcessing() == false)) {

				/**
				 * trash previous display now we are sure it is not needed
				 */
				//decode_pdf.repaint();

				/** if running terminate first */
				thumbnails.terminateDrawing();

				decode_pdf.flushObjectValues(true);

				//reset the viewableArea before opening a new file
				decode_pdf.resetViewableArea();

				currentGUI.stopThumbnails();

				//<start-os>
				if(!currentGUI.isSingle()) {
					openNewMultiplePage(commonValues.getSelectedFile());
				}
				//<end-os>

				try {
					openFile(commonValues.getSelectedFile());
				} catch (PdfException e) {
				}
			}

		} else { //no file selected so redisplay old
			decode_pdf.repaint();
			currentGUI.showMessageDialog(Messages.getMessage("PdfViewerMessage.NoSelection"));
		}

		return selectedFile;
	}

	/**move forward one page*/
	private void forward(int count) {

		if (!commonValues.isProcessing()) { //lock to stop multiple accesses

			/**if in range update count and decode next page. Decoded pages are cached so will redisplay
			 * almost instantly*/
			int updatedTotal=commonValues.getCurrentPage()+count;

			if (updatedTotal <= commonValues.getPageCount()) {

				if(commonValues.isMultiTiff()){

					//Update page number and draw new page
					tiffImageToLoad = (decode_pdf.getlastPageDecoded()-1) + count;
					drawMultiPageTiff();

					//Update Tiff page
					commonValues.setCurrentPage(updatedTotal);
					decode_pdf.setlastPageDecoded(tiffImageToLoad+1);
					currentGUI.setPageNumber();

					//Display new page
					decode_pdf.repaint();

				}else{
					/**
					 * adjust for double jump on facing
					 */
					if(decode_pdf.getDisplayView() == Display.FACING || decode_pdf.getDisplayView() == Display.CONTINUOUS_FACING){
						if((updatedTotal & 1)==1){
							if(updatedTotal<commonValues.getPageCount())
								updatedTotal++;
							else if(commonValues.getPageCount()-updatedTotal>1)
								updatedTotal--;
						}
					}
					commonValues.setCurrentPage(updatedTotal);
					currentGUI.setPageNumber();

					if(decode_pdf.getDisplayView() == Display.CONTINUOUS ||decode_pdf.getDisplayView() == Display.CONTINUOUS_FACING){

						currentGUI.decodePage(false);

						return ;
					}

					currentGUI.resetStatusMessage("Loading Page "+commonValues.getCurrentPage());
					/**reset as rotation may change!*/
					decode_pdf.setPageParameters(currentGUI.getScaling(), commonValues.getCurrentPage());

					//would reset scaling on page change to default
					//currentGUI.setScalingToDefault();

					//decode the page
					if(commonValues.isPDF())
						currentGUI.decodePage(false);

					//if scaling to window reset screen to fit rotated page
//					if(currentGUI.getSelectedComboIndex(Commands.SCALING)<3)
//					currentGUI.zoom();

				}
			}
		}else
			currentGUI.showMessageDialog(Messages.getMessage("PdfViewerDecodeWait.message"));
		
		hideRedundentNavButtons();
	}



	/** move back one page */
	private void back(int count) {

		if (!commonValues.isProcessing()) { //lock to stop multiple accesses

			/**
			 * if in range update count and decode next page. Decoded pages are
			 * cached so will redisplay almost instantly
			 */
			int updatedTotal=commonValues.getCurrentPage()-count;
			if (updatedTotal >= 1) {

				if(commonValues.isMultiTiff()){

					//Update page number and draw new page
					tiffImageToLoad = (decode_pdf.getlastPageDecoded()-1) - count;
					drawMultiPageTiff();

					//Update Tiff page
					commonValues.setCurrentPage(updatedTotal);
					decode_pdf.setlastPageDecoded(tiffImageToLoad+1);
					currentGUI.setPageNumber();

					//Display new page
					decode_pdf.repaint();

				}else{

					/**
					 * adjust for double jump on facing
					 */
					if(decode_pdf.getDisplayView() == Display.FACING || decode_pdf.getDisplayView() == Display.CONTINUOUS_FACING){
						if((updatedTotal & 1)==1 && updatedTotal!=1)
							updatedTotal--;
					}

					commonValues.setCurrentPage(updatedTotal);
					currentGUI.setPageNumber();

					if(decode_pdf.getDisplayView() == Display.CONTINUOUS ||
							decode_pdf.getDisplayView() == Display.CONTINUOUS_FACING){
						
						currentGUI.decodePage(false);

						return ;
					}

					currentGUI.resetStatusMessage("loading page "+commonValues.getCurrentPage());

					/** reset as rotation may change! */
					decode_pdf.setPageParameters(currentGUI.getScaling(), commonValues.getCurrentPage());

					//would reset scaling on page change to default
					//currentGUI.setScalingToDefault(); //set to 100%
					
					if(commonValues.isPDF())
						currentGUI.decodePage(false);

					//if scaling to window reset screen to fit rotated page
					//if(currentGUI.getSelectedComboIndex(Commands.SCALING)<3)
					//	currentGUI.zoom();

				}
			}
		}else
			currentGUI.showMessageDialog(Messages.getMessage("PdfViewerDecodeWait.message"));
		
		hideRedundentNavButtons();
	}

	public void gotoPage(String page) {
		int newPage;

		//allow for bum values
		try{
			newPage=Integer.parseInt(page);

			/**
			 * adjust for double jump on facing
			 */
			if(decode_pdf.getDisplayView() == Display.FACING || decode_pdf.getDisplayView() == Display.CONTINUOUS_FACING){
				if((newPage & 1)==1 && newPage!=1){
					newPage--;
				}
			}


			//allow for invalid value
			if((newPage>decode_pdf.getPageCount())|(newPage<1)){

				currentGUI.showMessageDialog(Messages.getMessage("PdfViewerPageLabel.text")+ ' ' +
						page+ ' ' +Messages.getMessage("PdfViewerOutOfRange.text")+ ' ' +decode_pdf.getPageCount());

				newPage=commonValues.getCurrentPage();



				currentGUI.setPageNumber();
			}

		}catch(Exception e){
			currentGUI.showMessageDialog('>' +page+ "< "+Messages.getMessage("PdfViewerInvalidNumber.text"));
			newPage=commonValues.getCurrentPage();
			currentGUI.pageCounter2.setText(String.valueOf(commonValues.getCurrentPage()));
		}

		//open new page
		if((!commonValues.isProcessing())&&(commonValues.getCurrentPage()!=newPage)){

			commonValues.setCurrentPage(newPage);
			currentGUI.decodePage(false);
			//currentGUI.zoom();
		}
		hideRedundentNavButtons();
	}

	private void open(final String file) {

		currentGUI.resetNavBar();


		boolean isURL = file.startsWith("http:") || file.startsWith("file:");
		try {

			if(!isURL){
				fileIsURL=false;
				commonValues.setFileSize(new File(file).length() >> 10);
			}else
				fileIsURL=true;

			commonValues.setSelectedFile(file);
			currentGUI.setViewerTitle(null);

		} catch (Exception e) {
			LogWriter.writeLog("Exception " + e + " getting paths");
		}

		/** check file exists */
		File testFile = new File(commonValues.getSelectedFile());
		if (!isURL && !testFile.exists()) {
			currentGUI.showMessageDialog(Messages.getMessage("PdfViewerFile.text") + commonValues.getSelectedFile() + Messages.getMessage("PdfViewerNotExist"));

			/** open the file*/
		} else if ((commonValues.getSelectedFile() != null) && (commonValues.isProcessing() == false)) {

			//<start-os>

			if(currentGUI.isSingle()){
				//<end-os>

				decode_pdf.flushObjectValues(true);

				//<start-os>
			}else
				decode_pdf=openNewMultiplePage(commonValues.getSelectedFile());
			//<end-os>

			//reset the viewableArea before opening a new file
			decode_pdf.resetViewableArea();
			/**/
			SwingWorker worker = new SwingWorker() {
				public Object construct() {
					if(!isOpening){
							isOpening=true;
							try {
								openFile(commonValues.getSelectedFile());
							} catch (PdfException e) {
							}
							isOpening=false;
					}
					return null;
				}
			};
			worker.start();

			/**
            while(1==1){

            SwingWorker worker = new SwingWorker() {
				public Object construct() {
					if(!isOpening){
						isOpening=true;
                        System.out.println("Open FIle>>>>>>>>>>>>>>>>>>>>>");
                        try {
                            decode_pdf.openPdfFile(commonValues.getSelectedFile());
                        } catch (PdfException e) {
                            e.printStackTrace();  //To change body of catch statement use File | Settings | File Templates.
                        }
                        //openFile(commonValues.getSelectedFile());
                        System.out.println("Open FIle<<<<<<<<<<<<<<<<<<<<<");
                        isOpening=false;
                    }
					return null;
				}
			};
			worker.start();

            while( !decode_pdf.isOpen()){
                try {
                    Thread.sleep(50);
                } catch (InterruptedException e) {
                    e.printStackTrace();  //To change body of catch statement use File | Settings | File Templates.
                }
            }

            //random sleep
            try {
                long sleep=(long)(Math.random()*100);
                System.out.println("sleep for "+sleep);
                    Thread.sleep( sleep);
                } catch (InterruptedException e) {
                    e.printStackTrace();  //To change body of catch statement use File | Settings | File Templates.
                }

            System.out.println("1stop!!!");
            decode_pdf.stopDecoding();
            System.out.println("2stop!!!");

            //decode_pdf.closePdfFile();
            }/***/

		}
	}

	private void updateRecentDocuments(String[] recentDocs) {
		if(recentDocs == null)
			return;

		for(int i=0; i<recentDocs.length;i++){
			if(recentDocs[i] != null){
				String shortenedFileName = recent.getShortenedFileName(recentDocs[i]);

				recentDocuments[i].setText(i+1 + ": " + shortenedFileName);
				if(recentDocuments[i].getText().equals(i+1 + ": "))
					recentDocuments[i].setVisible(false);
				else
					recentDocuments[i].setVisible(true);
				recentDocuments[i].setName(recentDocs[i]);
			}
		}
	}

	private void drawMultiPageTiff(){


        if(tiffHelper!=null){
            img=tiffHelper.getImage(tiffImageToLoad);

            if(img!=null){
                /** flush any previous pages */
                decode_pdf.getDynamicRenderer().flush();

                decode_pdf.addImage(img);
            }
        }
	}

	protected void recentDocumentsOption(JMenu file) {
		String[] recentDocs=properties.getRecentDocuments();
		if(recentDocs == null)
			return;

		for(int i=0;i<noOfRecentDocs;i++){

			if(recentDocs[i]==null)
				recentDocs[i]="";

			String fileNameToAdd=recentDocs[i];
			String shortenedFileName = recent.getShortenedFileName(fileNameToAdd);

			recentDocuments[i] = new JMenuItem(i+1 + ": " + shortenedFileName);
			if(recentDocuments[i].getText().equals(i+1 + ": "))
				recentDocuments[i].setVisible(false);
			recentDocuments[i].setName(fileNameToAdd);
			recentDocuments[i].addActionListener(new ActionListener(){
				public void actionPerformed(ActionEvent e) {

					if(currentPrinter.isPrinting())
						currentGUI.showMessageDialog(Messages.getMessage("PdfViewerPrintWait.message"));
					else if(commonValues.isProcessing() || isOpening)
						currentGUI.showMessageDialog(Messages.getMessage("PdfViewerDecodeWait.message"));
					else{
						/**
						 * warn user on forms
						 */
						handleUnsaveForms();

						JMenuItem item = (JMenuItem)e.getSource();
						String fileName = item.getName();

						if (fileName.length() != 0)
							open(fileName);
					}
				}
			});
			file.add(recentDocuments[i]);
		}
	}



	private void saveFile() {	

		/**
		 * create the file chooser to select the file
		 */
		File file=null;
		String fileToSave="";
		boolean finished=false;

		while(!finished){
			JFileChooser chooser = new JFileChooser(commonValues.getInputDir());
			chooser.setSelectedFile(new File(commonValues.getInputDir()+ '/' +commonValues.getSelectedFile()));
			chooser.addChoosableFileFilter(new FileFilterer(new String[]{"pdf"}, "Pdf (*.pdf)"));
			chooser.addChoosableFileFilter(new FileFilterer(new String[]{"fdf"}, "fdf (*.fdf)"));
			chooser.setFileSelectionMode(JFileChooser.FILES_ONLY);

			//set default name to current file name 
			int approved=chooser.showSaveDialog(null);
			if(approved==JFileChooser.APPROVE_OPTION){

				FileInputStream fis=null;
				FileOutputStream fos=null;

				file = chooser.getSelectedFile();
				fileToSave=file.getAbsolutePath();

				if(!fileToSave.endsWith(".pdf")){
					fileToSave += ".pdf";
					file=new File(fileToSave);
				}

				if(fileToSave.equals(commonValues.getSelectedFile()))
					return;

				if(file.exists()){
					int n=currentGUI.showConfirmDialog(fileToSave+ '\n' +
							Messages.getMessage("PdfViewerMessage.FileAlreadyExists")+ '\n' +
							Messages.getMessage("PdfViewerMessage.ConfirmResave"),
							Messages.getMessage("PdfViewerMessage.Resave"),JOptionPane.YES_NO_OPTION);
					if(n==1)
						continue;
				}

				try {
					fis=new FileInputStream(commonValues.getSelectedFile());
					fos=new FileOutputStream(fileToSave);

					byte[] buffer=new byte[4096];
					int bytes_read;

					while((bytes_read=fis.read(buffer))!=-1)
						fos.write(buffer,0,bytes_read);
				} catch (Exception e1) {

					//e1.printStackTrace();
					currentGUI.showMessageDialog(Messages.getMessage("PdfViewerException.NotSaveInternetFile"));
				}

				try{
					fis.close();
					fos.close();
				} catch (Exception e2) {
					//e2.printStackTrace();
				}

				finished=true;
			}else{
				return;
			}
		}
	}

	/**Clean up and exit program*/
	private void exit() {

		thumbnails.terminateDrawing();

		/**
		 * warn user on forms
		 */
		handleUnsaveForms();

		/**
		 * create the dialog
		 */
		currentGUI.showConfirmDialog(new JLabel(Messages.getMessage("PdfViewerExiting")),
				Messages.getMessage("PdfViewerprogramExit"), 
				JOptionPane.DEFAULT_OPTION,
				JOptionPane.PLAIN_MESSAGE);

		/**cleanup*/
		decode_pdf.closePdfFile();

		flush();
		
		try {
			properties.writeDoc();
		} catch (Exception e1) {
			// TODO Auto-generated catch block
		}
		
		//@exit
        if(SimpleViewer.exitOnClose)
            System.exit(1);
        else{
            currentGUI.getFrame().setVisible(false);
            currentGUI.getFrame().dispose();
        }
    }

	/**
	 * routine to remove all objects from temp store
	 */
	public final void flush() {

		String target=commonValues.getTarget();

		if(target!=null){
			//get contents

			File temp_files = new File(target);
			String[] file_list = temp_files.list();
			if (file_list != null) {
				for (int ii = 0; ii < file_list.length; ii++) {
					File delete_file = new File(target + commonValues.getSeparator()+file_list[ii]);
					delete_file.delete();
				}
			}

		}
	}

	//When page changes make sure only relevant navigation buttons are displayed
	public void hideRedundentNavButtons(){
		int maxPages = decode_pdf.getPageCount();
		if(commonValues.isMultiTiff()){
			maxPages = commonValues.getPageCount();
		}
		
		if(commonValues.getCurrentPage()==1)
			currentGUI.setBackNavigationButtonsEnabled(false);
		else
			currentGUI.setBackNavigationButtonsEnabled(true);

		if(commonValues.getCurrentPage()==maxPages)
			currentGUI.setForwardNavigationButtonsEnabled(false);
		else
			currentGUI.setForwardNavigationButtonsEnabled(true);
	}

	public boolean checkForUpdates(boolean showMessages) {
		boolean connectionSuccessful = true;
		boolean wasUpdateAvailable = false;

		try {


			//read the available version from a file on the server
			URL versionFile = new URL("http://www.jpedal.org/version.txt");
			URLConnection connection = versionFile.openConnection();

			java.io.BufferedReader in = new java.io.BufferedReader(new java.io.InputStreamReader(connection.getInputStream()));
			String availableVersion = in.readLine();
			in.close();

			// read the available version from a file on the server
			availableVersion = availableVersion.substring(0, 7);

			String currentVersion = PdfDecoder.version.substring(0, 7);


			//System.out.println(currentVersion+" "+availableVersion);
			if (!currentVersion.equals(availableVersion)) { // we have a later version
				UpdateDialog updateDialog = new UpdateDialog(currentGUI.getFrame(), currentVersion, availableVersion);
				updateDialog.setVisible(true);
				wasUpdateAvailable = true;
			} else { // the current version is up to date
				if(showMessages) { // want to display this fact to the user
					currentGUI.showMessageDialog("The current version is up to date", "Up to date", JOptionPane.INFORMATION_MESSAGE);
				}
			}

			/**/

		} catch (Exception e) {
			connectionSuccessful = false;
		} finally {
			if(!connectionSuccessful && showMessages){
				currentGUI.showMessageDialog("Error making connection so unable to check for updates", "Error", JOptionPane.ERROR_MESSAGE);
			}
		}

//		System.out.println(connectionSuccessful+" ><");
		return wasUpdateAvailable;
	}

	public void setPdfDecoder(PdfDecoder decode_pdf) {
		this.decode_pdf = decode_pdf;
	}

	//<start-os>
	public void setPageProperties(Object rotation, Object scaling) {
		if(multiViewListener!=null)
			multiViewListener.setPageProperties(rotation, scaling);

	}
	//<end-os>
}
