/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2010-2011
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

import java.awt.Image;
import java.awt.Toolkit;
import java.awt.print.PageFormat;
import java.awt.print.PrinterException;
import java.awt.print.PrinterJob;
import java.io.File;

import javax.print.DocFlavor;
import javax.print.PrintService;
import javax.print.PrintServiceLookup;
import javax.print.attribute.Attribute;
import javax.print.attribute.HashPrintRequestAttributeSet;
import javax.print.attribute.PrintRequestAttributeSet;
import javax.print.attribute.standard.PageRanges;
import javax.swing.JFileChooser;
import javax.swing.JFrame;
import javax.swing.JOptionPane;

import org.jpedal.PdfDecoder;
import org.jpedal.objects.PrinterOptions;
import org.jpedal.io.ObjectStore;

import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.utils.FilesOp;

public class OVDJob{
	private static final long serialVersionUID = 1L;
	private String pdfFilename = null;
	private String printerName = null;

	public OVDJob(String pdfFilename, String printerName, boolean externalMode) {
		this.printerName = printerName;
		this.pdfFilename = pdfFilename;
		
		if (externalMode)
			return;
		
		if (this.printerName != null && ! this.printerName.equals(OVDPrinterThread.filePrinterName))
			return;
		
		Image logo = Toolkit.getDefaultToolkit().getImage(getClass().getClassLoader().getResource("pics/ulteo.png"));
		
		JFileChooser fc = new JFileChooser();
		JFrame frame = new JFrame();
		frame.setIconImage(logo);
		
		if (fc.showOpenDialog(frame) == JFileChooser.APPROVE_OPTION) {
			File file = fc.getSelectedFile();
			if (file.exists()){
				int ret = JOptionPane.showConfirmDialog(null, 
						"The file "+file.getPath()+" already exixt, do you want to overwrite it",
						"The file "+file.getPath()+" already exixt",
						JOptionPane.YES_NO_OPTION,
						JOptionPane.QUESTION_MESSAGE
				);
				if (ret == JOptionPane.YES_OPTION){
					file.delete();
				} else {
					System.out.println("Nothing to do, the user abort printing");
					return;
				}
			}
			
			File src = new File(this.pdfFilename);
			if (! src.renameTo(file)) { 
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
		
		if (OSTools.isLinux()) {
			LinuxPrinter lp = new LinuxPrinter(this.printerName, this.pdfFilename);
			new Thread(lp).start();
			return true;
		}
		
		PrintService printService = OVDJob.getPrintService(this.printerName);
		if (printService == null) {
			System.out.println("Unable to find the printer");
			return false;
		}
		PdfDecoder decode_pdf = new PdfDecoder(true);
		try {
			decode_pdf.openPdfFile(this.pdfFilename);
		} catch (Exception e) {
			System.out.println("Exception " + e + " in pdf code");
		}
		int pageCount = decode_pdf.getPageCount();
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
			decode_pdf.closePdfFile();

			//Temporary file cleaning
			new File(this.pdfFilename).delete();
			String jpedalDir = ObjectStore.temp_dir;
			FilesOp.deleteDirectory(new File(jpedalDir));
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
			OVDJob printer = new OVDJob(args[0], null, true);
			printer.print();
		}
		catch(Exception e) {
			System.err.println("Error while printing the file["+pdfFile+"] : "+e.getMessage());
		}		
	}
	
}
