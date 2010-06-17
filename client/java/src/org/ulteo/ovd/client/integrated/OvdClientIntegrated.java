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

package org.ulteo.ovd.client.integrated;

import java.awt.SystemTray;
import net.propero.rdp.RdpConnection;
import org.apache.log4j.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.client.OvdClientRemoteApps;
import org.ulteo.ovd.client.authInterface.AuthFrame;
import org.ulteo.ovd.client.authInterface.LoginListener;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.integrated.Spool;
import org.ulteo.ovd.integrated.SystemAbstract;
import org.ulteo.ovd.integrated.SystemLinux;
import org.ulteo.ovd.integrated.SystemWindows;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.RdpConnectionOvd;

public class OvdClientIntegrated extends OvdClientRemoteApps {
	private Spool spool = null;
	private SystemAbstract system = null;

	public OvdClientIntegrated(String fqdn_, String login_, String password_, int resolution) {
		super(fqdn_, login_, password_, resolution);

		this.init();
	}

	public OvdClientIntegrated(String fqdn_, String login_, String password_, AuthFrame frame_, int resolution, LoginListener logList_) {
		super(fqdn_, login_, password_, frame_, resolution, logList_);

		this.init();
	}

	private void init() {
		this.logger = Logger.getLogger(OvdClientIntegrated.class);
		this.spool = new Spool(this);
	}

	@Override
	protected void runInit() {
		this.spool.createIconsDir();
		if (OSTools.isWindows()) {
			this.system = new SystemWindows();
		}
		else if (OSTools.isLinux()) {
			this.system = new SystemLinux();
		}
		else {
			this.logger.warn("This Operating System is not supported");
		}
	}

	@Override
	protected void runExit() {
		Thread fileListener = new Thread(this.spool);
		fileListener.start();
		while (fileListener.isAlive()) {
			try {
				Thread.sleep(100);
			} catch (InterruptedException ex) {
				this.logger.error(ex);
			}
		}
		this.exit(0);
	}

	@Override
	protected void customizeRemoteAppsConnection(RdpConnectionOvd co) {}

	@Override
	protected void uncustomizeRemoteAppsConnection(RdpConnectionOvd co) {}

	@Override
	public void ovdInited(OvdAppChannel o) {
		for (RdpConnectionOvd rc : this.availableConnections) {
			if (rc.getOvdAppChannel() != o)
				break;

			for (Application app : rc.getAppsList()) {
				this.system.install(app);
			}
		}
	}

	@Override
	protected void quitProperly(int i) {
		this.spool.deleteTree();
		this.spool = null;
	}

	@Override
	protected void display(RdpConnection co) {
		if (SystemTray.isSupported()) {
			new IntegratedTrayIcon(this);
		}
	}

	@Override
	protected void hide(RdpConnection co) {}

	@Override
	public void ovdInstanceStopped(int instance_) {
		this.spool.destroyInstance(instance_);
	}
}
