/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2011
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

package org.ulteo.ovd.applet;

import java.awt.Dimension;
import java.awt.Toolkit;
import java.awt.Window;
import java.awt.event.FocusListener;
import java.net.UnknownHostException;
import net.propero.rdp.RdesktopCanvas;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpConnection;
import org.ulteo.Logger;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.client.OvdClient;
import org.ulteo.ovd.sm.Callback;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.rdp.RdpConnectionOvd;

public class OvdClientDesktopApplet extends OvdClient {
	private ServerAccess server = null;
	private Properties properties = null;

	private OvdApplet applet = null;

	private boolean isFullscreen = false;
	private FullscreenWindow externalWindow = null;

	public OvdClientDesktopApplet(ServerAccess server_, Properties properties_, Callback obj_, OvdApplet applet_) throws ClassCastException {
		super(obj_);

		this.server = server_;
		this.properties = properties_;

		if (! (applet_ instanceof FocusListener))
			throw new ClassCastException("[Programmer error] The Applet class must implement FocusListener class");
		this.applet = applet_;
	}

	public Window getFullscreenWindow() {
		return this.externalWindow;
	}

	public void setFullscreen(boolean isFullscreen_) {
		this.isFullscreen = isFullscreen_;
	}

	public void adjustDesktopSize() {
		if (this.connections == null || this.connections.isEmpty())
			return;

		if (this.properties == null)
			return;

		RdpConnectionOvd rc = this.connections.get(0);

		// Prevent greometry modification while the connection is active
		if (rc.getState() != RdpConnectionOvd.STATE_DISCONNECTED)
			return;

		int bpp = this.properties.getRDPBpp();

		// Ensure that width is multiple of 4
		// Prevent artifact on screen with a with resolution
		// not divisible by 4
		Dimension screenSize = (this.isFullscreen) ? Toolkit.getDefaultToolkit().getScreenSize() : this.applet.getSize();
		rc.setGraphic((int) screenSize.width & ~3, (int) screenSize.height, bpp);
	}

	@Override
	protected boolean createRDPConnections() {
		byte flags = 0x00;
		flags |= RdpConnectionOvd.MODE_DESKTOP;

		if (this.properties.isMultimedia())
			flags |= RdpConnectionOvd.MODE_MULTIMEDIA;

		if (this.properties.isPrinters())
			flags |= RdpConnectionOvd.MOUNT_PRINTERS;

		if (this.properties.isDrives() == Properties.REDIRECT_DRIVES_FULL)
			flags |= RdpConnectionOvd.MOUNTING_MODE_FULL;
		else if (this.properties.isDrives() == Properties.REDIRECT_DRIVES_PARTIAL)
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

		if (this.server.getModeGateway()) {

			if (this.server.getToken().equals("")) {
				Logger.error("Server need a token to be identified on gateway, so token is empty !");
				return false;
			} else {
				rc.setCookieElement("token", this.server.getToken());
			}

			try {
				rc.useSSLWrapper(this.server.getHost(), this.server.getPort());
			} catch(OvdException ex) {
				Logger.error("Unable to create RdpConnectionOvd SSLWrapper: " + ex.getMessage());
				return false;
			} catch(UnknownHostException ex) {
				Logger.error("Undefined error during creation of RdpConnectionOvd SSLWrapper: " + ex.getMessage());
				return false;
			}
		}

		rc.setServer(this.server.getHost());
		rc.setCredentials(this.server.getLogin(), this.server.getPassword());
		
		rc.setAllDesktopEffectsEnabled(this.properties.isDesktopEffectsEnabled());

		if (this.keymap != null)
			rc.setKeymap(this.keymap);
		
		if (this.inputMethod != null)
			rc.setInputMethod(this.inputMethod);

		this.connections.add(rc);

		this.configureRDPConnection(rc);

		return true;
	}

	@Override
	protected void customizeConnection(RdpConnectionOvd co) {
		co.setShell("OvdDesktop");
	}

	@Override
	protected void runSessionReady() {}

	@Override
	protected void runExit() {}

	@Override
	protected void display(RdpConnection co) {
		RdesktopCanvas canvas = co.getCanvas();
		canvas.setLocation(0, 0);

		if (this.isFullscreen) {
			this.externalWindow = new FullscreenWindow(this);
			this.externalWindow.add(canvas);
			this.externalWindow.setFullscreen();
		}
		else {
			this.applet.removeAll();
			this.applet.add(canvas);
			this.applet.validate();
		}

		if (this.applet instanceof FocusListener)
			canvas.addFocusListener((FocusListener) this.applet);
	}

	@Override
	protected void hide(RdpConnection co) {
		if (this.isFullscreen) {
			if (this.externalWindow != null) {
				this.externalWindow.unFullscreen();
				this.externalWindow.setVisible(false);
				this.externalWindow = null;
			}
		}
		else {
			this.applet.removeAll();
		}
	}

	@Override
	protected void uncustomizeConnection(RdpConnectionOvd co) {}

	@Override
	public boolean checkRDPConnections() {
		Logger.error("Weird -- The method checkRDPConnections() should not be called");
		return true;
	}

	@Override
	protected void runDisconnecting() {}

	@Override
	protected void runSessionTerminated() {}

	@Override
	public void connected(RdpConnection co) {
		super.connected(co);
		this.applet.forwardJS(OvdApplet.JS_API_F_SERVER, 0, OvdApplet.JS_API_O_SERVER_CONNECTED);
	}

	@Override
	public void disconnected(RdpConnection co) {
		super.disconnected(co);
		this.applet.forwardJS(OvdApplet.JS_API_F_SERVER, 0, OvdApplet.JS_API_O_SERVER_DISCONNECTED);
		this.applet.stop();
	}

	@Override
	public void failed(RdpConnection co, String msg) {
		super.failed(co, msg);

		int tryNumber = co.getTryNumber();
		if (tryNumber < 1) {
			Logger.debug("checkRDPConnections -- Bad try number("+tryNumber+"). Will continue normal process.");
			return;
		}

		if (tryNumber > 1) {
			Logger.error("checkRDPConnections -- Several try to connect to "+co.getServer()+" failed. Will exit.");
			this.applet.forwardJS(OvdApplet.JS_API_F_SERVER, 0, OvdApplet.JS_API_O_SERVER_FAILED);
			return;
		}

		co.connect();
	}
}
