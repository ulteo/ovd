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

import java.net.URL;
import java.util.ArrayList;

import javax.print.DocFlavor;
import javax.print.PrintService;
import javax.print.PrintServiceLookup;
import javax.print.attribute.HashPrintRequestAttributeSet;
import javax.print.attribute.PrintRequestAttributeSet;

import net.propero.rdp.rdp5.rdpdr.Printer;
import net.propero.rdp.rdp5.rdpdr.RdpdrChannel;
import net.propero.rdp.rdp5.rdpdr.RdpdrDevice;

import org.apache.log4j.Logger;
import org.ulteo.rdp.rdpdr.OVDPrinter;




public class OVDPrinterManager {
	private static Logger logger = Logger.getLogger(OVDPrinterManager.class);
	private ArrayList<OVDPrinter> printerList;
	private String defaultPrinterName;
	private RdpdrChannel rdpdr = null;
	public static URL URLPrintingApplet = null;
	
	public OVDPrinterManager(RdpdrChannel rdpdr_){
		this.rdpdr = rdpdr_;

		this.printerList = new ArrayList<OVDPrinter>();
		this.defaultPrinterName = "";
	}
	
	
	/*
	 * search all known printer on the system
	 */
	public void searchAllPrinter() {
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
			logger.warn("Unable to find the default printer");
			isDefault = true;
		}

		for (int i=0 ; i<printService.length ; i++) {
			isDefault = false;
			String printerName = printService[i].getName();
			logger.debug("Printer discovered: " + printerName);
			if( printerName.equals(this.defaultPrinterName) )
				isDefault = true;

			String displayName = getValideDisplayName(printerName);
			printerList.add(i, new OVDPrinter(this.rdpdr, printerName, displayName,  isDefault));
			isDefault = false;
		}
		if (printerList.isEmpty()){
			printerList.add(0, new OVDPrinter(this.rdpdr, OVDPrinterThread.filePrinterName, OVDPrinterThread.filePrinterName, true));
		}		
	}
	
	/*
	 * add all printers mentioned by the user
	 */
	public void addPrinterList(ArrayList<String> pList){
		for (String printerName : pList) {
			if ( isExist(printerName) ){
				String displayName = getValideDisplayName(printerName);
				printerList.add(new OVDPrinter(this.rdpdr, printerName, displayName, true));
			}
		}
	}
	
	/*
	 * test if there is printer in the printer list
	 */
	public boolean hasPrinter(){
		return !this.printerList.isEmpty();
	}

	/*
	 * register all printer on rdpdr channel
	 */
	public void registerAll(RdpdrChannel rdpdrChannel) {
		for (RdpdrDevice printer: printerList)
			rdpdrChannel.register(printer);
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
}




