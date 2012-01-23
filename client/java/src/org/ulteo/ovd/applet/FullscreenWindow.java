/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
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

import java.awt.Dimension;
import java.awt.GraphicsEnvironment;
import java.awt.Toolkit;
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;
import javax.swing.JFrame;
import org.ulteo.Logger;
import org.ulteo.gui.GUIActions;
import org.ulteo.rdp.RdpActions;


public class FullscreenWindow extends JFrame implements WindowListener {
	private RdpActions actions = null;

	public FullscreenWindow(RdpActions actions_) {
		super(null, GraphicsEnvironment.getLocalGraphicsEnvironment().getDefaultScreenDevice().getDefaultConfiguration());

		this.actions = actions_;

		this.setTitle("Ulteo Remote Desktop");
		GUIActions.setIconImage(this, null).run();
		
		this.setUndecorated(true);
		this.setAlwaysOnTop(true);
		this.setDefaultCloseOperation(JFrame.DO_NOTHING_ON_CLOSE);
		this.addWindowListener(this);
	}
	
	public static Dimension getScreenSize() {
		return Toolkit.getDefaultToolkit().getScreenSize();
	}
	
	public void setFullscreen() {
		GUIActions.setFullscreen(this);
	}
	
	public void unFullscreen() {
		GUIActions.unsetFullscreen(this);
	}

	// WindowListener method interface
	
	@Override
	public void windowClosing(WindowEvent we) {
		if (we.getComponent() != this)
			return;

		if (this.actions == null) {
			Logger.error("Can't manage disconnection request: rdpAction is null");
			return;
		}

		new AppletLogoutPopup(this, this.actions);
	}
	
	@Override
	public void windowClosed(WindowEvent we) {}
	@Override
	public void windowOpened(WindowEvent we) {}
	@Override
	public void windowIconified(WindowEvent we) {}
	@Override
	public void windowDeiconified(WindowEvent we) {}
	@Override
	public void windowActivated(WindowEvent we) {}
	@Override
	public void windowDeactivated(WindowEvent we) {}
}
