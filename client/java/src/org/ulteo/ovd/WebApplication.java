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

package org.ulteo.ovd;

import java.io.UnsupportedEncodingException;
import java.net.MalformedURLException;
import java.net.URI;
import java.net.URISyntaxException;
import java.net.URL;
import java.net.URLEncoder;

import javax.swing.ImageIcon;

import org.ulteo.ovd.sm.IWebAppActivationHandler;
import org.ulteo.ovd.sm.WebAppsServerAccess;

public class WebApplication extends Application {
	
	protected final URI openUri;
	protected final WebAppsServerAccess webAppsServerAccess;

	public WebApplication (final org.ulteo.ovd.sm.Application app, final String url, final String login, final String password, final ImageIcon icon_, final WebAppsServerAccess webAppsServerAccess) {
		super(null, app.getId(), app.getName(), app.getMimes(), icon_);
		this.webAppsServerAccess = webAppsServerAccess;
		this.openUri = getOpenURI(url, app.getId(), login, password);
	}
	
	private URI getOpenURI(final String url, final int appId, final String login, final String password) {
		final String uri;
		try {
			uri = url + "/open?id=" + appId + "&user=" + URLEncoder.encode(login, "UTF-8") + "&pass=" + URLEncoder.encode(password, "UTF-8");
		} catch (UnsupportedEncodingException e) {
			throw new RuntimeException(e);
		}
		try {
			return new URI(uri);
		} catch (URISyntaxException e) {
			throw new RuntimeException(e);
		}
	}
	
	public boolean isWebApp() {
		return true;
	}
	
	public URI getOpenURI() {
		return openUri;
	}
	
	public void addActivationHandler(IWebAppActivationHandler handler) {
		this.webAppsServerAccess.addActivationHandler(handler);
	}

}
