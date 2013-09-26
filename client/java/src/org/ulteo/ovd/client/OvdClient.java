/*
 * Copyright (C) 2010-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Vincent ROULLIER <v.roullier@ulteo.com> 2013
 * Author David LECHEVALIER <david@ulteo.com> 2011, 2012
 * Author Thomas MOUTON <thomas@ulteo.com> 2010, 2012-2013
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
import net.propero.rdp.Rdp;
import net.propero.rdp.RdpConnection;
import net.propero.rdp.RdpListener;
import org.ulteo.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.sm.News;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.SessionManagerException;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.RdpConnectionOvd;

public abstract class OvdClient implements RdpListener {
	
	public enum DisconnectionMode {SUSPEND, LOGOFF};
	public static final DisconnectionMode DEFAULT_DISCONNECTION_MODE = DisconnectionMode.SUSPEND;
	
	public static final String productName = "OVD Client";
	
	public static final long REQUEST_TIME_FREQUENTLY = 2000;
	public static final long REQUEST_TIME_OCCASIONALLY = 60000;

	private static final long DISCONNECTION_MAX_DELAY = 3500;
	
	protected String sessionStatus = SessionManagerCommunication.SESSION_STATUS_UNKNOWN;
	

	public static HashMap<String,String> toMap(String login_, String password_) {
		HashMap<String,String> map = new HashMap<String, String>();

		map.put(SessionManagerCommunication.FIELD_LOGIN, login_);
		map.put(SessionManagerCommunication.FIELD_PASSWORD, password_);

		return map;
	}

	protected ArrayList<RdpConnectionOvd> availableConnections = null;
	protected SessionManagerCommunication smComm = null;
	protected Thread getStatus = null;
	protected ArrayList<RdpConnectionOvd> connections = null;
	protected CopyOnWriteArrayList<RdpConnectionOvd> performedConnections = null;
	
	private String keymap = null;
	private String inputMethod = null;
	private boolean offscreenCache = false;
	private boolean useFrameMarker = false;
	private boolean useTLS = false;
	private boolean packetCompression = false;
	private int persistentCacheMaxCells = 0;
	private String persistentCachePath = null;
	private int socketTimeout = 0;
	private int diskBandwidthLimit = 0;
	private boolean useKeepAlive = false;
	private int keepAliveInterval = 0;
	private int networkConnectionType = Rdp.CONNECTION_TYPE_UNKNOWN;
	
	protected Thread sessionStatusMonitoringThread = null;
	protected boolean continueSessionStatusMonitoringThread = false;
	protected long sessionStatusSleepingTime = REQUEST_TIME_FREQUENTLY;

	protected boolean isCancelled = false;
	protected boolean connectionIsActive = true;
	protected boolean persistent = false;
	
	private boolean waitSession = false;
	private final Object waitSessionLock = new Object();

	public OvdClient(SessionManagerCommunication smComm, boolean persistent) {
		this.smComm = smComm;

		this.connections = new ArrayList<RdpConnectionOvd>();
		this.availableConnections = new ArrayList<RdpConnectionOvd>();
		this.performedConnections = new CopyOnWriteArrayList<RdpConnectionOvd>();

		this.persistent = persistent;
	}
	
	public boolean isWaitRecoveryModeEnabled = false;
	public void enableWaitRecoveryMode(boolean waitRecoveryMode_) {
		this.isWaitRecoveryModeEnabled = waitRecoveryMode_;
	}
	
	private void setWaitSession(boolean waitSession_) {
		synchronized(this.waitSessionLock) {
			this.waitSession = waitSession_;
		}
	}
	
	protected boolean getWaitSession() {
		synchronized(this.waitSessionLock) {
			return this.waitSession;
		}
	}

	public ArrayList<RdpConnectionOvd> getAvailableConnections() {
		return this.availableConnections;
	}
	
	protected void suspendSession() {
		Logger.info("Session is suspended");
		this.setWaitSession(true);
		
		for (RdpConnectionOvd each : this.connections)
			each.stop();
	}
	
	protected void resumeSession() {
		Logger.info("Session is resumed");
		this.setWaitSession(false);
		
		for (RdpConnectionOvd each : this.connections) {
			each.addRdpListener(this);
			each.connect();
		}
	}

	protected DisconnectionMode getDisconnectionMode() {
		return DEFAULT_DISCONNECTION_MODE;
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

		if (! this.getWaitSession() && this.sessionStatusMonitoringThread != null) {
			this.continueSessionStatusMonitoringThread = false;
			this.sessionStatusMonitoringThread = null;
		}

		// stop all RDP connections and wait
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

		if (! this.getWaitSession() && this.sessionStatusMonitoringThread != null && this.sessionStatusMonitoringThread.isAlive()) {
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

	/**
	 * disconnect performers
	 */
	public void disconnection() {
		if (! this.connectionIsActive)
			return;

		this.isCancelled = true;
	}

	public void performDisconnectAll() {
		final Timer forceDisconnectionTimer = new Timer();

		final TimerTask forceDisconnectionTask = new TimerTask() {
			@Override
			public void run() {
				sessionTerminated();
			}
		};

		long delay = 0;
		
		if (this.persistent) {
			forceDisconnectionTimer.schedule(forceDisconnectionTask, delay);
			return;
		}
		
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
		Logger.info("Input method activated: " + packetCompression);

		this.inputMethod = inputMethod;
	}
	
	/**
	 * 
	 * unable/disable packet compression
	 * @param packetCompression
	 */
	public void setPacketCompression(boolean packetCompression) {
		Logger.info("Packet compression activation " + packetCompression);
		
		this.packetCompression = packetCompression;
	}

	/**
	 * enable/disable offscreen cache feature
	 * @param offscreenCache 
	 */
	public void setOffscreenCache(boolean offscreenCache) {
		Logger.info("Offscreen cache activation " + offscreenCache);
		
		this.offscreenCache = offscreenCache;
	}

	/**
	 * enable/disable frame marker feature
	 * @param useFrameMarker 
	 */
	public void setUseFrameMarker(boolean useFrameMarker) {
		Logger.info("Frame marker feature activation " + useFrameMarker);
		
		this.useFrameMarker = useFrameMarker;
	}

	/**
	 * enable/disable TLS Transport layer feature
	 * @param useTLS 
	 */
	public void setUseTLS(boolean useTLS) {
		Logger.info("TLS transport layer activation: " + useTLS);
		
		this.useTLS = useTLS;
	}
	
	/**
	 * unable persistant cache
	 * @param persistentCacheMaxCells maximum cells of the persistent cache
	 * @param persistentCachePath temporary path of the persistent cache
	 */
	public void setPersistentCaching(int persistentCacheMaxCells, String persistentCachePath) {
		Logger.info("Persistent cache path: " + persistentCachePath);
		Logger.info("Maximun persistent cache cellule count: " + persistentCacheMaxCells);
		
		this.persistentCacheMaxCells = persistentCacheMaxCells;
		this.persistentCachePath = persistentCachePath;
	}

	/**
	 * unable and set bandwith limitation
	 * @param socketTimeout set socket timeout
	 * @param diskBandwidthLimit if superior to 0, define disk bandwith limit
	 */
	public void setBandWidthLimitation(int socketTimeout, int diskBandwidthLimit) {
		Logger.info("Socket timeout " + socketTimeout);
		Logger.info("Bandwith limit " + diskBandwidthLimit+" bytes/second");

		this.socketTimeout = socketTimeout;
		this.diskBandwidthLimit = diskBandwidthLimit;
	}

	/**
	 * enable/disable keepalive capability
	 * @param useKeepalive 
	 */
	public void setUseKeepAlive(boolean useKeepalive) {
		Logger.info("Keepalive activation: " + useKeepalive);
		
		this.useKeepAlive = useKeepalive;
	}
	
	/**
	 * set keep alive interval
	 * @param keepAliveInterval keep alive interval in seconde
	 */
	public void setKeepAliveInterval(int keepAliveInterval) {
		Logger.info("Keepalive interval: "+keepAliveInterval);
		this.keepAliveInterval = keepAliveInterval;
	}
	
	/**
	 * set network connection type
	 * @param networkConnectionType the network connection type
	 */
	public void setNetworkConnectionType(int networkConnectionType) {
		Logger.info("Network connection type: " + networkConnectionType);
		
		this.networkConnectionType = networkConnectionType;
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
		rc.setUseFrameMarker(this.useFrameMarker);
		rc.setPacketCompression(this.packetCompression);
		rc.setUseTLS(this.useTLS);
		rc.setNetworkConnectionType(this.networkConnectionType);
       
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
		if (this.useKeepAlive) {
			rc.setUseKeepAlive();
			if (this.keepAliveInterval != 0) {
				rc.setKeepAliveInterval(this.keepAliveInterval);
			}
		}
	}

}
