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

import com.sshtools.j2ssh.SshClient;
import com.sshtools.j2ssh.authentication.AuthenticationProtocolState;
import com.sshtools.j2ssh.authentication.PasswordAuthenticationClient;
import com.sshtools.j2ssh.configuration.SshConnectionProperties;
import com.sshtools.j2ssh.forwarding.ForwardingIOChannel;
import com.sshtools.j2ssh.transport.HostKeyVerification;
import com.sshtools.j2ssh.transport.IgnoreHostKeyVerification;


/*
import com.sshtools.j2ssh.SshEventAdapter;

class SshHandler extends SshEventAdapter {
	protected Viewer e;
	public SshHandler(Viewer d) {
		super();
		this.e = d;
	}
	
	public void onSocketTimeout(TransportProtocol transport, boolean stillConnected) {
		System.out.println("OvdApplet onSocketTimeout ("+stillConnected+")");
		this.e.stop();
	}
	public void onDisconnect(TransportProtocol transport) {
		System.out.println("OvdApplet onDisconnect");
		this.e.stop();
	}
}
*/


public class SshConnection extends SshClient {
	private SshConnectionProperties ssh_properties;
	private PasswordAuthenticationClient pwd;
	
	protected String proxyType, proxyHost, proxyUsername, proxyPassword;
	protected int proxyPort;

	public SshConnection(String host, int port, String user, String password) {
		this.ssh_properties = new SshConnectionProperties();
		this.ssh_properties.setHost(host);
		this.ssh_properties.setPort(port);	

		this.pwd = new PasswordAuthenticationClient();
		this.pwd.setUsername(user);
		this.pwd.setPassword(Utils.DecryptString(password));


		this.setSocketTimeout(5000);
	}

	public void setProxy(String type, String host, int port, String username, String password) {
		this.ssh_properties.setPort(443); //Always use this when using proxy
		this.ssh_properties.setTransportProviderString(type);
		this.ssh_properties.setProxyHost(host);
		this.ssh_properties.setProxyPort(port);
		this.ssh_properties.setProxyUsername(username);
		this.ssh_properties.setProxyPassword(password);
	}

	public boolean connectStrict() {
		System.out.println("SshConnection connectStrict");

		try {
			HostKeyVerification hkv = new IgnoreHostKeyVerification();

			this.connect(this.ssh_properties, hkv);
		}
		// UnknownHostException, IOException 
		catch(Exception e) {
			System.err.println("SshConnection start: got exception");
			e.printStackTrace();
			this.disconnect();
			return false;
		}

		return true;
	}

	public boolean connect() {
		System.out.println("SshConnection start");

		try {
			HostKeyVerification hkv = new IgnoreHostKeyVerification();
			this.connect(this.ssh_properties, hkv);

			int result = this.authenticate(pwd);
			if (result != AuthenticationProtocolState.COMPLETE) {
				System.err.println("SshConnection failed");
				this.disconnect();
				return false;
			}

			System.out.println("SshConnection Authentication completed.");
		}
		// UnknownHostException, IOException 
		catch(Exception e) {
			System.err.println("SshConnection start: got exception");
			e.printStackTrace();
			this.disconnect();
			return false;
		}

		return true;
	}

	public ForwardingIOChannel createTunnel(int port) {
		System.out.println("SshConnection createTunnel "+port);
		ForwardingIOChannel channel;

		try {
			channel = new ForwardingIOChannel(ForwardingIOChannel.LOCAL_FORWARDING_CHANNEL, "d_"+port, 
					"localhost", port, 
					/* Useless parameters */ null, 0);
			if(! this.openChannel(channel)) {
				System.err.println("SshConnection: Unable to open Channel (localhost:"+port+")");
				return null;
			}

		}
		catch(Exception e) {
			System.err.println("SshConnection createTunnel: got exception");
			e.printStackTrace();
			return null;
		}

		return channel;
	}
}
