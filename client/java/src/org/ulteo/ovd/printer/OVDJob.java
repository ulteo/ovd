/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author David Lechevalier <david@ulteo.com> 2010
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
package org.ulteo.ovd.printer;

import java.awt.print.PageFormat;
import java.awt.print.PrinterException;
import java.awt.print.PrinterJob;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.IOException;

import javax.print.DocFlavor;
import javax.print.PrintService;
import javax.print.PrintServiceLookup;
import javax.print.attribute.Attribute;
import javax.print.attribute.HashPrintRequestAttributeSet;
import javax.print.attribute.PrintRequestAttributeSet;
import javax.print.attribute.standard.PageRanges;
import javax.swing.JFileChooser;
import javax.swing.JOptionPane;

import org.jpedal.PdfDecoder;
import org.jpedal.objects.PrinterOptions;

public class OVDJob{
	private static final long serialVersionUID = 1L;
	private String pdfFilename = null;
	private String printerName = null;

	/********/
	/**flag to output information for debugging the code*/
	private static boolean debugCode=false;
	/**the decoder object which decodes the pdf and returns a data object*/
	private PdfDecoder decode_pdf = null;
	/**number of pages in the document*/
	private int pageCount;

	
	public OVDJob(String pdfFilename, String printerName) {
		this.printerName = printerName;
		this.pdfFilename = pdfFilename;
		if (this.printerName != null && ! this.printerName.equals(OVDPrinterThread.filePrinterName))
			return;
		
		this.printerName = null;
		//Create a file chooser
		int returnVal = 0;
		JFileChooser fc = null;
		try {
			fc = new JFileChooser();
			returnVal = fc.showOpenDialog(null);
		}
		catch (Exception e){
			e.printStackTrace();
		}
		if (returnVal == JFileChooser.APPROVE_OPTION) {
			File file = fc.getSelectedFile();
			// Destination directory 
			File pdf = new File(this.pdfFilename);
			// Move file to new directory
			if (file.exists()){
				int ret = JOptionPane.showConfirmDialog(null, 
						"The file "+file.getPath()+" already exixt, do you want to overwrite it",
						"The file "+file.getPath()+" already exixt",
						JOptionPane.YES_NO_OPTION,
						JOptionPane.QUESTION_MESSAGE
				);
				if (ret == JOptionPane.YES_OPTION){
					file.delete();
				}
				else {
					System.out.println("Nothing to do, the user abort printing");
					return;
				}
			}
			boolean success = moveTo(pdf, file);
			if (!success) { 
				System.err.println("Unable to save file ["+this.pdfFilename+"] to ["+file.getPath()+"]");
				return;
			}
			System.out.println("Succed to save job to "+file.getPath());
			return;
		}
		System.out.println("Nothing to do, the user abort printing");
		return;
	}
	
	public String toString() {
		return "["+this.printerName+","+this.pdfFilename+"]";
	}
	
	private static PrintService getPrintService(String printerName) {
		DocFlavor flavor = DocFlavor.INPUT_STREAM.AUTOSENSE;
		PrintRequestAttributeSet pras = new HashPrintRequestAttributeSet();
		PrintService printServices[] = PrintServiceLookup.lookupPrintServices(flavor, pras);
		for (PrintService printService : printServices) {
			String printer_name = printService.getName();
			if (printerName.equals(printer_name)) {
				return printService;
			}
		}
		return null;
	}

	
	public boolean print(){
		if (this.printerName == null) {
			return true;
		}
		
		PrintService printService = OVDJob.getPrintService(this.printerName);
		if (printService == null) {
			System.out.println("Unable to find the printer");
			return false;
		}
		try {
			decode_pdf = new PdfDecoder(true);
			decode_pdf.openPdfFile(this.pdfFilename);

			/**get number of pages*/
			pageCount=decode_pdf.getPageCount();
		} catch (Exception e) {
			System.out.println("Exception " + e + " in pdf code");
		}
		if ((decode_pdf.isEncrypted()) && (!decode_pdf.isExtractionAllowed())) {
			System.out.println("Encrypted settings");
			return false;
		}
		try {
			// Set the Document length so the user can know it in advance
			PrintRequestAttributeSet attributeSet = new HashPrintRequestAttributeSet();
			PageRanges pr = new PageRanges(1,pageCount);
			attributeSet.add(pr);
	
			Attribute[] attribs=attributeSet.toArray();
			for (int i=0; i<attribs.length; i++) {
				System.out.println(i+" "+attribs[i].getName()+ ' ' +attribs[i].toString());
			}
			//Select all pages here, the printer will choose which ones to print
			decode_pdf.setPagePrintRange(1,pageCount);
			decode_pdf.setPrintAutoRotateAndCenter(false); 
			decode_pdf.setPrintCurrentView(false);
			decode_pdf.setPrintPageScalingMode(PrinterOptions.PAGE_SCALING_NONE);
			decode_pdf.setCenterOnScaling(false);
			decode_pdf.setUsePDFPaperSize(true);
			//decode_pdf.setPrintAutoRotateAndCenter(true);
			PrinterJob printJob = PrinterJob.getPrinterJob();
			printJob.setPrintService(printService);
			printJob.setPageable(decode_pdf);
			PageFormat pf = printJob.defaultPage();
			decode_pdf.setPageFormat(pf);
	
			//Print PDF document
			printJob.print(attributeSet);
			//new File(this.pdfFile).delete();
			return true;
		} 
		catch (PrinterException ee) {
			System.err.println(ee.getMessage());
		}
		catch (Exception ee) {
			System.err.println(ee.getMessage());
		}
		return false;
	}
	
	
	private boolean moveTo(File src, File dest) {
		FileInputStream srcBuf = null;
		FileOutputStream destBuf = null;
		try {
			srcBuf = new FileInputStream(src);
		} catch (FileNotFoundException e) {
			System.out.println("Unable to find file ["+src.getAbsolutePath()+"]");
			return false;
		}
		try {
			dest.createNewFile();
		} 
		catch (IOException e) {
			System.out.println("Unable to create file ["+dest.getAbsolutePath()+"]");
			return false;
		}
		try {
			destBuf = new FileOutputStream(dest);
		} 
		catch (FileNotFoundException e) {
			System.out.println("Unable to find file ["+dest.getAbsolutePath()+"]");
		}
		byte[] buf = new byte[1024];
		int len;
		try {
			while ((len = srcBuf.read(buf)) > 0) {
				destBuf.write(buf, 0, len);
			}
			srcBuf.close();
			destBuf.close();
		}
		catch (IOException e) {
			System.out.println("Error while copying file from ["+src.getAbsolutePath()+"] to ["+dest.getAbsolutePath()+"]");
			return false;
		}
		src.delete();
		return true;
	}

	
	public static void main (String[] args) {
		if (args.length != 1) {
			System.err.println("This sample take one argument : the PDF file to print");
		}
		File pdfFile= new File(args[0]);
		if (! pdfFile.exists()) {
			System.err.println("The file : ["+pdfFile.getPath()+"] did not exist");
			return ;
		}
		System.out.println("Try to print the file ["+pdfFile.getPath()+"]");
		try{
			OVDJob printer = new OVDJob(args[0], null);
			printer.print();
		}
		catch(Exception e) {
			System.err.println("Error while printing the file["+pdfFile+"] : "+e.getMessage());
		}		
	}
	
	
}
