/*
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2009-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
 * Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
 * Author David LECHEVALIER <david@ulteo.com> 2011
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

import org.ulteo.ovd.client.OvdClient;
import org.ulteo.ovd.client.OvdClientDesktop;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;

public class Desktop extends OvdApplet implements FocusListener {

	private boolean fullscreenMode = false;
	private ServerAccess aps = null;
	
	@Override
	protected void _init() {
		((OvdClientDesktopApplet)this.ovd).createRDPConnection(this.aps);
		
		this.setLayout(new BorderLayout());
	}
	
	
	@Override
	protected void _start() {
		((OvdClientDesktop)this.ovd).adjustDesktopSize();
	}
	
	@Override
	protected void _stop() {}
	
	@Override
	protected void _destroy() {
		this.aps = null;
	}
	
	@Override
	protected int getMode() {
		return Properties.MODE_DESKTOP;
	}
	
	@Override
	public void readParameters() throws Exception {
		Integer port;
		try {
			port = Integer.parseInt(this.getParameterNonEmpty("port"));
		}
		catch (NumberFormatException e) {
			throw new Exception("Unable to get valid port from applet parameters: "+e.getMessage());
		}
		
		this.aps = new ServerAccess(this.getParameterNonEmpty("server"), port, 
				this.getParameterNonEmpty("username"), this.getParameterNonEmpty("password"));
		this.aps.setGatewayToken(this.getParameter("token"));
		
		if (this.getParameter("fullscreen") != null)
			this.fullscreenMode = true;
	}
	
	@Override
	protected OvdClient createOvdClient(Properties properties) {
		OvdClientDesktopApplet client = new OvdClientDesktopApplet(properties, this);
		client.setFullscreen(this.fullscreenMode);
		return client;
	}
	
	// FocusListener's method interface

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

	// Methods called by Javascript
	
	/**
	 * switch back fullscreen window requested by javascript
	 */
	public void switchBackFullscreenWindow() {
		System.out.println("switch back fullscreen window requested by javascript");
		Frame w = (Frame) ((OvdClientDesktopApplet) this.ovd).getFullscreenWindow();
		w.setExtendedState(Frame.NORMAL);
	}
}
