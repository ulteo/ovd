/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
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

package org.ulteo.ovd.client.remoteApps;

import java.util.HashMap;
import net.propero.rdp.RdpConnection;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.RdpConnectionOvd;

public class OvdClientIntegrated extends OvdClientRemoteApps {

	protected static HashMap<String, String> toMap(String token) {
		HashMap<String, String> map = new HashMap<String, String>();

		map.put(SessionManagerCommunication.FIELD_TOKEN, token);

		return map;
	}

	public OvdClientIntegrated(SessionManagerCommunication smComm) {
		super(smComm);
		
		this.showDesktopIcons = this.smComm.getResponseProperties().isDesktopIcons();
	}

	@Override
	protected void runSessionReady() {
		this.spool.waitThreadEnd();
		this.exit(0);
	}

	@Override
	public void ovdInited(OvdAppChannel o) {
		for (RdpConnectionOvd rc : this.availableConnections) {
			if (rc.getOvdAppChannel() != o)
				continue;

			boolean associate = (rc.getFlags() & RdpConnectionOvd.MOUNTING_MODE_MASK) != 0;

			for (Application app : rc.getAppsList()) {
				this.system.install(app, this.showDesktopIcons, associate);
			}
		}

		this.system.refresh();
	}

	@Override
	protected void hide(RdpConnection co) {
		for (Application app : ((RdpConnectionOvd) co).getAppsList()) {
			this.system.uninstall(app);
		}
	}

	@Override
	public void ovdInstanceStopped(int instance_) {
		this.spool.destroyInstance(instance_);
	}
}
