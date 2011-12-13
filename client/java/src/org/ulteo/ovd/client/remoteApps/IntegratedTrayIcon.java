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
import java.awt.Image;
import java.awt.MenuItem;
import java.awt.PopupMenu;
import java.awt.SystemTray;
import java.awt.TrayIcon;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;

import javax.swing.JFrame;

import org.ulteo.ovd.client.OvdClientFrame;
import org.ulteo.rdp.RdpActions;

public class IntegratedTrayIcon extends TrayIcon implements ActionListener {
	private OvdClientFrame portal = null;
	private RdpActions rdpActions = null;

	public IntegratedTrayIcon(OvdClientFrame portal, Image logo, RdpActions actions) {
		super(logo, "Open Virtual Desktop");
		this.setImage(logo);
		this.portal = portal;
		this.setImageAutoSize(true);
		this.addActionListener(this);
		this.rdpActions = actions;
		this.initPopupMenu();
	}

	private void initPopupMenu() {
		PopupMenu popup = new PopupMenu();
		
		MenuItem itemDisconnect = new MenuItem("Disconnect");
		itemDisconnect.addActionListener(new ActionListener() {
			@Override
			public void actionPerformed(ActionEvent e) {
				rdpActions.disconnect();
			}
		});
		popup.add(itemDisconnect);
	
		MenuItem itemClose = new MenuItem("Close");
		itemClose.addActionListener(new ActionListener() {
			@Override
			public void actionPerformed(ActionEvent e) {
				portal.haveToQuit(true);
				rdpActions.disconnect();
			}
		});
		popup.add(itemClose);
		
		this.setPopupMenu(popup);
	}

	public void addSysTray() {
		try {
			SystemTray.getSystemTray().add(this);
		} catch (AWTException e) {
			e.printStackTrace();
		}
	}
	
	public void removeSysTray() {
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
