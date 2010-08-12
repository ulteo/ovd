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

import java.awt.Color;
import java.awt.Font;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.Image;
import java.awt.Insets;
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;

import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JOptionPane;

import org.ulteo.ovd.client.I18n;
import org.ulteo.rdp.RdpActions;

public class PortalFrame extends JFrame implements WindowListener {

	private RdpActions rdpActions = null;
	private JLabel user = null;
	private JLabel application = new JLabel(I18n._("My applications"));
	private JLabel runningApps = new JLabel(I18n._("Running applications"));
	private MyApplicationPanel appsPanel = null;
	private RunningApplicationPanel runningAppsPanel = null;
	private GridBagConstraints gbc = null;
	private SouthEastPanel sep = null;	
	private Image frameLogo = null;
	private Font font = new Font("Dialog", 1, 12);
	
	public PortalFrame(String username) {
		if (username == null)
			username = "";
		String displayName = I18n._("Welcome {user}");
		displayName = displayName.replaceAll("\\{user\\}", username);
		this.addWindowListener(this);
		this.user = new JLabel(displayName);
		this.init();
	}
	
	
	public void init() {
		
		this.setTitle("OVD Remote Applications");
		this.setSize(700,400);
		this.setResizable(false);
		this.setLocationRelativeTo(null);
		this.setDefaultCloseOperation(JFrame.DO_NOTHING_ON_CLOSE);
		this.frameLogo = this.getToolkit().getImage(getClass().getClassLoader().getResource("pics/ulteo.png"));
		this.setIconImage(frameLogo);
		
		this.user.setFont(new Font("Dialog", 1, 18));
		this.user.setForeground(new Color(97, 99, 102));
		this.application.setFont(font);
		this.runningApps.setFont(font);
		
		this.runningAppsPanel = new RunningApplicationPanel();
		this.appsPanel = new MyApplicationPanel(this.runningAppsPanel);
		
		this.setLayout(new GridBagLayout());
		this.gbc = new GridBagConstraints();
		
		gbc.insets = new Insets(0, 0, 5, 20);
		gbc.gridx = 0;
		gbc.gridy = 1;
		gbc.anchor = GridBagConstraints.SOUTHWEST;
		this.add(application, gbc);
		
		gbc.gridy = 2;
		gbc.anchor = GridBagConstraints.CENTER;
		gbc.insets.bottom = 20;
		this.add(appsPanel, gbc);
		
		gbc.gridx = 1;
		gbc.gridy = 0;
		gbc.gridheight = 1;
		gbc.fill = GridBagConstraints.NONE;
		this.add(user, gbc);
		
		gbc.gridy = 1;
		gbc.insets.bottom = 5;
		gbc.anchor = GridBagConstraints.SOUTHWEST;
		this.add(runningApps, gbc);
		
		gbc.gridy = 2;
		gbc.anchor = GridBagConstraints.CENTER;
		gbc.insets.bottom = 20;
		gbc.insets.right = 5;
		this.add(runningAppsPanel, gbc);
		this.validate();
	}
	
	public void initButtonPan(RdpActions _rdpActions) {
		this.rdpActions = _rdpActions;
		this.gbc.gridy = 4;
		this.gbc.gridx = 1;
		this.gbc.insets.bottom = 10;
		this.gbc.anchor = GridBagConstraints.SOUTHEAST;
		this.sep = new SouthEastPanel(_rdpActions);
		this.add(sep, gbc);
		this.validate();
	}
		
	public MyApplicationPanel getApplicationPanel() {
		return this.appsPanel;
	}
	
	public RunningApplicationPanel getRunningApplicationPanel() {
		return runningAppsPanel;
	}

	public void enableIconsButton() {
		this.sep.toggleIconsButton(true);
	}

	@Override
	public void windowActivated(WindowEvent arg0) {}


	@Override
	public void windowClosed(WindowEvent arg0) {}


	@Override
	public void windowClosing(WindowEvent arg0) {
		if (this.rdpActions != null) {
			int option = JOptionPane.showConfirmDialog(null, I18n._("Do you really want to close the window ?"), I18n._("Warning !"), JOptionPane.YES_NO_OPTION, JOptionPane.WARNING_MESSAGE);

			if(option == JOptionPane.OK_OPTION) {
				this.rdpActions.exit(0);
			}
			else {
				this.setVisible(true);
			}
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
