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

package org.ulteo.rdp.rdpdr;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.util.LinkedList;
import java.util.UUID;

import org.apache.log4j.Logger;
import org.ulteo.ovd.printer.OVDPrinterThread;

import net.propero.rdp.rdp5.rdpdr.Printer;
import net.propero.rdp.rdp5.rdpdr.RdpdrChannel;



public class OVDPrinter extends Printer {
	protected static Logger logger = Logger.getLogger(OVDPrinter.class);
	public static LinkedList<UUID> jobs = new LinkedList<UUID>();
	public static OVDPrinterThread printerThread = null;
	public static boolean externalMode = false;
	
	
	public OVDPrinter(RdpdrChannel rdpdr_, String name, String displayName, boolean isDefault) {
		super(rdpdr_, name, displayName, isDefault);
		this.driver = "Ulteo TS Printer Driver";
	}
	
	
	public static void setPrinterThread(OVDPrinterThread ovdPrinter){
		OVDPrinter.printerThread = ovdPrinter;
	}
	
	public static void setExternalMode(boolean externalMode){
		OVDPrinter.externalMode = externalMode;
	}

	public int create(int device, int desired_access, int share_mode, int disposition, int flags_and_attributes, String filename,int[] result){
		logger.debug("Create action");
		UUID uuid = UUID.randomUUID();
		int handle = 0;
		String pdf_filename = System.getProperty("java.io.tmpdir") + File.separator + uuid + ".pdf";
		File pdf_file = new File(pdf_filename);
		// Make sure the file or directory exists and isn't write protected
		pdf_file.delete();
		if (pdf_file.exists()){
			logger.warn("The temporary file["+pdf_filename+"] associated to the job already exist");
			return RdpdrChannel.STATUS_INVALID_PARAMETER;
		}
		try {
			pdf_file.createNewFile();
		} catch (IOException e) {
			logger.warn("The temporary file["+pdf_filename+"] can not be created");
			return RdpdrChannel.STATUS_INVALID_PARAMETER;
		}
		OVDPrinter.jobs.add(uuid);
		handle = OVDPrinter.jobs.indexOf(uuid);
		logger.debug("handle : "+handle);
		if (handle == -1) {
			logger.warn("Handle ["+handle+"] is not a valid handle");
			return RdpdrChannel.STATUS_INVALID_PARAMETER;
		}
		result[0] = handle;
		return RdpdrChannel.STATUS_SUCCESS;
	}
	
	public int write(int handle, byte[] data, int length, int offset, int[] result){
		logger.debug("Write action on " + handle);
		UUID uuid = null;
		try {
			uuid = (UUID)OVDPrinter.jobs.get(handle);
		}
		catch (IndexOutOfBoundsException e) {
			logger.warn("The job with the handle " + handle + " dis not exist");
			return RdpdrChannel.STATUS_INVALID_PARAMETER;
		}

		String pdf_filename = System.getProperty("java.io.tmpdir") + File.separator + uuid + ".pdf";
		File pdf_file = new File(pdf_filename);
		result[0] = 0;
		// Make sure the file or directory exists and isn't write protected
		if (!pdf_file.exists()){
			logger.warn("The temporary file["+pdf_filename+"] associated to the job did not exist");
			return RdpdrChannel.STATUS_INVALID_PARAMETER;
		}
		if (!pdf_file.canWrite()){
			logger.warn("The temporary file["+pdf_filename+"] associated to the job is not accessible");
			return RdpdrChannel.STATUS_INVALID_PARAMETER;
		}
		try{
			FileOutputStream out = new FileOutputStream(pdf_file, true);
			out.write(data);
			out.flush();
			out.close();
		}
		catch(IOException e){
			logger.warn("Unable to write data in the temporary file["+pdf_filename+"]");
			return RdpdrChannel.STATUS_INVALID_PARAMETER;
		}
		result[0] = length;
		return RdpdrChannel.STATUS_SUCCESS;
	}	
	
	public int close(int file)
	{
		logger.debug("Close action on "+file);
		UUID uuid = null;
		try {
			uuid = (UUID)OVDPrinter.jobs.remove(file);
		}
		catch (IndexOutOfBoundsException e) {
			logger.warn("The job with the handle " + handle + " dis not exist");
			return RdpdrChannel.STATUS_INVALID_PARAMETER;
		}
		String pdfFilename = System.getProperty("java.io.tmpdir") + File.separator + uuid + ".pdf";
		if (OVDPrinter.printerThread == null) {
			logger.warn("No printer to process the job " + uuid + ".pdf");
			return RdpdrChannel.STATUS_CANCELLED;
		}
		OVDPrinter.printerThread.printPages(this.printer_name, pdfFilename, this.externalMode);
		return RdpdrChannel.STATUS_SUCCESS;
	}
}
