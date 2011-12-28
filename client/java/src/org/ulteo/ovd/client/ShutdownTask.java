/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
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

import org.ulteo.ovd.integrated.SystemAbstract;

public class ShutdownTask extends Thread {
	private OvdClient client = null;

	public ShutdownTask(OvdClient client_) {
		if (client_ == null)
			throw new IllegalArgumentException("'OvdClient' parameter cannot be null");
		
		this.client = client_;
	}

	@Override
	public void run() {
		this.client.disconnection();

		//Cleaning up all useless OVD data
		SystemAbstract.cleanAll();

		if (this.client instanceof OvdClientRemoteApps) {
			((OvdClientRemoteApps) this.client).getSpool().terminate();
		}
	}
}
