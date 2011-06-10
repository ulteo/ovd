/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2011
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
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

import java.net.UnknownHostException;

import net.propero.rdp.RdesktopException;

import org.ulteo.Logger;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.sm.Callback;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.rdp.RdpConnectionOvd;

public abstract class OvdClientDesktop extends OvdClient {

	public OvdClientDesktop(Callback obj) {
		this(null, obj, false);
	}

	public OvdClientDesktop(SessionManagerCommunication smComm, Callback obj, boolean persistent) {
		super(smComm, obj, false);
	}

	public abstract void adjustDesktopSize(RdpConnectionOvd rc);
	
	protected abstract Properties getProperties();
	
	@Override
	public RdpConnectionOvd createRDPConnection(ServerAccess server) {
		Properties properties = getProperties();
		
		byte flags = 0x00;
		flags |= RdpConnectionOvd.MODE_DESKTOP;
		
		if (properties.isMultimedia())
			flags |= RdpConnectionOvd.MODE_MULTIMEDIA;
		
		if (properties.isPrinters())
			flags |= RdpConnectionOvd.MOUNT_PRINTERS;

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
		
		if (server.getModeGateway()) {

			if (server.getToken().equals("")) {
				Logger.error("Server need a token to be identified on gateway, so token is empty !");
				return null;
			} else {
				rc.setCookieElement("token", server.getToken());
			}

			try {
				rc.useSSLWrapper(server.getHost(), server.getPort());
			} catch(OvdException ex) {
				Logger.error("Unable to create RdpConnectionOvd SSLWrapper: " + ex.getMessage());
				return null;
			} catch(UnknownHostException ex) {
				Logger.error("Undefined error during creation of RdpConnectionOvd SSLWrapper: " + ex.getMessage());
				return null;
			}
		}

		rc.setServer(server.getHost());
		rc.setCredentials(server.getLogin(), server.getPassword());
		rc.setAllDesktopEffectsEnabled(properties.isDesktopEffectsEnabled());

		if (this.keymap != null)
			rc.setKeymap(this.keymap);
		
		if (this.inputMethod != null)
			rc.setInputMethod(this.inputMethod);
		
		return rc;
	}
}
