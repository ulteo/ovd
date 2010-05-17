/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

package org.ulteo.ovd.standalone;

import java.awt.Frame;
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;
import java.awt.event.WindowStateListener;
import java.util.Observable;
import java.net.URL;
import java.util.ArrayList;
import java.util.Observer;
import javax.swing.ImageIcon;
import javax.swing.JFrame;
import javax.swing.JPanel;
import javax.swing.SwingUtilities;

import net.propero.rdp.RdpConnection;
import net.propero.rdp.RdpListener;

import org.apache.log4j.BasicConfigurator;
import org.apache.log4j.Level;
import org.apache.log4j.Logger;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.rdp.RdpConnectionOvd;

public class Client extends JFrame implements WindowListener, WindowStateListener, Runnable, RdpListener {
	private AuthenticationPanel panel_auth = null;
	private LoadingPanel panel_load = null;
	private JPanel panel_session = null;
	private JPanel current_panel = null;
	private SessionManagerCommunication c = null;
	private ArrayList<RdpConnectionOvd> connections = null;
	private String session_mode = "";
	private Thread sessionThread = null;
	private Logger logger = null;
	
	public Client() {
		super("Ulteo OVD Standalone Client");

		BasicConfigurator.configure();
		this.logger = Logger.getLogger(Client.class.getName());
		this.logger.setLevel(Level.INFO);

		this.connections = new ArrayList<RdpConnectionOvd>();

		this.setSize(480, 320);
		this.setLocationRelativeTo(null);
		this.setResizable(false);
		this.addWindowStateListener(this);


		URL icon_url = this.getClass().getResource("/ressources/ulteo.png");
		this.setIconImage(new ImageIcon(icon_url).getImage());

		this.panel_auth = new AuthenticationPanel(this);
		this.panel_load = new LoadingPanel(this);

		this.current_panel = this.panel_auth;
		this.add(this.current_panel);
		this.pack();
	}

	public void setSessionPanel(JPanel panel) {
		this.panel_session = panel;
		
	}

	public void switch2Auth() {
		if (this.current_panel != null)
			this.remove(this.current_panel);

		this.current_panel = this.panel_auth;
		this.add(this.current_panel);
		this.pack();
	}

	public void switch2Load() {
		if (this.current_panel != null)
			this.remove(this.current_panel);

		this.current_panel = this.panel_load;
		this.add(this.current_panel);
		this.pack();

		this.sessionThread = new Thread(this);
		this.sessionThread.start();
	}

	public void switch2Session() {
		if (this.current_panel != null)
			this.remove(this.current_panel);

		this.current_panel = this.panel_session;
		this.add(((Desktop)this.current_panel).getCanvas());
		this.pack();
		this.setLocation(0, 0);
	}

	public void switch2AuthCallbak() {
		SwingUtilities.invokeLater(new Runnable() {
				public void run() {
					switch2Auth();
				}
		});
	}

	public void run() {
		try {
			this.c = new SessionManagerCommunication(this.panel_auth.getServer());
			
			if (!c.askForSession(this.panel_auth.getLogin(), this.panel_auth.getPassword(), this.panel_auth.getMode())) {
				this.switch2Auth();
				return;
			}
			this.connections = c.getConnections();
			this.session_mode = c.getSessionMode();

			if (this.session_mode.equalsIgnoreCase("portal")) {
				// ToDo: Portal Mode
				System.out.println("ToDo: Portal Mode");
			} else if (this.session_mode.equalsIgnoreCase("desktop")) {
				if (this.connections.size() < 1)
					return;
				RdpConnectionOvd co = this.connections.get(0);
				co.addRdpListener(this);

				Desktop desk = new Desktop(this, co);
				co.connect();
				
				this.panel_session = desk;
			}
		} catch (Exception ex) {
			this.logger.error(ex.getMessage());
		}
	}

	public void exit() {
		System.out.println("exit");
		
		if (this.sessionThread != null && this.sessionThread.isAlive()) {
			
			this.sessionThread.interrupt();
			try {
				this.sessionThread.join(500);
			} catch (InterruptedException e) {
				System.err.println("Thread didn't join in 500m, hope the garbage collector will do his job");
				this.sessionThread = null;
				e.printStackTrace();
			}
			
			try {
				Thread.sleep(5000);
			} catch (InterruptedException e) {
				// TODO Auto-generated catch block
				e.printStackTrace();
			}
		}
		
	

		this.setVisible(false);
		this.removeAll();
		this.panel_auth = null;
		this.panel_load = null;
		this.panel_session = null;

		System.exit(0);
	}

	public void windowActivated(java.awt.event.WindowEvent e) {}
	public void windowClosed(java.awt.event.WindowEvent e) {}
	public void windowClosing(java.awt.event.WindowEvent e) {
		this.exit();
	}
	
	public void windowDeactivated(java.awt.event.WindowEvent e) {}
	public void windowDeiconified(java.awt.event.WindowEvent e) {}
	public void windowIconified(java.awt.event.WindowEvent e) {}
	public void windowOpened(java.awt.event.WindowEvent e) {}


	public static void main(String args[]) {
		Client o = new  Client();
		o.addWindowListener(o);
		o.setVisible(true);
	}

	public void windowStateChanged(WindowEvent we) {
		if (this.current_panel == this.panel_session && this.session_mode.equalsIgnoreCase("desktop") && we.getNewState() == Frame.MAXIMIZED_BOTH) {
			this.setResizable(false);
			this.setLocation(0, 0);
		}
	}

	@Override
	public void connected(RdpConnection co) {
		if (this.session_mode.equalsIgnoreCase("desktop")) {
			this.switch2Session();
		}
	}

	@Override
	public void connecting(RdpConnection co) {}

	@Override
	public void disconnected(RdpConnection co) {
		if (this.session_mode.equalsIgnoreCase("desktop")) {
			this.exit();
		}
	}

	@Override
	public void failed(RdpConnection co) {}
}
