/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
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

import java.util.ArrayList;
import java.util.TimerTask;

import org.apache.log4j.Logger;
import org.ulteo.rdp.rdpdr.OVDPrinter;


public class PrinterUpdater extends TimerTask{
	Logger logger = Logger.getLogger(PrinterUpdater.class);
	private OVDPrinterManager printerManager = null;
		
	public PrinterUpdater(OVDPrinterManager pm) {
		this.printerManager = pm;
	}
		
	public void run() {
		if (! this.printerManager.isReady()) {
			return;
		}
		ArrayList<OVDPrinter> list = this.printerManager.searchNewPrinter();
		
		for (OVDPrinter p: list) {
			this.printerManager.mount(p);
		}		
	}
}
