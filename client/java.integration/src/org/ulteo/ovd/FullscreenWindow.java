/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
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

package org.ulteo.ovd;

import java.awt.Dimension;
import java.awt.event.FocusEvent;
import java.awt.event.FocusListener;
import java.awt.GraphicsEnvironment;
import java.awt.GraphicsDevice;
import java.awt.Toolkit;
import javax.swing.JFrame;


public class FullscreenWindow extends JFrame implements FocusListener {
	public FullscreenWindow() {
		super(null, GraphicsEnvironment.getLocalGraphicsEnvironment().getDefaultScreenDevice().getDefaultConfiguration());

		this.setUndecorated(true);
		this.addFocusListener(this);
	}
	
	public static Dimension getScreenSize() {
		return Toolkit.getDefaultToolkit().getScreenSize();
	}
	
	public void setFullscreen() {
		GraphicsEnvironment ge = GraphicsEnvironment.getLocalGraphicsEnvironment();
		GraphicsDevice gs = ge.getDefaultScreenDevice();
		gs.setFullScreenWindow(this);
		this.validate();
	}
	
	public void unFullscreen() {
		GraphicsEnvironment ge = GraphicsEnvironment.getLocalGraphicsEnvironment();
		GraphicsDevice gs = ge.getDefaultScreenDevice();
		gs.setFullScreenWindow(null);
	}
	
	public void focusGained(FocusEvent fe) {
		this.setAlwaysOnTop(true);
	}
	public void focusLost(FocusEvent fe) {
		this.setAlwaysOnTop(false);
	}
}
