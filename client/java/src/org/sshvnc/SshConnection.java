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


public class SshConnection {
    public SshClient ssh;
    public String user,password,host;
    public int port = 22;
    public ForwardingIOChannel channel;
	public int vncPort;
	public String vncPassword;
	public SshConnectionProperties ssh_properties;

    public ChannelInputStream in;
    public ChannelOutputStream out;

	public SshConnection() {
		ssh = new SshClient();
		ssh_properties = new SshConnectionProperties();
	}

    public boolean start() {
		try {
			ssh.setSocketTimeout(20000);
			// Create SSH properties
			
			ssh_properties.setHost(this.host);
			ssh_properties.setPort(this.port);

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

			int result = ssh.authenticate(pwd);
			if(result==AuthenticationProtocolState.COMPLETE) {
				System.out.println("ssh: Authentication completed.");
			} else {
				System.out.println("ssh: Authentication failed");
				return false;
			}
			int vncServerPort = vncPort;
			int vncLocalPort = vncServerPort+10;

			channel = new ForwardingIOChannel(ForwardingIOChannel.LOCAL_FORWARDING_CHANNEL,
											  "VNC","localhost",vncServerPort,"0.0.0.0",vncLocalPort);
			if(! ssh.openChannel(channel)) {
				System.err.println("ssh: Unable to open Channel");
				return false;
			}

			in = channel.getInputStream();
			out = channel.getOutputStream();
		}
		catch(Exception e) {
			System.out.println("ssh: tunnel problem");
			e.printStackTrace();
			this.stop();
			return false;
		}

		return true;
    }

    public void stop() {
		if(in != null && out != null){
			try{
				in.close();
				out.close();
			}catch(IOException ioe){
				System.err.println("Problem closing IO streams");
			}
		}

		channel = null;
		ssh.disconnect();
    }
}


