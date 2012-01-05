/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2011
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
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
import java.awt.Insets;
import java.awt.Toolkit;

import java.net.UnknownHostException;
import net.propero.rdp.RdesktopCanvas;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpConnection;
import org.ulteo.Logger;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.client.OvdClient;
import org.ulteo.ovd.sm.Callback;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.ovd.sm.SessionManagerException;
import org.ulteo.rdp.RdpConnectionOvd;

public class OvdClientDesktop extends OvdClient {
	private DesktopFrame desktop = null;
	private boolean desktopLaunched = false;
	private Dimension resolution = null;
	private boolean fullscreen = false;
	
	public OvdClientDesktop(SessionManagerCommunication smComm, Dimension resolution) {
		super(smComm, null, false);

		this.init(resolution);
	}

	public OvdClientDesktop(SessionManagerCommunication smComm, Dimension resolution, Callback obj, boolean persistent) {
		super(smComm, obj, persistent);

		this.init(resolution);
	}

	private void init(Dimension resolution_) {
		this.resolution = resolution_;

		Dimension screenSize = Toolkit.getDefaultToolkit().getScreenSize();
		this.fullscreen = (this.resolution.width == screenSize.width && this.resolution.height == screenSize.height);
	}

	@Override
	protected void runSessionReady() {
		this.desktop.setVisible(true);
	}

	@Override
	protected void runExit() {}

	@Override
	protected void runSessionTerminated() {}

	@Override
	protected void customizeConnection(RdpConnectionOvd co) {
		if (! this.desktopLaunched)
			this.initDesktop(co);
		co.setShell("OvdDesktop");
	}

	@Override
	protected void uncustomizeConnection(RdpConnectionOvd co) {}

	@Override
	public void display(RdpConnection co) {
		RdesktopCanvas canvas = co.getCanvas();
		this.desktop.setCanvas(canvas);
		this.desktop.getContentPane().add(canvas);
		canvas.validate();
		this.desktop.pack();
	}

	@Override
	public void hide(RdpConnection co) {
		this.desktopLaunched = false;
		this.desktop.destroy();
		this.desktop = null;
	}

	private void initDesktop(RdpConnectionOvd co) {
		this.desktop = new DesktopFrame(this.resolution, this.fullscreen, this);

		if (! this.fullscreen) {
			Insets inset = null;
			inset = this.desktop.getInsets();
			this.desktop.setLocationRelativeTo(null);
			co.setGraphic((this.desktop.getWidth()-(inset.left+inset.right)+2), (this.desktop.getHeight()-(inset.bottom+inset.top)+2), co.getBpp());
		}
		this.desktopLaunched = true;
	}

	@Override
	protected boolean createRDPConnections() {	
		Properties properties = this.smComm.getResponseProperties();
		ServerAccess server = this.smComm.getServers().get(0);
		
		
		byte flags = 0x00;
		flags |= RdpConnectionOvd.MODE_DESKTOP;
		
		if (properties.isMultimedia())
			flags |= RdpConnectionOvd.MODE_MULTIMEDIA;
		
		if (properties.isPrinters())
			flags |= RdpConnectionOvd.MOUNT_PRINTERS;

		if (properties.isDrives() == Properties.REDIRECT_DRIVES_FULL)
			flags |= RdpConnectionOvd.MOUNTING_MODE_FULL;
		else if (properties.isDrives() == Properties.REDIRECT_DRIVES_PARTIAL)
			flags |= RdpConnectionOvd.MOUNTING_MODE_PARTIAL;
		
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
				Logger.error("Undefined error during creation of RdpConnectionOvd SSLWrapper: " + ex.getMessage());
				return false;
			}
		}

		rc.setServer(server.getHost());
		rc.setCredentials(server.getLogin(), server.getPassword());
		
		int bpp = this.smComm.getResponseProperties().getRDPBpp();
		
		// Ensure that width is multiple of 4
		// Prevent artifact on screen with a with resolution
		// not divisible by 4
		Dimension screenSize = Toolkit.getDefaultToolkit().getScreenSize();
		rc.setGraphic((int) screenSize.width & ~3, (int) screenSize.height, bpp);

		rc.setAllDesktopEffectsEnabled(this.smComm.getResponseProperties().isDesktopEffectsEnabled());

		if (this.keymap != null)
			rc.setKeymap(this.keymap);
		
		if (this.inputMethod != null)
			rc.setInputMethod(this.inputMethod);
		
		this.connections.add(rc);
		
		return true;
	}

	@Override
	public boolean checkRDPConnections() {
		if (this.performedConnections.isEmpty()) {
			Logger.debug("checkRDPConnections -- No connections. Will abort normal process.");
			return false;
		}

		RdpConnectionOvd co = this.performedConnections.get(0);
		int state = co.getState();

		if (state == RdpConnectionOvd.STATE_CONNECTED)
			return true;

		if (state != RdpConnectionOvd.STATE_FAILED) {
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

		String session_status = null;
		try {
			session_status = this.smComm.askForSessionStatus();
		} catch (SessionManagerException ex) {
			Logger.error("checkRDPConnections -- Failed to get session status from session manager: "+ex.getMessage()+". Will exit.");
			this.hide(co);
			return false;
		}
		if (session_status == null) {
			Logger.error("checkRDPConnections -- Failed to get session status from session manager: Internal error. Will exit.");
			this.hide(co);
			return false;
		}

		if (session_status.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_INITED) || session_status.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_ACTIVE)) {
			this.performedConnections.remove(co);
			co.connect();

			return true;
		}

		Logger.info("checkRDPConnections -- Your session has ended. Will exit.");
		this.hide(co);

		return false;
	}

	@Override
	protected void runDisconnecting() {}
}
