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

import org.ulteo.Logger;
import org.ulteo.ovd.client.OvdClientPerformer;
import org.ulteo.ovd.client.OvdClientRemoteApps;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.rdp.RdpConnectionOvd;

public class OvdClientIntegrated extends OvdClientRemoteApps implements OvdClientPerformer {

	public OvdClientIntegrated(SessionManagerCommunication smComm) {
		super(smComm);
		
		this.showDesktopIcons = this.smComm.getResponseProperties().isDesktopIcons();
	}

	@Override
	protected void hide(RdpConnectionOvd rc) {
		this.unpublish(rc);
	}

	@Override
	public void ovdInstanceStopped(int instance_) {
		if (this.spool == null)
			return;
		
		this.spool.destroyInstance(instance_);
	}
	
	
	// interface OvdClientPerformer's methods 

	@Override
	public void createRDPConnections() {
		this.configureRDP(this.smComm.getResponseProperties());
		_createRDPConnections(this.smComm.getServers());
	}
	
	@Override
	public boolean checkRDPConnections() {
		return _checkRDPConnections();
	}
	
	@Override
	public void runSessionReady() {}
	
}
