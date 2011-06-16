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
import java.awt.event.FocusListener;
import net.propero.rdp.RdesktopCanvas;
import net.propero.rdp.RdpConnection;
import org.ulteo.Logger;
import org.ulteo.ovd.client.OvdClientDesktop;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.rdp.RdpConnectionOvd;

public class OvdClientDesktopApplet extends OvdClientDesktop {
	private Properties properties = null;

	private OvdApplet applet = null;

	private boolean isFullscreen = false;
	private FullscreenWindow externalWindow = null;
	
	public OvdClientDesktopApplet(Properties properties_, OvdApplet applet_) throws ClassCastException {
		super();

		this.properties = properties_;

		if (! (applet_ instanceof FocusListener))
			throw new ClassCastException("[Programmer error] The Applet class must implement FocusListener class");
		this.applet = applet_;
	}

	public void setFullscreen(boolean isFullscreen_) {
		this.isFullscreen = isFullscreen_;
	}

	@Override
	public RdpConnectionOvd createRDPConnection(ServerAccess server) {
		RdpConnectionOvd rc = super.createRDPConnection(server);
		this.connections.add(rc);
		this.customizeConnection(rc);
		rc.addRdpListener(this);
		return rc;
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
	protected Dimension getScreenSize() {
		return (this.isFullscreen) ? super.getScreenSize() : this.applet.getSize();
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
