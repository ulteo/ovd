/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
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
import org.ulteo.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.integrated.Spool;
import org.ulteo.ovd.integrated.SystemLinux;
import org.ulteo.ovd.integrated.SystemWindows;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.RdpConnectionOvd;

public class OvdClientIntegrated extends OvdClientRemoteApps {

	protected static HashMap<String, String> toMap(String token) {
		HashMap<String, String> map = new HashMap<String, String>();

		map.put(SessionManagerCommunication.FIELD_TOKEN, token);

		return map;
	}

	protected boolean showDesktopIcons = false;

	public OvdClientIntegrated(SessionManagerCommunication smComm) {
		super(smComm);
		this.init();
	}

	private void init() {
		this.showDesktopIcons = this.smComm.getResponseProperties().isDesktopIcons();
		this.spool = new Spool(this);
		
		String sm = this.smComm.getHost();
		this.spool.createIconsDir();
		if (OSTools.isWindows()) {
			this.system = new SystemWindows(sm);
		}
		else if (OSTools.isLinux()) {
			this.system = new SystemLinux(sm);
		}
		else {
			Logger.warn("This Operating System is not supported");
		}
		
		this.system.setShortcutArgumentInstance(this.spool.getInstanceName());
	}

	@Override
	protected void runSessionReady() {}

	@Override
	protected void runExit() {
		this.spool.start();
		this.spool.waitThreadEnd();
		this.exit(0);
	}

	@Override
	protected void customizeRemoteAppsConnection(RdpConnectionOvd co) {}

	@Override
	protected void uncustomizeRemoteAppsConnection(RdpConnectionOvd co) {}

	@Override
	public void ovdInited(OvdAppChannel o) {
		super.ovdInited(o);
		
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
		super.hide(co);
		
		for (Application app : ((RdpConnectionOvd) co).getAppsList()) {
			this.system.uninstall(app);
		}
	}

	@Override
	public void ovdInstanceStopped(int instance_) {
		this.spool.destroyInstance(instance_);
	}
}
