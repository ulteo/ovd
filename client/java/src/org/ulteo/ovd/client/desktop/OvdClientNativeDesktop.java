/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Vincent ROULLIER <v.roullier@ulteo.com> 2013
 * Author David LECHEVALIER <david@ulteo.com> 2011, 2012
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2012
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
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

package org.ulteo.ovd.client.desktop;

import java.awt.Dimension;
import java.util.List;
import net.propero.rdp.RdesktopCanvas;
import net.propero.rdp.RdpConnection;

import org.ulteo.Logger;
import org.ulteo.gui.GUIActions;
import org.ulteo.gui.SwingTools;
import org.ulteo.ovd.client.NativeClientActions;
import org.ulteo.ovd.client.OvdClientDesktop;
import org.ulteo.ovd.client.OvdClientPerformer;
import org.ulteo.ovd.client.authInterface.LoadingFrame;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.rdp.RdpConnectionOvd;
import org.ulteo.ovd.client.Newser;
import org.ulteo.ovd.sm.News;
import org.ulteo.ovd.sm.SessionManagerException;

public class OvdClientNativeDesktop extends OvdClientDesktop implements NativeClientActions {
	
	private DesktopFrame desktop = null;
	private LoadingFrame loadingFrame;
	private boolean is_user_disconnection;
	
	public OvdClientNativeDesktop(SessionManagerCommunication smComm, LoadingFrame loadingFrame, Dimension resolution, boolean persistent) {
		super(smComm, persistent);
		this.loadingFrame = loadingFrame;
		
		this.desktop = new DesktopFrame(resolution, this);
	}

	@Override
	public void connect() {
		this.loadingFrame.setVisible(false);
		super.connect();
	}
	
	@Override
	protected void customizeConnection(RdpConnectionOvd co) {
		if (! this.desktop.isFullscreen()) {
			Dimension dim = this.desktop.getInternalSize();
			co.setGraphic(dim.width, dim.height, co.getBpp());
		}
		co.setShell("OvdDesktop");
	}

	@Override
	public void display(RdesktopCanvas canvas) {
		this.desktop.setCanvas(canvas);
		this.desktop.getContentPane().add(canvas);
		canvas.validate();
		this.desktop.pack();
	}

	@Override
	public void hide(RdpConnectionOvd co) {
		this.desktop.destroy();
	}

	@Override
	protected Properties getProperties() {
		return this.smComm.getResponseProperties();
	}

	
	// interface OvdClientPerformer's methods 
	
	@Override
	public void createRDPConnections() {
		ServerAccess server = this.smComm.getServers().get(0);
		this.createRDPConnection(server);
		this.adjustDesktopSize();
	}

	@Override
	public boolean checkRDPConnections() {
		if (this.performedConnections.isEmpty()) {
			Logger.debug("checkRDPConnections -- No connections. Will abort normal process.");
			return false;
		}

		RdpConnectionOvd co = this.performedConnections.get(0);
		RdpConnection.State state = co.getState();

		if (state == RdpConnection.State.CONNECTED || state == RdpConnection.State.CONNECTING)
			return true;

		if (state != RdpConnection.State.FAILED) {
			Logger.debug("checkRDPConnections -- Bad connection state("+state+"). Will continue normal process.");
			return true;
		}

		int tryNumber = co.getTryNumber();
		if (tryNumber < 1) {
			Logger.debug("checkRDPConnections -- Bad try number("+tryNumber+"). Will continue normal process.");
			return true;
		}

		if (tryNumber > 1) {
			Logger.debug("checkRDPConnections -- Several try to connect to "+co.getServer()+" failed. Will exit.");
			this.hide(co);
			return false;
		}

		return false;
	}

	@Override
	public void createWebAppsConnections() {
	}

	@Override
	public boolean checkWebAppsConnections() {
		return true;
	}

	@Override
	public void runSessionReady() {
		this.desktop.setVisible(true);
	}

	@Override
	public void disconnection() {
		if (! this.connectionIsActive)
			return;
		
		super.disconnection();
		this.performDisconnectAll();
		SwingTools.invokeLater(GUIActions.setVisible(this.loadingFrame, false));
		this.desktop.disconnecting();
	}

	@Override
	public DisconnectionMode getDisconnectionMode() {
		return this.desktop.getDisconnectionMode();
	}
	
	// interface NativeClientActions' methods 
	
	@Override
	public boolean haveToQuit() {
		return this.desktop.haveToQuit();
	}

	@Override
	public void disconnect(boolean disconnect) {
		this.is_user_disconnection = true;
		this.persistent = disconnect;
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
		this.createWebAppsConnections();
		
		for (RdpConnectionOvd rc : this.connections) {
			this.customizeConnection(rc);
			rc.addRdpListener(this);
		}
		
		this.sessionStatusMonitoringThread = new Thread(this);
		this.continueSessionStatusMonitoringThread = true;
		this.sessionStatusMonitoringThread.start();
		
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
		// session status monitoring
		this.sessionStatusSleepingTime = REQUEST_TIME_FREQUENTLY;
		boolean isActive = false;
		
		while (this.continueSessionStatusMonitoringThread) {
			String oldSessionStatus = this.sessionStatus;
			this.sessionStatus = this.smComm.askForSessionStatus();
			
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
						this.runSessionReady();
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
