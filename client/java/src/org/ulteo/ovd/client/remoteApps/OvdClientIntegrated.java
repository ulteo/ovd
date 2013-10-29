/*
 * Copyright (C) 2010-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Vincent ROULLIER <v.roullier@ulteo.com> 2013
 * Author Thomas MOUTON <thomas@ulteo.com> 2010, 2012-2013
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
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

import java.util.List;

import org.ulteo.Logger;
import org.ulteo.ovd.client.Newser;
import org.ulteo.ovd.client.OvdClientPerformer;
import org.ulteo.ovd.client.OvdClientRemoteApps;
import org.ulteo.ovd.sm.News;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.SessionManagerException;
import org.ulteo.rdp.RdpConnectionOvd;
import org.ulteo.ovd.client.desktop.SessionStatus;


public class OvdClientIntegrated extends OvdClientRemoteApps implements OvdClientPerformer {
	
	public OvdClientIntegrated(SessionManagerCommunication smComm, boolean persistent) {
		super(smComm, persistent);
		this.enableWaitRecoveryMode(true);
		
		this.showDesktopIcons = this.smComm.getResponseProperties().isDesktopIcons();
	}

	@Override
	protected void hide(RdpConnectionOvd rc) {
		this.unpublish(rc);
	}
	
	
	// interface OvdClientPerformer's methods 

	@Override
	public void createRDPConnections() {
		List<ServerAccess> servers = this.smComm.getServers();
		List<ServerAccess> rdp_servers = new ArrayList<ServerAccess>();

		for (ServerAccess server : servers) {
			if(server.isRDP()) {
				rdp_servers.add(server);
			}
		}

		this.configureRDP(this.smComm.getResponseProperties());
		_createRDPConnections(rdp_servers);
	}
	
	@Override
	public boolean checkRDPConnections() {
		return _checkRDPConnections();
	}

	@Override
	public void createWebAppsConnections() {
		List<ServerAccess> servers = this.smComm.getServers();
		List<ServerAccess> webapps_servers = new ArrayList<ServerAccess>();

		for (ServerAccess server : servers) {
			if(! server.isRDP()) {
				webapps_servers.add(server);
			}
		}

		_createWebAppsConnections(webapps_servers);
	}
	
	@Override
	public boolean checkWebAppsConnections() {
		return _checkWebAppsConnections();
	}
	
	@Override
	public void runSessionReady() {}

	@Override
	public void perform() {
		System.out.println("OvdClientIntegrated : perform");
		if (!(this instanceof OvdClientPerformer))
			throw new ClassCastException("OvdClient must inherit from an OvdClientPerformer to use 'perform' action");

		if (this.smComm == null)
			throw new NullPointerException("Client cannot be performed with a non existent SM communication");
		
		this.createRDPConnections();
		this.createWebAppsConnections();
		
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

			if (! ((OvdClientPerformer)this).checkRDPConnections() && ! ((OvdClientPerformer)this).checkWebAppsConnections()) {
				this.disconnection();
				break;
			}

			while (! this.availableConnections.isEmpty()) {
				try {
					Thread.sleep(1000);
				} catch (InterruptedException ex) {}

				if (! ((OvdClientPerformer)this).checkRDPConnections() && ! ((OvdClientPerformer)this).checkWebAppsConnections()) {
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
		this.sessionStatusSleepingTime = REQUEST_TIME_FREQUENTLY;
		boolean isActive = false;
		
		while (this.continueSessionStatusMonitoringThread) {
			String oldSessionStatus = this.sessionStatus;
			this.sessionStatus = SessionStatus.getSessionStatus();
			String smStatus = this.smComm.askForSessionStatus();
			
			if (! this.sessionStatus.equalsIgnoreCase(oldSessionStatus)) {
				Logger.info("session status switch from " + oldSessionStatus + " to " + this.sessionStatus);
				if ( oldSessionStatus.equalsIgnoreCase(SessionStatus.SESSION_STATUS_INACTIVE) &&
						this.sessionStatus.equalsIgnoreCase(SessionStatus.SESSION_STATUS_ACTIVE )) {
					// Session is resumed
					this.resumeSession();
					
					this.sessionStatusSleepingTime = REQUEST_TIME_FREQUENTLY;
					continue;
				}
				else if (oldSessionStatus.equalsIgnoreCase(SessionStatus.SESSION_STATUS_ACTIVE) &&
						this.sessionStatus.equalsIgnoreCase(SessionStatus.SESSION_STATUS_INACTIVE)) {
					// Session is suspended
					this.suspendSession();
					this.sessionStatusSleepingTime = REQUEST_TIME_FREQUENTLY;
					continue;
				}
				else if (oldSessionStatus.equalsIgnoreCase(SessionStatus.SESSION_STATUS_UNKNOWN) &&
						this.sessionStatus.equalsIgnoreCase(SessionStatus.SESSION_STATUS_ACTIVE)) {
					Logger.info("Session is ready");
					this.sessionStatusSleepingTime = REQUEST_TIME_FREQUENTLY;
					this.connect();
					Logger.info("Session is ready");
					((OvdClientPerformer)this).runSessionReady();
					continue;
				
				} else if (oldSessionStatus.equalsIgnoreCase(SessionStatus.SESSION_STATUS_ACTIVE ) &&
						this.sessionStatus.equalsIgnoreCase(SessionStatus.SESSION_STATUS_UNKNOWN)) {
					Logger.info("Session is terminated");
					this.sessionTerminated();
					continue;
				}
			}
			
			if (smStatus.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_WAIT_DESTROY) ||
					smStatus.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_DESTROYED) ||
					smStatus.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_UNKNOWN)) {
				Logger.info("Session is killed by admin");
				this.sessionTerminated();
				continue;
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
