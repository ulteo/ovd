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

import java.awt.Insets;
import java.util.HashMap;
import net.propero.rdp.RdpConnection;
import org.apache.log4j.Logger;
import org.ulteo.ovd.client.OvdClient;
import org.ulteo.ovd.client.authInterface.AuthFrame;
import org.ulteo.ovd.client.authInterface.LoginListener;
import org.ulteo.ovd.client.authInterface.OptionPanel;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.rdp.RdpConnectionOvd;

public class OvdClientDesktop extends OvdClient {
	private DesktopFrame desktop = null;
	private boolean desktopLaunched = false;
	private int resolution = 0;

	public OvdClientDesktop(String fqdn_, String login_, String password_, int resolution) {
		super(fqdn_, OvdClient.toMap(login_, password_));

		this.init(resolution);
	}

	public OvdClientDesktop(String fqdn_, String login_, String password_, AuthFrame frame_, int resolution, LoginListener logList_) {
		super(fqdn_, OvdClient.toMap(login_, password_), frame_, logList_);

		this.init(resolution);
	}

	private void init(int resolution_) {
		this.resolution = resolution_;
		this.logger = Logger.getLogger(OvdClientDesktop.class);
		this.setSessionMode(SessionManagerCommunication.SESSION_MODE_DESKTOP);
	}

	@Override
	protected void runInit() {}

	@Override
	protected void runExit() {}

	@Override
	protected void quitProperly(int i) {}

	@Override
	protected void customizeConnection(RdpConnectionOvd co) {
		if (! this.desktopLaunched)
			this.initDesktop(co);
	}

	@Override
	protected void uncustomizeConnection(RdpConnectionOvd co) {}

	@Override
	public void display(RdpConnection co) {
		this.desktop.setVisible(true);
	}

	@Override
	public void hide(RdpConnection co) {
		desktop.setVisible(false);
		desktop.dispose();
	}

	private void initDesktop(RdpConnectionOvd co) {
		switch (this.resolution) {
			case 0 :
				this.desktop = new DesktopFrame(OptionPanel.SMALL_RES, false);
				break;
			case 1 :
				this.desktop = new DesktopFrame(OptionPanel.MEDUIM_RES, false);
				break;
			case 2 :
				this.desktop = new DesktopFrame(OptionPanel.HIGH_RES, false);
				break;
			case 3 :
				this.desktop = new DesktopFrame(OptionPanel.MAXIMISED, false);
				break;
			case 4 :
				this.desktop = new DesktopFrame(OptionPanel.FULLSCREEN, true);
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
	public void connecting(RdpConnection co) {
		super.connecting(co);

		this.desktop.getContentPane().add(co.getCanvas());
		co.getCanvas().validate();
		this.desktop.pack();
	}
}
