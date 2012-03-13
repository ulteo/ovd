/*
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2012
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

package net.propero.rdp;

import java.io.IOException;
import java.net.Socket;
import java.security.PublicKey;
import java.security.SecureRandom;
import java.security.cert.Certificate;
import java.security.cert.CertificateException;
import java.security.cert.X509Certificate;

import javax.net.ssl.SSLContext;
import javax.net.ssl.SSLPeerUnverifiedException;
import javax.net.ssl.SSLSession;
import javax.net.ssl.SSLSocket;
import javax.net.ssl.SSLSocketFactory;
import javax.net.ssl.TrustManager;
import javax.net.ssl.X509TrustManager;

import org.ulteo.Logger;

public class TLSLayer {
	private SSLSocket sslSocket;
	
	
	TrustManager[] trustAllCerts = new TrustManager[] { new X509TrustManager() {

		public X509Certificate[] getAcceptedIssuers() {
			return new X509Certificate[0];
		}

		public void checkServerTrusted(X509Certificate[] certs, String authType) throws CertificateException {
			return;
		}

		public void checkClientTrusted(X509Certificate[] certs, String authType) throws CertificateException {
			return;
		}
	}};
	
	
	Socket initTransportLayer(Socket rdpSocket, String host, int port) throws IOException {
		try {
			SSLContext ctx = SSLContext.getInstance("TLSv1");
				
			ctx.init(null, trustAllCerts, new SecureRandom());
			SSLSocketFactory ssf = ctx.getSocketFactory();
			
			Logger.debug("TLS connect to " + host + ":" + port);
			sslSocket = (SSLSocket)ssf.createSocket(rdpSocket, host, port, true);
				
			sslSocket.startHandshake();
			
			SSLSession sslSession = sslSocket.getSession();
			sslSession.getPeerCertificates();
			if (sslSession == null)
				throw new IOException("Error while etablishing a TLS connection: Unable to establish ssl session");
			
		} catch (Exception e) {
			throw new IOException("Unable to etablish a TLS connection: "+e.getMessage());
		}

		return sslSocket;
	}
	
	public PublicKey getSessionPublicKey() {
		SSLSession sslSession = sslSocket.getSession();
		if (sslSession == null) {
			Logger.error("Error while etablishing a TLS connection: Unable to establish ssl session");
			return null;
		}
		
		Certificate[] certificates = null;
		try {
			certificates = sslSession.getPeerCertificates();
		} catch (SSLPeerUnverifiedException e) {
			Logger.error("Unable to get Public Key "+e.getMessage());
			return null;
		}

		if (certificates != null && certificates.length != 0)
			return certificates[0].getPublicKey();
		else
			Logger.error("Error while etablishing a TLS connection: No certificate delivered by the server");

		return null;
	}
}
