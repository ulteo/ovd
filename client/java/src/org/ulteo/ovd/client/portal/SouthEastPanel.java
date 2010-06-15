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

import java.util.ArrayList;

import javax.swing.JButton;
import javax.swing.JPanel;

import org.ulteo.ovd.Application;
import org.ulteo.ovd.client.I18n;
import org.ulteo.rdp.RdpActions;


public class SouthEastPanel extends JPanel{
	
	private JButton disconnect = new JButton(I18n._("Disconnect"));
	private JButton killApp = new JButton(I18n._("Kill"));
	
	public SouthEastPanel(RdpActions rdpActions, ArrayList<Application> apps, int[] selectedApps) {
		disconnect.addActionListener(new LogoutListener(rdpActions));
		killApp.addActionListener(new KillListener(rdpActions, apps, selectedApps));
		this.add(killApp);
		this.add(disconnect);
	}
	
}
