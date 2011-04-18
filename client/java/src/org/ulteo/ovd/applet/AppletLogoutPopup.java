/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2011
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

import javax.swing.JFrame;
import org.ulteo.ovd.client.AbstractLogoutPopup;
import org.ulteo.rdp.RdpActions;
import org.ulteo.utils.I18n;

public class AppletLogoutPopup extends AbstractLogoutPopup {
	RdpActions actions = null;

	public AppletLogoutPopup(JFrame frame_, RdpActions actions_) {
		super(frame_);

		this.actions = actions_;

		this.setTitle(I18n._("Warning!"));
		this.setText(I18n._("Do you really want to close the window?"));
		this.setChoices(new String[] {I18n._("Yes"), I18n._("No")});

		this.showPopup();
	}

	@Override
	protected void processOption(int option_) {
		switch (option_) {
			case 0 :
				this.actions.disconnectAll();
				break;
			default:
				this.setVisible(true);
				break;
		}
	}
}
