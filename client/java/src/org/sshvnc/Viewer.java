/**
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
 **/

package org.sshvnc;

import java.awt.Container;
import java.io.IOException;

import org.vnc.ArgParser;
import org.vnc.RfbProto;
import org.vnc.VncViewer;
import org.vnc.Options;

import com.sshtools.j2ssh.SshClient;
import com.sshtools.j2ssh.authentication.AuthenticationProtocolState;
import com.sshtools.j2ssh.authentication.PasswordAuthenticationClient;
import com.sshtools.j2ssh.configuration.SshConnectionProperties;
import com.sshtools.j2ssh.connection.ChannelInputStream;
import com.sshtools.j2ssh.connection.ChannelOutputStream;
import com.sshtools.j2ssh.forwarding.ForwardingIOChannel;

import com.sshtools.j2ssh.transport.HostKeyVerification;
import com.sshtools.j2ssh.transport.ConsoleKnownHostsKeyVerification;


public class Viewer extends VncViewer{
    public SshClient ssh;
    public String sshUser,sshPassword,sshHost;
    public String portList;
    public ForwardingIOChannel channel;

    //Proxy parameters
    public String proxyType,proxyHost,proxyUsername,proxyPassword;
    public int proxyPort;

    protected ChannelInputStream in; /*InputStream*/
    protected ChannelOutputStream out; /*OutputStream*/ 

    public Viewer() { super(); }
    public Viewer(Container c) { super(c); }

    public static void main(String[] argv) {
	Viewer v = new Viewer();
	v.arg_parser = new ArgParser(argv);
	v.readParameters();

	v.init();
	v.start();
    }

    public void init() {
	try {
	    ssh = new SshClient();
	    ssh.setSocketTimeout(20000);
	    // Create SSH properties
	    SshConnectionProperties properties = new SshConnectionProperties();
	    properties.setHost(sshHost);
	    String[] sTemp = portList.split(",");
	    int[] arrayPorts = new int[sTemp.length];
	    for(int i=0; i<sTemp.length; i++){
		try{
		    arrayPorts[i] = Integer.parseInt(sTemp[i]);
		    properties.setPort(arrayPorts[i],i);
		}catch(NumberFormatException nfe){
		    System.err.println("One of the entered ports is not valid "+sTemp[i]);
		    throw nfe;
		}
	    }

	    if(proxyHost != null && !proxyHost.equals("")){
		properties.setTransportProviderString(proxyType);
		properties.setPort(443); //Always use this when using proxy
		properties.setProxyHost(proxyHost);
		properties.setProxyPort(proxyPort);
		properties.setProxyUsername(proxyUsername);
		properties.setProxyPassword(proxyPassword);
	    }

	    HostKeyVerification hkv;
	    try {
		hkv = new ConsoleKnownHostsKeyVerification();
	    } catch (Exception e) {
		// hkv = new IgnoreHostKeyVerification();
		throw e;
	    }
	    ssh.connect(properties, hkv);

	    PasswordAuthenticationClient pwd = new PasswordAuthenticationClient();
	    pwd.setUsername(sshUser);
	    pwd.setPassword(sshPassword);
	    int result = ssh.authenticate(pwd);
	    if(result==AuthenticationProtocolState.COMPLETE) {
		System.out.println("Authentication completed.");
	    } else {
		System.out.println("Authentication failed");
	    }
	    int vncServerPort = Options.port;
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
	    stop();
	    return;
	}

	System.out.println("sshvnc init 1");
	super.init();
	System.out.println("sshvnc init 2");
    }

    protected boolean connectAndAuthenticate() throws Exception {
	rfb = new RfbProto(in, out, this);
	return super.connectAndAuthenticate();
    }

    public void readParameters() {
		String buffer;

	sshHost = readParameter("ssh.host", true);
	sshUser = readParameter("ssh.user", true);
	// ArnauVP: we read the whole list and we'll parse it later
	portList = readParameter("ssh.port", true);
	sshPassword = readParameter("ssh.password", true);


	// Read proxy parameters, if any -- by ArnauVP
	proxyType = readParameter("proxyType", false);
	proxyHost = readParameter("proxyHost", false);
	buffer = readParameter("proxyPort", false);
	if (buffer != null) {
		try {
			proxyPort = Integer.parseInt(buffer);
		} catch(NumberFormatException e) {}
	}
	proxyUsername = readParameter("proxyUsername", false);
	proxyPassword = readParameter("proxyPassword", false);

	buffer = readParameter("PORT", true);
	org.vnc.Options.port = Integer.parseInt(buffer);

	buffer = readParameter("GEOMETRY", true);
	if (buffer != null) {
		try {
			int cut = buffer.indexOf("x", 0);
			org.vnc.Options.width = Integer.parseInt(buffer.substring(0, cut));
			org.vnc.Options.height = Integer.parseInt(buffer.substring(cut + 1));
		} catch(Exception e) {
			System.err.println("GEOMETRY parsing error");
		}
	}

	Options.password = readParameter("PASSWORD", true);
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

	super.stop();
	channel = null;
	ssh.disconnect();
    }


}


