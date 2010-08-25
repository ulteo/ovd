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

import java.awt.Dimension;
import java.awt.Toolkit;

import net.propero.rdp.RdesktopException;

import org.ulteo.Logger;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.client.OvdClient;
import org.ulteo.ovd.client.authInterface.LoadingStatus;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.sm.Callback;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.SessionManagerException;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.OvdAppListener;
import org.ulteo.rdp.RdpConnectionOvd;

public abstract class OvdClientRemoteApps extends OvdClient implements OvdAppListener {
	
	public OvdClientRemoteApps(SessionManagerCommunication smComm) {
		super(smComm, null);
	}

	public OvdClientRemoteApps(SessionManagerCommunication smComm, Callback obj) {
		super(smComm, obj);
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
	
	@Override
	protected void runDisconnecting() {}

	public abstract void ovdInited(OvdAppChannel o);

	public void ovdInstanceStarted(int instance_) {}
	public void ovdInstanceStopped(int instance_) {}
	public void ovdInstanceError(int instance_) {}
	

	@Override
	protected boolean createRDPConnections() {
		Properties properties = this.smComm.getResponseProperties();
		Dimension screenSize = Toolkit.getDefaultToolkit().getScreenSize();
		
		byte flags = 0x00;
		flags |= RdpConnectionOvd.MODE_APPLICATION;
		
		if (properties.isMultimedia())
			flags |= RdpConnectionOvd.MODE_MULTIMEDIA;
		
		if (properties.isPrinters())
			flags |= RdpConnectionOvd.MOUNT_PRINTERS;
		
		int numberOfServer = this.smComm.getServers().size();
		int serverIncrement = (int)100/numberOfServer;
		int serverIndex = 0;
		int status = 0;
		for (ServerAccess server : this.smComm.getServers()) {
			RdpConnectionOvd rc = null;
			
			try {
				rc = new RdpConnectionOvd(flags);
			} catch (RdesktopException ex) {
				Logger.error("Unable to create RdpConnectionOvd object: "+ex.getMessage());
				return false;
			}
			
			try {
				rc.initSecondaryChannels();
			} catch (RdesktopException ex) {
				Logger.error("Unable to init channels of RdpConnectionOvd object: "+ex.getMessage());
			}
			
			rc.setServer(server.getHost());
			rc.setCredentials(server.getLogin(), server.getPassword());
			// Ensure that width is multiple of 4
			// Prevent artifact on screen with a with resolution
			// not divisible by 4
			rc.setGraphic((int) screenSize.width & ~3, (int) screenSize.height, RdpConnectionOvd.DEFAULT_BPP);
			
			if (this.keymap != null)
				rc.setKeymap(this.keymap);
						
			int numberOfApplication = server.getApplications().size();
			int ApplicationIncrement = (int)serverIncrement/numberOfApplication;
			int ApplicationIndex = 0;
			for (org.ulteo.ovd.sm.Application appItem : server.getApplications()) {
				try {
					int subStatus = (int)(status + ApplicationIndex * ApplicationIncrement); 
					this.obj.updateProgress(LoadingStatus.STATUS_SM_GET_APPLICATION, subStatus);
					
					Application app = new Application(rc, appItem.getId(), appItem.getName(), appItem.getMimes(), this.smComm.askForIcon(Integer.toString(appItem.getId())));
					rc.addApp(app);
				} catch (SessionManagerException ex) {
					ex.printStackTrace();
					Logger.warn("Cannot get the \""+appItem.getName()+"\" icon: "+ex.getMessage());
				}
				ApplicationIndex++;
			}
			status+=serverIndex * serverIncrement;
			serverIndex++;
			
			this.connections.add(rc);
		}
		this.obj.updateProgress(LoadingStatus.STATUS_SM_GET_APPLICATION, 100);
		
		return true;
	}
}
