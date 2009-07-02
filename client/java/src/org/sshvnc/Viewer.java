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
import java.awt.Frame;
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;
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


public class Viewer extends org.vnc.SimpleClient.Viewer {
    public SshClient ssh;
    public String sshUser,sshPassword,sshHost;
    public int sshPort = 22;
    public ForwardingIOChannel channel;
	public int vncPort;
	public String vncPassword;
	public SshConnectionProperties ssh_properties;

    protected ChannelInputStream in; /*InputStream*/
    protected ChannelOutputStream out; /*OutputStream*/ 

	public Viewer() {
		super();
		ssh = new SshClient();
		ssh_properties = new SshConnectionProperties();
		this.window.setTitle("SshVNC");
	}

	public Viewer(Container c) {
		super(c);
		ssh = new SshClient();
		ssh_properties = new SshConnectionProperties();
	}

	public void process_init() {
		if (! ssh_start())
			return;

		this.vnc.setInOut(in, out);
		
		super.process_init();
	}

    public boolean ssh_start() {
		try {
			ssh.setSocketTimeout(20000);
			// Create SSH properties
			
			ssh_properties.setHost(sshHost);
			ssh_properties.setPort(sshPort);

			HostKeyVerification hkv;
			try {
				hkv = new ConsoleKnownHostsKeyVerification();
			} catch (Exception e) {
				// hkv = new IgnoreHostKeyVerification();
				throw e;
			}
			ssh.connect(ssh_properties, hkv);

			PasswordAuthenticationClient pwd = new PasswordAuthenticationClient();
			pwd.setUsername(sshUser);
			pwd.setPassword(sshPassword);
			System.out.println("user: '"+sshUser+"'\npassword: "+sshPassword);
			int result = ssh.authenticate(pwd);
			if(result==AuthenticationProtocolState.COMPLETE) {
				System.out.println("Authentication completed.");
			} else {
				System.out.println("SSh Authentication failed");
				ssh_stop();
				return false;
			}
			int vncServerPort = vncPort;
			int vncLocalPort = vncServerPort+10;

			channel = new ForwardingIOChannel(ForwardingIOChannel.LOCAL_FORWARDING_CHANNEL,
											  "VNC","localhost",vncServerPort,"0.0.0.0",vncLocalPort);
			if(ssh.openChannel(channel)){
				System.out.println("Channel open");
				in = channel.getInputStream();
				out = channel.getOutputStream();
			}
		} catch(Exception e) {
			System.out.println("ssh tunnel problem");
			e.printStackTrace();
			ssh_stop();
			return false;
		}

		return true;
    }

    public void ssh_stop() {
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

	public String vncGetPassword() {
		return this.vncPassword;
	}

	public void windowClosing(WindowEvent evt) {
		super.windowClosing(evt);
		ssh_stop();
	}

/*	public void setVncContainer(Container c) {
		this.vnc.container = c;
	}
*/

	public static void usage() {
		System.err.println("Usage: "+Viewer.class.getName()+" vncport vncpassword login:password@host[:port]");
    }

	public static boolean parse_commandline(Viewer v, String[] argv) {
		if (argv.length < 3) {
			System.err.println("Missing argumet");
			return false;
		}

		try {
			v.vncPort = Integer.parseInt(argv[0]);
			v.vncPassword = argv[1];

			String[] buffer = argv[2].split("@", 2);
			String[] buffer2 = buffer[0].split(":", 2);
			v.sshUser = buffer2[0];
			v.sshPassword = buffer2[1];

			buffer2 = buffer[1].split(":", 2);
			v.sshHost = buffer2[0];
			if (buffer2.length == 2)
				v.sshPort = Integer.parseInt(buffer2[1]);
		} catch(Exception e) {
			System.err.println("Invalid argument");
			return false;
		}

		return true;
	}

    public static void main(String[] args) {
		Viewer v = new Viewer();

		if (! parse_commandline(v, args)) {
			usage();
			System.exit(1);
		}

		v.process_init();
		v.loop();
    }
}


