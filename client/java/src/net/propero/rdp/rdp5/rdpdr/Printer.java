/* Printer.java
 * Component: ported from RdeskTop
 * 
 * Revision: $Revision: 1.0
 * Author: tomqq (hekong@gmail.com)
 * Date: 2009/05/16
 *
 * Copyright (c) tomqq
 *
 * Purpose: 
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307
 * USA
 * 
 * (See gpl.txt for details of the GNU General Public License.)
 * 
 */
package net.propero.rdp.rdp5.rdpdr;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileWriter;
import java.util.LinkedList;

import javax.print.Doc;
import javax.print.DocFlavor;
import javax.print.DocPrintJob;
import javax.print.PrintService;
import javax.print.PrintServiceLookup;
import javax.print.SimpleDoc;
import javax.print.attribute.HashPrintRequestAttributeSet;
import javax.print.attribute.PrintRequestAttributeSet;

import net.propero.rdp.RdpPacket;
import net.propero.rdp.RdpPacket_Localised;
import org.apache.log4j.Logger;

public class Printer extends RdpdrDevice{
	//parameter
	private static int printerCount = 0;
	private String display_name; 

	protected static Logger logger = Logger.getLogger(Printer.class);

	public static final int DEVICE_TYPE = 0x04;//DEVICE_TYPE_PRINTER
	public String driver;
	public boolean default_printer = true;
	public String printer_name;
	public int bloblen = 0;
	String path = null;//printer ps file 
	public static String only_printer_file;
	public static LinkedList job_list = new LinkedList();
	public static RdpPacket_Localised ps_buffer;

	//status
	public final int STATUS_SUCCESS			= 0x00000000;
	
	public Printer(RdpdrChannel rdpdr_, String name, String display_name, boolean is_default){//temp data for test
		super(rdpdr_);
		
		this.name = "PRN" + printerCount;
		printerCount++;
		this.printer_name = name;
		this.display_name = display_name;
		this.device_type = 0x04;
		this.driver = "HP Color LaserJet 8500 PS";//use this driver for default
		this.default_printer = is_default;
		this.pdevice_data.put("PRINTER", this);
		this.path = System.getProperty("java.io.tmpdir") + File.separator;
		this.path += "uprinter.ps";
		Printer.only_printer_file = path;
	}

	/* on Windows, with network printer, the printer name is the printer location in the network 
	 * This location is unusable by Windows client
	 * */
	public String get_display_name(){
		return display_name;
	}
	
	public int create(int device, int desired_access, int share_mode, int disposition, int flags_and_attributes, String filename,int[] result){
		Printer.ps_buffer = null;
		return STATUS_SUCCESS;
	}
	public int read(int handle, byte[] data, int length, int offset, int[] result){
		return 0;
	}
	public int write(int handle, byte[] data, int length, int offset, int[] result){
		int old_size = 0;
		if(Printer.ps_buffer == null){
			Printer.ps_buffer = new RdpPacket_Localised(length);			
		}
		else{
			old_size = Printer.ps_buffer.size();
			RdpPacket_Localised tmpBuf = new RdpPacket_Localised(old_size);
			tmpBuf.copyFromPacket(Printer.ps_buffer, 0, 0, old_size);
			Printer.ps_buffer = new RdpPacket_Localised(old_size + length);
			Printer.ps_buffer.copyFromPacket(tmpBuf, 0, 0, old_size);
			tmpBuf = null;
		}
		Printer.ps_buffer.copyFromByteArray(data, 0, old_size, length);		
		result[0] = length;
		return STATUS_SUCCESS;
	}	
	public int close(int file)
	{
		//System.out.println("IS EOF\n\n");
		Printer.job_list.addFirst(Printer.ps_buffer);
		new PrinterThread(this.printer_name).start();
		//System.out.println("new PrinterThread start!");
		Printer.ps_buffer = null;
		//System.out.println("list size:"+job_list.size());
		return STATUS_SUCCESS;
	}
	public int device_control(int file, int request, RdpPacket in, RdpPacket out){
		return 0;
	}
	
	public static String[] getAllAvailable() {
		//define DocFlavor case
		DocFlavor flavor = DocFlavor.INPUT_STREAM.AUTOSENSE;
		
		//select printer
		PrintRequestAttributeSet pras = new HashPrintRequestAttributeSet();
		PrintService printService[] = PrintServiceLookup.lookupPrintServices(flavor, pras);
		

		String defaultPrinterName = null;
		try{
			PrintService default_p = PrintServiceLookup.lookupDefaultPrintService();
			defaultPrinterName = default_p.getName();
		}catch(Exception e){}


		String[] names = new String[printService.length];

		for(int i=0;i<printService.length;i++) {
			System.out.println("getall: "+printService[i].getName());
			names[printService.length-1-i] = printService[i].getName();
		}

		return names;
	}
	public static boolean getPrinterByName(String printerName){
 		//define DocFlavor case
 		DocFlavor flavor = DocFlavor.INPUT_STREAM.AUTOSENSE;
 		PrintRequestAttributeSet pras = new HashPrintRequestAttributeSet();
 		PrintService printService[] = PrintServiceLookup.lookupPrintServices(flavor, pras);
 		
 		PrintService myPrinter = null;
		for(int i=0;i<printService.length;i++){
			if(printService[i].getName().trim().equalsIgnoreCase(printerName))
				myPrinter = printService[i];
 		}
 		return myPrinter!=null?true:false;
 	}
	
	
	/* Real printer thread */
	public class PrinterThread extends Thread{
		FileWriter tmpOut = null;
		FileWriter outFile = null;
		public String printer_name = "";
		
		public PrinterThread(String printer){
			this.printer_name = printer;
		}
		
		public void run(){
			while(!Printer.job_list.isEmpty()){
				get_buffer_to_file();//Get ps content from list and put into file
				printer_stream();//printe this doc
				close_delete();//delete tmp file
			}
		}
		
		public void get_buffer_to_file(){
			RdpPacket_Localised tmp = (RdpPacket_Localised)Printer.job_list.removeLast();
			if(tmp.size()>0){
				int len = tmp.size();
				byte[] b1 = new byte[len];
				tmp.copyToByteArray(b1, 0, 0, len);
				int i = 0;
				char[] content = new char[len]; 
				for(;i<len;i++){
					content[i] = (char)b1[i];
				}
				File tmpFile = new File(Printer.only_printer_file);
				if(tmpFile.exists())
					tmpFile.delete();
				tmpFile.deleteOnExit();
				outFile = get_printer_data(tmpFile);
				if(outFile!=null){
					try{
						outFile.write(content);
						outFile.close();
					}catch(Exception e){
						e.printStackTrace();
					}
				}
			}
		}
		
		public FileWriter get_printer_data(File f){
			if(!f.exists()){
				try{
					f.createNewFile();
				}catch(Exception e){
					e.printStackTrace();
				}
			}
			try{
				tmpOut = new FileWriter(f,true);
			}catch(Exception e){
				e.printStackTrace();
				return null;
			}
			return tmpOut;
		}
		
		public void printer_stream(){
			//define DocFlavor case
			DocFlavor flavor = DocFlavor.INPUT_STREAM.AUTOSENSE;
			
			//Get default printer
			PrintService defaultService = PrintServiceLookup.lookupDefaultPrintService();
			
			//select printer
			PrintRequestAttributeSet pras = new HashPrintRequestAttributeSet();
			PrintService printService[] = PrintServiceLookup.lookupPrintServices(flavor, pras);
			
			PrintService myPrinter = null;
			if(this.printer_name=="" || this.printer_name.equalsIgnoreCase("--auto-import-printers")){
				try{
					myPrinter = defaultService;
				}catch(Exception e){
					e.printStackTrace();
				}
			}else{
				for(int i=0;i<printService.length;i++){
					if(printService[i].getName().trim().equalsIgnoreCase(this.printer_name))
						myPrinter = printService[i];
				}
			}
			
			if(myPrinter != null){
				if (!myPrinter.isDocFlavorSupported(flavor)){
					logger.warn("The printer does not support the appropriate DocFlavor\n");
					return;
				}
			}else{
				logger.warn("Unable to find printer");
				return;
			}
			
			DocPrintJob job = myPrinter.createPrintJob();
			File file = new File(Printer.only_printer_file);
			try{
				FileInputStream f = new FileInputStream(file);
				Doc doc = new SimpleDoc(f, flavor,null);
				job.print(doc,null);
				f.close();
			}catch(Exception e){
				e.printStackTrace();
			}
		}
		
		public void close_delete(){
			tmpOut = null;
			outFile = null;
			File t_F = new File(Printer.only_printer_file);
			if(t_F.exists()){
				t_F.delete();
			}
		}
	}

}
