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
import java.util.List;
import org.ulteo.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.integrated.mime.MimetypesManager;
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

	public void run() {
		this.generateShortcuts();
		this.downloadMimetypesIcons();
	}

	private void downloadMimetypesIcons() {
		// Download mimetypes icons
		new MimetypesManager(this.system, this.sm, this.connections).run();
	}

	private void generateShortcuts() {
		for (RdpConnectionOvd server : this.connections) {
			for (Application app : server.getAppsList()) {
				if (this.system.create(app) == null)
					Logger.error("The "+app.getName()+" shortcut could not be created");
			}

			this.integratedConnections.add(server);
			
			this.fireShortcutGenerationIsDone(server);
		}
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
