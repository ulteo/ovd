/*
 * Copyright (C) 2009 Ulteo SAS
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

package org.ulteo.ovd.standalone;

import java.awt.GraphicsEnvironment;
import java.awt.Insets;
import java.awt.Rectangle;
import javax.swing.JPanel;
import net.propero.rdp.RdesktopCanvas;
import net.propero.rdp.RdpConnection;

public class Desktop extends JPanel {
	private Client parent = null;
	private RdpConnection connection = null;

	Desktop(Client parent_, RdpConnection connection_) {
		super();
		
		this.parent = parent_;
		this.connection = connection_;
		Rectangle dim = GraphicsEnvironment.getLocalGraphicsEnvironment().getMaximumWindowBounds();
		Insets insets = this.parent.getInsets();
		this.connection.opt.width = dim.width - (insets.left + insets.right);
		this.connection.opt.height = dim.height - (insets.top + insets.bottom);
		this.setSize(this.connection.opt.width, this.connection.opt.height);
	}

	public RdesktopCanvas getCanvas() {
		RdesktopCanvas canvas = this.connection.getCanvas();
		canvas.setLocation(0, 0);
		return canvas;
	}
}
