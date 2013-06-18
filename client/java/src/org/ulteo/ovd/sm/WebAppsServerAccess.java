/*
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
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

package org.ulteo.ovd.sm;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.UnsupportedEncodingException;
import java.net.MalformedURLException;
import java.net.URL;
import java.net.URLEncoder;
import java.util.ArrayList;
import java.util.List;

import org.ulteo.Logger;
import org.ulteo.ovd.WebApplication;

public class WebAppsServerAccess extends ServerAccess implements Runnable {

	private final String url;
	private final List<WebApplication> webApplications;
	private final List<IWebAppActivationHandler> handlers;
	private boolean initialized = false;

	public WebAppsServerAccess(final String host, final int port, final String login, final String password, final String url) {
		super(host, port, login, password);
		this.url = url;
		this.webApplications = new ArrayList<WebApplication>();
		this.handlers = new ArrayList<IWebAppActivationHandler>();
	}
	
	public void setApplications(List<org.ulteo.ovd.sm.Application> apps) {
		this.applications = apps;
		this.webApplications.clear();
		for (final org.ulteo.ovd.sm.Application app : apps) {
			final WebApplication webApp = new WebApplication(app, this.getWebAppsUrl(), this.getLogin(), this.getPassword(), null, this);
			this.webApplications.add(webApp);
		}
	}

	public String getWebAppsUrl() {
		return url;
	}

	/**
	 * Check if this server is accessed through RDP.
	 * 
	 * @return true if this server needs RDP connection.
	 */
	public boolean isRDP() {
		// Web apps are accessed through system browser.
		return false;
	}
	
	public List<WebApplication> getWebApplications() {
		return this.webApplications;
	}

	private URL getConnectURL() {
		final String uri;
		try {
			uri = url + "/connect?id=0&user=" + URLEncoder.encode(getLogin(), "UTF-8") + "&pass=" + URLEncoder.encode(getPassword(), "UTF-8");
		} catch (UnsupportedEncodingException e) {
			throw new RuntimeException(e);
		}
		try {
			return new URL(uri);
		} catch (MalformedURLException e) {
			throw new RuntimeException(e);
		}
	}
	
	public void addActivationHandler(IWebAppActivationHandler handler) {
		this.handlers.add(handler);
		if (!initialized) {
			initialized = true;
			new Thread(this).start();
		}
	}
	
	private boolean activate() {
		final URL url = getConnectURL();
		Logger.info("Connect URL " + url.toString());
		
		// TODO: remove from production code!
		javax.net.ssl.HttpsURLConnection.setDefaultHostnameVerifier(
			new javax.net.ssl.HostnameVerifier() {
					public boolean verify(String hostname,
							javax.net.ssl.SSLSession sslSession) {
						return true;
					}
				});

		try {
			final InputStream urlStream = url.openStream();
			final BufferedReader in = new BufferedReader(new InputStreamReader(urlStream));

			String inputLine;
			while ((inputLine = in.readLine()) != null) {
				/* consume all input */
				Logger.debug("Connect content: " + inputLine);
			}
			in.close();
			Logger.info("Server " + url.toString() + " activated");
			return true;
		} catch (IOException e) {
			return false;
		}
	}
	
	public void run() {
		final boolean active = activate();
		for (IWebAppActivationHandler handler : handlers) {
			handler.handle(active);
		}
	}

}
