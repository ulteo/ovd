/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 * Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
 * Author Omar AKHAM <oakham@ulteo.com> 2011
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

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Timer;
import java.util.TimerTask;
import java.util.concurrent.CopyOnWriteArrayList;
import net.propero.rdp.RdpConnection;
import net.propero.rdp.RdpListener;
import org.ulteo.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.client.authInterface.LoadingStatus;
import org.ulteo.ovd.sm.Callback;
import org.ulteo.ovd.sm.News;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.SessionManagerException;
import org.ulteo.rdp.RdpActions;
import org.ulteo.rdp.RdpConnectionOvd;

public abstract class OvdClient extends Thread implements Runnable, RdpListener, RdpActions {
	
	public static final String productName = "OVD Client";
	
	private static final long REQUEST_TIME_FREQUENTLY = 2000;
	private static final long REQUEST_TIME_OCCASIONALLY = 60000;

	private static final long DISCONNECTION_MAX_DELAY = 3500;
	
	protected String sessionStatus = SessionManagerCommunication.SESSION_STATUS_INIT;
	

	public static HashMap<String,String> toMap(String login_, String password_) {
		HashMap<String,String> map = new HashMap<String, String>();

		map.put(SessionManagerCommunication.FIELD_LOGIN, login_);
		map.put(SessionManagerCommunication.FIELD_PASSWORD, password_);

		return map;
	}

	protected boolean graphic = false;

	protected Callback obj = null;

	protected SessionManagerCommunication smComm = null;
	protected Thread getStatus = null;
	protected ArrayList<RdpConnectionOvd> connections = null;
	protected ArrayList<RdpConnectionOvd> availableConnections = null;
	protected CopyOnWriteArrayList<RdpConnectionOvd> performedConnections = null;
	protected String keymap = null;
	protected String inputMethod = null;
	
	protected Thread sessionStatusMonitoringThread = null;
	protected boolean continueSessionStatusMonitoringThread = false;
	protected long sessionStatusSleepingTime = REQUEST_TIME_FREQUENTLY;

	protected boolean isCancelled = false;
	private boolean connectionIsActive = true;
	private boolean exitAfterLogout = false;
	protected boolean persistent = false;

	public OvdClient(Callback obj_) {
		this.initMembers(null, true);

		this.initCallback(obj_);
	}

	public OvdClient(SessionManagerCommunication smComm, Callback obj_, boolean persistent) {
		this.initMembers(smComm, true);

		this.persistent = persistent;
		this.initCallback(obj_);
	}

	private void initMembers(SessionManagerCommunication smComm, boolean graphic_) {
		this.smComm = smComm;
		this.graphic = graphic_;

		this.connections = new ArrayList<RdpConnectionOvd>();
		this.availableConnections = new ArrayList<RdpConnectionOvd>();
		this.performedConnections = new CopyOnWriteArrayList<RdpConnectionOvd>();
	}

	private void initCallback(Callback obj_) {
		if (obj_ != null) {
			this.obj = obj_;
			return;
		}

		this.obj = new Callback() {
			@Override
			public void reportBadXml(String data) {
				org.ulteo.Logger.error("Callback::reportBadXml: "+data);
			}

			@Override
			public void reportError(int code, String msg) {
				org.ulteo.Logger.error("Callback::reportError: "+code+" => "+msg);
			}

			@Override
			public void reportErrorStartSession(String code) {
				org.ulteo.Logger.error("Callback::reportErrorStartSession: "+code);
			}

			@Override
			public void reportNotFoundHTTPResponse(String moreInfos) {
				org.ulteo.Logger.error("Callback::reportNotFoundHTTPResponse: "+moreInfos);
			}

			@Override
			public void reportUnauthorizedHTTPResponse(String moreInfos) {
				org.ulteo.Logger.error("Callback::reportUnauthorizedHTTPResponse: "+moreInfos);
			}

			@Override
			public void sessionConnected() {
				org.ulteo.Logger.info("Callback::sessionConnected");
			}

			@Override
			public void sessionDisconnecting() {
				org.ulteo.Logger.info("Callback::sessionDisconnected");
			}

			@Override
			public void updateProgress(LoadingStatus status, int substatus) {
				org.ulteo.Logger.info("Callback::updateProgress "+status+","+substatus);
			}
		};
	}

	private void addAvailableConnection(RdpConnectionOvd rc) {
		this.availableConnections.add(rc);
	}

	private void removeAvailableConnection(RdpConnectionOvd rc) {
		this.availableConnections.remove(rc);
	}

	protected int countAvailableConnection() {
		return this.availableConnections.size();
	}

	public ArrayList<RdpConnectionOvd> getAvailableConnections() {
		return this.availableConnections;
	}

	public ArrayList<RdpConnectionOvd> getConnections() {
		return this.connections;
	}
	
	public String getInstance() {
		return null;
	}

	@Override
	public void run() {
		// session status monitoring
		this.sessionStatusSleepingTime = REQUEST_TIME_FREQUENTLY;
		boolean isActive = false;
		
		while (this.continueSessionStatusMonitoringThread) {
			try {
				String status = this.smComm.askForSessionStatus();

				if (status == null)
					status = SessionManagerCommunication.SESSION_STATUS_UNKNOWN;
				
				if (! status.equals(this.sessionStatus)) {
					org.ulteo.Logger.info("session status switch from "+this.sessionStatus+" to "+status);
					this.sessionStatus = status;
					if (this.sessionStatus.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_INITED) || 
							this.sessionStatus.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_ACTIVE) ||
							(this.sessionStatus.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_INACTIVE) && this.persistent)) {
						if (! isActive) {
							isActive = true;
							this.sessionStatusSleepingTime = REQUEST_TIME_OCCASIONALLY;
							this.sessionReady();
						}
					}
					else {
						if (isActive) {
							isActive = false;
							this.sessionTerminated();
						}
						else if (status.equals(SessionManagerCommunication.SESSION_STATUS_UNKNOWN)) {
							this.sessionTerminated();
						}
					}
				}
				
				List<News> newsList = this.smComm.askForNews();
				this.updateNews(newsList);
			}
			catch (SessionManagerException ex) {
				org.ulteo.Logger.error("Session status monitoring: "+ex.getMessage());
			}
			try {
					Thread.sleep(this.sessionStatusSleepingTime);
			}
			catch (InterruptedException ex) {
			}
		}
	}	
	
	public boolean perform(Options option) {
		if (this.smComm == null)
			throw new NullPointerException("Client cannot be performed with a null SM communication");

		if (this.createRDPConnections()) {
			for (RdpConnectionOvd rc : this.connections) {
				this.configureRDPConnection(rc);
			    this.applyConfig(rc, option);
			}

			this.sessionStatusMonitoringThread = new Thread(this);
			this.continueSessionStatusMonitoringThread = true;
			this.sessionStatusMonitoringThread.start();
		}

		do {
			// Waiting for all the RDP connections are performed
			while (this.performedConnections.size() < this.connections.size()) {
				if (! this.connectionIsActive)
					break;
				
				try {
					Thread.sleep(50);
				} catch (InterruptedException ex) {}
			}

			if (! this.checkRDPConnections()) {
				this.disconnectAll();
				break;
			}
		} while (this.performedConnections.size() < this.connections.size());
		
		while (! this.performedConnections.isEmpty() && this.connectionIsActive) {
			try {
				Thread.sleep(100);
			} catch (InterruptedException ex) {}
		}
		
		try {
			this.smComm.askForLogout(this.persistent);
		} catch (SessionManagerException e) {
			Logger.error("Failed to inform the session manager about the RDP session ending.");
		}

		return this.exitAfterLogout;
	}

	public void configureRDPConnection(RdpConnectionOvd rc) {
		this.customizeConnection(rc);
		rc.addRdpListener(this);
	}

	public abstract boolean checkRDPConnections();

	public void sessionReady() {
		org.ulteo.Logger.info("Session is ready");

		if (this.obj != null)
			this.obj.sessionConnected();

		for (RdpConnectionOvd rc : this.connections) {
			rc.connect();
		}
		
		this.runSessionReady();

		this.runExit();
	}

	protected abstract void runSessionReady();

	public synchronized void sessionTerminated() {
		if (! this.connectionIsActive)
			return;

		org.ulteo.Logger.info("Session is terminated");
		
		this.runSessionTerminated();

		this.connectionIsActive = false;

		if (this.sessionStatusMonitoringThread != null) {
			this.continueSessionStatusMonitoringThread = false;
			this.sessionStatusMonitoringThread = null;
		}

		this.cleanConnections();
	}

	protected abstract void runSessionTerminated();

	protected abstract void runExit();
	
	protected abstract void runDisconnecting();

	private void stopAllRDPConnections() {
		if (this.connections == null)
			return;

		for (RdpConnection rc : this.connections) {
			rc.stop();
		}
	}

	private void waitAllRDPServersLogout() {
		if (this.connections == null)
			return;

		boolean rdpActivity;
		do {
			try {
				Thread.sleep(2000);
			} catch (InterruptedException ex) {}

			rdpActivity = false;
			for (RdpConnection rc : this.connections) {
				if (rc.isConnected()) {
					rdpActivity = true;
				}
			}
		} while (rdpActivity);
	}

	private void cleanConnections() {
		this.stopAllRDPConnections();
		this.waitAllRDPServersLogout();
	}

	protected abstract void customizeConnection(RdpConnectionOvd co);

	protected abstract void uncustomizeConnection(RdpConnectionOvd co);

	protected abstract void display(RdpConnection co);

	protected abstract void hide(RdpConnection co);
	
	protected abstract boolean createRDPConnections();

	/* RdpListener */
	public void connected(RdpConnection co) {
		Logger.info("Connected to "+co);

		this.performedConnections.add((RdpConnectionOvd) co);
		this.addAvailableConnection((RdpConnectionOvd)co);

		this.display(co);
	}

	public void connecting(RdpConnection co) {
		Logger.info("Connecting to "+co);

	}

	public void disconnected(RdpConnection co) {
		co.removeRdpListener(this);

		this.uncustomizeConnection((RdpConnectionOvd) co);

		this.hide(co);
		this.performedConnections.remove((RdpConnectionOvd) co);
		this.removeAvailableConnection((RdpConnectionOvd)co);
		Logger.info("Disconnected from "+co);

		if (this.sessionStatusMonitoringThread != null && this.sessionStatusMonitoringThread.isAlive()) {
			// Break session status monitoring sleep to check with SessionManager ASAP
			this.sessionStatusSleepingTime = REQUEST_TIME_FREQUENTLY;
			this.sessionStatusMonitoringThread.interrupt();
		}
	}

	public void failed(RdpConnection co, String msg) {
		Logger.error("Connection to "+co+" failed: "+msg);

		this.performedConnections.add((RdpConnectionOvd) co);
	}

	/* RdpActions */
	public void disconnect(RdpConnection rc) {
		try {
			((RdpConnectionOvd) rc).sendLogoff();
		} catch (OvdException ex) {
			Logger.warn(rc.getServer()+": "+ex.getMessage());
		}
	}

	public void seamlessEnabled(RdpConnection co) {}

	public void disconnectAll() {
		if (! this.connectionIsActive)
			return;

		this.isCancelled = true;
		this.obj.sessionDisconnecting();
	}

	public void performDisconnectAll() {
		this.runDisconnecting();

		final Timer forceDisconnectionTimer = new Timer();

		final TimerTask forceDisconnectionTask = new TimerTask() {
			@Override
			public void run() {
				sessionTerminated();
			}
		};

		long delay = 0;
		if (this.persistent) {
			forceDisconnectionTimer.schedule(forceDisconnectionTask, 0);
			return;
		}
		if (this.smComm != null) {
			Thread disconnectThread = new Thread(new Runnable() {
				public void run() {
					try {
						smComm.askForLogout(persistent);
					} catch (SessionManagerException ex) {
						org.ulteo.Logger.error("Disconnection error: "+ex.getMessage());
					}

					forceDisconnectionTimer.cancel();
					forceDisconnectionTask.run();
				}
			});
			disconnectThread.start();

			delay = DISCONNECTION_MAX_DELAY;
		}

		forceDisconnectionTimer.schedule(forceDisconnectionTask, delay);
	}
	
	public void exit(int return_code) {
		this.exitAfterLogout = true;

		this.disconnectAll();
	}
	
	public void setKeymap(String keymap) {
		this.keymap = keymap;
	}
	
	public void setInputMethod(String inputMehtod) {
		this.inputMethod  = inputMehtod;
	}
	
	public void updateNews(List<News> newsList) {}

	public void applyConfig(RdpConnectionOvd rc, Options opts) {
		if (opts == null)
			return;
		
		if (opts.usePacketCompression)
			rc.setPacketCompression(opts.usePacketCompression);
		
		if (opts.useOffscreenCache)
			rc.setUseOffscreenCache(opts.useOffscreenCache);
		
		if (opts.usePersistantCache) {
			rc.setPersistentCaching(opts.usePersistantCache);
			
			rc.setPersistentCachingPath(opts.persistentCachePath);
			rc.setPersistentCachingMaxSize(opts.persistentCacheMaxCells);
		}

		if (opts.useBandwithLimitation) {
			rc.setUseBandWidthLimitation(true);
			rc.setSocketTimeout(opts.socketTimeout);
			
			if (opts.useDiskBandwithLimitation) {
				rc.getRdpdrChannel().setSpoolable(true, opts.diskBandwidthLimit);
			}
		}
	}
}
