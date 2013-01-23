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
import org.ulteo.utils.FilesOp;

public class WindowsPrinter implements Runnable {
	private final String printingCommand = "lpr.exe -print-to "; 
	
	private String printerName;
	private String job;
	private String tempDir;
	
	public WindowsPrinter(String printerName, String job) {
		this.printerName = printerName;
		this.job = job;
		this.tempDir = System.getProperty("java.io.tmpdir");
		
		// extracting resource in %TEMP%
		InputStream is = WindowsPrinter.class.getResourceAsStream("/resources/print/lpr.exe");
		File dest = new File(tempDir+File.separator+"lpr.exe");
		if (dest.exists())
			dest.delete();
		
		try {
			FileOutputStream fos = new FileOutputStream(dest);
			int c = 0;
			while ((c = is.read()) != -1)
				fos.write(c);
			
			fos.close();
		} catch (FileNotFoundException e) {
			e.printStackTrace();
		} catch (IOException e) {
			e.printStackTrace();
		}
	}

	@Override
	public void run() {
		try {
			Process p = Runtime.getRuntime().exec(this.tempDir+File.separator+printingCommand+" "+this.printerName+" "+this.job);
			p.waitFor();
			if (p.exitValue() == 0) {
				Logger.info("Job printed successfully");
			}
		} catch (IOException e) {
			Logger.warn("Unable to print the current job :" + e.getMessage());
		} catch (InterruptedException e) {
			Logger.warn("Unable to print the current job :" + e.getMessage());
		}
	}
}
