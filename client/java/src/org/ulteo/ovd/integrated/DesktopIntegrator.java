/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2011
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

package org.ulteo.ovd.integrated;

import java.util.ArrayList;
import java.util.Collections;
import java.util.HashMap;
import java.util.List;

import javax.swing.ImageIcon;

import org.ulteo.Logger;
import org.ulteo.ovd.Application;
 import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.rdp.RdpConnectionOvd;

public class DesktopIntegrator extends Thread {
	private SystemAbstract system = null;
	private List<RdpConnectionOvd> connections = null;
	private SessionManagerCommunication sm = null;

	private List<DesktopIntegrationListener> listeners = null;

	private List<RdpConnectionOvd> integratedConnections = null;

	public DesktopIntegrator(SystemAbstract system_, List<RdpConnectionOvd> connections_, SessionManagerCommunication sm_) {
		if (system_ == null || connections_ == null || sm_ == null)
			throw new NullPointerException("'DesktopIntegrator' does not accept a null parameter in constructor");
		
		this.system = system_;
		this.connections = connections_;
		this.sm = sm_;

		this.listeners = Collections.synchronizedList(new ArrayList<DesktopIntegrationListener>());
		this.integratedConnections = Collections.synchronizedList(new ArrayList<RdpConnectionOvd>());
	}

	@Override
	public void run() {
		// generate shortcuts
		for (RdpConnectionOvd rc : this.connections) {
			for (Application app : rc.getAppsList()) {
				if (this.system.create(app) == null)
					Logger.error("The "+app.getName()+" shortcut could not be created");
			}
			this.integratedConnections.add(rc);
			this.fireShortcutGenerationIsDone(rc);
		}
		
		// download mimetypes icons
		for (RdpConnectionOvd rc : this.connections) {
			HashMap<String, ImageIcon> mime_types = getMimeTypes(rc);
			int updatedIcons = this.system.updateMimeTypesIconsCache(mime_types);
			if (updatedIcons > 0)
				Logger.info("Mime-types cache updated: "+updatedIcons+" icons");
		}
		this.system.refresh();
	}

	/**
	 * get all mime-type needed
	 * @param rc
	 * 		the {@link RdpConnectionOvd} rc object to get mime-types 
	 * @return
	 * 		all new mime-types to update
	 */
	private HashMap<String, ImageIcon> getMimeTypes(RdpConnectionOvd rc) {
		if (rc == null)
			throw new NullPointerException("'generateMimeTypes' cannot receive a null paramater");
			
		HashMap<String, ImageIcon> mimeTypesIcons = new HashMap<String, ImageIcon>();

		for (Application app : rc.getAppsList()) {
			if (app == null)
				continue;

			for (String mime : app.getSupportedMimeTypes()) {
				if (mime == null || mime.isEmpty())
					continue;

				if (mimeTypesIcons.containsKey(mime))
					continue;

				if (this.system.getMimeTypeIcon(mime) != null)
					continue;

				ImageIcon icon = this.sm.askForMimeTypeIcon(mime);
				if (icon == null)
					continue;

				mimeTypesIcons.put(mime, icon);
			}
		}
		return mimeTypesIcons;
	}

	public boolean isDesktopIntegrationDone(RdpConnectionOvd co_) {
		return this.integratedConnections.contains(co_);
	}

	public void addDesktopIntegrationListener(DesktopIntegrationListener listener_) {
		if (listener_ == null)
			return;

		this.listeners.add(listener_);
	}

	public void removeDesktopIntegrationListener(DesktopIntegrationListener listener_) {
		if (listener_ == null)
			return;

		this.listeners.remove(listener_);
	}

	private void fireShortcutGenerationIsDone(RdpConnectionOvd co) {
		if (co == null)
			return;

		for (DesktopIntegrationListener listener : this.listeners) {
			listener.shortcutGenerationIsDone(co);
		}
	}
}
