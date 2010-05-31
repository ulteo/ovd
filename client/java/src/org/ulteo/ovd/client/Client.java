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

package org.ulteo.ovd.client;

import java.awt.Container;
import java.awt.Insets;
import java.util.ArrayList;
import java.util.logging.Level;
import java.util.logging.Logger;

import net.propero.rdp.RdpConnection;
import net.propero.rdp.RdpListener;

import org.ulteo.ovd.Application;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.client.authInterface.AuthFrame;
import org.ulteo.ovd.client.authInterface.LoginListener;
import org.ulteo.ovd.client.authInterface.OptionPanel;
import org.ulteo.ovd.client.desktop.DesktopFrame;
import org.ulteo.ovd.client.portal.Menu;
import org.ulteo.ovd.client.portal.PortalFrame;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.OvdAppListener;
import org.ulteo.rdp.RdpConnectionOvd;


public class Client extends Thread implements OvdAppListener, RdpListener {

	private SessionManagerCommunication smComm = null;
	private ArrayList<RdpConnectionOvd> connections = null;
	private String fqdn = null;
	private String login = null;
	private String password = null;
	private AuthFrame frame = null;
	private int mode = 0;
	private DesktopFrame desktop = null;
	private PortalFrame portal = null;
	private boolean desktopLaunched = false;
	private int resolution = 0;
	private int language = 0;
	private int keymap = 0;
	private boolean graphic = true;
	private ArrayList<Application> startedAppList = new ArrayList<Application>();
	private boolean isCancelled = false;
	private LoginListener logList = null;

	public static final String productName = "OVD Client";

	public Client(String fqdn_, String login_, String password_, int mode , AuthFrame frame, int resolution, LoginListener logList) {
		this.fqdn = fqdn_;
		this.login = login_;
		this.password = password_;
		this.frame = frame;
		this.mode = mode;
		this.resolution = resolution;
		this.logList = logList;
	}

	public Client(String fqdn_, String login_, String password_, int mode, int resolution) {
		this.fqdn = fqdn_;
		this.login = login_;
		this.password = password_;
		this.mode = mode;
		this.resolution = resolution;
		this.graphic = false;
	}

	public void run() {
		/*BasicConfigurator.configure();
			(Logger.getLogger("net.propero.rdp")).setLevel(org.apache.log4j.Level.INFO);*/
		if(graphic) 
			logList.initLoader();
		this.smComm = new SessionManagerCommunication(fqdn);
		String session_mode = (mode == 0) ? SessionManagerCommunication.SESSION_MODE_DESKTOP : SessionManagerCommunication.SESSION_MODE_REMOTEAPPS;  
		if (! this.smComm.askForSession(login, password, session_mode))
			return;

		if (this.isCancelled) {
			this.smComm = null;
			return;
		}

		this.connections = smComm.getConnections();
		
		if( mode == 0) {
			for (RdpConnectionOvd co : this.connections) {
				co.addRdpListener(this);
				if(!desktopLaunched)
					initDesktop(co);
				co.connect();
				desktopLaunched = true;
			}
		}
		else {
			for (RdpConnectionOvd co : this.connections) {
				co.addRdpListener(this);
				try {
					co.addOvdAppListener(this);
					co.connect();
				} catch (OvdException e) {
					e.printStackTrace();
				}
			}
		}
	}

	public void disconnectAll() {
		this.isCancelled = true;
		if(this.connections != null) {
			for(RdpConnectionOvd co : connections) {
				co.disconnect();
			}
		}
	}

	public void initDesktop(RdpConnectionOvd co) {
		switch (resolution) {
		case 0 :
			desktop = new DesktopFrame(OptionPanel.SMALL_RES, false);
			break;
		case 1 : 
			desktop = new DesktopFrame(OptionPanel.MEDUIM_RES, false);
			break;
		case 2 :
			desktop = new DesktopFrame(OptionPanel.HIGH_RES, false);
			break;
		case 3 :
			desktop = new DesktopFrame(OptionPanel.MAXIMISED, false);
			break;
		case 4 :
			desktop = new DesktopFrame(OptionPanel.FULLSCREEN, true);
			break;
		}

		if(resolution != 4) {
			Insets inset = null;
			inset = desktop.getInsets();
			desktop.setLocationRelativeTo(null);
			co.setGraphic((desktop.getWidth()-(inset.left+inset.right)+2), (desktop.getHeight()-(inset.bottom+inset.top)+2));
		}
		desktopLaunched = true;
	}

	public void showDesktop(RdpConnection co) {
		desktop.getContentPane().add(co.getCanvas());
		co.getCanvas().validate();
		desktop.pack();
	}

	public void initPortal(ArrayList<Application> apps, SessionManagerCommunication sm) {
		portal = new PortalFrame(apps, sm);
	}

	@Override
	public void ovdInited(OvdAppChannel o) {
		for (RdpConnectionOvd co : this.connections) {
			if (co.getOvdAppChannel() == o) {
				if (! co.isOvdAppChannelInitialized()) {
					if( mode == 1) {
						Menu menu = portal.getMain().getCenter().getMenu();
						for (Application app : co.getAppsList()) {
							menu.install(app, co.getOvdAppChannel());
						}
						menu.addScroller();
					}
					co.setOvdAppChannelInitialized(true);
				}
			}
			if( mode == 1 ) 
				portal.getMain().getSouth().initButtonPan(co.getOvdAppChannel());
		}

	}

	@Override
	public void ovdInstanceError(int instance) {

	}

	@Override
	public void ovdInstanceStarted(int instance) {

	}

	@Override
	public void ovdInstanceStopped(int instance) {
		for (Application app : startedAppList) {
			if(instance == app.getInstanceNum()) {
				startedAppList.remove(app);
				portal.getMain().getCenter().getCurrent().update(startedAppList);
				portal.getMain().getCenter().getCurrent().revalidate();
				break;
			}
		}
	}

	public SessionManagerCommunication getSmComm() {
		return smComm;
	}

	public void setSmComm(SessionManagerCommunication smComm) {
		this.smComm = smComm;
	}

	@Override
	public void connected(RdpConnection co) {
		if(graphic) {
			logList.getLoader().setVisible(false);
			logList.getLoader().dispose();
			frame.setVisible(false);
			frame.dispose();
		}
		if (mode == 0)
			showDesktop(co);
		else if (mode == 1) {
			initPortal(startedAppList, smComm);
		}
	}

	@Override
	public void connecting(RdpConnection co) {
		Logger.getLogger(Client.class.getName()).log(Level.INFO, "Connecting to "+co.getServer());
	}

	@Override
	public void disconnected(RdpConnection co) {
		if (graphic) {
			if (logList.getLoader().isVisible()) {
				logList.getLoader().setVisible(false);
				logList.getLoader().dispose();
			}
		}

		Logger.getLogger(Client.class.getName()).log(Level.INFO, "Disconnected from "+co.getServer());
		if(graphic) {
			Container cp = frame.getContentPane();
			cp.removeAll();
		}
		if(mode == 0) {
			desktop.setVisible(false);
			desktop.dispose();
		}
		else {
			if( mode == 1 ) {
				portal.setVisible(false);
				portal.dispose();
				for(RdpConnectionOvd con : this.connections) {
					try {
						con.removeOvdAppListener(this);
					} catch (OvdException e) {
						e.printStackTrace();
					}
				}
			}
			else {
				
			}
		}
		if(graphic) {
			frame.init();
			frame.setDesktopLaunched(false);
		} else {
			System.exit(0);
		}
	}

	@Override
	public void failed(RdpConnection co) {
		Logger.getLogger(Client.class.getName()).log(Level.WARNING, "Connection to "+co.getServer()+" failed");
	}
}