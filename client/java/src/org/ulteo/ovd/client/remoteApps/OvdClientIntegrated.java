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

import java.awt.SystemTray;
import java.util.HashMap;
import net.propero.rdp.RdpConnection;
import org.apache.log4j.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.client.authInterface.AuthFrame;
import org.ulteo.ovd.client.authInterface.LoginListener;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.integrated.Spool;
import org.ulteo.ovd.integrated.SystemAbstract;
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

	private Spool spool = null;
	private SystemAbstract system = null;

	private boolean authByToken = false;

	public OvdClientIntegrated(String fqdn_, boolean use_https_, String token_) {
		super(fqdn_, use_https_, OvdClientIntegrated.toMap(token_));

		this.authByToken = true;

		this.init();
	}

	public OvdClientIntegrated(String fqdn_, boolean use_https_, String login_, String password_) {
		super(fqdn_, use_https_, login_, password_);

		this.init();
	}

	public OvdClientIntegrated(String fqdn_, boolean use_https_, String login_, String password_, AuthFrame frame_, LoginListener logList_) {
		super(fqdn_, use_https_, login_, password_, frame_, logList_);

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
	protected boolean askSM() {
		if (this.authByToken)
			return this.smComm.askForApplications(this.params);
		else
			return super.askSM();
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
