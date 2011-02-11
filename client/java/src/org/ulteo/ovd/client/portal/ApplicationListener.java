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
import org.apache.log4j.Logger;

import org.ulteo.ovd.Application;
import org.ulteo.ovd.ApplicationInstance;
import org.ulteo.ovd.integrated.RestrictedAccessException;

public class ApplicationListener implements ActionListener{
	private Logger logger = Logger.getLogger(ApplicationListener.class);
	Application app = null;
	RunningApplicationPanel runningApps = null;
	
	public ApplicationListener(Application app, RunningApplicationPanel runningApps) {
		this.app = app;
		this.runningApps = runningApps;
	}
	
	@Override
	public void actionPerformed(ActionEvent arg0) {
		long a = ((long)((Math.random()+1)*1000000));
		long b = ((long)((Math.random()+1)*100000));
		int token = (int)((a/b)*(long)((Math.random()+1)*10000));

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
