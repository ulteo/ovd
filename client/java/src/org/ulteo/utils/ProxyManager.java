/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
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

package org.ulteo.utils;

import java.io.IOException;
import java.net.InetSocketAddress;
import java.net.Proxy;
import java.net.ProxySelector;
import java.net.Socket;
import java.net.URI;
import java.net.URISyntaxException;
import java.util.List;

import net.propero.rdp.RdesktopException;

import org.apache.commons.httpclient.HttpStatus;
import org.apache.commons.httpclient.ProxyClient;
import org.apache.commons.httpclient.UsernamePasswordCredentials;
import org.apache.commons.httpclient.auth.AuthScope;

import org.ulteo.Logger;
import org.ulteo.ovd.client.profile.ProfileProperties;

public class ProxyManager {
	private String host = null;
	private int port = -1;
	private String login = null;
	private String password = null;
	private boolean useProxy = false;


	public ProxyManager() {
		this.host = System.getProperty("https.proxyHost");
		try {
			this.port = Integer.parseInt(System.getProperty("https.proxyPort"));
		}
		catch (NumberFormatException e) {
			this.port = -1;
		}
		this.useProxy = (this.host != null) && (this.port != -1);
		
		this.login = System.getProperty("https.proxyUsername");
		this.password = System.getProperty("https.proxyPassword");
	}

	public boolean available() {
		return this.useProxy;
	}
	
	public void detect(String remoteHost) {
		Logger.debug("Detect proxy");
		List<Proxy> proxyList = null;
		System.setProperty("java.net.useSystemProxies", "true");
		ProxySelector ps = ProxySelector.getDefault();
		
		if (ps == null) {
			Logger.warn("Unable to detect a proxy: no proxySelector available");
			return;
		}
		try {
			URI uri = new URI("https://"+remoteHost);
			proxyList = ps.select(uri);
		}
		catch (URISyntaxException e) {
			Logger.warn("Unable to detect a proxy: bad URL");
			return ;
		}
		if (proxyList != null) {
			for (Proxy proxy: proxyList) {
				InetSocketAddress addr = (InetSocketAddress) proxy.address();
				if (addr != null) {
					this.host = addr.getHostName();
					this.port = addr.getPort();
					System.setProperty("https.proxyHost", this.host);
					System.setProperty("https.proxyPort", new Integer(this.port).toString());
					Logger.debug("using proxy["+this.host+":"+this.port+"]");
					break;
				}
			}
		}
	}
	
	
	public boolean updateProxy(ProfileProperties properties) {
		String proxyHost = properties.getProxyHost();
		String proxyPort = properties.getProxyPort();

		if (properties.getProxyUsername() != null) {
			this.login = properties.getProxyUsername();
			this.password = properties.getProxyPassword();
		}

		switch (properties.getProxyType()) {
		case auto:
			Logger.debug("Using auto proxy");
			this.detect(properties.getHost());
			break;
			
		case custom:
			Logger.debug("Using custom proxy");
			this.host = proxyHost;
			System.setProperty("https.proxyHost", proxyHost);
			try {
				this.port = new Integer(proxyPort);
				System.setProperty("https.proxyPort", proxyPort);
			}
			catch (NumberFormatException e) {
				Logger.warn("Invalid proxy port in the custom configuration");
				this.useProxy = false;
				this.host = null;
				this.port = -1;
				System.setProperty("https.proxyHost", "");
				System.setProperty("https.proxyPort", "");
			}
			break;
			
		case none:
			Logger.debug("No proxy used");
			this.host = null;
			this.port = -1;
			this.useProxy = false;
			System.setProperty("https.proxyHost", "");
			System.setProperty("https.proxyPort", "");
			break;	
		}
		return true;
	}
	
	
	public Socket connect(String remoteHost, int remotePort) throws IOException,RdesktopException {
		ProxyClient proxyClient = new ProxyClient();
        
		Logger.debug("Connecting to proxy["+this.host+":"+this.port+"]");
        
		proxyClient.getHostConfiguration().setProxy(this.host, this.port);
		proxyClient.getHostConfiguration().setHost(remoteHost, remotePort);

		if (this.login != null && this.password != null) {
			Logger.debug("Use credentials");
			proxyClient.getState().setProxyCredentials(AuthScope.ANY, new UsernamePasswordCredentials(this.login, this.password));
		}
		Logger.debug("Connected to proxy["+this.host+":"+this.port+"]");
		ProxyClient.ConnectResponse resp = proxyClient.connect();

		if (resp.getConnectMethod().getStatusCode() != HttpStatus.SC_OK) {
			Logger.debug("Failed to connect to proxy["+this.host+":"+this.port+"]" + resp.getConnectMethod().getStatusLine());
			return null;
		}
		Logger.debug("Proxy OK");
		return resp.getSocket();
	}
}
