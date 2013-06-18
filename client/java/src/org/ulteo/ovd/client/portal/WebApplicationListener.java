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

package org.ulteo.ovd.client.portal;

import java.awt.Desktop;
import java.awt.event.ActionEvent;
import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.net.URI;
import java.net.URL;

import org.ulteo.Logger;
import org.ulteo.ovd.WebApplication;
import org.ulteo.ovd.sm.IWebAppActivationHandler;

public class WebApplicationListener extends ApplicationListener {

	public WebApplicationListener(final WebApplication app, final ApplicationLink link) {
		super(app);
		app.addActivationHandler(new IWebAppActivationHandler() {
			
			@Override
			public void handle(final boolean active) {
				link.setEnabled(active);
			}
		});
	}

	@Override
	public void actionPerformed(ActionEvent event) {
		Logger.info("Opening web app " + this.app.getName());
		final URI uri = ((WebApplication)app).getOpenURI();
		
		Logger.info("URI " + uri.toString());

		try {
			Desktop.getDesktop().browse(uri);
		} catch (IOException e) {
			Logger.error("Cannot open web app URI");
			// Ignore the error. TODO: maybe display error message?
			throw new RuntimeException(e);
		}
	}

}
