/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
 * Author Arnaud LEGRAND <arnaud@ulteo.com> 2010
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

package org.ulteo.rdp;
import java.io.IOException;
import java.net.ProxySelector;
import java.net.Socket;
import java.security.cert.CertificateException;
import java.security.cert.X509Certificate;
import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.SSLContext;
import javax.net.ssl.SSLSession;
import javax.net.ssl.SSLSocket;
import javax.net.ssl.SSLSocketFactory;
import javax.net.ssl.TrustManager;
import javax.net.ssl.X509TrustManager;

import org.ulteo.utils.ProxyManager;

import net.propero.rdp.RdesktopException;
import net.propero.rdp.SocketFactory;

public class TCPSSLSocketFactory implements SocketFactory {
	private String host;
	private int port;

	public TCPSSLSocketFactory (String host, int port) {
		this.host = host;
		this.port = port;
	}

	public Socket createSocket() throws IOException,RdesktopException {
		Socket rdpsock= null;
		ProxyManager proxy = new ProxyManager();
		if (! proxy.available())
			proxy.detect(this.host);
		
		// Do not let java use a proxy 
		ProxySelector ps = ProxySelector.getDefault();
		ProxySelector.setDefault(null);
		
		TrustManager[] trustAllCerts = new TrustManager[] { new X509TrustManager() {

			public X509Certificate[] getAcceptedIssuers() {
				return null;
			}

			public void checkServerTrusted(X509Certificate[] certs,
					String authType) throws CertificateException {
				return;
			}

			public void checkClientTrusted(X509Certificate[] certs,
					String authType) throws CertificateException {
				return;
			}
		}};

		HostnameVerifier trustAllHosts = new HostnameVerifier() {
			public boolean verify(String hostname, SSLSession session) {
				return true;
			}
		};
		
		try {
			SSLContext sc = SSLContext.getInstance("SSLv3");
			sc.init(null, trustAllCerts, null);
			SSLSocketFactory ssf = sc.getSocketFactory();
			if (proxy.available()) {
				Socket proxySock = proxy.connect(this.host, this.port);
				if (proxySock == null)
					throw new RdesktopException("Creating SSL context failed: Unable to open a connection to the proxy");
				
				rdpsock = ssf.createSocket(proxySock, this.host, this.port, false);
			} 
			else {
				rdpsock = ssf.createSocket(this.host, this.port);
			}
			SSLSession session = ((SSLSocket) rdpsock).getSession();
		} catch (Exception e) {
			throw new RdesktopException("Creating SSL context failed:" + e.getMessage());
		}
		finally {
			ProxySelector.setDefault(ps);
		}
		return rdpsock;
	}
}
