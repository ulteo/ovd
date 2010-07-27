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
import java.awt.GraphicsConfiguration;
import java.awt.GraphicsEnvironment;
import java.awt.Image;
import java.awt.Insets;
import java.awt.Toolkit;
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;

import javax.swing.JFrame;
import javax.swing.JOptionPane;

import org.ulteo.ovd.client.I18n;
import org.ulteo.rdp.RdpActions;

public class DesktopFrame extends JFrame implements WindowListener {

	private Image logo = null;
	private RdpActions actions = null;
	public static int screenWidth = Toolkit.getDefaultToolkit().getScreenSize().width;
	public static int screenHeight = Toolkit.getDefaultToolkit().getScreenSize().height;
	private static GraphicsConfiguration gconf = GraphicsEnvironment.getLocalGraphicsEnvironment().getDefaultScreenDevice().getDefaultConfiguration();
	private static Insets insets = Toolkit.getDefaultToolkit().getScreenInsets(gconf);
	public static Dimension SMALL_RES = new Dimension(800,600);
	public static Dimension MEDUIM_RES = new Dimension(1024,768);
	public static Dimension HIGH_RES = new Dimension(1280,678);
	public static Dimension MAXIMISED = new Dimension(screenWidth-insets.left-insets.right, screenHeight-insets.top-insets.bottom);
	public static Dimension FULLSCREEN = new Dimension(screenWidth, screenHeight);
	
	public DesktopFrame(Dimension dim, boolean undecorated, RdpActions actions_) {
		this.actions = actions_;
		this.logo = getToolkit().getImage(getClass().getClassLoader().getResource("pics/ulteo.png"));
		setIconImage(logo);
		setSize(dim);
		setPreferredSize(dim);
		this.setTitle("Ulteo Remote Desktop");
		setAlwaysOnTop(undecorated);
		setUndecorated(undecorated);
		setResizable(false);
		setDefaultCloseOperation(JFrame.DO_NOTHING_ON_CLOSE);
		setLocation(0, 0);
		setVisible(false);
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
			this.actions.exit(0);
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
