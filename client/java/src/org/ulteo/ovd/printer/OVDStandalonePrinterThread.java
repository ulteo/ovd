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

import java.util.concurrent.BlockingQueue;
import java.util.concurrent.LinkedBlockingQueue;


public class OVDStandalonePrinterThread implements OVDPrinterThread, Runnable{
	private BlockingQueue<OVDJob> spool;
	
	
	public OVDStandalonePrinterThread() {
		spool = new LinkedBlockingQueue<OVDJob>() ;
	}

	
	public void run(){
		OVDJob job = null;
		try{
			job = spool.take();
		}
		catch (InterruptedException e){ }
		job.print();
	}
	
	public void printPages(String printerName, String pdfFilename) {
		this.spool.add(new OVDJob(pdfFilename, printerName));
		new Thread(this).start();
	}

	@Override
	public boolean hasFocus() {
		return false;
	}	
}
