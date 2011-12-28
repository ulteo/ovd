/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2011
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

import net.propero.rdp.RdesktopCanvas;
import net.propero.rdp.RdpConnection;
import org.ulteo.Logger;
import org.ulteo.gui.GUIActions;
import org.ulteo.gui.SwingTools;
import org.ulteo.ovd.client.NativeClientActions;
import org.ulteo.ovd.client.OvdClientDesktop;
import org.ulteo.ovd.client.authInterface.LoadingFrame;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.rdp.RdpConnectionOvd;

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

		if (state == RdpConnection.State.CONNECTED)
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

		String session_status = this.smComm.askForSessionStatus();
		if (session_status.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_UNKNOWN)) {
			Logger.error("checkRDPConnections -- Failed to get session status from session manager: ");
			this.hide(co);
			return false;
		}
		else if (session_status.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_INITED) || session_status.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_ACTIVE)) {
			this.performedConnections.remove(co);
			co.connect();
			return true;
		}

		Logger.info("checkRDPConnections -- Your session has ended. Will exit.");
		this.hide(co);

		return false;
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
	
	// interface NativeClientActions' methods 
	
	@Override
	public boolean haveToQuit() {
		return this.desktop.haveToQuit();
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
	
}
