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
package org.ulteo.ovd.applet;

import java.awt.Component;
import java.util.Timer;
import java.util.TimerTask;

import org.ulteo.ovd.printer.OVDPrinterThread;
import org.ulteo.utils.AbstractFocusManager;

public class AppletFocusManager implements AbstractFocusManager{
	private OVDPrinterThread printerThread = null;

	public class FocusTask extends TimerTask {
		private Component component;
		private long taskCount = 10;
		
		public FocusTask(Component c) {
			this.component = c;
		}
	
		public void run() {
			this.component.requestFocusInWindow();
			this.component.requestFocus();

			if (this.taskCount == 0)
			{
				this.cancel();
				return;
			}
			taskCount--;
		}
	}

	public AppletFocusManager(OVDPrinterThread pt) {
		this.printerThread = pt;
	}

	@Override
	public void performedFocusGain(Component component) { 
	}

	@Override
	public void performedFocusLost(Component component) {
		if (component == null) {
			return;
		}
		if (this.printerThread != null && this.printerThread.hasFocus()) {
			Timer timer = new Timer();
			timer.schedule(new FocusTask(component), 0, 500);
		}
	}
}
