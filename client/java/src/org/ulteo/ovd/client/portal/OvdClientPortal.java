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

package org.ulteo.ovd.client.portal;

import net.propero.rdp.RdpConnection;
import org.apache.log4j.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.client.OvdClientRemoteApps;
import org.ulteo.ovd.client.authInterface.AuthFrame;
import org.ulteo.ovd.client.authInterface.LoginListener;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.RdpConnectionOvd;

public class OvdClientPortal extends OvdClientRemoteApps {
	private PortalFrame portal = null;

	public OvdClientPortal(String fqdn_, String login_, String password_) {
		super(fqdn_, login_, password_);

		this.init();
	}

	public OvdClientPortal(String fqdn_, String login_, String password_, AuthFrame frame_, LoginListener logList_) {
		super(fqdn_, login_, password_, frame_, logList_);

		this.init();
	}

	private void init() {
		this.logger = Logger.getLogger(OvdClientPortal.class);
		this.portal = new PortalFrame();
	}

	@Override
	protected void runInit() {}

	@Override
	protected void runExit() {}

	@Override
	protected void customizeRemoteAppsConnection(RdpConnectionOvd co) {
		try {
			co.addOvdAppListener(this.portal.getMain().getCenter().getCurrent());
		} catch (OvdException ex) {
			this.logger.error(ex);
		}
	}

	@Override
	protected void uncustomizeRemoteAppsConnection(RdpConnectionOvd co) {
		try {
			co.removeOvdAppListener(this.portal.getMain().getCenter().getCurrent());
		} catch (OvdException e) {
			e.printStackTrace();
		}
	}

	@Override
	public void ovdInited(OvdAppChannel o) {
		for (RdpConnectionOvd rc : this.availableConnections) {
			Menu menu = this.portal.getMain().getCenter().getMenu();
			for (Application app : rc.getAppsList()) {
				menu.install(app);
			}
			menu.addScroller();

			this.portal.initButtonPan(this);
		}
	}

	@Override
	protected void quitProperly(int i) {}

	@Override
	protected void display(RdpConnection co) {
		this.portal.setVisible(true);
	}

	@Override
	protected void hide(RdpConnection co) {
		this.portal.setVisible(false);
		this.portal.dispose();
	}
	
}
