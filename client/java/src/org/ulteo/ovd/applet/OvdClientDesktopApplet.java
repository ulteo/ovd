/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2012
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
import java.awt.Window;
import java.awt.event.FocusListener;
import java.util.concurrent.ConcurrentHashMap;
import net.propero.rdp.RdesktopCanvas;
import net.propero.rdp.RdpConnection;
import org.ulteo.Logger;
import org.ulteo.ovd.client.OvdClientDesktop;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.rdp.RdpConnectionOvd;

import java.util.Enumeration;
import java.applet.Applet;

public class OvdClientDesktopApplet extends OvdClientDesktop {
	private Properties properties = null;
private ConcurrentHashMap<Integer, RdpConnectionOvd> matching = null;

	private WebClient webclient = null;
	private Applet applet = null;

	private boolean isFullscreen = false;
	private FullscreenWindow externalWindow = null;
	
	public OvdClientDesktopApplet(Properties properties_, WebClient webclient_) throws ClassCastException {
		super();
		this.matching = new ConcurrentHashMap<Integer, RdpConnectionOvd>();

		this.properties = properties_;


		this.webclient = webclient_;

		System.out.println("Looking for applet '"+this.webclient.container+"'");
		Enumeration<Applet> applets = this.webclient.getAppletContext().getApplets();
		while (applets.hasMoreElements()) {
			Applet a = applets.nextElement();
			System.out.println("  * found applet: "+a+" id: "+a.getParameter("id"));
			String applet_id = a.getParameter("id");
			if (applet_id == null || ! applet_id.equals(this.webclient.container)) {
				continue;
			}
			
			this.applet = a;
			System.out.println("    * this is the applet I'm looking for!");
			break;
		}
		
		if (this.applet == null) {
			System.err.println("Unable to find another applet to host desktop session");
			// maybe usefull to throw an exception ...
			throw new ClassCastException("Unable to find applet '"+this.webclient.container+"' Desktop session canno't be started");
		}
	}

	public Window getFullscreenWindow() {
		return this.externalWindow;
	}

	public void setFullscreen(boolean isFullscreen_) {
		this.isFullscreen = isFullscreen_;
	}

	/**
	 * create a {@link RdpConnectionOvd} and add it to the connections list
	 * @param JSId
	 * 		ID used for referencing the server beside to the WebClient
	 * @param server
	 * 		information object needed to create the {@link RdpConnectionOvd}
	 * @return
	 * 		<code>true</code> if the function succeed, <code>false</code> instead
	 */
	public boolean addServer(int JSId, ServerAccess server) {
		RdpConnectionOvd co = createRDPConnection(server);
		if (co == null)
			return false;
		
		// adjustDesktopSize size must be called in the start method and before the connect.
		// This prevents that the session start with the resolution 800x600 
		// It guarantee that the detected resolution is right
		this.adjustDesktopSize();
		
		this.customizeConnection(co);
		this.matching.put(JSId, co);
		
		co.addRdpListener(this);
		co.connect();
		return true;
	}


	@Override
	public RdpConnectionOvd createRDPConnection(ServerAccess server) {
		RdpConnectionOvd rc = super.createRDPConnection(server);
		this.customizeConnection(rc);
		rc.addRdpListener(this);
		return rc;
	};
	
	@Override
	protected void customizeConnection(RdpConnectionOvd co) {
		co.setShell("OvdDesktop");
	}

	@Override
	protected void display(RdesktopCanvas canvas) {
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

		if (this.webclient instanceof FocusListener)
			canvas.addFocusListener((FocusListener) this.webclient);
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
	public Dimension getScreenSize() {
		return (this.isFullscreen) ? super.getScreenSize() : this.applet.getSize();
	}
	
	@Override
	public void connected(RdpConnection co) {
		super.connected(co);
		this.webclient.forwardServerStatusToJS(0, WebClient.JS_API_O_SERVER_CONNECTED);
	}

	@Override
	public void disconnected(RdpConnection co) {
		super.disconnected(co);
		this.webclient.forwardServerStatusToJS(0, WebClient.JS_API_O_SERVER_DISCONNECTED);
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
			this.webclient.forwardServerStatusToJS(0, WebClient.JS_API_O_SERVER_FAILED);
			return;
		}

		co.connect();
	}
}
