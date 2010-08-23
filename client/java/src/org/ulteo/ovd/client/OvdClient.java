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

package org.ulteo.ovd.client;

import java.util.ArrayList;
import java.util.HashMap;
import net.propero.rdp.RdpConnection;
import net.propero.rdp.RdpListener;
import org.apache.log4j.Logger;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.sm.Callback;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.SessionManagerException;
import org.ulteo.rdp.RdpActions;
import org.ulteo.rdp.RdpConnectionOvd;

public abstract class OvdClient extends Thread implements Runnable, RdpListener, RdpActions {
	
	public static final String productName = "OVD Client";
	
	private static final long REQUEST_TIME_FREQUENTLY = 2000;
	private static final long REQUEST_TIME_OCCASIONALLY = 5000;
	
	protected String sessionStatus = SessionManagerCommunication.SESSION_STATUS_INIT;
	

	public static HashMap<String,String> toMap(String login_, String password_) {
		HashMap<String,String> map = new HashMap<String, String>();

		map.put(SessionManagerCommunication.FIELD_LOGIN, login_);
		map.put(SessionManagerCommunication.FIELD_PASSWORD, password_);

		return map;
	}

	protected Logger logger = Logger.getLogger(OvdClient.class);

	protected boolean graphic = false;

	protected Callback obj = null;

	protected SessionManagerCommunication smComm = null;
	protected Thread getStatus = null;
	protected ArrayList<RdpConnectionOvd> connections = null;
	protected ArrayList<RdpConnectionOvd> availableConnections = null;

	protected Thread sessionStatusMonitoringThread = null;
	protected boolean continueSessionStatusMonitoringThread = false;
	
	private boolean connectionIsActive = true;
	private boolean exitAfterLogout = false;

	public OvdClient(SessionManagerCommunication smComm) {
		this.initMembers(smComm, false);
	}

	public OvdClient(SessionManagerCommunication smComm, Callback obj_) {
		this.obj = obj_;
		this.initMembers(smComm, true);
	}

	private void initMembers(SessionManagerCommunication smComm, boolean graphic_) {
		this.smComm = smComm;
		this.graphic = graphic_;

		this.availableConnections = new ArrayList<RdpConnectionOvd>();
		
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
				public void updateProgress(int status, int substatus) {
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

	
	@Override
	public void run() {
		// session status monitoring
		long t = REQUEST_TIME_FREQUENTLY;
		boolean isActive = false;
		
		while (this.continueSessionStatusMonitoringThread) {
			try {
				String status = this.smComm.askForSessionStatus();
				
				if (! status.equals(this.sessionStatus)) {
					org.ulteo.Logger.info("session status switch from "+this.sessionStatus+" to "+status);
					this.sessionStatus = status;
					
					if (this.sessionStatus.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_INITED) || this.sessionStatus.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_ACTIVE)) {
						if (! isActive) {
							isActive = true;
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
				
			}
			catch (SessionManagerException ex) {
				org.ulteo.Logger.error("Session status monitoring: "+ex.getMessage());
			}
			try {
					Thread.sleep(t);
			}
			catch (InterruptedException ex) {
			}
		}
	}	
	
	public boolean perform() {
		this.runInit();

		this.connections = new ArrayList<RdpConnectionOvd>();
		this.createRDPConnections();
		for (RdpConnectionOvd rc : this.connections) {
			this.customizeConnection(rc);
			rc.addRdpListener(this);
		}

		this.sessionStatusMonitoringThread = new Thread(this);
		this.continueSessionStatusMonitoringThread = true;
		this.sessionStatusMonitoringThread.start();
		
		while (this.connectionIsActive) {
			try {
				Thread.sleep(2000);
			} catch (InterruptedException ex) {}
		}

		return this.exitAfterLogout;
	}

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

	public void sessionTerminated() {
		org.ulteo.Logger.info("Session is terminated");

		this.cleanConnections();

		if (this.sessionStatusMonitoringThread != null) {
			this.continueSessionStatusMonitoringThread = false;
			this.sessionStatusMonitoringThread = null;
		}
		
		this.runSessionTerminated();

		this.connectionIsActive = false;
	}

	protected abstract void runSessionTerminated();

	protected abstract void runInit();

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
		this.logger.info("Connected to "+co.getServer());
		this.addAvailableConnection((RdpConnectionOvd)co);

		this.display(co);
	}

	public void connecting(RdpConnection co) {
		this.logger.info("Connecting to "+co.getServer());

	}

	public void disconnected(RdpConnection co) {
		co.removeRdpListener(this);

		this.uncustomizeConnection((RdpConnectionOvd) co);

		this.hide(co);
		this.removeAvailableConnection((RdpConnectionOvd)co);
		this.logger.info("Disconnected from "+co.getServer());
	}

	public void failed(RdpConnection co) {
		this.logger.error("Connection to "+co.getServer()+" failed");
	}

	/* RdpActions */
	public void disconnect(RdpConnection rc) {
		try {
			((RdpConnectionOvd) rc).sendLogoff();
		} catch (OvdException ex) {
			this.logger.warn(rc.getServer()+": "+ex.getMessage());
		}
	}

	public void seamlessEnabled(RdpConnection co) {}

	public void disconnectAll() {
		this.obj.sessionDisconnecting();
	}

	public void performDisconnectAll() {
		this.runDisconnecting();
		boolean logoutRet;
		try {
			logoutRet = this.smComm.askForLogout();
		} catch (SessionManagerException ex) {
			this.logger.error(ex.getMessage());
			logoutRet = false;
		}

		if (! logoutRet) {
			this.cleanConnections();
		}
	}
	
	public void exit(int return_code) {
		this.exitAfterLogout = true;

		this.disconnectAll();
	}
}
