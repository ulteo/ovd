/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
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
import java.awt.Component;
import java.awt.Font;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.Image;
import java.awt.Insets;
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;
import java.util.ArrayList;
import java.util.List;

import javax.swing.ImageIcon;
import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import org.ulteo.Logger;

import org.ulteo.utils.I18n;
import org.ulteo.ovd.client.authInterface.LogoutPopup;
import org.ulteo.gui.GUIActions;
import org.ulteo.gui.SwingTools;
import org.ulteo.ovd.client.remoteApps.IntegratedTrayIcon;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.rdp.RdpActions;

public class PortalFrame extends JFrame implements WindowListener {

	private RdpActions rdpActions = null;
	private JLabel user = null;
	private JLabel application = new JLabel(I18n._("My applications"));
	private JLabel runningApps = new JLabel(I18n._("Running applications"));
	private MyApplicationPanel appsPanel = null;
	private RunningApplicationPanel runningAppsPanel = null;
	private NewsPanel newsPanel = null;
	private boolean newPanelAdded = false;
	private GridBagConstraints gbc = null;
	private SouthEastPanel sep = null;	
	private Font font = new Font("Dialog", 1, 12);
	private IntegratedTrayIcon systray = null;

	private boolean iconsButtonEnabled = false;
	
	public PortalFrame(String username) {
		if (username == null)
			username = "";
		String displayName = I18n._("Welcome {user}");
		displayName = displayName.replaceAll("\\{user\\}", username);
		this.addWindowListener(this);
		this.user = new JLabel(displayName);
		this.init();
		this.newsPanel = new NewsPanel();
	}
	
	
	public void init() {
		Image frameLogo = this.getToolkit().getImage(getClass().getClassLoader().getResource("pics/ulteo.png"));
		
		JLabel ulteoLogo = new JLabel(new ImageIcon(this.getToolkit().getImage(getClass().getClassLoader().getResource("pics/logo_small.png"))));
		try {
			this.systray = new IntegratedTrayIcon(this, frameLogo);
		} catch (UnsupportedOperationException ex) {
			Logger.error("Systray is not supported: "+ex.getMessage());
			this.systray = null;
		}
		
		this.setIconImage(frameLogo);
		this.setTitle("OVD Remote Applications");
		this.setSize(700,400);
		this.setResizable(false);
		this.setLocationRelativeTo(null);
		this.setDefaultCloseOperation(JFrame.DO_NOTHING_ON_CLOSE);
		
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
		if (this.systray != null)
			this.systray.addSysTray();
		this.validate();
	}
	
	public void initButtonPan(RdpActions _rdpActions) {
		this.rdpActions = _rdpActions;
		this.gbc.gridy = 4;
		this.gbc.gridx = 1;
		this.gbc.insets.bottom = 10;
		this.gbc.anchor = GridBagConstraints.SOUTHEAST;
		this.sep = new SouthEastPanel(_rdpActions);
		this.enableIconsButton();
		this.add(sep, gbc);
		this.validate();
	}
		
	public MyApplicationPanel getApplicationPanel() {
		return this.appsPanel;
	}
	
	public RunningApplicationPanel getRunningApplicationPanel() {
		return runningAppsPanel;
	}
	
	public IntegratedTrayIcon getSystray() {
		return this.systray;
	}

	public void enableIconsButton() {
		if (this.iconsButtonEnabled)
			return;

		if (this.sep == null)
			return;

		this.iconsButtonEnabled = true;
		this.sep.toggleIconsButton(true);
	}

	
	public NewsPanel getNewsPanel() {
		return this.newsPanel;
	}
	
	public boolean containsNewsPanel() {
		return this.newPanelAdded;
	}
	
	public void addNewsPanel() {
		GridBagConstraints gbc = new GridBagConstraints();
		
		gbc.anchor = GridBagConstraints.SOUTHWEST;
		gbc.gridheight = 1;
		gbc.gridy = 4;
		gbc.gridx = 0;
		gbc.fill = GridBagConstraints.NONE;
		gbc.insets = new Insets(0, 0, 5, 20);
		gbc.insets.bottom = 5;
		gbc.insets.right = 5;
		
		List<Component> list1 = new ArrayList<Component>();
		List<GridBagConstraints> list2 = new ArrayList<GridBagConstraints>();
		
		list1.add(this.newsPanel);
		list2.add(gbc);
		
		SwingTools.invokeLater(GUIActions.addComponents(this, list1, list2));
		
		this.newPanelAdded = true;
	}
	
	public void removeNewsPanel() {
		List<Component> list1 = new ArrayList<Component>();
		list1.add(this.newsPanel);
		
		SwingTools.invokeLater(GUIActions.removeComponents(this, list1));
		SwingTools.invokeLater(GUIActions.validate(this));
		
		this.newPanelAdded = false;
	}
	
	
	@Override
	public void windowActivated(WindowEvent arg0) {}


	@Override
	public void windowClosed(WindowEvent arg0) {}


	@Override
	public void windowClosing(WindowEvent arg0) {
		if (this.rdpActions != null)
			new LogoutPopup(this, this.rdpActions);
		else
			System.err.println("can't manage disconnection request: rdpAction is null");
	}


	@Override
	public void windowDeactivated(WindowEvent arg0) {}


	@Override
	public void windowDeiconified(WindowEvent arg0) {}


	@Override
	public void windowIconified(WindowEvent arg0) {
		if (OSTools.isWindows() && this.systray != null)
			this.setVisible(false);
		/* Bug on linux, when frame is iconified,
		 * it will never be deiconified by clicking on systray icon */
	}


	@Override
	public void windowOpened(WindowEvent arg0) {}
}
