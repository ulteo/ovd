/*
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David Lechevalier <david@ulteo.com> 2013
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

import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;

import org.ulteo.Logger;

public class WindowsPrinter implements Runnable {
	private final String printingCommand = "lpr.exe -restrict -print-to ";
	private final String printingExecutable = "/resources/print/lpr.exe";
	private final String printingPolicy = "/resources/print/sumatrapdfrestrict.ini";
	private String printerName;
	private String job;
	private String tempDir;
	
	public WindowsPrinter(String printerName, String job) {
		this.printerName = printerName;
		this.job = job;
		this.tempDir = System.getProperty("java.io.tmpdir");
		
		this.extract_resource(this.printingExecutable, tempDir+File.separator+"lpr.exe");
		this.extract_resource(this.printingPolicy, tempDir+File.separator+"sumatrapdfrestrict.ini");
	}
	
	private boolean extract_resource(String name, String dist) {
		InputStream is = WindowsPrinter.class.getResourceAsStream(name);
		File dest = new File(dist);
		if (dest.exists()) {
			dest.delete();
		}
		
		try {
			FileOutputStream fos = new FileOutputStream(dest);
			byte[] buffer = new byte[1024];
			int count;
			while ((count = is.read(buffer)) != -1) {
				fos.write(buffer, 0, count);
			}
			
			fos.close();
			is.close();
			
		} catch (FileNotFoundException e) {
			Logger.error("Unable to find the resource "+name);
			return false;
		} catch (IOException e) {
			Logger.error("Unable to find the resource: "+e.getMessage());
			return false;
		}
		
		return true;
	}

	@Override
	public void run() {
		try {
			String command = this.tempDir+File.separator+printingCommand+" \""+this.printerName+"\" "+this.job;
			System.out.println("executing command "+command);
			Process p = Runtime.getRuntime().exec(command);
			p.waitFor();
			if (p.exitValue() == 0) {
				Logger.info("Job printed successfully");
			}
		} catch (IOException e) {
			Logger.warn("Unable to print the current job :" + e.getMessage());
		} catch (InterruptedException e) {
			Logger.warn("Unable to print the current job :" + e.getMessage());
		}

		new File(this.job).delete();
	}
}
