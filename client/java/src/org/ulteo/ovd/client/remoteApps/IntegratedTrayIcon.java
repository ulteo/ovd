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

package org.ulteo.ovd.client.remoteApps;

import java.awt.AWTException;
import java.awt.Frame;
import java.awt.Image;
import java.awt.SystemTray;
import java.awt.TrayIcon;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;

import javax.swing.JFrame;

import org.ulteo.Logger;

public class IntegratedTrayIcon extends TrayIcon implements ActionListener {
	private Frame portal = null;
	private SystemTray systemTray = null;


	public IntegratedTrayIcon(Frame portal, Image logo) throws UnsupportedOperationException {
		super(logo, "Open Virtual Desktop");
		this.setImage(logo);
		this.portal = portal;
		this.setImageAutoSize(true);
		this.addActionListener(this);
		this.systemTray = SystemTray.getSystemTray();
	}

	public void addSysTray() {
		try {
			this.systemTray.add(this);
		} catch (AWTException e) {
			e.printStackTrace();
		}
	}
	
	public void removeSysTray() {
		try {
			this.systemTray.remove(this);
		} catch (Exception ex) {
			Logger.error("An error occured while removing the systray icon: "+ex.getMessage());
		}
	}
	
	public void actionPerformed(ActionEvent ae) {
		if (portal.isVisible()) {
			portal.setExtendedState(JFrame.ICONIFIED);
			portal.setVisible(false);
		}
		else {
			portal.setVisible(true);
			portal.setState(JFrame.NORMAL);
		}
	}

}
