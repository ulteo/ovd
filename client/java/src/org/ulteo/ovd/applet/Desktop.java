/*
 * Copyright (C) 2009-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2009-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
 * Author Samuel BOVEE <samuel@ulteo.com> 2010
 * Author David LECHEVALIER <david@ulteo.com> 2011, 2012
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

import java.awt.BorderLayout;
import java.awt.Frame;
import java.awt.event.FocusEvent;
import java.awt.event.FocusListener;

import org.ulteo.ovd.client.authInterface.LoadingStatus;
import org.ulteo.ovd.sm.Callback;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.rdp.RdpConnectionOvd;

public class Desktop extends OvdApplet implements FocusListener, Callback {
	
	private boolean fullscreenMode = false;
	private String username = null;
	private String password = null;
	private String token = null;
	
	@Override
	protected void _init(Properties properties) {
		ServerAccess aps = new ServerAccess(this.server, this.port, this.username, this.password);
		if (this.token != null) {
			aps.setModeGateway(true);
			aps.setToken(this.token);
		}

		this.ovd = new OvdClientDesktopApplet(aps, properties, this, this);
		this.ovd.setKeymap(this.keymap);
		if (this.rdp_input_method != null)
			this.ovd.setInputMethod(this.rdp_input_method);
		
		((OvdClientDesktopApplet)this.ovd).setFullscreen(this.fullscreenMode);
		((OvdClientDesktopApplet)this.ovd).createRDPConnections();
		for (RdpConnectionOvd co : this.ovd.getConnections())
			this.applyConfig(co);
		
		BorderLayout layout = new BorderLayout();
		this.setLayout(layout);
	}
	
	
	@Override
	protected void _start() {
		// adjustDesktopSize size must be called in the start method and before the connect.
		// This prevents that the session start with the resolution 800x600 
		// It guarantee that the detected resolution is right
		((OvdClientDesktopApplet)this.ovd).adjustDesktopSize();
		this.ovd.sessionReady();
	}
	
	@Override
	protected void _stop() {}
	
	@Override
	protected void _destroy() {
		this.username = null;
		this.password = null;
		this.token = null;
	}
	
	@Override
	protected int getMode() {
		return Properties.MODE_DESKTOP;
	}
	
	@Override
	public void readParameters() throws Exception {
		this.server = this.getParameterNonEmpty("server");
		this.username = this.getParameterNonEmpty("username");
		this.password = this.getParameterNonEmpty("password");
		this.token = this.getParameter("token");
		this.wc = this.getParameter("wc_url");
		String strPort = this.getParameterNonEmpty("port");
		try {
			this.port = Integer.parseInt(strPort);
		}
		catch (NumberFormatException e) {
			throw new Exception("Unable to get valid port from applet parameters: "+e.getMessage());
		}
		
		String buf = this.getParameter("fullscreen");
		if (buf != null)
			this.fullscreenMode = true;
	}

	@Override
	public void focusGained(FocusEvent e) {
		if (this.focusManager != null && !this.started_stop) {
			this.focusManager.performedFocusGain(e.getComponent());
		}
	}

	@Override
	public void focusLost(FocusEvent e) {
		if (this.focusManager != null && !this.started_stop) {
			this.focusManager.performedFocusLost(e.getComponent());
		}
	}

	@Override
	public void sessionDisconnecting() {
		if (this.ovd == null || this.ovd.getAvailableConnections() == null)
			return;

		for (RdpConnectionOvd co : this.ovd.getAvailableConnections())
			co.disconnect();
	}

	public void reportError(int code, String msg) {}
	public void reportErrorStartSession(String code) {}
	public void reportBadXml(String data) {}
	public void reportUnauthorizedHTTPResponse(String moreInfos) {}
	public void reportNotFoundHTTPResponse(String moreInfos) {}
	public void sessionConnected() {}
	public void updateProgress(LoadingStatus clientInstallApplication, int subStatus) {}

       public void switchBackFullscreenWindow() {
		System.out.println("switch back fullscreen window requested by javascript");
		Frame w = (Frame)((OvdClientDesktopApplet) this.ovd).getFullscreenWindow();
		w.setExtendedState(Frame.NORMAL);
       }
}
