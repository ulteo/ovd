/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

package org.ulteo.ovd.client;

import java.io.File;
import org.ulteo.Logger;
import org.ulteo.ovd.client.remoteApps.OvdClientRemoteApps;
import org.ulteo.utils.FilesOp;
import org.ulteo.ovd.integrated.Constants;
import org.ulteo.ovd.integrated.Spool;
import org.ulteo.ovd.integrated.SystemAbstract;

public class ShutdownTask extends Thread {
	private OvdClient client = null;

	public ShutdownTask(OvdClient client_) {
		this.client = client_;
	}

	@Override
	public void run() {
		if (this.client == null) {
			Logger.error("Shutdown task: client is null");
			return;
		}

		this.client.disconnectAll();

		//Cleaning up all useless OVD data
		SystemAbstract.cleanAll();

		String instance = this.client.getInstance();
		if (instance == null)
			return;

		if (this.client instanceof OvdClientRemoteApps) {
			Spool spool = ((OvdClientRemoteApps) this.client).getSpool();
			if (spool != null)
				spool.stop();
		}

		File instance_dir = new File(Constants.PATH_REMOTE_APPS+Constants.FILE_SEPARATOR+instance);
		if (! instance_dir.exists())
			return;

		FilesOp.deleteDirectory(instance_dir);
	}
}
