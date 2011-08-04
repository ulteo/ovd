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
import java.applet.AppletContext;
import java.io.File;

import org.apache.log4j.Logger;


public class OVDAppletPrinterThread implements OVDPrinterThread {
	protected static Logger logger = Logger.getLogger(OVDPrinterThread.class);
	
	private final int maxRetry = 5;
	private final String spoolDir = "OVDSpool";
	
	private AppletContext appletContext;
	private String printerAppletName = "PrinterApplet";
	private String spoolPath = "";
	
	
	
	public OVDAppletPrinterThread(AppletContext appletContext) {
		super();
		this.appletContext = appletContext;
		//create spooldir
		this.spoolPath = System.getProperty("java.io.tmpdir")+ File.separator + this.spoolDir;
		File spoolFile = new File(this.spoolPath);
		spoolFile.mkdir();
		if (! spoolFile.exists() && ! spoolFile.isDirectory()) {
			logger.error("Unable to initialize the spool Dir ["+this.spoolPath+"]");
		}
	}
	
	
	@Override
	public void printPages(String printerName, String pdfFilename, boolean externalMode) {
		if (printerName == null || printerName.equals("")) {
			printerName = OVDAppletPrinterThread.filePrinterName;
		}
		File pdfFile = new File(pdfFilename);
		if (! pdfFile.exists()) {
			logger.error("Unable to spool the pdf file, the file ["+pdfFile.getAbsolutePath()+"] did not exist");
			return;
		}
		int count = maxRetry;
		Applet applet = null;
		while (count > 0) {
			applet = appletContext.getApplet(this.printerAppletName); 
			if (applet != null) {
				break;
			}
		}
		if (applet == null) {
			logger.error("Unable to get the printing applet "+this.printerAppletName);
			return;
		}
		File printerDir = new File(this.spoolPath + File.separator + printerName);
		printerDir.mkdir();
		if (! printerDir.exists() && !printerDir.isDirectory()) {
			logger.error("Unable to spool the pdf file, the directory ["+printerDir.getAbsolutePath()+"] can not be created");
			return;
		}
		File spoolFile = new File(printerDir.getAbsolutePath()+ File.separator + pdfFile.getName());
		if (spoolFile.exists()) {
			logger.error("Unable to spool the pdf file, the file ["+spoolFile.getAbsolutePath()+"] already exist");
			return;			
		}
		if (pdfFile.renameTo(spoolFile)) {
			applet.add(spoolFile.getAbsolutePath(), null);
		}
		else {
			logger.error("Unable to move pdf ["+pdfFile.getAbsolutePath()+"] file to spool dir  ["+spoolFile.getAbsolutePath());
			return;			
		}
	}

	@Override
	public boolean hasFocus() {
		Applet printer = null;
		printer = appletContext.getApplet(printerAppletName);
		if (printer != null) {
			return printer.hasFocus();
		}
		return false;
	}
}
