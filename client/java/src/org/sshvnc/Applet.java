/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
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

package org.sshvnc;

import java.awt.Container;
import java.awt.Label;
import java.awt.Color;
import java.awt.Frame;

import org.vnc.VncClient;


public class Applet extends java.applet.Applet implements org.vnc.Dialog {
	private boolean stopped = false;

	public VncClient vnc;
	protected SshConnection ssh;

	protected String vncPassword = null;

	//Proxy parameters
	public String proxyType,proxyHost,proxyUsername,proxyPassword;
	public int proxyPort;

	public void init() {
		System.out.println("SSHVnc init");
		vnc = new VncClient(this, this);
		ssh = new SshConnection();

		readParameters();

		if(proxyHost != null && !proxyHost.equals("")) {
			this.ssh.ssh_properties.setTransportProviderString(proxyType);
			this.ssh.port = 443; //Always use this when using proxy
			this.ssh.ssh_properties.setProxyHost(proxyHost);
			this.ssh.ssh_properties.setProxyPort(proxyPort);
			this.ssh.ssh_properties.setProxyUsername(proxyUsername);
			this.ssh.ssh_properties.setProxyPassword(proxyPassword);
		}
	}

	public void start() {
		System.out.println("SSHVnc start");

		if (! this.ssh.start())
			stop();
		
		this.vnc.setInOut(this.ssh.in, this.ssh.out);
	
		if (! this.vnc.connect())
			stop();

		if (! this.vnc.authenticate())
			stop();

		if (! vnc.init())
			stop();

		vnc.start_background_process();
		System.out.println("Session start");
	}
   
	public void stop() {
		if (stopped)
			return;
		stopped = true;

		System.out.println("SSHVnc stop");
		this.vnc.stop();
		this.ssh.stop();
	}
   
	public void destroy() {
		System.out.println("SSHVnc destroy");

		proxyType = null;
		proxyHost = null;
		proxyUsername = null;
		proxyPassword = null;

		vnc = null;
		ssh = null;

		vncPassword = null;
	}

	public void readParameters() {
		this.ssh.host = getParameter("ssh.host");
		try {
			this.ssh.port = Integer.parseInt(getParameter("ssh.port"));
		} catch(NumberFormatException e) {}
		this.ssh.user = getParameter("ssh.user");
		this.ssh.password = getParameter("ssh.password");

		// Read proxy parameters, if any -- by ArnauVP
		proxyType = getParameter("proxyType");
		proxyHost = getParameter("proxyHost");
		try {
			proxyPort = Integer.parseInt(getParameter("proxyPort"));
		} catch(NumberFormatException e) {}

		proxyUsername = getParameter("proxyUsername");
		proxyPassword = getParameter("proxyPassword");

		try {
			this.ssh.vncPort = Integer.parseInt(getParameter("vnc.port"));
		} catch(NumberFormatException e) {}


		this.vncPassword = getParameter("vnc.password");
    }

	// Begin Implements org.vnc.Dialog
	public String vncGetPassword() {
		return this.vncPassword;
	}
	
	public void vncSetError(String err) {
		System.err.println("Vnc error: " + err);
		stop();
	}
	// End Implements org.vnc.Dialog
}
