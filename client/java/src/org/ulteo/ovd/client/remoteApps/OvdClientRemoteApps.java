/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

package org.ulteo.ovd.client.remoteApps;

import java.awt.Rectangle;

import net.propero.rdp.RdesktopException;

import org.ulteo.Logger;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.client.OvdClient;
import org.ulteo.ovd.client.authInterface.LoadingStatus;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.sm.Callback;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.SessionManagerException;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.OvdAppListener;
import org.ulteo.rdp.RdpConnectionOvd;
import java.net.UnknownHostException;
import java.util.List;
import org.ulteo.utils.jni.WorkArea;
import org.ulteo.ovd.integrated.Spool;
import org.ulteo.ovd.integrated.SystemAbstract;

public abstract class OvdClientRemoteApps extends OvdClient implements OvdAppListener {

	protected Spool spool = null;
	protected SystemAbstract system = null;

	private int numberOfApplication = 0;
	private int ApplicationIncrement = 0;
	private int ApplicationIndex = 0;
	
	public OvdClientRemoteApps(SessionManagerCommunication smComm) {
		super(smComm, null);
	}

	public OvdClientRemoteApps(SessionManagerCommunication smComm, Callback obj) {
		super(smComm, obj);
	}

	@Override
	public String getInstance() {
		if (this.spool == null)
			return null;

		return this.spool.getInstanceName();
	}

	@Override
	protected void customizeConnection(RdpConnectionOvd co) {
		try {
			co.addOvdAppListener(this);
		} catch (OvdException ex) {
			this.logger.error(ex);
		}
		
		int applicationIncrement = 100 / co.getAppsList().size();
		for (Application app : co.getAppsList()) {
			if (this.system.create(app) == null)
				org.ulteo.Logger.error("The "+app.getName()+" shortcut could not be created");

			int subStatus = this.ApplicationIndex * this.ApplicationIncrement;
			this.obj.updateProgress(LoadingStatus.STATUS_CLIENT_INSTALL_APPLICATION, subStatus);
			this.ApplicationIndex++;
		}

		this.customizeRemoteAppsConnection(co);
	}

	protected abstract void customizeRemoteAppsConnection(RdpConnectionOvd co);

	@Override
	protected void uncustomizeConnection(RdpConnectionOvd co) {
		try {
			co.removeOvdAppListener(this);
		} catch (OvdException ex) {
			this.logger.error(ex);
		}
		this.uncustomizeRemoteAppsConnection(co);
	}

	protected abstract void uncustomizeRemoteAppsConnection(RdpConnectionOvd co);
	
	@Override
	protected void runDisconnecting() {}

	@Override
	protected void runSessionTerminated() {
		this.spool.stop();
		this.spool.deleteTree();
		this.spool = null;

		for (RdpConnectionOvd co : this.connections) {
			for (Application app : co.getAppsList())
				this.system.clean(app);
		}
	}

	public abstract void ovdInited(OvdAppChannel o);

	public void ovdInstanceStarted(int instance_) {}
	public void ovdInstanceStopped(int instance_) {}
	public void ovdInstanceError(int instance_) {}
	

	@Override
	protected boolean createRDPConnections() {
		Properties properties = this.smComm.getResponseProperties();
		Rectangle screenSize = WorkArea.getWorkAreaSize();
		
		byte flags = 0x00;
		flags |= RdpConnectionOvd.MODE_APPLICATION;
		
		if (properties.isMultimedia())
			flags |= RdpConnectionOvd.MODE_MULTIMEDIA;
		
		if (properties.isPrinters())
			flags |= RdpConnectionOvd.MOUNT_PRINTERS;
		
		List<ServerAccess> serversList = this.smComm.getServers();
		this.numberOfApplication = 0;

		for (ServerAccess server : serversList)
			this.numberOfApplication += server.getApplications().size();

		this.ApplicationIncrement = 100 / numberOfApplication;
		this.ApplicationIndex = 0;

		for (ServerAccess server : serversList) {
			if (this.isCancelled)
				return false;

			RdpConnectionOvd rc = null;
			
			try {
				rc = new RdpConnectionOvd(flags);
			} catch (RdesktopException ex) {
				Logger.error("Unable to create RdpConnectionOvd object: "+ex.getMessage());
				return false;
			}
			
			try {
				rc.initSecondaryChannels();
			} catch (RdesktopException ex) {
				Logger.error("Unable to init channels of RdpConnectionOvd object: "+ex.getMessage());
			}

			if (server.getModeGateway()) {

				if (server.getToken().equals("")) {
						Logger.error("Server need a token to be identified on gateway, so token is empty !");
						return false;
				} else {
					rc.setCookieElement("token", server.getToken());
				}

				try {
					rc.useSSLWrapper(server.getHost(), server.getPort());
				} catch(OvdException ex) {
					Logger.error("Unable to create RdpConnectionOvd SSLWrapper: " + ex.getMessage());
					return false;
				} catch(UnknownHostException ex) {
					Logger.error("Undefine error during creation of RdpConnectionOvd SSLWrapper: " + ex.getMessage());
					return false;
				}
			}
			
			rc.setServer(server.getHost());
			rc.setCredentials(server.getLogin(), server.getPassword());
			// Ensure that width is multiple of 4
			// Prevent artifact on screen with a with resolution
			// not divisible by 4
			rc.setGraphic((int) screenSize.width & ~3, (int) screenSize.height, RdpConnectionOvd.DEFAULT_BPP);
			rc.setGraphicOffset(screenSize.x, screenSize.y);
			
			if (this.keymap != null)
				rc.setKeymap(this.keymap);
			
			for (org.ulteo.ovd.sm.Application appItem : server.getApplications()) {
				if (this.isCancelled)
					return false;
				
				try {
					int subStatus = this.ApplicationIndex * this.ApplicationIncrement;
					this.obj.updateProgress(LoadingStatus.STATUS_SM_GET_APPLICATION, subStatus);
					
					Application app = new Application(rc, appItem.getId(), appItem.getName(), appItem.getMimes(), this.smComm.askForIcon(Integer.toString(appItem.getId())));
					rc.addApp(app);
				} catch (SessionManagerException ex) {
					ex.printStackTrace();
					Logger.warn("Cannot get the \""+appItem.getName()+"\" icon: "+ex.getMessage());
				}
				this.ApplicationIndex++;
			}
			
			this.connections.add(rc);
		}
		this.obj.updateProgress(LoadingStatus.STATUS_SM_GET_APPLICATION, 100);
		this.ApplicationIndex = 0;
		
		return true;
	}

	@Override
	public boolean checkRDPConnections() {
		String session_status = null;
		try {
			session_status = this.smComm.askForSessionStatus();
		} catch (SessionManagerException ex) {
			Logger.error("checkRDPConnections -- Failed to get session status from session manager: "+ex.getMessage()+". Will exit.");
			for (RdpConnectionOvd co : this.performedConnections) {
				this.hide(co);
			}
			return false;
		}
		if (session_status == null) {
			Logger.error("checkRDPConnections -- Failed to get session status from session manager: Internal error. Will exit.");
			for (RdpConnectionOvd co : this.performedConnections) {
				this.hide(co);
			}
			return false;
		}

		if (!(session_status.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_INITED) || session_status.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_ACTIVE))) {
			Logger.info("checkRDPConnections -- Your session has ended. Will exit.");
			for (RdpConnectionOvd co : this.performedConnections) {
				this.hide(co);
			}
			return false;
		}

		boolean retry = false;

		int nbApps = 0;
		int nbAppsAvailable = 0;

		for (RdpConnectionOvd co : this.performedConnections) {
			int nbAppsByServer = co.getAppsList().size();
			nbApps += nbAppsByServer;

			int state = co.getState();

			if (state == RdpConnectionOvd.STATE_CONNECTED) {
				nbAppsAvailable += nbAppsByServer;
				continue;
			}

			if (state != RdpConnectionOvd.STATE_FAILED) {
				Logger.debug("checkRDPConnections "+co.getServer()+" -- Bad connection state("+state+"). Will continue normal process.");
				continue;
			}

			int tryNumber = co.getTryNumber();
			if (tryNumber < 1) {
				Logger.debug("checkRDPConnections "+co.getServer()+" -- Bad try number("+tryNumber+"). Will continue normal process.");
				continue;
			}

			if (tryNumber > 1) {
				Logger.error("checkRDPConnections "+co.getServer()+" -- Several try to connect failed.");
				this.hide(co);
				continue;
			}

			Logger.warn("checkRDPConnections "+co.getServer()+" -- Connection failed. Will try to reconnect.");
			this.performedConnections.remove(co);
			co.connect();
			retry = true;
		}

		if (retry)
			return true;

		float percent = ((float) (100 * nbAppsAvailable)) / nbApps;
		if (percent < 50) {
			Logger.error("Less than 50 percent of applications are available("+percent+"%). Will exit.");
			return false;
		}
		Logger.warn("More than 50 percent of applications are available("+percent+"%). Will continue.");

		return true;
	}
}
