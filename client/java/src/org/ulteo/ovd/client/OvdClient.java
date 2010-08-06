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

import java.awt.Container;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Map;
import javax.swing.JOptionPane;
import net.propero.rdp.RdpConnection;
import net.propero.rdp.RdpListener;
import org.apache.log4j.Logger;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.client.authInterface.AuthFrame;
import org.ulteo.ovd.client.authInterface.KeyLoginListener;
import org.ulteo.ovd.client.authInterface.LoginListener;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.SessionManagerException;
import org.ulteo.ovd.sm.SessionStatusListener;
import org.ulteo.rdp.RdpActions;
import org.ulteo.rdp.RdpConnectionOvd;

public abstract class OvdClient extends Thread implements RdpListener, RdpActions, SessionStatusListener {
	public static final String productName = "OVD Client";

	public static HashMap<String,String> toMap(String login_, String password_) {
		HashMap<String,String> map = new HashMap<String, String>();

		map.put(SessionManagerCommunication.FIELD_LOGIN, login_);
		map.put(SessionManagerCommunication.FIELD_PASSWORD, password_);

		return map;
	}

	protected Logger logger = Logger.getLogger(OvdClient.class);

	protected String fqdn = null;
	protected boolean use_https = true;
	protected HashMap<String,String> params = null;
	protected boolean graphic = false;

	protected AuthFrame frame = null;
	protected LoginListener logList = null;
	protected boolean isCancelled = false;

	protected SessionManagerCommunication smComm = null;
	protected Thread getStatus = null;
	protected ArrayList<RdpConnectionOvd> connections = null;
	protected ArrayList<RdpConnectionOvd> availableConnections = null;

	private boolean runThread = true;
	private boolean exitAfterLogout = false;

	public OvdClient(String fqdn_, boolean use_https_, HashMap<String,String> params_) {
		this.initMembers(fqdn_, use_https_, params_, false);
	}

	public OvdClient(String fqdn_, boolean use_https_, HashMap<String,String> params_, AuthFrame frame_, LoginListener logList_) {
		this.initMembers(fqdn_, use_https_, params_, true);
		this.frame = frame_;
		this.logList = logList_;
	}

	private void initMembers(String fqdn_, boolean use_https_, HashMap<String,String> params_, boolean graphic_) {
		this.fqdn = fqdn_;
		this.use_https = use_https_;
		this.params = params_;
		this.graphic = graphic_;

		this.availableConnections = new ArrayList<RdpConnectionOvd>();

		this.smComm = new SessionManagerCommunication(this.fqdn, this.use_https);
		this.smComm.addSessionStatusListener(this);
	}

	protected void setSessionMode(String sessionMode_) {
		this.params.put(SessionManagerCommunication.FIELD_SESSION_MODE, sessionMode_);
	}

	private void addAvailableConnection(RdpConnectionOvd rc) {
		this.availableConnections.add(rc);
	}

	private void removeAvailableConnection(RdpConnectionOvd rc) {
		System.out.println("removeAvailableConnection");
		System.out.println("before availableconnection.size: "+this.countAvailableConnection());
		this.availableConnections.remove(rc);
		System.out.println("after availableconnection.size: "+this.countAvailableConnection());
	}

	protected int countAvailableConnection() {
		return this.availableConnections.size();
	}

	public ArrayList<RdpConnectionOvd> getAvailableConnections() {
		return this.availableConnections;
	}

	protected boolean askSM() {
		try {
			return this.smComm.askForSession(this.params);
		} catch (SessionManagerException ex) {
			this.logger.error(ex.getMessage());
			if (this.graphic) {
				JOptionPane.showMessageDialog(null, ex.getMessage(), "Error", JOptionPane.ERROR_MESSAGE);
			}
			return false;
		}
	}

	@Override
	public void run() {
		if (this.graphic) {
			this.logList.initLoader();
		}

		this.runInit();
		
		if (! this.askSM()) {
			this.switchToAuthFrame();
			return;
		}

		this.smComm.startSessionStatusMonitoring();

		if (! this.isCancelled) {
			this.connections = this.smComm.getConnections();
			for (RdpConnectionOvd rc : this.connections) {
				rc.addRdpListener(this);

				this.customizeConnection(rc);
			}

			for (RdpConnectionOvd rc : this.connections) {
				rc.connect();
			}
		}

		while (this.runThread) {
			try {
				Thread.sleep(2000);
			} catch (InterruptedException ex) {}
		}

		if (this.exitAfterLogout)
			this.quit(0);
		else
			this.switchToAuthFrame();
	}

	public void sessionManagerIsOnline() {
		this.logger.info("SessionManager is online");
		this.logList.getLoader().setCancelButtonEnabled(true);
	}

	public void sessionReady(String sessionId) {
		this.logger.info("Session is ready");

		if (this.graphic && (this.logList.getLoader().isVisible() || this.frame.getMainFrame().isVisible())) {
			this.logList.getLoader().setVisible(false);
			this.logList.getLoader().dispose();
			this.frame.hideWindow();
		}

		this.runSessionReady(sessionId);

		this.runExit();
	}

	protected void switchToAuthFrame() {
		if (this.graphic) {
			KeyLoginListener.PUSHED = false;
			if (this.logList.getLoader().isVisible()) {
				this.logList.getLoader().removeAll();
				this.logList.getLoader().setVisible(false);
				this.logList.getLoader().dispose();
			}
			Container cp = this.frame.getMainFrame().getContentPane();
			cp.removeAll();

			this.frame.init();
			this.frame.setDesktopLaunched(false);
		}
		else {
			System.exit(0);
		}
	}

	protected abstract void runInit();

	protected abstract void runSessionReady(String sessionId);

	protected abstract void runExit();

	public void sessionTerminated(String sessionId) {
		this.logger.info("Session is terminated");

		this.cleanConnections();
		
		this.runSessionTerminated(sessionId);
	}

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

	protected abstract void runSessionTerminated(String sessionId);

	private void quit(int i) {
		this.quitProperly(i);
		while (this.countAvailableConnection() > 0) {
			System.out.println("countAvailableConnection: "+this.countAvailableConnection());
			try {
				Thread.sleep(100);
			} catch (InterruptedException e) {}
		}
		System.exit(i);
	}

	private void cleanConnections() {
		this.stopAllRDPConnections();
		this.waitAllRDPServersLogout();
		this.runThread = false;

		this.smComm.stopSessionStatusMonitoring();
		this.smComm.removeSessionStatusListener(this);
	}

	protected abstract void quitProperly(int i);

	protected abstract void customizeConnection(RdpConnectionOvd co);

	protected abstract void uncustomizeConnection(RdpConnectionOvd co);

	protected abstract void display(RdpConnection co);

	protected abstract void hide(RdpConnection co);

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
		this.isCancelled = true;

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
