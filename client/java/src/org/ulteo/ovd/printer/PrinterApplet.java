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

import java.applet.Applet;
import java.awt.Component;
import java.io.File;
import java.util.concurrent.BlockingQueue;
import java.util.concurrent.LinkedBlockingQueue;

import javax.print.DocFlavor;
import javax.print.PrintService;
import javax.print.PrintServiceLookup;
import javax.print.attribute.HashPrintRequestAttributeSet;
import javax.print.attribute.PrintRequestAttributeSet;


public class PrinterApplet extends Applet {
	private static final long serialVersionUID = 1L;
	private BlockingQueue<OVDJob> spool;
	private boolean running = true;

	public void init() {
		System.out.println("Initilise PDF Printer");
		spool = new LinkedBlockingQueue<OVDJob>() ;
		DocFlavor flavor = DocFlavor.INPUT_STREAM.AUTOSENSE;
		PrintRequestAttributeSet pras = new HashPrintRequestAttributeSet();
		PrintServiceLookup.lookupPrintServices(flavor, pras);
	}
	
	public void start() {
		System.out.println("Start PDF Printer");
		while (running) {
			OVDJob job = null;
			try {
				job = (OVDJob)spool.take();
			}
			catch (InterruptedException e) {
			}
			if (job != null) {
				job.print();
			}
			else
				System.out.println("Invalid job");
		}
	}
	

	//it is the only method, we can use for inter-applet communication
	public Component add(String spoolPath, Component component) {
		if(component != null) {
			return super.add(spoolPath, component);
		}
			
		File spoolFile = new File(spoolPath);
		if (! spoolFile.exists()) {
			System.out.println("The spool file ["+spoolPath+"] can not be found");
		}
		String printerName = spoolFile.getParentFile().getName();
		try{
			spool.put(new OVDJob(spoolPath, printerName));
		}
		catch (InterruptedException e){
		}
		return null;
	}
	
	public void stop() {
		System.out.println("Stopping the applet");
		this.running = false;
	}
		
	public void spoolJob(String printerName, String pdfFilename) {
		this.spool.add(new OVDJob(pdfFilename, printerName));
	}
	
	
	
}
