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
import org.ulteo.ovd.client.authInterface.LoadingStatus;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.sm.Callback;
import org.ulteo.ovd.sm.News;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.SessionManagerException;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.RdpActions;
import org.ulteo.rdp.RdpConnectionOvd;

public abstract class OvdClient implements Runnable, RdpListener, RdpActions {
	
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

	protected Callback obj = null;

	private ArrayList<RdpConnectionOvd> availableConnections = null;
	protected SessionManagerCommunication smComm = null;
	protected Thread getStatus = null;
	protected ArrayList<RdpConnectionOvd> connections = null;
	protected CopyOnWriteArrayList<RdpConnectionOvd> performedConnections = null;
	
	private String keymap = null;
	private String inputMethod = null;
	private boolean offscreenCache = false;
	private boolean packetCompression = false;
	private int persistentCacheMaxCells = 0;
	private String persistentCachePath = null;
	private int socketTimeout = 0;
	private int diskBandwidthLimit = 0;
	
	protected Thread sessionStatusMonitoringThread = null;
	protected boolean continueSessionStatusMonitoringThread = false;
	protected long sessionStatusSleepingTime = REQUEST_TIME_FREQUENTLY;

	protected boolean isCancelled = false;
	private boolean connectionIsActive = true;
	protected boolean persistent = false;

	public OvdClient(SessionManagerCommunication smComm, Callback obj_, boolean persistent) {
		this.smComm = smComm;
		this.obj = obj_;
		
		if (this.obj == null) {
			this.obj = new Callback() {
				@Override
				public void reportBadXml(String data) {
					Logger.error("Callback::reportBadXml: "+data);
				}

				@Override
				public void reportError(int code, String msg) {
					Logger.error("Callback::reportError: "+code+" => "+msg);
				}

				@Override
				public void reportErrorStartSession(String code) {
					Logger.error("Callback::reportErrorStartSession: "+code);
				}

				@Override
				public void reportNotFoundHTTPResponse(String moreInfos) {
					Logger.error("Callback::reportNotFoundHTTPResponse: "+moreInfos);
				}

				@Override
				public void reportUnauthorizedHTTPResponse(String moreInfos) {
					Logger.error("Callback::reportUnauthorizedHTTPResponse: "+moreInfos);
				}

				@Override
				public void sessionConnected() {
					Logger.info("Callback::sessionConnected");
				}

				@Override
				public void sessionDisconnecting() {
					Logger.info("Callback::sessionDisconnected");
				}

				@Override
				public void updateProgress(LoadingStatus status, int substatus) {
					Logger.info("Callback::updateProgress "+status+","+substatus);
				}
			};
		}

		this.connections = new ArrayList<RdpConnectionOvd>();
		this.availableConnections = new ArrayList<RdpConnectionOvd>();
		this.performedConnections = new CopyOnWriteArrayList<RdpConnectionOvd>();

		this.persistent = persistent;
	}

	public ArrayList<RdpConnectionOvd> getAvailableConnections() {
		return this.availableConnections;
	}

	@Override
	public void run() {
		// session status monitoring
		this.sessionStatusSleepingTime = REQUEST_TIME_FREQUENTLY;
		boolean isActive = false;
		
		while (this.continueSessionStatusMonitoringThread) {
			String status = this.smComm.askForSessionStatus();

			if (! status.equals(this.sessionStatus)) {
				Logger.info("session status switch from "+this.sessionStatus+" to "+status);
				this.sessionStatus = status;
				if (this.sessionStatus.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_INITED) || 
						this.sessionStatus.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_ACTIVE) ||
						(this.sessionStatus.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_INACTIVE) && this.persistent)) {
					if (! isActive) {
						isActive = true;
						this.sessionStatusSleepingTime = REQUEST_TIME_OCCASIONALLY;

						this.obj.sessionConnected();
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
					else if (status.equals(SessionManagerCommunication.SESSION_STATUS_UNKNOWN)) {
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
	
	/**
	 * not called by applet mode
	 * @return
	 */
	void perform() {
		if (!(this instanceof OvdClientPerformer))
			throw new ClassCastException("OvdClient must inherit from an OvdClientPerformer to use 'perform' action");
		
		if (this.smComm == null)
			throw new NullPointerException("Client cannot be performed with a non existent SM communication");

		((OvdClientPerformer)this).createRDPConnections();
		
		for (RdpConnectionOvd rc : this.connections) {
			this.customizeConnection(rc);
			rc.addRdpListener(this);
		}

		this.sessionStatusMonitoringThread = new Thread(this);
		this.continueSessionStatusMonitoringThread = true;
		this.sessionStatusMonitoringThread.start();

		do {
			// Waiting for all the RDP connections are performed
			while (this.performedConnections.size() < this.connections.size()) {
				if (! this.connectionIsActive)
					break;
				
				try {
					Thread.sleep(50);
				} catch (InterruptedException ex) {}
			}

			if (! ((OvdClientPerformer)this).checkRDPConnections()) {
				this.disconnect();
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
	}

	public void connect() {
		for (RdpConnectionOvd rc : this.connections)
			rc.connect();
	}

	public synchronized void sessionTerminated() {
		if (! this.connectionIsActive)
			return;

		Logger.info("Session is terminated");
		
		this.runSessionTerminated();

		this.connectionIsActive = false;

		if (this.sessionStatusMonitoringThread != null) {
			this.continueSessionStatusMonitoringThread = false;
			this.sessionStatusMonitoringThread = null;
		}

		// stop all RDP connections and wait
		if (this.connections != null) {
			for (RdpConnection rc : this.connections)
				rc.stop();
	
			boolean rdpActivity;
			do {
				rdpActivity = false;
				for (RdpConnection rc : this.connections) {
					if (rc.isConnected()) {
						rdpActivity = true;
						try {
							Thread.sleep(2000);
						} catch (InterruptedException ex) {
							rdpActivity = false;
						}
						break;
					}
				}
			} while (rdpActivity);
		}
	}

	protected abstract void runSessionTerminated();

	protected abstract void customizeConnection(RdpConnectionOvd co);

	protected abstract void hide(RdpConnectionOvd co);
	
	public abstract RdpConnectionOvd createRDPConnection(ServerAccess server);

	/* RdpListener */
	
	@Override
	public void connected(RdpConnection co) {
		Logger.info("Connected to "+co);

		this.performedConnections.add((RdpConnectionOvd) co);
		this.availableConnections.add((RdpConnectionOvd) co);
	}

	@Override
	public void connecting(RdpConnection co) {
		Logger.info("Connecting to "+co);
	}

	@Override
	public void disconnected(RdpConnection co) {
		co.removeRdpListener(this);

		this.hide((RdpConnectionOvd)co);
		this.performedConnections.remove(co);
		this.availableConnections.remove(co);
		Logger.info("Disconnected from "+co);

		if (this.sessionStatusMonitoringThread != null && this.sessionStatusMonitoringThread.isAlive()) {
			// Break session status monitoring sleep to check with SessionManager ASAP
			this.sessionStatusSleepingTime = REQUEST_TIME_FREQUENTLY;
			this.sessionStatusMonitoringThread.interrupt();
		}
	}

	@Override
	public void failed(RdpConnection co, String msg) {
		Logger.error("Connection to "+co+" failed: "+msg);

		this.performedConnections.add((RdpConnectionOvd) co);
	}

	@Override
	public void seamlessEnabled(RdpConnection co) {}

	/* RdpActions */

	@Override
	public void disconnect() {
		if (! this.connectionIsActive)
			return;

		this.isCancelled = true;
		this.obj.sessionDisconnecting();
	}

	public void performDisconnectAll() {
		final Timer forceDisconnectionTimer = new Timer();

		final TimerTask forceDisconnectionTask = new TimerTask() {
			@Override
			public void run() {
				sessionTerminated();
			}
		};

		if (this.persistent) {
			forceDisconnectionTimer.schedule(forceDisconnectionTask, 0);
			return;
		}
		
		long delay = 0;
		if (this.smComm != null && ! OSTools.is_applet) {
			Thread disconnectThread = new Thread(new Runnable() {
				public void run() {
					try {
						smComm.askForLogout(persistent);
					} catch (SessionManagerException ex) {
						Logger.error("Disconnection error: "+ex.getMessage());
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
	
	/**
	 * select keyboard mapping to use
	 * @param keymap specific keymap
	 */
	public void setKeymap(String keymap) {
		this.keymap = keymap;
	}
	
	/**
	 * select input method
	 * @param inputMethod 
	 */
	public void setInputMethod(String inputMethod) {
		this.inputMethod = inputMethod;
	}
	
	/**
	 * 
	 * unable/disable packet compression
	 * @param packetCompression
	 */
	public void setPacketCompression(boolean packetCompression) {
		this.packetCompression = packetCompression;
	}

	/**
	 * enable/disable offscreen cache feature
	 * @param offscreenCache 
	 */
	public void setOffscreenCache(boolean offscreenCache) {
		this.offscreenCache = offscreenCache;
	}

	/**
	 * unable persistant cache
	 * @param persistentCacheMaxCells maximum cells of the persistent cache
	 * @param persistentCachePath temporary path of the persistent cache
	 */
	public void setPersistentCaching(int persistentCacheMaxCells, String persistentCachePath) {
		this.persistentCacheMaxCells = persistentCacheMaxCells;
		this.persistentCachePath = persistentCachePath;
	}

	/**
	 * unable and set bandwith limitation
	 * @param socketTimeout set socket timeout
	 * @param diskBandwidthLimit if superior to 0, define disk bandwith limit
	 */
	public void setBandWidthLimitation(int socketTimeout, int diskBandwidthLimit) {
		this.socketTimeout = socketTimeout;
		this.diskBandwidthLimit = diskBandwidthLimit;
	}

	/**
	 * configure a specific RdpConnection
	 * @param rc
	 */
	protected void configure(RdpConnectionOvd rc) {
		if (this.keymap != null)
			rc.setKeymap(this.keymap);
		if (this.inputMethod != null)
			rc.setInputMethod(this.inputMethod);
		
		rc.setUseOffscreenCache(this.offscreenCache);
		rc.setPacketCompression(this.packetCompression);
       
		if (this.persistentCacheMaxCells != 0) {
			rc.setPersistentCaching(true);
			rc.setPersistentCachingMaxSize(this.persistentCacheMaxCells);
			rc.setPersistentCachingPath(this.persistentCachePath);
		}

		if (this.socketTimeout != 0) {
			rc.setUseBandWidthLimitation(true);
			rc.setSocketTimeout(this.socketTimeout);
			if (diskBandwidthLimit != 0)
				rc.getRdpdrChannel().setSpoolable(true, this.diskBandwidthLimit);
		}
	}
    
	/**
	 * find the {@link RdpConnectionOvd} corresponding to a given {@link OvdAppChannel}
	 * @param specific {@link OvdAppChannel}
	 * @return {@link RdpConnectionOvd} if found, null instead
	 */
	protected RdpConnectionOvd find(OvdAppChannel channel) {
		for (RdpConnectionOvd rc : this.getAvailableConnections()) {
			if (rc.getOvdAppChannel() == channel)
				return rc;
		}
		return null;
	}
	
	/**
	 * find the {@link Application} available in the several {@link RdpConnectionOvd}
	 * @param id id of the {@link Application} to search
	 * @return {@link Application} if found, null instead
	 */
	public Application findAppById(int id) {
		for (RdpConnectionOvd rc : this.getAvailableConnections()) {
			for (Application app : rc.getAppsList()) {
				if (app.getId() == id) {
					return app;
				}
			}
		}
		return null;
	}

}
