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

package org.ulteo.ovd.client.authInterface;

import java.awt.Dimension;
import java.awt.Image;
import java.awt.KeyboardFocusManager;
import java.awt.event.WindowAdapter;
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;

import javax.swing.JFrame;
import javax.swing.JOptionPane;

import org.ulteo.rdp.Connection;

public class AuthFrame extends JFrame implements WindowListener {

	private Image logo = null;
	private MainPanel mp = null;
	private Connection connection = null;
	private boolean desktopLaunched = false;
	private boolean use_https = true;

	public AuthFrame(boolean use_https_) {
		this.use_https = use_https_;

		init();
	}

	public void init() {
		KeyboardFocusManager.setCurrentKeyboardFocusManager(null);
		logo = getToolkit().getImage(getClass().getClassLoader().getResource("pics/ulteo.png"));
		setIconImage(logo);

		setSize(400,600);
		setPreferredSize(new Dimension(400,600));
		setLocationRelativeTo(null);

		setTitle("Ulteo Remote Desktop");
		setDefaultCloseOperation(JFrame.DO_NOTHING_ON_CLOSE);

		setVisible(true);
		setResizable(false);

		mp = new MainPanel(this);
		setContentPane(mp);

		this.addWindowListener(this);
		this.addWindowFocusListener(new WindowAdapter() {
			public void windowGainedFocus(WindowEvent e) {
				mp.getLogPan().getUsername().requestFocusInWindow();
			}
		});
		mp.setFocusOnLogin();
		pack();
	}

	public boolean isHttps() {
		return this.use_https;
	}

	public Connection getConnection() {
		return connection;
	}

	public void setConnection(Connection co) {
		this.connection = co;
	}

	public boolean isDesktopLaunched() {
		return desktopLaunched;
	}

	public void setDesktopLaunched(boolean desktopLaunched) {
		this.desktopLaunched = desktopLaunched;
	}

	@Override
	public void windowActivated(WindowEvent arg0) {}

	@Override
	public void windowClosed(WindowEvent arg0) {}

	@Override
	public void windowClosing(WindowEvent we) {

		if(desktopLaunched) {		
			int option = JOptionPane.showConfirmDialog(null, "Do you really want to close the window ?", "Warning !", JOptionPane.YES_NO_OPTION, JOptionPane.WARNING_MESSAGE);

			if(option == JOptionPane.OK_OPTION) {
				// TODO close connection
				System.exit(0);
			}
			else {
				this.setVisible(true);
			}
		}
		else {
			System.exit(0);
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
