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
import java.io.File;
import java.io.FileNotFoundException;
import java.io.IOException;

import javax.swing.JFrame;
import javax.swing.JOptionPane;

import org.ini4j.Wini;
import org.ulteo.ovd.integrated.Constants;
import org.ulteo.rdp.Connection;

public class AuthFrame extends JFrame implements WindowListener {

	private Image logo = null;
	private MainPanel mp = null;
	private Connection connection = null;
	private boolean desktopLaunched = false;
	private boolean use_https = true;
	private String username = null;
	private String ovdServer = null;
	private String initMode = null;
	private int mode = 0;
	private String initRes = null;
	private int resolution = 1;
	private String token = null;

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
		
		boolean defaultProfileIsPresent = true;
		File defaultProfile = new File(Constants.clientConfigFilePath+Constants.separator+"default.conf");
		try {
			parseProfileFile(defaultProfile);
		} catch (FileNotFoundException e) {
			defaultProfileIsPresent = false;
			System.out.println("no default profile");
		} catch (IOException e) {
			e.printStackTrace();
		}
		
		if(defaultProfileIsPresent) {
			mp.getIds().getRememberMe().setSelected(true);
			this.getMp().getLogPan().getUsername().setText(username);
			this.getMp().getHostPan().getHostname().setText(ovdServer);
			this.getMp().getOptionPanel().getComboMode().setSelectedIndex(mode);
			this.getMp().getOptionPanel().getScreenSizeSelecter().setValue(resolution);
		}
	}

	public void parseProfileFile(File profile) throws IOException, FileNotFoundException {
		Wini ini = new Wini(profile);
		username = ini.get("user", "login");
		ovdServer = ini.get("server", "host");
		initMode = ini.get("sessionMode", "ovdSessionMode");
		if (initMode.equals("desktop"))
			mode = 0;
		else if (initMode.equals("portal"))
			mode = 1;
		else
			mode = 2;

		initRes = ini.get("screen", "size");
		if(initRes.equals("800x600"))
			resolution=0;
		else if(initRes.equals("1024x768"))
			resolution=1;
		else if(initRes.equals("1280x678"))
			resolution=2;
		else if(initRes.equals("maximized"))
			resolution=3;
		else
			resolution=4;				

		token = ini.get("token", "token");
	}

	public MainPanel getMp() {
		return mp;
	}

	public void setMp(MainPanel mp) {
		this.mp = mp;
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
