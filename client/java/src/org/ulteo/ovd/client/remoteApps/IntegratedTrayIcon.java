/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
 * Author Thomas MOUTON <thomas@ulteo.com> 2012
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
import java.awt.Image;
import java.awt.MenuItem;
import java.awt.PopupMenu;
import java.awt.SystemTray;
import java.awt.TrayIcon;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;

import javax.swing.JFrame;

import org.ulteo.Logger;
import org.ulteo.ovd.client.NativeClientActions;
import org.ulteo.ovd.client.OvdClient;
import org.ulteo.ovd.client.OvdClientFrame;

public class IntegratedTrayIcon extends TrayIcon implements ActionListener {
	
	private OvdClientFrame portal = null;
	private NativeClientActions rdpActions = null;

	public IntegratedTrayIcon(OvdClientFrame portal, Image logo, NativeClientActions actions) {
		super(logo, "Open Virtual Desktop");
		
		this.portal = portal;
		this.rdpActions = actions;
		
		this.setImage(logo);
		this.setImageAutoSize(true);
		this.addActionListener(this);
		
		this.initPopupMenu();
	}

	private void initPopupMenu() {
		PopupMenu popup = new PopupMenu();
		
		MenuItem itemDisconnect = new MenuItem("Disconnect");
		itemDisconnect.addActionListener(new ActionListener() {
			@Override
			public void actionPerformed(ActionEvent e) {
				portal.setDisconnectionMode(OvdClient.DisconnectionMode.SUSPEND);
				rdpActions.disconnect(true);
			}
		});
		popup.add(itemDisconnect);
	
		MenuItem itemClose = new MenuItem("Close");
		itemClose.addActionListener(new ActionListener() {
			@Override
			public void actionPerformed(ActionEvent e) {
				portal.openLogoutPopup();
			}
		});
		popup.add(itemClose);
		
		this.setPopupMenu(popup);
	}

	public void add() {
		try {
			SystemTray.getSystemTray().add(this);
		} catch (IllegalArgumentException e) {
			Logger.warn("Systray is already implemented");
		} catch (AWTException e) {
			Logger.warn("Operating system does not have a systray");
		}
	}
	
	public void remove() {
		SystemTray.getSystemTray().remove(this);
	}
	
	@Override
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
