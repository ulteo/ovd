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
* Printer.java
* ---------------
*/
package org.jpedal.examples.simpleviewer.utils;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.print.PageFormat;
import java.awt.print.Paper;
import java.awt.print.PrinterException;
import java.awt.print.PrinterJob;


//<start-13>
import org.jpedal.examples.simpleviewer.gui.popups.*;
import javax.print.PrintService;
import javax.print.attribute.HashPrintRequestAttributeSet;
import javax.print.attribute.PrintRequestAttributeSet;
import javax.print.attribute.SetOfIntegerSyntax;
import javax.print.attribute.standard.PageRanges;
//<end-13>

import javax.swing.JComboBox;
import javax.swing.JOptionPane;
import javax.swing.ProgressMonitor;
import javax.swing.Timer;

import org.jpedal.PdfDecoder;

import org.jpedal.exception.PdfException;
import org.jpedal.gui.GUIFactory;
import org.jpedal.objects.PrinterOptions;
import org.jpedal.utils.LogWriter;
import org.jpedal.utils.Messages;



public class Printer {
	
	/**flag to stop mutliple prints*/
	private static int printingThreads=0;
	
	/**page range to print*/
	int rangeStart=1,rangeEnd=1;
	
	/**type of printing - all, odd, even*/
	int subset=PrinterOptions.ALL_PAGES;
	
	JComboBox scaling=null;
	
	/**Check to see if Printing cancelled*/
	boolean wasCancelled=false;
	
	/**Allow Printing Cancelled to appear once*/
	boolean messageShown=false;
	
	boolean pagesReversed=false;
	
	/**provide user with visual clue to print progress*/
	Timer updatePrinterProgress=null;

	private ProgressMonitor status = null;
	
	public void printPDF(final PdfDecoder decode_pdf,final GUIFactory currentGUI) {
		
		//provides atomic flag on printing so we don't exit until all done
		printingThreads++;
		
		/**
		 * printing in thread to improve background printing -
		 * comment out if not required
		 */
		Thread worker = new Thread() {
			public void run() {
				
				boolean printFile=false;
				try {

					//setup print job and objects
					PrinterJob printJob = PrinterJob.getPrinterJob();
					PageFormat pf = printJob.defaultPage();

                    if(PdfDecoder.debugPrint){
                        System.out.println("------------------------");

                        System.out.println("Default Page format used="+pf);
                        System.out.println("Orientation="+pf.getOrientation());
                        System.out.println("Width="+pf.getWidth()+" imageableW="+pf.getImageableWidth());
                        System.out.println("Height="+pf.getHeight()+" imageableH="+pf.getImageableHeight());
                        System.out.println("------------------------");
                    }

                    //<link><a name="printerpagesize" />
                    /**
					 * default page size
					 */
					Paper paper = new Paper();
					paper.setSize(595, 842);
					paper.setImageableArea(43, 43, 509, 756);

					pf.setPaper(paper);

					//VERY useful for debugging! (shows the imageable
					// area as a green box bordered by a rectangle)
					//decode_pdf.showImageableArea();

					/**
					 * SERVERSIDE printing IF you wish to print using a
					 * server, do the following
					 * 
					 * See SilentPrint.java example
					 *  
					 */

					/**
					 * workaround to improve performance on PCL printing 
					 * by printing using drawString or Java's glyph if font
					 * available in Java
					 */
					//decode_pdf.setTextPrint(PdfDecoder.NOTEXTPRINT); //normal mode - only needed to reset
					//decode_pdf.setTextPrint(PdfDecoder.TEXTGLYPHPRINT); //intermediate mode - let Java create Glyphs if font matches
					//decode_pdf.setTextPrint(PdfDecoder.TEXTSTRINGPRINT); //try and get Java to do all the work

					//<link><a name="printerpageable" /> 
					/**/

					/**
					 * Example 1 - uses Pagable interface and can get page Range 
					 * 
					 * ADDITIONAL  FEATURES NOT AVAILABLE (see Example 3)
					 * 
					 * RECOMMENDED for Java 1.4 and Above
					 */
					//setup JPS to use JPedal
					printJob.setPageable(decode_pdf);

					//setup default values to padd into JPS
					PrintRequestAttributeSet aset=new HashPrintRequestAttributeSet();
					aset.add(new PageRanges(1,decode_pdf.getPageCount()));

					// useful debugging code to show supported values and values returned by printer
					//Attribute[] settings = aset.toArray();

					//Class[] attribs=printJob.getPrintService().getSupportedAttributeCategories();
					//for(int i=0;i<attribs.length;i++)
					//System.out.println(i+" "+attribs[i]);

					//for(int i=0;i<settings.length;i++) //show values set by printer
					//	System.out.println(i+" "+settings[i].toString()+" "+settings[i].getName());

                    //<link><a name="printerstandarddialog" />
                    //<start-os>
					/*
					//<end-os>
                     decode_pdf.setPageFormat(pf); 
                     printFile=printJob.printDialog(aset);

					//set page range
					PageRanges r=(PageRanges) aset.get(PageRanges.class);
					if((r!=null)&&(printFile)){

						//Option A - use range
						decode_pdf.setPagePrintRange((SetOfIntegerSyntax) r);

						//Option B - use start and end value checking in right order
						int[][] values=r.getMembers();
						int[] pages=values[0];
						int p1=pages[0];
						int p2=pages[1];

						//all returns huge number not page end range
						if(p2==2147483647)
							p2=decode_pdf.getPageCount();

						if(p1<p2){
							rangeStart=p1;
							rangeEnd=p2;
						}else{
							rangeStart=p2;
							rangeEnd=p1;
						}

						//page range can also be set with
						//decode_pdf.setPagePrintRange(p1,p2);

					}


                    //<link><a name="printerprintable" />
                    /**
	                
					// Example 2 - uses printable interface and can get page Range
					//RECOMMENDED for Java 1.3
					//allow user to edit settings and select printing with 1.3 support
					printJob.setPrintable(decode_pdf, pf);

					printFile=printJob.printDialog();

                    /**
					 * example 3 - custom dialog so we can copy Acrobat PDF settings
					 * (removed from OS versions)
					 */
                    /*
                    //<start-os>
                    /**/
                    //<end-os>

                    PrintPanel printPanel=null;

                    try{
                        printPanel=(PrintPanel)currentGUI.printDialog(getAvailablePrinters(),printJob.getPrintService().getName());
                    }catch(Exception e){
                        //<start-os>
                        e.printStackTrace();
                        //<end-os>

                        printingThreads--;
                        JOptionPane.showMessageDialog(currentGUI.getFrame(),"No printers setup");
                        return ;
                    }
                    
                    printFile = printPanel.okClicked();

					decode_pdf.repaint();

					// set values in JPS

					// choose the printer, testing if printer in list
					setPrinter(printJob, printPanel.getPrinter());

                    //<link><a name="printerrange" />
                    //range of pages
					int printMode=0;
					subset=PrinterOptions.ALL_PAGES;
					if(printPanel.isOddPagesOnly()){
						printMode=PrinterOptions.ODD_PAGES_ONLY;
						subset=PrinterOptions.ODD_PAGES_ONLY;
					}else if(printPanel.isEvenPagesOnly()){
						printMode=PrinterOptions.EVEN_PAGES_ONLY;
						subset=PrinterOptions.EVEN_PAGES_ONLY;
					}

                    //flag to show reversed
					pagesReversed=printPanel.isPagesReversed();
					if(pagesReversed)
						printMode=printMode+PrinterOptions.PRINT_PAGES_REVERSED;

					decode_pdf.setPrintPageMode(printMode);

					//can also take values such as  new PageRanges("3,5,7-9,15");
					SetOfIntegerSyntax range=printPanel.getPrintRange();

					if(range==null){
						currentGUI.showMessageDialog("No pages to print");	
					}else{
						decode_pdf.setPagePrintRange(range);

						// workout values for progress monitor
						rangeStart= range.next(0); // find first

						// find last
						int i = rangeStart;
						rangeEnd = rangeStart;
						if(range.contains(2147483647)) //allow for all returning largest int
							rangeEnd=decode_pdf.getPageCount();
						else{
							while (range.next(i) != -1)
								i++;
							rangeEnd = i;
						}

						//pass through number of copies
						printJob.setCopies(printPanel.getCopies());

                        //<link><a name="printerautorotateandcenter" />
                        //Auto-rotate and scale flag
						decode_pdf.setPrintAutoRotateAndCenter(printPanel.isAutoRotateAndCenter());

                        //<link><a name="printercurrentview" />
                        // Are we printing the current area only
						decode_pdf.setPrintCurrentView(printPanel.isPrintingCurrentView());

                        //<link><a name="printerscaling" />
                        //set mode - see org.jpedal.objects.contstants.PrinterOptions for all values
						decode_pdf.setPrintPageScalingMode(printPanel.getPageScaling());

                        //<link><a name="printercenteronscaling" />
                        //by default scaling will center on page as well. If you DO NOT want this to happen, use
						decode_pdf.setCenterOnScaling(true); //always ensure default in case user changed
						//decode_pdf.setCenterOnScaling(false);
						
						//set paper size
						pf.setPaper(printPanel.getSelectedPaper());
						decode_pdf.setPageFormat(pf); 

                        //<link><a name="printerusepdfsize" />
                        // flag if we use paper size or PDF size
						decode_pdf.setUsePDFPaperSize(printPanel.isPaperSourceByPDFSize());

					}

                    /**/

                    /**
					 * popup to show user progress
					 */
					status = new ProgressMonitor(currentGUI.getFrame(),"","",1,100);

					/** used to track user stopping movement and call refresh every 2 seconds*/
					updatePrinterProgress = new Timer(1000,new ActionListener() {

						public void actionPerformed(ActionEvent event) {

							int currentPage=decode_pdf.getCurrentPrintPage();

							if(currentPage>0)
								updatePrinterProgess(decode_pdf,currentPage);

							//make sure turned off
							if(currentPage==-1){
								updatePrinterProgress.stop();
								status.close();
							}				
						}
					});
					updatePrinterProgress.setRepeats(true);
					updatePrinterProgress.start();

					
					
					/**
					 * generic call to both Pageable and printable
					 */
					if (printFile)
						printJob.print();

				} catch (PrinterException ee) {
					ee.printStackTrace();
					LogWriter.writeLog("Exception " + ee + " printing");
					//<start-13>
					currentGUI.showMessageDialog(ee.getMessage()+ ' ' +ee+ ' ' + ' ' +ee.getCause());
					//<end-13>
				} catch (Exception e) {
					LogWriter.writeLog("Exception " + e + " printing");
					e.printStackTrace();
					currentGUI.showMessageDialog("Exception "+e);
				} catch (Error err) {
					err.printStackTrace();
					LogWriter.writeLog("Error " + err + " printing");
					currentGUI.showMessageDialog("Error "+err);
				}
				
				/**
				 * visual print update progress box
				 */
				if(updatePrinterProgress!=null){
					updatePrinterProgress.stop();
					status.close();
				}
				/**report any or our errors 
				 * (we do it this way rather than via PrinterException as MAC OS X has a nasty bug in PrinterException)
				 */
				if(!printFile && !decode_pdf.isPageSuccessful()){
					String errorMessage=Messages.getMessage("PdfViewerError.ProblemsEncountered")+decode_pdf.getPageFailureMessage()+ '\n';
					
					if(decode_pdf.getPageFailureMessage().toLowerCase().indexOf("memory") != -1)
						errorMessage += Messages.getMessage("PdfViewerError.RerunJava")+
										Messages.getMessage("PdfViewerError.RerunJava1")+
										Messages.getMessage("PdfViewerError.RerunJava2");
					
					currentGUI.showMessageDialog(errorMessage);
				}
				
				printingThreads--;
				
				//redraw to clean up
				decode_pdf.invalidate();
				decode_pdf.updateUI();
				decode_pdf.repaint();
				
				
				if((printFile && !wasCancelled)){
					currentGUI.showMessageDialog(Messages.getMessage("PdfViewerPrintingFinished"));
					
					decode_pdf.resetCurrentPrintPage();
				}
			}

            //<link><a name="printerlist" />

            private String[] getAvailablePrinters() {
				PrintService[] service=PrinterJob.lookupPrintServices();
				int noOfPrinters = service.length;
				
				String[] serviceNames = new String[noOfPrinters];
				
				for(int i=0;i<noOfPrinters;i++)
					serviceNames[i] = service[i].getName();
				return serviceNames;
			}

		};
		
		//start printing in background (comment out if not required)
		worker.start();
		
	}
	
	/**visual print indicator*/
	private String dots=".";
	
	private void updatePrinterProgess(PdfDecoder decode_pdf,int currentPage) {
		
		
		//Calculate no of pages printing
		int noOfPagesPrinting=(rangeEnd-rangeStart+1);
		
		//Calculate which page we are currently printing
		int currentPrintingPage=(currentPage-rangeStart);
		
		int actualCount=noOfPagesPrinting;
		int actualPage=currentPrintingPage;
		int actualPercentage= (int) (((float)actualPage/(float)actualCount)*100); 
		
		
		if(status.isCanceled()){
			decode_pdf.stopPrinting();
			updatePrinterProgress.stop();
			status.close();
			wasCancelled=true;
			printingThreads--;
		
			if(!messageShown){
				JOptionPane.showMessageDialog(null,Messages.getMessage("PdfViewerPrint.PrintingCanceled"));
				messageShown=true;
			}
			return;
			
		}
	
		
		//update visual clue
		dots=dots+ '.';
		if(dots.length()>8)
			dots=".";
		
		//allow for backwards
		boolean isBackwards=((currentPrintingPage<=0));
		
		if(rangeStart==rangeEnd)
			isBackwards=false;
		
		if((isBackwards))
			noOfPagesPrinting=(rangeStart-rangeEnd+1);
		
		int percentage = (int) (((float)currentPrintingPage / (float)noOfPagesPrinting) * 100);
		
		if((!isBackwards)&&(percentage<1))
			percentage=1;
		
		
		//invert percentage so percentage works correctly
		if(isBackwards){
			percentage=-percentage;
			currentPrintingPage=-currentPrintingPage;
		}
		
		if(pagesReversed)
			percentage=100-percentage;
		
		status.setProgress(percentage);
		String message="";
		
		if(subset==PrinterOptions.ODD_PAGES_ONLY){
			actualCount=((actualCount/2)+1);
			actualPage=actualPage/2;
		
		}else if(subset==PrinterOptions.EVEN_PAGES_ONLY){
			actualCount=((actualCount/2)+1);
			actualPage=actualPage/2;
			
		}
		
		/*
		 * allow for printing 1 page 
		 * Set to page 1 of 1 like Adobe
		 */
		if (actualCount==1){
			actualPercentage=50;
			actualPage=1;
			status.setProgress(actualPercentage);
		}
		
		message=actualPage + " "+Messages.getMessage("PdfViewerPrint.Of")+ ' ' +
		actualCount + ": " + actualPercentage + '%' + ' ' +dots;
			
		if(pagesReversed){
			message=(actualCount-actualPage) + " "+Messages.getMessage("PdfViewerPrint.Of")+ ' ' +
			actualCount + ": " + percentage + '%' + ' ' +dots;
			
			status.setNote(Messages.getMessage("PdfViewerPrint.ReversedPrinting")+ ' ' + message);
		
		}else if(isBackwards)
			status.setNote(Messages.getMessage("PdfViewerPrint.ReversedPrinting")+ ' ' + message);
		else
			status.setNote(Messages.getMessage("PdfViewerPrint.Printing")+ ' ' + message);
			
		}
	
	
		
	//}

	public boolean isPrinting() {
		
		return printingThreads>0;
	}

    //<link><a name="printerset" />

    private void setPrinter(PrinterJob printJob, String chosenPrinter) throws PrinterException, PdfException {

        PrintService[] service=PrinterJob.lookupPrintServices(); //list of printers
		//enable if on list otherwise error
		boolean matchFound=false;
		int count=service.length;
		for(int i=0;i<count;i++){
			if(service[i].getName().equals(chosenPrinter)){
				printJob.setPrintService(service[i]);
				//System.out.println("Set to "+service[i]);
				i=count;
				matchFound=true;
			}
		}
		if(!matchFound)
			throw new PdfException("Unknown printer "+chosenPrinter);
	}

}
