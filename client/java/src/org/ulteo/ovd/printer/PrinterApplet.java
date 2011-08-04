/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author David Lechevalier <david@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
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
import javax.print.PrintServiceLookup;
import javax.print.attribute.HashPrintRequestAttributeSet;
import javax.print.attribute.PrintRequestAttributeSet;


public class PrinterApplet extends Applet implements Runnable {
	private static final long serialVersionUID = 1L;
	private BlockingQueue<OVDJob> spool;
	private boolean running = false;
	private boolean hasfocus = true;
	private Thread thread = null;
	
	public void init() {
		System.out.println("Initilise PDF Printer");
		this.spool = new LinkedBlockingQueue<OVDJob>() ;
		DocFlavor flavor = DocFlavor.INPUT_STREAM.AUTOSENSE;
		PrintRequestAttributeSet pras = new HashPrintRequestAttributeSet();
		PrintServiceLookup.lookupPrintServices(flavor, pras);
	}
	
	public void start() {
		System.out.println("Start PDF Printer");
		
		this.setFocusable(false);
		if (! this.isRunning()) {
			this.setRunning(true);
			this.thread = new Thread(this);
			this.thread.start();
		}
	}

	public boolean hasFocus() {
		return this.hasfocus;
	}
	
	public void stop() {
		System.out.println("Stopping the applet");
		
		this.setRunning(false);
		if (this.thread != null && this.thread.isAlive()) {
			this.thread.interrupt();
			this.thread = null;
		}
	}
	
	@Override
	public void run() {
		while (this.isRunning()) {
			OVDJob job = null;
			try {
				this.hasfocus = false;
				job = (OVDJob)spool.take();
				this.hasfocus = true;
			}
			catch (InterruptedException e) {
				job = null;
			}
			if (job != null) {
				job.print();
			}
			else
				System.out.println("Invalid job");
		}
		this.hasfocus = false;
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
			spool.put(new OVDJob(spoolPath, printerName, false));
		}
		catch (InterruptedException e){
		}
		return null;
	}
	
	public void spoolJob(String printerName, String pdfFilename) {
		this.spool.add(new OVDJob(pdfFilename, printerName, false));
	}
	
	private synchronized boolean isRunning() {
		return this.running;
	}
	
	public synchronized void setRunning(boolean running_) {
		this.running = running_;
	}
}
