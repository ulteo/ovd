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
import java.io.File;

import javax.print.DocFlavor;
import javax.print.PrintService;
import javax.print.PrintServiceLookup;
import javax.print.attribute.HashPrintRequestAttributeSet;
import javax.print.attribute.PrintRequestAttributeSet;
import javax.swing.JFileChooser;
import javax.swing.JFrame;
import javax.swing.JOptionPane;


import org.ulteo.Logger;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.utils.FilesOp;

public class OVDJob{
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
			System.out.println("returning true, no printer");
			return true;
		}
		
		if (OSTools.isLinux() || OSTools.isMac()) {
			LinuxPrinter lp = new LinuxPrinter(this.printerName, this.pdfFilename);
			new Thread(lp).start();
			return true;
		}
		
		if (OSTools.isWindows()) {
			WindowsPrinter lp = new WindowsPrinter(this.printerName, this.pdfFilename);
			new Thread(lp).start();
			return true;
		}
		
		Logger.error("Your system is not suppored for printing task");
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
