/*
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2009-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
 * Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
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
import java.awt.event.FocusEvent;
import java.awt.event.FocusListener;

import org.ulteo.ovd.client.OvdClientDesktop;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.rdp.RdpConnectionOvd;

public class Desktop extends OvdApplet implements FocusListener {

	protected int port = 0;
	protected String server = null;
	private String username = null;
	private String password = null;
	private String token = null;
	private boolean fullscreenMode = false;
	
	private RdpConnectionOvd rc = null;
	
	@Override
	protected void _init(Properties properties) {
		this.ovd = new OvdClientDesktopApplet(properties, this);
		this.ovd.setKeymap(this.keymap);
		((OvdClientDesktopApplet)this.ovd).setFullscreen(this.fullscreenMode);

		ServerAccess aps = new ServerAccess(this.server, this.port, this.username, this.password);
		aps.token = this.token;
		this.rc = ((OvdClientDesktopApplet)this.ovd).createRDPConnection(aps);
		
		BorderLayout layout = new BorderLayout();
		this.setLayout(layout);
	}
	
	
	@Override
	protected void _start() {	
		((OvdClientDesktop)this.ovd).adjustDesktopSize(this.rc);
	}
	
	@Override
	protected void _stop() {}
	
	@Override
	protected void _destroy() {
		this.server = null;
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
		String strPort = this.getParameterNonEmpty("port");
		try {
			this.port = Integer.parseInt(strPort);
		}
		catch (NumberFormatException e) {
			throw new Exception("Unable to get valid port from applet parameters: "+e.getMessage());
		}
		
		if (this.getParameter("fullscreen") != null)
			this.fullscreenMode = true;
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

}
