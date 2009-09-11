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


package org.ulteo;

import java.io.IOException;

import com.sshtools.j2ssh.SshClient;
import com.sshtools.j2ssh.authentication.AuthenticationProtocolState;
import com.sshtools.j2ssh.authentication.PasswordAuthenticationClient;
import com.sshtools.j2ssh.configuration.SshConnectionProperties;
import com.sshtools.j2ssh.connection.ChannelInputStream;
import com.sshtools.j2ssh.connection.ChannelOutputStream;
import com.sshtools.j2ssh.forwarding.ForwardingIOChannel;
import com.sshtools.j2ssh.transport.HostKeyVerification;
import com.sshtools.j2ssh.transport.ConsoleKnownHostsKeyVerification;

import javax.swing.JOptionPane;

public class OvdSshConnection extends java.applet.Applet {
    public static SshClient ssh;
    public String user,password,host;
    public int port = 22;
 	public SshConnectionProperties ssh_properties;

	protected String proxyType, proxyHost, proxyUsername, proxyPassword;
	protected int proxyPort;

	protected boolean stopped = false;

	public void init() {
		System.out.println("OvdSSHConnection init");

		ssh = new SshClient();
		ssh_properties = new SshConnectionProperties();

		readParameters();

		if(proxyHost != null && !proxyHost.equals("")) {
			this.ssh_properties.setPort(443); //Always use this when using proxy
			this.ssh_properties.setTransportProviderString(proxyType);
			this.ssh_properties.setProxyHost(proxyHost);
			this.ssh_properties.setProxyPort(proxyPort);
			this.ssh_properties.setProxyUsername(proxyUsername);
			this.ssh_properties.setProxyPassword(proxyPassword);
		}
	}

	public void start() {
		System.out.println("OvdSSHConnection start");

		try {
			ssh.setSocketTimeout(20000);

			HostKeyVerification hkv;
			try {
				hkv = new ConsoleKnownHostsKeyVerification();
			} catch (Exception e) {
				// hkv = new IgnoreHostKeyVerification();
				throw e;
			}
			ssh.connect(ssh_properties, hkv);

			PasswordAuthenticationClient pwd = new PasswordAuthenticationClient();
			pwd.setUsername(this.user);
			pwd.setPassword(this.password);
			// System.out.println("user: '"+this.user+"'\npassword: "+this.password);
			int result = ssh.authenticate(pwd);
			if(result==AuthenticationProtocolState.COMPLETE) {
				System.out.println("Authentication completed.");
			} else {
				System.out.println("SSh Authentication failed");
				this.stop();
				return;
			}
		}
		catch(Exception e) {
			System.out.println("ssh problem");
			e.printStackTrace();
			this.stop();
		}
	}

	public static boolean createTunnel(int port) {
		System.out.println("OvdSSHConnection createTunnel");
		ChannelInputStream in;
		ChannelOutputStream out;

		try {
			ForwardingIOChannel channel = new ForwardingIOChannel(ForwardingIOChannel.LOCAL_FORWARDING_CHANNEL,
															  "VNC","localhost", port,"0.0.0.0", port+10);
			if(! ssh.openChannel(channel)) {
				System.err.println("Unable to open Channel");
				return false;
			}

			System.out.println("Channel open");
			in = channel.getInputStream();
			out = channel.getOutputStream();
		}
		catch(Exception e) {
			System.out.println("ssh tunnel problem");
			e.printStackTrace();
			return false;
		}

		return true;
    }
   
	public void stop() {
		if (stopped)
			return;
		stopped = true;
		System.out.println("OvdSSHConnection stop");

		ssh.disconnect();
    }

   
	public void destroy() {
		System.out.println("SSHVnc destroy");
	}



	public void readParameters() {
		String buf;

		//this.ssh.host = getParameter("ssh.host");
		this.ssh_properties.setHost(getParameter("ssh.host"));

		String[] buffer = getParameter("ssh.port").split(",");
		if (buffer.length == 0) {
			System.err.println("no port given");
			stop();
		}
		try {
			// this.ssh.port = Integer.parseInt(buffer[0]);
			this.ssh_properties.setPort(Integer.parseInt(buffer[0]));
		} catch(NumberFormatException e) {}

		this.user = getParameter("ssh.user");
		this.password = Utils.DecryptString(getParameter("ssh.password"));

		// Read proxy parameters, if any -- by ArnauVP
		proxyType = getParameter("proxyType");
		proxyHost = getParameter("proxyHost");
		try {
			proxyPort = Integer.parseInt(getParameter("proxyPort"));
		} catch(NumberFormatException e) {}

		proxyUsername = getParameter("proxyUsername");
		proxyPassword = getParameter("proxyPassword");
    }

    void showMessage(String msg) {
		//vncContainer.removeAll();
		JOptionPane.showMessageDialog(this, "The Online Desktop has closed.\n" +
				      "Thanks for using our service!\n", "Online Desktop session finished",JOptionPane.INFORMATION_MESSAGE);
		System.err.println("ERROR: "+msg+"\n");
    }
  
    public String getAppletInfo() {
		return "UlteoVNC";
    }
}
