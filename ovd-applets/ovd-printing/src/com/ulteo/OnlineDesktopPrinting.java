/**
* ===========================================
* Ulteo Online Desktop Printing Applet
* ===========================================
*
* Project Info:  http://www.ulteo.com
* 
*  This project relies heavily on GPL-licensed JPedal: http://www.jpedal.org/
*  
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
* OnlineDesktopPrinting.java
* ---------------
*/


package com.ulteo;

import java.awt.Graphics;
import java.awt.print.PageFormat;
import java.awt.print.Paper;
import java.awt.print.PrinterJob;
import javax.print.PrintService;
import javax.print.PrintServiceLookup;
import javax.print.ServiceUI;
import javax.print.attribute.Attribute;
import javax.print.attribute.HashPrintRequestAttributeSet;
import javax.print.attribute.PrintRequestAttributeSet;
import javax.print.attribute.standard.*;
import javax.swing.JApplet;
import javax.swing.UIManager;

import org.jpedal.PdfDecoder;
import org.jpedal.utils.LogWriter;

import java.net.URL;
import java.net.URLConnection;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.io.BufferedOutputStream;
import java.io.FileOutputStream;
import java.io.File;
import org.jpedal.examples.simpleviewer.utils.FileFilterer;
import javax.swing.JFileChooser;
import javax.swing.JOptionPane;


/**
 * @author ArnauVP
 *
 */
public class OnlineDesktopPrinting extends JApplet {

	static final long serialVersionUID = 1L;
	String version = "0.5";
	
	// Parameters to be passed from the html page
	String url; // url of the pdf
	String fileName;//filename of the pdf

	/********/
	/**flag to output information for debugging the code*/
	private static boolean debugCode=false;
	/**the decoder object which decodes the pdf and returns a data object*/
	private PdfDecoder decode_pdf = null;
	/**number of pages in the document*/
	private int pageCount;
	/********/
	
	
	public void init(){
		readParameters();
		try {
			UIManager.setLookAndFeel( UIManager.getSystemLookAndFeelClassName() );
			//UIManager.setLookAndFeel( UIManager.getCrossPlatformLookAndFeelClassName() );
		}catch (Exception e) { 
			System.err.println("Exception setting Look and Feel");
		}
		
	    try {
	        javax.swing.SwingUtilities.invokeAndWait(new Runnable() {
	            public void run() {
	            	decodeAndPrintFile(url);
	                System.out.println("UlteoPrinting finished");
	            }
	        });
	    } catch (Exception e) {
	        System.err.println("Exception printing file");
	        e.printStackTrace();
	    }
	}
	
	public void readParameters(){
		url = getParameter("url");
		fileName = getParameter("filename");
	}
	
	/**
	 * routine to decode a file and print it
	 */
	private void decodeAndPrintFile(String fileURL) {
		
		/**
		 * open the file and get page count
		*/
		try {
			
			logMessage("Opening file :" + fileURL+" to print.");
			
			decode_pdf = new PdfDecoder(true);
			//decode_pdf.setExtractionMode(0, 72,1);
			decode_pdf.setUseDownloadWindow(false);
			decode_pdf.openPdfFileFromURL(fileURL);
			
			/**get number of pages*/
			pageCount=decode_pdf.getPageCount();
		} catch (Exception e) {
			reportError("Exception " + e + " in pdf code");
		}
		

		/**
		 * print if allowed and values found
		 */
		if ((decode_pdf.isEncrypted())&& (!decode_pdf.isExtractionAllowed()))
			logMessage("Encrypted settings");
		else
			printPages();
			
		
		/**close the pdf file*/
		decode_pdf.closePdfFile();

	}
	
	
	/**PRINTING CODE
	 * if you put this into a thread you will need to synchronize 
	 * and ensure terminated if program exits
	 * Checks printer is present and sets tray if supported.
	 * 
	 * Uses pageable interface so does not work under 1.3
	 */
	private void printPages() {
		
		try{
			
			// Find system printers
			PrintService defService = PrintServiceLookup.lookupDefaultPrintService(); 
			PrintService[] services = PrintServiceLookup.lookupPrintServices(null,null);
			
			// No printer found ==> let the user download the file
			if(services.length == 0){
				System.err.println("No printer found.");
				System.err.println("Check connections and that you are not using CUPS over 1.2.8");

				int ans = JOptionPane.showConfirmDialog(this,"No printer was found in your system.\nDo you wish to download the file to your disk?", "Download file?", JOptionPane.YES_NO_OPTION);
				if(ans == JOptionPane.YES_OPTION)	downloadFile();
				return;
			}
			if(defService==null)	defService = services[0];
			// Set the Document length so the user can know it in advance			
			PrintRequestAttributeSet attributeSet = new HashPrintRequestAttributeSet();
			PageRanges pr = new PageRanges(1,pageCount);
			attributeSet.add(pr);
			
			//GUI for the user to choose printer and options
			PrintService selection;
			try {
				selection = ServiceUI.printDialog(null, 100, 100, services, defService, null, attributeSet);
			} catch(NullPointerException npe) {
				System.err.println("You're using a buggy version of Java and CUPS.");
				System.err.println("Upgrade to the latest version of Java available or " +
						"apply the workaround from https://bugs.launchpad.net/ubuntu/+source/sun-java6/+bug/156191/comments/18");
				int ans = JOptionPane.showConfirmDialog(this,"No printer was found in your system.\nDo you wish to download the file to your disk?", "Download file?", JOptionPane.YES_NO_OPTION);
				if(ans == JOptionPane.YES_OPTION)	downloadFile();
				return;
			}			
			// Set the chosen printservice to the Job
			PrinterJob printJob = PrinterJob.getPrinterJob();
			printJob.setPrintService(selection);
			
			Attribute[] attribs=attributeSet.toArray();
			for(int i=0;i<attribs.length;i++){
			System.out.println(i+" "+attribs[i].getName()+ ' ' +attribs[i].toString());
			}
			
			//Select all pages here, the printer will choose which ones to print
			decode_pdf.setPagePrintRange(1,pageCount);

			// Tell the PDF decoder which format the user has selected
				// This is not working as of Java 6
				//PageFormat pf = printJob.getPageFormat(attributeSet);
			PageFormat pf = printJob.defaultPage();
			MediaPrintableArea mediasize = (MediaPrintableArea) attributeSet.get(MediaPrintableArea.class);
			System.out.println(mediasize.toString());
			float xmargin = mediasize.getX(MediaPrintableArea.INCH);
			float ymargin = mediasize.getY(MediaPrintableArea.INCH);
			float width = mediasize.getWidth(MediaPrintableArea.INCH);
			float height = mediasize.getHeight(MediaPrintableArea.INCH);
			float totalwidth = (width + 2*xmargin)*72;
			float totalheight = (height + 2*ymargin)*72;
			
			// Create a paper with the size set in the dialog
			Paper paper = new Paper();
			paper.setSize(totalwidth, totalheight);
            paper.setImageableArea(0,0, totalwidth, totalheight);
			pf.setPaper(paper);
			
			// Auto-rotate and scale flag
			decode_pdf.setPrintAutoRotateAndCenter(true);
			decode_pdf.setCenterOnScaling(true);
			decode_pdf.setPageFormat(pf);
			
			// This IS important
			printJob.setPageable(decode_pdf);
			
			//Print PDF document
			printJob.print(attributeSet);

		}catch(Exception ee){
			LogWriter.writeLog("Exception "+ee+" printing");
		}catch(Error err){
			LogWriter.writeLog("Error "+err+" printing");
		}

	}

    /**single routine to log activity for easy debugging*/
    private static void logMessage(String message){
    		
    		//change to suit your needs
    		if(debugCode){
    			System.out.println(message);
    			LogWriter.writeLog(message);
    		}
    }

	/**single routine so error handling can be easily setup*/
    private static void reportError(String message){
    		
    		//change to suit your needs
    		System.err.println(message);
    		LogWriter.writeLog(message);
    }

    private void downloadFile(){
    	URL theURL;
		URLConnection theCon;
		InputStream is = null;
		OutputStream os = null;
		int returnVal = JFileChooser.CANCEL_OPTION;
		boolean perm2write = false;
		File myFile = null;
		JFileChooser jfc = new JFileChooser();
		FileFilterer filter = new FileFilterer(new String[]{"pdf"},"PDF file (*.pdf)");
		jfc.setFileFilter(filter);
		File targetFile = new File(System.getProperty("user.home") + System.getProperty("file.separator") + fileName);
		jfc.setSelectedFile(targetFile);
		System.out.println("Downloading the file instead.");
		do{
			// Ask the user where to save the file
			returnVal = jfc.showSaveDialog(OnlineDesktopPrinting.this);
			if(returnVal == JFileChooser.APPROVE_OPTION){
				myFile = jfc.getSelectedFile();
				// Check if file has *.pdf extension; otherwise add it (for Windows users mainly)
				if(!(myFile.getName().endsWith(".pdf") || myFile.getName().endsWith(".PDF"))){
					myFile = new File(myFile.getAbsolutePath().concat(".pdf"));
				}
				if(myFile.exists()){
					// Warning! File exists, overwrite?
					int answ = JOptionPane.showConfirmDialog(this,"File already exists.\nAre you sure you want to overwrite it?", "Warning!", JOptionPane.YES_NO_OPTION, JOptionPane.WARNING_MESSAGE);
					if( answ == JOptionPane.YES_OPTION)		perm2write=true;
				}else{
					// File doesn't exist, we can go ahead
					perm2write = true;
				}
			}else{
				// User cancelled: return
				return;
			}
		}while(!perm2write);
		
		// File doesn't exist or user has given permission to overwrite it. Let's go!
		try{
			theURL = new URL(url);
			theCon = theURL.openConnection();
			int numRead = 0;
			long numWritten = 0;
			byte[] buffer = new byte[1024];
			is = theCon.getInputStream();
			os = new BufferedOutputStream(new FileOutputStream(myFile.getAbsoluteFile()));

			while ((numRead = is.read(buffer)) != -1) {
				os.write(buffer, 0, numRead);
				numWritten += numRead;
			}

		}catch(Exception e){
			e.printStackTrace();
		}finally{
			try {
				if (is != null) {
					is.close();
				}
				if (os != null) {
					os.close();
				}
			} catch (IOException ioe) {
			}
		}
		return;
    }	
	
	public void stop(){
		
	}

	public void destroy(){
		
	}
	
	public void update(Graphics g) {
	}
	

}
	
