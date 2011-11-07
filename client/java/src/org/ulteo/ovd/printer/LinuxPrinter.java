/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David Lechevalier <david@ulteo.com> 2011
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

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;

import org.ulteo.Logger;

public class LinuxPrinter implements Runnable {
	private final String linuxPrintingCommand = "lpr -P "; 
	
	private String printerName;
	private String job;
	
	public LinuxPrinter(String printerName, String job) {
		this.printerName = printerName;
		this.job = job;
	}

	@Override
	public void run() {
		try {
			Process p = Runtime.getRuntime().exec(linuxPrintingCommand+" "+this.printerName+" "+this.job);
			p.waitFor();
			if (p.exitValue() == 0) {
				Logger.info("Job printed successfully");
			}
		
			BufferedReader reader = new BufferedReader(new InputStreamReader(p.getErrorStream()));
			String line = "";
			try {
				while((line = reader.readLine()) != null) {
					Logger.warn("Printer output "+line);
				}
			} finally {
				reader.close();
				p.destroy();
			}
		} catch (IOException e) {
			Logger.warn("Unable to print the current job :" + e.getMessage());
		} catch (InterruptedException e) {
			Logger.warn("Unable to print the current job :" + e.getMessage());
		}
	}
}
