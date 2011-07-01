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

package org.ulteo.ovd.integrated.mime;

import java.util.HashMap;
import java.util.List;
import javax.swing.ImageIcon;
import org.ulteo.Logger;
import org.ulteo.ovd.integrated.SystemAbstract;
import org.ulteo.ovd.sm.Application;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.SessionManagerException;

public class MimetypesManager extends Thread {
	private SystemAbstract system = null;
	private SessionManagerCommunication sm = null;
	private List<ServerAccess> serversList = null;

	public MimetypesManager(SystemAbstract system_, SessionManagerCommunication sm_, List<ServerAccess> serversList_) {
		this.system = system_;
		this.sm = sm_;
		this.serversList = serversList_;
	}

	@Override
	public void run() {
		if (this.system == null) {
			Logger.error("Mimetypes icons download skipped: No system is specified.");
			return;
		}

		if (this.sm == null) {
			Logger.error("Mimetypes icons download skipped: No session manager is specified.");
			return;
		}
		
		if (this.serversList == null || this.serversList.isEmpty()) {
			Logger.error("Mimetypes icons download skipped: Server list does not exist or is empty.");
			return;
		}

		// mime-type icon processing
		HashMap<String, ImageIcon> mimeTypesIcons = new HashMap<String, ImageIcon>();

		for (ServerAccess server : this.serversList) {
			if (server == null)
				continue;

			for (Application app : server.getApplications()) {
				if (app == null)
					continue;

				for (String mime : app.getMimes()) {
					if (mime == null || mime.isEmpty())
						continue;

					if (mimeTypesIcons.containsKey(mime))
						continue;

					if (this.system.getMimeTypeIcon(mime) != null)
						continue;

					try {
						ImageIcon icon = this.sm.askForMimeTypeIcon(mime);
						if (icon == null)
							continue;

						mimeTypesIcons.put(mime, icon);
					} catch (SessionManagerException ex) {}
				}
			}
		}

		int updatedIcons = this.system.updateMimeTypesIconsCache(mimeTypesIcons);
		if (updatedIcons > 0)
			Logger.info("Mime-types cache updated: "+updatedIcons+" icons");

		this.system.refresh();
	}
}
