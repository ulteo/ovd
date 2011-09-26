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

import java.io.UnsupportedEncodingException;
import java.net.URL;
import java.net.URLDecoder;
import java.util.ArrayList;
import java.util.Timer;

import javax.print.DocFlavor;
import javax.print.PrintService;
import javax.print.PrintServiceLookup;
import javax.print.attribute.HashPrintRequestAttributeSet;
import javax.print.attribute.PrintRequestAttributeSet;

import net.propero.rdp.rdp5.rdpdr.Printer;
import net.propero.rdp.rdp5.rdpdr.RdpdrChannel;

import org.apache.log4j.Logger;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.rdp.rdpdr.OVDPrinter;
import org.ulteo.rdp.rdpdr.OVDRdpdrChannel;




public class OVDPrinterManager {
	private static Logger logger = Logger.getLogger(OVDPrinterManager.class);
	private ArrayList<OVDPrinter> printerList;
	private String defaultPrinterName;
	private ArrayList<RdpdrChannel> rdpdr_list = null;
	public static URL URLPrintingApplet = null;
	Timer printAction = null;
	
	
	public OVDPrinterManager(){
		this.printerList = new ArrayList<OVDPrinter>();
		this.defaultPrinterName = "";
		rdpdr_list = new ArrayList<RdpdrChannel>();
	}
	
	public void register_connection(RdpdrChannel channel) {
		logger.debug("Register new rdpdr channel");
		this.rdpdr_list.add(channel);
	}
	
	public void launch() {
		this.printAction = new Timer();
		this.printAction.schedule(new PrinterUpdater(this), 0, 10000);
	}
	
	public void stop() {
		if (this.printAction == null)
			return;
		
		this.printAction.cancel();
		this.printAction = null;
		this.rdpdr_list = new ArrayList<RdpdrChannel>();
	}
	
	/*
	 * search all known printer on the system
	 */
	public ArrayList<OVDPrinter> searchNewPrinter() {
		ArrayList<OVDPrinter> newPrinterList = new ArrayList<OVDPrinter>();
		
		boolean isDefault;
		DocFlavor flavor = DocFlavor.INPUT_STREAM.AUTOSENSE;
		PrintRequestAttributeSet pras = new HashPrintRequestAttributeSet();
		PrintService printService[] = PrintServiceLookup.lookupPrintServices(flavor, pras);
		/* On Linux, the first call to PrintServiceLookup.lookupPrintServices failed randomly */
		if (printService.length == 0) {
			printService = PrintServiceLookup.lookupPrintServices(flavor, pras);
		}

		try{
			PrintService default_p = PrintServiceLookup.lookupDefaultPrintService();
			this.defaultPrinterName = default_p.getName();
		}
		catch(Exception e){
			isDefault = true;
		}

		for (int i=0 ; i<printService.length ; i++) {
			isDefault = false;
			String printerName = printService[i].getName();
			if (OSTools.isLinux()) {
				try {
					printerName = URLDecoder.decode(printerName, "UTF-8");
				} catch (UnsupportedEncodingException e) {
					logger.warn("Unable to decode printer name ("+printerName+") :"+e.getMessage());
				}
			}
			
			if( printerName.equals(this.defaultPrinterName) )
				isDefault = true;

			String displayName = getValideDisplayName(printerName);
			if (this.getPrinterByPrinterName(printerName) == null)
				newPrinterList.add(i, new OVDPrinter(null, printerName, displayName,  isDefault));
			isDefault = false;
		}
		
		if (newPrinterList.isEmpty() && this.printerList.isEmpty() && !OVDPrinter.externalMode) {
			newPrinterList.add(0, new OVDPrinter(null, OVDPrinterThread.filePrinterName, OVDPrinterThread.filePrinterName, true));
		}
		
		return newPrinterList;
	}
	
	/*
	 * add all printers mentioned by the user
	 */
	public void addPrinterList(ArrayList<String> pList){
		for (String printerName : pList) {
			if ( isExist(printerName) ){
				String displayName = getValideDisplayName(printerName);
				printerList.add(new OVDPrinter(null, printerName, displayName, true));
			}
		}
	}
	
	/*
	 * test if there is printer in the printer list
	 */
	public boolean hasPrinter(){
		return !this.printerList.isEmpty();
	}

	
	public void mount(OVDPrinter p) {
		logger.debug("Printer discovered: " + p.printer_name);
		if (this.getPrinterByPrinterName(p.printer_name) != null)
			return;
		for(RdpdrChannel c: rdpdr_list) {
			c.register(p);
			((OVDRdpdrChannel)c).mountNewPrinter(p);
			printerList.add(p);
		}
	}
	
	/*
	 * get the printer display name by verifying duplicate name 
	 */
	private String getValideDisplayName(String printerName){
		/* on Windows system, with network printer, the printer is the printer location */
		int index = printerName.lastIndexOf("\\") + 1;
		String displayName = printerName.substring(index);
		index = 0;
		String newDisplayName = displayName;
		
		Printer doublon = this.getPrinterByDisplayName(newDisplayName);
		while ( doublon != null && newDisplayName.equals(doublon.get_display_name()) ){
			newDisplayName = displayName+"("+index+")";
			index++;
			doublon = this.getPrinterByDisplayName(newDisplayName);
		}
		return newDisplayName;
	}

	/*
	 * get printer device by using display name
	 */
	private Printer getPrinterByDisplayName(String displayName){
		for (Printer p : printerList){
			if ( p.get_display_name().equals(displayName))
				return p;
		}
		return null;
	}
	
	private Printer getPrinterByPrinterName(String printerName){
		for (Printer p : printerList){
			if ( p.printer_name.equals(printerName))
				return p;
		}
		return null;
	}
	
	/*
	 * test validity of a printer
	 */
	private boolean isExist(String printerName){
 		//define DocFlavor case
 		DocFlavor flavor = DocFlavor.INPUT_STREAM.AUTOSENSE;
 		PrintRequestAttributeSet pras = new HashPrintRequestAttributeSet();
 		PrintService printService[] = PrintServiceLookup.lookupPrintServices(flavor, pras);
 		
 		PrintService myPrinter = null;
		for(int i=0;i<printService.length;i++){
			if(printService[i].getName().trim().equalsIgnoreCase(printerName))
				myPrinter = printService[i];
 		}
 		return (myPrinter != null);
 	}
	
	public boolean isReady() {
		boolean ret = true;

		if (rdpdr_list.size() == 0)
			return false;
		
		for(RdpdrChannel c: rdpdr_list) {
			ret &= ((OVDRdpdrChannel)c).isReady(); 
		}
		
		return ret;
	}
}




