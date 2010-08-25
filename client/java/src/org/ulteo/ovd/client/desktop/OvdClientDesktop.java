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

package org.ulteo.ovd.client.desktop;

import java.awt.Dimension;
import java.awt.Insets;
import java.awt.Toolkit;

import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpConnection;
import org.ulteo.Logger;
import org.ulteo.ovd.client.OvdClient;
import org.ulteo.ovd.sm.Callback;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.rdp.RdpConnectionOvd;

public class OvdClientDesktop extends OvdClient {
	private DesktopFrame desktop = null;
	private boolean desktopLaunched = false;
	private int resolution = 0;
	
	public OvdClientDesktop(SessionManagerCommunication smComm, int resolution) {
		super(smComm, null);

		this.init(resolution);
	}

	public OvdClientDesktop(SessionManagerCommunication smComm, int resolution, Callback obj) {
		super(smComm, obj);

		this.init(resolution);
	}

	private void init(int resolution_) {
		this.resolution = resolution_;
	}

	@Override
	protected void runInit() {}

	@Override
	protected void runSessionReady() {
		this.desktop.setVisible(true);
	}

	@Override
	protected void runExit() {}

	@Override
	protected void runSessionTerminated() {}

	@Override
	protected void customizeConnection(RdpConnectionOvd co) {
		if (! this.desktopLaunched)
			this.initDesktop(co);
	}

	@Override
	protected void uncustomizeConnection(RdpConnectionOvd co) {}

	@Override
	public void display(RdpConnection co) {}

	@Override
	public void hide(RdpConnection co) {
		desktop.setVisible(false);
	}

	private void initDesktop(RdpConnectionOvd co) {
		switch (this.resolution) {
			case 0 :
				this.desktop = new DesktopFrame(DesktopFrame.SMALL_RES, false, this);
				break;
			case 1 :
				this.desktop = new DesktopFrame(DesktopFrame.MEDUIM_RES, false, this);
				break;
			case 2 :
				this.desktop = new DesktopFrame(DesktopFrame.HIGH_RES, false, this);
				break;
			case 3 :
				this.desktop = new DesktopFrame(DesktopFrame.MAXIMISED, false, this);
				break;
			case 4 :
				this.desktop = new DesktopFrame(DesktopFrame.FULLSCREEN, true, this);
				break;
		}

		if(this.resolution != 4) {
			Insets inset = null;
			inset = this.desktop.getInsets();
			this.desktop.setLocationRelativeTo(null);
			co.setGraphic((this.desktop.getWidth()-(inset.left+inset.right)+2), (this.desktop.getHeight()-(inset.bottom+inset.top)+2));
		}
		this.desktopLaunched = true;
	}

	@Override
	protected boolean createRDPConnections() {	
		Properties properties = this.smComm.getResponseProperties();
		ServerAccess server = this.smComm.getServers().get(0);
		
		
		byte flags = 0x00;
		flags |= RdpConnectionOvd.MODE_DESKTOP;
		
		if (properties.isMultimedia())
			flags |= RdpConnectionOvd.MODE_MULTIMEDIA;
		
		if (properties.isPrinters())
			flags |= RdpConnectionOvd.MOUNT_PRINTERS;
		
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
		Dimension screenSize = Toolkit.getDefaultToolkit().getScreenSize();
		rc.setGraphic((int) screenSize.width & ~3, (int) screenSize.height, RdpConnectionOvd.DEFAULT_BPP);
		if (this.keymap != null)
			rc.setKeymap(this.keymap);
		
		this.connections.add(rc);
		
		return true;
	}

	@Override
	public void connecting(RdpConnection co) {
		super.connecting(co);

		this.desktop.getContentPane().add(co.getCanvas());
		co.getCanvas().validate();
		this.desktop.pack();
	}

	@Override
	protected void runDisconnecting() {}
}
