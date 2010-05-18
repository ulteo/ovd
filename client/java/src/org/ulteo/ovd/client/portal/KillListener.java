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

public class KillListener implements ActionListener {

	private OvdAppChannel chan = null;
	public static int[] selectedApp = null;
	public static ArrayList<Application> apps = null;
	
	public KillListener(OvdAppChannel chan, ArrayList<Application> apps, int[] selectedApp) {
		this.chan = chan;
		this.apps = apps;
		this.selectedApp = selectedApp;
	}
	
	@Override
	public void actionPerformed(ActionEvent e) {
		System.out.println("action kill !");
		System.out.println("selectedApp : "+selectedApp);
		System.out.println("selectedApp.length : "+selectedApp.length);
	}

}
