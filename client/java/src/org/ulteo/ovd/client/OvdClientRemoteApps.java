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

package org.ulteo.ovd.client;

import java.util.HashMap;
import org.apache.log4j.Logger;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.client.authInterface.AuthFrame;
import org.ulteo.ovd.client.authInterface.LoginListener;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.OvdAppListener;
import org.ulteo.rdp.RdpConnectionOvd;

public abstract class OvdClientRemoteApps extends OvdClient implements OvdAppListener {

	public OvdClientRemoteApps(String fqdn_, HashMap<String,String> params_) {
		super(fqdn_, params_);
		
		this.init();
	}

	public OvdClientRemoteApps(String fqdn_, String login_, String password_) {
		super(fqdn_, OvdClient.toMap(login_, password_));

		this.init();
	}

	public OvdClientRemoteApps(String fqdn_, String login_, String password_, AuthFrame frame_, LoginListener logList_) {
		super(fqdn_, OvdClient.toMap(login_, password_), frame_, logList_);

		this.init();
	}

	private void init() {
		this.logger = Logger.getLogger(OvdClientRemoteApps.class);

		this.setSessionMode(SessionManagerCommunication.SESSION_MODE_REMOTEAPPS);
	}

	@Override
	protected void customizeConnection(RdpConnectionOvd co) {
		try {
			co.addOvdAppListener(this);
		} catch (OvdException ex) {
			this.logger.error(ex);
		}
		this.customizeRemoteAppsConnection(co);
	}

	protected abstract void customizeRemoteAppsConnection(RdpConnectionOvd co);

	@Override
	protected void uncustomizeConnection(RdpConnectionOvd co) {
		try {
			co.removeOvdAppListener(this);
		} catch (OvdException ex) {
			this.logger.error(ex);
		}
		this.uncustomizeRemoteAppsConnection(co);
	}

	protected abstract void uncustomizeRemoteAppsConnection(RdpConnectionOvd co);

	public abstract void ovdInited(OvdAppChannel o);

	public void ovdInstanceStarted(int instance_) {}
	public void ovdInstanceStopped(int instance_) {}
	public void ovdInstanceError(int instance_) {}
}
