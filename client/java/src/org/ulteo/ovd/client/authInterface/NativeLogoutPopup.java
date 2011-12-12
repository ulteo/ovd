/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
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

package org.ulteo.ovd.client.authInterface;

import javax.swing.JFrame;
import org.ulteo.ovd.client.AbstractLogoutPopup;

import org.ulteo.utils.I18n;
import org.ulteo.rdp.RdpActions;

public class NativeLogoutPopup extends AbstractLogoutPopup {
	private RdpActions actions = null;
	
	/**
	 * have to quit after logout
	 */
	private boolean have_to_quit = false;
	
	public NativeLogoutPopup(JFrame frame_, RdpActions actions_) {
		super(frame_);

		this.actions = actions_;

		this.setTitle(I18n._("Warning!"));
		this.setText(I18n._("This will close your session, do you really want to continue?"));
		this.setChoices(new String[] {I18n._("Yes"), I18n._("Go back to authentication"), I18n._("No")});
	}
	
	/**
	 * respond if native client have to quit after logout
	 * @return have to quit after logout
	 */
	public boolean haveToQuit() {
		return this.have_to_quit;
	}
	
	@Override
	protected void processOption(int option_) {
		switch (option_) {
			case 0 :
				this.have_to_quit = true;
				this.actions.disconnect();
				break;
			case 1 :
				this.actions.disconnect();
				break;
			default:
				this.setVisible(true);
				break;
		}
	}
}
