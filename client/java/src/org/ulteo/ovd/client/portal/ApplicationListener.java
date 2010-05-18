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
import java.util.ArrayList;

import org.ulteo.ovd.Application;
import org.ulteo.rdp.OvdAppChannel;

public class ApplicationListener implements ActionListener{
	Application app = null;
	OvdAppChannel chan = null;
	CurrentApps currentApps = null;
	ArrayList<Application> apps = null;
	
	public ApplicationListener(Application app, OvdAppChannel chan, CurrentApps currentApps, ArrayList<Application> apps) {
		this.app = app;
		this.chan = chan;
		this.currentApps = currentApps;
		this.apps = apps;
	}
	
	@Override
	public void actionPerformed(ActionEvent arg0) {
		long a = ((long)((Math.random()+1)*1000000));
		long b = ((long)((Math.random()+1)*100000));
		long token = (a/b)*(long)((Math.random()+1)*10000);
		app.setInstanceNum((int)token);
		apps.add(app);
		KillListener.apps = apps;
		currentApps.update(apps);
		chan.sendStartApp((int)token, app.getId());
	}
}
