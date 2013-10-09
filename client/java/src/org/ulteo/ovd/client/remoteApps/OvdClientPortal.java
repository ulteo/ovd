/*
 * Copyright (C) 2010-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Vincent ROULLIER <v.roullier@ulteo.com> 2013
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2013
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
 * Author David LECHEVALIER <david@ulteo.com> 2011
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
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

import java.awt.Image;
import java.awt.event.ActionListener;
import java.awt.event.ComponentEvent;
import java.awt.event.ComponentListener;
import java.util.ArrayList;
import java.util.Collections;
import java.util.List;

import javax.swing.ImageIcon;

import net.propero.rdp.RdpConnection;

import org.ulteo.Logger;
import org.ulteo.gui.GUIActions;
import org.ulteo.gui.SwingTools;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.client.NativeClientActions;
import org.ulteo.ovd.client.Newser;
import org.ulteo.ovd.client.OvdClientPerformer;
import org.ulteo.ovd.client.OvdClientRemoteApps;
import org.ulteo.ovd.client.authInterface.LoadingFrame;
import org.ulteo.ovd.client.authInterface.LoadingStatus;
import org.ulteo.ovd.client.portal.PortalFrame;
import org.ulteo.ovd.sm.News;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.SessionManagerException;
import org.ulteo.ovd.sm.WebAppsServerAccess;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.RdpConnectionOvd;

public class OvdClientPortal extends OvdClientRemoteApps implements ComponentListener, Newser, NativeClientActions {
	
	private PortalFrame portal = null;
	private IntegratedTrayIcon systray = null;
	private String username = null;
	private List<Application> appsList = null;
	private List<Application> appsListToEnable = null;
	private boolean autoPublish = false;
	private boolean hiddenAtStart = false;
	private boolean showBugReporter = false;
	private float ApplicationIncrement = 0;
	private LoadingFrame loadingFrame;
	private boolean is_user_disconnection;
	private Thread session_thread = null;
	
	public OvdClientPortal(SessionManagerCommunication smComm, LoadingFrame loadingFrame, String login_, boolean autoPublish, boolean showDesktopIcons_, boolean hiddenAtStart_, boolean showBugReporter_, boolean persistent) {
		super(smComm, persistent);
		this.loadingFrame = loadingFrame;
		
		this.username = login_;
		this.autoPublish = this.publicated = autoPublish;
		this.showDesktopIcons = showDesktopIcons_;
		this.hiddenAtStart = hiddenAtStart_;
		this.showBugReporter = showBugReporter_;
		
		this.appsList = new ArrayList<Application>();
		
		Image logo = new ImageIcon(getClass().getClassLoader().getResource("pics/ulteo.png"), "Ulteo OVD").getImage();
		
		this.portal = new PortalFrame(this, this.username, logo, this.showBugReporter);
		this.portal.addComponentListener(this);
		
		this.systray = new IntegratedTrayIcon(portal, logo, this);
	}

	public boolean isAutoPublish() {
		return this.autoPublish;
	}
	
	@Override
	protected ImageIcon getAppIcon(Application app) {
		this.loadingFrame.updateProgression(LoadingStatus.SM_GET_APPLICATION, (int)(this.ApplicationIndex * this.ApplicationIncrement));
		return super.getAppIcon(app);
	}
	
	@Override
	public void connect() {
		this.loadingFrame.setVisible(false);
		super.connect();
	}

	@Override
	public void performDisconnectAll() {
		for (ActionListener each : this.systray.getActionListeners())
			this.systray.removeActionListener(each);
		
		super.performDisconnectAll();
	}

	@Override
	protected void runSessionTerminated() {
		super.runSessionTerminated();

		this.systray.remove();
		
		this.portal.setVisible(false);
		this.portal.dispose();
	}

	@Override
	protected void customizeConnection(RdpConnectionOvd co) {
		super.customizeConnection(co);
		
		try {
			co.addOvdAppListener(this.portal.getRunningApplicationPanel());
		} catch (OvdException ex) {
			Logger.error(co.getServer()+": Failed to add ovd applications listener: "+ex);
		}
		this.loadingFrame.updateProgression(LoadingStatus.CLIENT_WAITING_SERVER, 0);

		for (Application app : co.getOvdAppChannel().getApplicationsList()) {
			this.appsList.add(app);
		}
		// Add shortcuts for web apps.
		for (WebAppsServerAccess server : this.webAppsServers) {
			for (Application app : server.getWebApplications()) {
				this.appsList.add(app);
			}
		}
	}

	@Override
	public void disconnected(RdpConnection co) {
		super.disconnected(co);
		
		try {
			((RdpConnectionOvd) co).removeOvdAppListener(this.portal.getRunningApplicationPanel());
		} catch (OvdException e) {
			e.printStackTrace();
		}
	}

	@Override
	public void ovdInited(OvdAppChannel channel) {
		if (this.autoPublish)
			super.ovdInited(channel);
		
		RdpConnectionOvd rc = find(channel);
		if (rc != null) {
			for (Application app : rc.getOvdAppChannel().getApplicationsList()) {
				if (! this.portal.isVisible()) {
					if (this.appsListToEnable == null)
						this.appsListToEnable = new ArrayList<Application>();
					this.appsListToEnable.add(app);
				}
				else
					this.portal.getApplicationPanel().toggleAppButton(app, true);
			}

			if (this.desktopIntegrator == null)
				this.portal.initLocalDesktopIntegrationButton(false);
			else if (this.desktopIntegrator.isDesktopIntegrationDone(rc))
				this.portal.initLocalDesktopIntegrationButton(true);
		}

		this.loadingFrame.setVisible(false);
	}

	@Override
	protected void hide(RdpConnectionOvd rc) {
		final OvdAppChannel ovdAppChannel = rc.getOvdAppChannel();
		for (Application app : ovdAppChannel.getApplicationsList()) {
			this.portal.getApplicationPanel().toggleAppButton(app, false);
		}
		
		this.unpublish(rc);
	}
	
	@Override
	public void shortcutGenerationIsDone(RdpConnectionOvd co) {
		if (this.autoPublish)
			super.shortcutGenerationIsDone(co);
	
		this.portal.initLocalDesktopIntegrationButton(true);
	}
	
	
	// interface ComponentListener's methods 

	@Override
	public void componentShown(ComponentEvent ce) {
		if (ce.getComponent() == this.portal) {
			Collections.sort(this.appsList);
			this.portal.getApplicationPanel().initButtons(this.appsList);
		}

		if (this.appsListToEnable != null) {
			for (Application app : this.appsListToEnable)
				this.portal.getApplicationPanel().toggleAppButton(app, true);
		}
	}

	@Override
	public void componentResized(ComponentEvent ce) {}
	
	@Override
	public void componentMoved(ComponentEvent ce) {}
	
	@Override
	public void componentHidden(ComponentEvent ce) {}
	
	
	// interface Newser's methods 
	
	@Override
	public void updateNews(List<News> newsList) {
		if (newsList.size() == 0) {
			if (this.portal.containsNewsPanel()) {
				this.portal.removeNewsPanel();
			}
		}
		else {
			if (! this.portal.containsNewsPanel())
				this.portal.addNewsPanel();
			
			this.portal.getNewsPanel().updateNewsLinks(newsList);
		}
	}

	
	// interface OvdClientPerformer's methods 

	@Override
	public void createRDPConnections() {
		List<ServerAccess> servers = this.smComm.getServers();
		
		int nbApplications = 0;
		for (ServerAccess server : servers)
			nbApplications += server.applications.size();
		this.ApplicationIncrement = (float) (100.0 / nbApplications);

		this.configureRDP(this.smComm.getResponseProperties());
		_createRDPConnections(servers);
	}
	
	@Override
	public boolean checkRDPConnections() {
		return _checkRDPConnections();
	}

	@Override
	public void runSessionReady() {
		this.portal.initButtonPan();
		this.systray.add();

		if (this.portal.getApplicationPanel().isScollerInited())
			this.portal.getApplicationPanel().addScroller();
		
		this.portal.setVisible(! this.hiddenAtStart);
	}
	
	@Override
	public void disconnection() {
		if (! this.connectionIsActive)
			return;
		
		super.disconnection();
		this.performDisconnectAll();
		SwingTools.invokeLater(GUIActions.setVisible(this.loadingFrame, false));
		this.portal.disconnecting();
	}
	
	@Override
	public DisconnectionMode getDisconnectionMode() {
		return this.portal.getDisconnectionMode();
	}
	
	// interface NativeClientActions' methods 
	
	@Override
	public boolean haveToQuit() {
		return this.portal.haveToQuit();
	}

	@Override
	public void disconnect() {
		this.is_user_disconnection = true;
		this.disconnection();
	}

	@Override
	public boolean isUserDisconnection() {
		return this.is_user_disconnection;
	}
	
	@Override
	public boolean isPersistentSessionEnabled() {
		return this.persistent;
	}

	@Override
	public void perform() {
		if (!(this instanceof OvdClientPerformer))
			throw new ClassCastException("OvdClient must inherit from an OvdClientPerformer to use 'perform' action");

		if (this.smComm == null)
			throw new NullPointerException("Client cannot be performed with a non existent SM communication");
		
		this.createRDPConnections();
		
		this.sessionStatusMonitoringThread = new Thread(this);
		this.continueSessionStatusMonitoringThread = true;
		this.sessionStatusMonitoringThread.start();

		for (RdpConnectionOvd rc : this.connections) {
			this.customizeConnection(rc);
			rc.addRdpListener(this);
		}
		
		do
		{
			// Waiting for the session is resumed
			while (this.getWaitSession()) {
				try {
					Thread.sleep(1000);
				} catch (InterruptedException ex) {}
			}
			
			// Waiting for all the RDP connections are performed
			while (this.performedConnections.size() < this.connections.size()) {
				if (! this.connectionIsActive)
					break;
				
				try {
					Thread.sleep(1000);
				} catch (InterruptedException ex) {}
			}

			if (! ((OvdClientPerformer)this).checkRDPConnections()) {
				this.disconnection();
				break;
			}

			while (! this.availableConnections.isEmpty()) {
				try {
					Thread.sleep(1000);
				} catch (InterruptedException ex) {}

				if (! ((OvdClientPerformer)this).checkRDPConnections()) {
					this.disconnection();
					break;
				}
			}
			
			try {
				Thread.sleep(1000);
			} catch (InterruptedException ex) {}
			
		} while (this.connectionIsActive);
	}

	@Override
	public void run() {
		// session status monitoring
		this.sessionStatusSleepingTime = REQUEST_TIME_FREQUENTLY;
		boolean isActive = false;
		
		while (this.continueSessionStatusMonitoringThread) {
			String oldSessionStatus = this.sessionStatus;
			this.sessionStatus = this.smComm.askForSessionStatus();
			System.out.println("Session Status : " + this.sessionStatus);
			
			if (! this.sessionStatus.equals(oldSessionStatus)) {
				Logger.info("session status switch from " + oldSessionStatus + " to " + this.sessionStatus);
				
				if (this.isWaitRecoveryModeEnabled) {
					if (this.sessionStatus.equals(SessionManagerCommunication.SESSION_STATUS_INITED) || 
						this.sessionStatus.equals(SessionManagerCommunication.SESSION_STATUS_ACTIVE)) {
						// Session is resumed
						this.resumeSession();

						this.sessionStatusSleepingTime = REQUEST_TIME_OCCASIONALLY;
						continue;
					}
					else if (this.sessionStatus.equals(SessionManagerCommunication.SESSION_STATUS_INACTIVE)) {
						// Session is suspended
						this.suspendSession();

						this.sessionStatusSleepingTime = REQUEST_TIME_FREQUENTLY;
						continue;
					}
				}
				
				if (this.sessionStatus.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_INITED) || 
						this.sessionStatus.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_ACTIVE) ||
						(this.sessionStatus.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_INACTIVE) && this.persistent)) {
					if (! isActive) {
						isActive = true;
						this.sessionStatusSleepingTime = REQUEST_TIME_OCCASIONALLY;
						this.connect();
						Logger.info("Session is ready");
						((OvdClientPerformer)this).runSessionReady();
					}
				}
				else {
					if (isActive) {
						isActive = false;
						this.sessionTerminated();
					}
					else if (this.sessionStatus.equals(SessionManagerCommunication.SESSION_STATUS_UNKNOWN)) {
						this.sessionTerminated();
					}
				}
			}
			
			if (this instanceof Newser) {
				try {
					List<News> newsList = this.smComm.askForNews();
					((Newser)this).updateNews(newsList);
				} catch (SessionManagerException e) {
					Logger.warn("news cannot be received: " + e.getMessage());
				}
			}
			try {
					Thread.sleep(this.sessionStatusSleepingTime);
			}
			catch (InterruptedException ex) {
			}
		}
	}

}
