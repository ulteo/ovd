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

import org.ulteo.ovd.client.authInterface.DisconnectionFrame;
import org.ulteo.ovd.client.authInterface.NativeLogoutPopup;

public class OvdClientFrame extends JFrame implements WindowListener {

	protected NativeClientActions actions = null;
	
	private DisconnectionFrame discDialog = new DisconnectionFrame();
	
	/**
	 * have to quit after logout
	 */
	private boolean have_to_quit = false;
	
	public OvdClientFrame(NativeClientActions actions) {
		if (actions == null)
			throw new IllegalArgumentException("actions parameter cannot be null");
		
		this.actions = actions;
		this.addWindowListener(this);
	}
	
	/**
	 * respond if native client have to quit after logout
	 * @return have to quit after logout
	 */
	public boolean haveToQuit() {
		return this.have_to_quit;
	}
	
	/**
	 * set if native client have to quit after logout
	 * @param quit have to quit after logout
	 */
	public void haveToQuit(boolean quit) {
		this.have_to_quit = quit;
	}
	
	/**
	 * display the disconnecting window
	 */
	public void disconnecting() {
		 this.discDialog.setVisible(true);
	 }

	@Override
	public void windowActivated(WindowEvent e) {}

	@Override
	public void windowClosed(WindowEvent e) {
		this.discDialog.setVisible(false);
	}

	@Override
	public void windowClosing(WindowEvent e) {
		NativeLogoutPopup nlp = new NativeLogoutPopup(this, this.actions);
		nlp.showPopup();
		this.have_to_quit = nlp.haveToQuit();
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
