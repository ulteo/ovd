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

package org.ulteo.ovd.client.desktop;

import java.awt.Dimension;
import java.awt.Image;
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;

import javax.swing.JFrame;
import javax.swing.JOptionPane;

import org.ulteo.ovd.client.I18n;

public class DesktopFrame extends JFrame implements WindowListener {

	private Image logo = null; 
	
	public DesktopFrame(Dimension dim, boolean undecorated) {
		logo = getToolkit().getImage(getClass().getClassLoader().getResource("pics/ulteo.png"));
		setIconImage(logo);
		setSize(dim);
		setPreferredSize(dim);
		this.setTitle("Ulteo Remote Desktop");
		setAlwaysOnTop(undecorated);
		setUndecorated(undecorated);
		setResizable(false);
		setDefaultCloseOperation(JFrame.DO_NOTHING_ON_CLOSE);
		setLocation(0, 0);
		setVisible(true);
		this.addWindowListener(this);
		pack();
	}

	@Override
	public void windowActivated(WindowEvent arg0) {}

	@Override
	public void windowClosed(WindowEvent arg0) {}

	@Override
	public void windowClosing(WindowEvent arg0) {			
		int option = JOptionPane.showConfirmDialog(null, I18n._("Do you really want to close the window ?"), I18n._("Warning !"), JOptionPane.YES_NO_OPTION, JOptionPane.WARNING_MESSAGE);

		if(option == JOptionPane.OK_OPTION) {
			System.exit(0);
		}
		else {
			this.setVisible(true);
		}
	}

	@Override
	public void windowDeactivated(WindowEvent arg0) {}

	@Override
	public void windowDeiconified(WindowEvent arg0) {}

	@Override
	public void windowIconified(WindowEvent arg0) {}

	@Override
	public void windowOpened(WindowEvent arg0) {}
}
