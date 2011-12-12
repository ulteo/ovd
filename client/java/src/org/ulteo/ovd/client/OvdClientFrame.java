/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
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

package org.ulteo.ovd.client;

import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;
import javax.swing.JFrame;

import org.ulteo.ovd.client.authInterface.NativeLogoutPopup;
import org.ulteo.rdp.RdpActions;

public class OvdClientFrame extends JFrame implements WindowListener{

	protected RdpActions actions = null;
	
	public OvdClientFrame(RdpActions actions) {
		this.actions = actions;
		this.addWindowListener(this);
	}

	@Override
	public void windowActivated(WindowEvent e) {}

	@Override
	public void windowClosed(WindowEvent e) {}

	@Override
	public void windowClosing(WindowEvent e) {
		if (this.actions != null)
			new NativeLogoutPopup(this, this.actions);
		else
			System.err.println("Can't manage disconnection request: rdpAction is null");
	}

	@Override
	public void windowDeactivated(WindowEvent e) {}

	@Override
	public void windowDeiconified(WindowEvent e) {}

	@Override
	public void windowIconified(WindowEvent e) {}

	@Override
	public void windowOpened(WindowEvent e) {}

}
