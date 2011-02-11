/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
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

package org.ulteo.ovd.client.portal;

import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;

import org.ulteo.ovd.Application;
import org.ulteo.ovd.ApplicationInstance;
import org.ulteo.ovd.integrated.RestrictedAccessException;

public class ApplicationListener implements ActionListener{
	Application app = null;
	RunningApplicationPanel runningApps = null;
	
	public ApplicationListener(Application app, RunningApplicationPanel runningApps) {
		this.app = app;
		this.runningApps = runningApps;
	}
	
	public void actionPerformed(ActionEvent arg0) {
		int token = (int) (Math.random() * 1000000000);
		ApplicationInstance ai = new ApplicationInstance(this.app, null, token);
		ai.setLaunchedFromShortcut(false);
		this.runningApps.addInstance(ai);

		try {
			ai.startApp();
		} catch (RestrictedAccessException ex) {
			org.ulteo.Logger.error("Weird: Should not appear: "+ex);
		}
	}
}
