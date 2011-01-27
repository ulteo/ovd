/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

import java.awt.event.ComponentEvent;
import java.awt.event.ComponentListener;
import javax.swing.JFrame;
import javax.swing.JPanel;
import net.propero.rdp.RdesktopCanvas;

public class ScrollableDesktopFrame extends JFrame implements ComponentListener {

	private DesktopFrame desktopFrame = null;
	private ScrollableDesktopPanel scrollableDesktop = null;

	public ScrollableDesktopFrame(DesktopFrame desktopFrame_) {
		super("Ulteo Remote Desktop");

		this.desktopFrame = desktopFrame_;

		this.setVisible(false);
		this.setUndecorated(false);
		this.setSize(DesktopFrame.MAXIMISED);
		this.setResizable(true);
		this.setDefaultCloseOperation(JFrame.DO_NOTHING_ON_CLOSE);
		this.setIconImage(this.desktopFrame.getIconImage());

		this.addWindowListener(this.desktopFrame);
		this.addComponentListener(this);
	}

	public void setCanvas(RdesktopCanvas canvas) {
		this.scrollableDesktop = new ScrollableDesktopPanel(canvas);
	}

	public JPanel getView() {
		if (this.scrollableDesktop == null)
			return null;

		return this.scrollableDesktop.getView();
	}

	public void componentResized(ComponentEvent ce) {}
	public void componentMoved(ComponentEvent ce) {}
	public void componentShown(ComponentEvent ce) {
		this.getContentPane().removeAll();
		this.getContentPane().add(this.scrollableDesktop);
		this.getContentPane().validate();
		
		this.scrollableDesktop.getView().requestFocus();
	}
	public void componentHidden(ComponentEvent ce) {}
}
