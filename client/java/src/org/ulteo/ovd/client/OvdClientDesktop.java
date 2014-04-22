/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2011
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011-2012
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

import java.awt.Dimension;
import java.awt.Toolkit;

import net.propero.rdp.RdesktopCanvas;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpConnection;

import org.ulteo.Logger;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.rdp.RdpConnectionOvd;

public abstract class OvdClientDesktop extends OvdClient {

	public OvdClientDesktop() {
		this(null, false);
	}

	public OvdClientDesktop(SessionManagerCommunication smComm, boolean persistent) {
		super(smComm, persistent);
		
		// extend the size of the list for receiving the future desktop connection
		this.connections.add(null);
	}
	
	@Override
	public void connected(RdpConnection co) {
		super.connected(co);
		this.display(co.getCanvas());
	}
	
	/**
	 * action to perform when a RDP connection is connected 
	 * @param canvas Rdp desktop Canvas to configure  
	 */
	protected abstract void display(RdesktopCanvas canvas);

	protected abstract Properties getProperties();
	
	public void adjustDesktopSize() {
		RdpConnectionOvd rc = this.getConnection();
		if (rc == null)
			return;

		// Prevent greometry modification while the connection is active
		if (rc.getState() != RdpConnection.State.DISCONNECTED)
			return;

		// Ensure that width is multiple of 4
		// Prevent artifact on screen with a with resolution not divisible by 4
		Dimension screenSize = getScreenSize();
		int bpp = this.getProperties().getRDPBpp();
		rc.setGraphic((int) screenSize.width & ~3, (int) screenSize.height, bpp);
	}

	public Dimension getScreenSize() {
		return Toolkit.getDefaultToolkit().getScreenSize();
	}
	
	@Override
	public RdpConnectionOvd createRDPConnection(ServerAccess server) {
		Properties properties = getProperties();
		
		int flags = 0x00;
		flags |= RdpConnectionOvd.MODE_DESKTOP;
		
		if (properties.isMultimedia())
			flags |= RdpConnectionOvd.MODE_MULTIMEDIA;
		
		if (properties.isPrinters())
			flags |= RdpConnectionOvd.MOUNT_PRINTERS;
		
		if (properties.isCardsReaders())
			flags |= RdpConnectionOvd.MOUNT_SMARTCARD;

		if (properties.isDrives() == Properties.REDIRECT_DRIVES_FULL)
			flags |= RdpConnectionOvd.MOUNTING_MODE_FULL;
		else if (properties.isDrives() == Properties.REDIRECT_DRIVES_PARTIAL)
			flags |= RdpConnectionOvd.MOUNTING_MODE_PARTIAL;
		
		RdpConnectionOvd rc = null;
		
		try {
			rc = new RdpConnectionOvd(flags);
		} catch (RdesktopException ex) {
			Logger.error("Unable to create RdpConnectionOvd object: "+ex.getMessage());
			return null;
		}
		
		try {
			rc.initSecondaryChannels();
		} catch (RdesktopException ex) {
			Logger.error("Unable to init channels of RdpConnectionOvd object: "+ex.getMessage());
		}
		
		rc.enableGatewayMode(server);
		rc.setServer(server.getHost(), server.getPort());
		rc.setCredentials(server.getLogin(), server.getPassword());
		rc.setAllDesktopEffectsEnabled(properties.isDesktopEffectsEnabled());
		if (properties.isUseLocalIME()) {
			rc.setInputMethod("unicode_local_ime");
		}
		this.configure(rc);
		this.connections.set(0, rc);
		return rc;
	}
	
	/**
	 * return the unique {@link RdpConnectionOvd} of this desktop client
	 * @return an {@link RdpConnectionOvd}, <code>null</code> if not created yet 
	 */
	public RdpConnectionOvd getConnection() {
		return this.connections.get(0);
	}

	@Override
	protected void runSessionTerminated() {}
}
