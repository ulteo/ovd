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

package org.ulteo.ovd.applet;

import java.util.concurrent.ConcurrentHashMap;

import net.propero.rdp.RdpConnection;

import org.ulteo.Logger;
import org.ulteo.ovd.client.remoteApps.OvdClientRemoteApps;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.RdpConnectionOvd;

public class OvdClientApplicationsApplet extends OvdClientRemoteApps {
	private Properties properties = null;

	// matching variable associate the RDP connection list index with the JS RDP connection id
	private ConcurrentHashMap<Integer, Integer> matching = null;

	private OvdApplet applet = null;

	public OvdClientApplicationsApplet(Properties properties_, OvdApplet applet_) throws ClassCastException {
		super(null);

		this.properties = properties_;
		this.applet = applet_;
		this.matching = new ConcurrentHashMap<Integer, Integer>();

		this.configureRDP(this.properties);
	}

	@Override
	protected void customizeRemoteAppsConnection(RdpConnectionOvd co) {
		co.setAllDesktopEffectsEnabled(this.properties.isDesktopEffectsEnabled());
	}

	@Override
	protected void runSessionReady() {}

	@Override
	protected void runExit() {}

	@Override
	protected void uncustomizeRemoteAppsConnection(RdpConnectionOvd co) {}

	public boolean addServer(ServerAccess server, int JSId) {
		RdpConnectionOvd co = this.initRDPConnection(server);
		
		if (co == null)
			return false;
		
		this.configureRDPConnection(co);
		this.applet.applyConfig(co);
		co.connect();
		
		Integer listId = new Integer(this.connections.indexOf(co));
		if (listId == null)
			return false;

		this.matching.put(new Integer(JSId), listId);

		return true;
	}
	
	public void startApplication(int token, int app_id, int server_id) {
		this.startApplication(token, app_id, server_id, -1, null, null);
	}

	public void startApplication(int token, int app_id, int server_id, int shareType, String path, String sharename) {
		Integer listId = this.matching.get(server_id);
		if (listId == null) {
			Logger.error("Cannot launch application "+app_id+"("+token+"): Bad server id");
			return;
		}

		RdpConnectionOvd co = this.connections.get(listId.intValue());
		if (co == null) {
			Logger.error("Cannot launch application "+app_id+"("+token+"): Weird. The server id exists but there is not RDP connection");
			return;
		}

		OvdAppChannel chan = co.getOvdAppChannel();
		if (chan == null) {
			Logger.error("Cannot launch application "+app_id+"("+token+"): Weird. The server "+server_id+" has any OVD Applications channel");
			return;
		}

		if (! chan.isReady()) {
			Logger.warn("Cannot launch application "+app_id+"("+token+"): The OVD Applications channel (server "+server_id+") is not ready");
			return;
		}

		if (shareType < 0 || sharename == null || path == null) {
			chan.sendStartApp(token, app_id);
		}
		else {
			chan.sendStartApp(token, app_id, shareType, sharename, path);
		}

	}

	@Override
	public boolean checkRDPConnections() {
		Logger.error("Weird -- The method checkRDPConnections() should not be called");
		return true;
	}

	@Override
	public void ovdInited(OvdAppChannel channel) {
		super.ovdInited(channel);

		for (Integer JSId : this.matching.keySet()) {
			Integer listId = this.matching.get(JSId);
			if (listId == null)
				continue;

			RdpConnectionOvd rc = this.connections.get(listId.intValue());
			if (rc == null)
				continue;

			if (rc.getOvdAppChannel() == channel) {
				this.applet.forwardJS(OvdApplet.JS_API_F_SERVER, JSId, OvdApplet.JS_API_O_SERVER_READY);
				return;
			}
		}
		Logger.error("Received an OVD inited message but no connection is corresponding");
	}

	@Override
	public void ovdInstanceError(int instance) {
		this.applet.forwardJS(OvdApplet.JS_API_F_INSTANCE, new Integer(instance), OvdApplet.JS_API_O_INSTANCE_ERROR);
	}

	@Override
	public void ovdInstanceStarted(int instance) {
		this.applet.forwardJS(OvdApplet.JS_API_F_INSTANCE, new Integer(instance), OvdApplet.JS_API_O_INSTANCE_STARTED);
	}

	@Override
	public void ovdInstanceStopped(int instance) {
		this.applet.forwardJS(OvdApplet.JS_API_F_INSTANCE, new Integer(instance), OvdApplet.JS_API_O_INSTANCE_STOPPED);
	}

	@Override
	public void connected(RdpConnection co) {
		super.connected(co);

		for (Integer JSId: this.matching.keySet()) {
			Integer listId = this.matching.get(JSId);
			if (listId == null)
				continue;

			RdpConnectionOvd rc = this.connections.get(listId.intValue());
			if (rc == co) {
				this.applet.forwardJS(OvdApplet.JS_API_F_SERVER, JSId, OvdApplet.JS_API_O_SERVER_CONNECTED);
				return;
			}
		}
	}

	@Override
	public void disconnected(RdpConnection co) {
		for (Integer JSId: this.matching.keySet()) {
			Integer listId = this.matching.get(JSId);
			if (listId == null)
				continue;

			RdpConnectionOvd rc = this.connections.get(listId.intValue());
			if (rc == co) {
				this.matching.remove(JSId);
				this.applet.forwardJS(OvdApplet.JS_API_F_SERVER, JSId, OvdApplet.JS_API_O_SERVER_DISCONNECTED);
				return;
			}
		}

		super.disconnected(co);
	}

	@Override
	public void failed(RdpConnection co, String msg) {
		int state = co.getState();
		if (state == RdpConnectionOvd.STATE_CONNECTED) {
			return;
		}
		if (state != RdpConnectionOvd.STATE_FAILED) {
			Logger.debug("checkRDPConnections "+co.getServer()+" -- Bad connection state("+state+"). Will continue normal process.");
			return;
		}

		int tryNumber = co.getTryNumber();
		if (tryNumber < 1) {
			Logger.debug("checkRDPConnections "+co.getServer()+" -- Bad try number("+tryNumber+"). Will continue normal process.");
			return;
		}

		if (tryNumber > 1) {
			Logger.error("checkRDPConnections "+co.getServer()+" -- Several try to connect failed.");
			for (Integer JSId : this.matching.keySet()) {
				Integer listId = this.matching.get(JSId);
				if (listId == null)
					continue;

				RdpConnectionOvd rc = this.connections.get(listId.intValue());
				if (rc == co) {
					this.matching.remove(JSId);
					this.applet.forwardJS(OvdApplet.JS_API_F_SERVER, JSId, OvdApplet.JS_API_O_SERVER_FAILED);
					return;
				}
			}
			Logger.error("checkRDPConnections "+co.getServer()+" -- Failed to retrieve connection.");
			return;
		}

		Logger.warn("checkRDPConnections "+co.getServer()+" -- Connection failed. Will try to reconnect.");
		co.connect();

		super.failed(co, msg);
	}
}
