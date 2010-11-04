/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
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

package org.ulteo.ovd.client.authInterface;

import javax.swing.JFrame;
import javax.swing.JOptionPane;

import org.ulteo.utils.I18n;
import org.ulteo.rdp.RdpActions;

public class LogoutPopup extends JOptionPane {
	
	private JFrame frame = null;
	private String[] choice = {I18n._("Yes"), I18n._("Go back to authentication"), I18n._("No")};
	private RdpActions actions = null;

	
	public LogoutPopup(JFrame frame, RdpActions actions) {
		this.frame = frame;
		this.actions = actions;
		
		this.showPopup();
	}
	
	public void showPopup() {
		int option = showOptionDialog(frame, I18n._("Do you really want to close the window?"), 
				I18n._("Warning!"),
				JOptionPane.YES_NO_CANCEL_OPTION,
				JOptionPane.WARNING_MESSAGE,
				null,
				choice,
				choice[2]);

		switch (option) {
			case 0 :
				this.actions.exit(0);
				break;
			
			case 1 :
				this.actions.disconnectAll();
				break;
			
			default:
				this.setVisible(true);
				break;
		}
	}
}
