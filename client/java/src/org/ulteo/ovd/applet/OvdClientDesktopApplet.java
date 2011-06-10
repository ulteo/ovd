/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2011
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

package org.ulteo.ovd.applet;

import java.awt.Dimension;
import java.awt.Toolkit;
import java.awt.event.FocusListener;
import net.propero.rdp.RdesktopCanvas;
import net.propero.rdp.RdpConnection;
import org.ulteo.Logger;
import org.ulteo.ovd.FullscreenWindow;
import org.ulteo.ovd.client.OvdClientDesktop;
import org.ulteo.ovd.sm.Callback;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.rdp.RdpConnectionOvd;

public class OvdClientDesktopApplet extends OvdClientDesktop {
	private Properties properties = null;

	private OvdApplet applet = null;

	private boolean isFullscreen = false;
	private FullscreenWindow externalWindow = null;
	
	private ServerAccess server;
	public RdpConnectionOvd rc;

	public OvdClientDesktopApplet(Properties properties_, ServerAccess server, Callback obj_, OvdApplet applet_) throws ClassCastException {
		super(obj_);

		this.properties = properties_;
		this.server = server;

		if (! (applet_ instanceof FocusListener))
			throw new ClassCastException("[Programmer error] The Applet class must implement FocusListener class");
		this.applet = applet_;
	}

	public void setFullscreen(boolean isFullscreen_) {
		this.isFullscreen = isFullscreen_;
	}

	@Override
	public void adjustDesktopSize(RdpConnectionOvd rc) {
		if (rc == null || this.properties == null)
			return;

		// Prevent greometry modification while the connection is active
		if (rc.getState() != RdpConnection.State.DISCONNECTED)
			return;

		int bpp = this.properties.getRDPBpp();

		// Ensure that width is multiple of 4
		// Prevent artifact on screen with a with resolution
		// not divisible by 4
		Dimension screenSize = (this.isFullscreen) ? Toolkit.getDefaultToolkit().getScreenSize() : this.applet.getSize();
		rc.setGraphic((int) screenSize.width & ~3, (int) screenSize.height, bpp);
	}

	public void createRDPConnections() {
		this.rc = createRDPConnection(this.server);
		this.connections.add(rc);
		this.customizeConnection(rc);
		rc.addRdpListener(this);
	};
	
	@Override
	protected void customizeConnection(RdpConnectionOvd co) {
		co.setShell("OvdDesktop");
	}

	@Override
	protected void runSessionReady() {}

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
	protected void hide(RdpConnectionOvd rc) {
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
	protected Properties getProperties() {
		return this.properties;
	}
	
	@Override
	public boolean checkRDPConnections() {
		Logger.error("Weird -- The method checkRDPConnections() should not be called");
		return true;
	}

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
