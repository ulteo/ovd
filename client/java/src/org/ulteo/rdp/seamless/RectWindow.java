/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2009
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

package org.ulteo.rdp.seamless;

import java.awt.Color;
import java.awt.Component;
import java.awt.Dimension;
import java.awt.Frame;
import java.awt.Graphics;
import java.awt.Rectangle;
import java.awt.Window;

public class RectWindow extends Component {
	private static final int BORDER_SIZE = 5;

	private LineWindow left = null;
	private LineWindow right = null;
	private LineWindow top = null;
	private LineWindow bottom = null;

	private int maxWidth = 0;
	private int maxHeight = 0;

	public RectWindow(Frame f, Dimension dim) {
		this.left = new LineWindow(f);
		this.right = new LineWindow(f);
		this.top = new LineWindow(f);
		this.bottom = new LineWindow(f);

		this.maxWidth = dim.width;
		this.maxHeight = dim.height;
	}

	@Override
	public void setBounds(int x, int y, int width, int height) {
		x = Math.max(x, 0);
		y = Math.max(y, 0);

		Rectangle r_left = new Rectangle(x, y, RectWindow.BORDER_SIZE, height);
		Rectangle r_right = new Rectangle(x+width-RectWindow.BORDER_SIZE, y, RectWindow.BORDER_SIZE, height);
		Rectangle r_top = new Rectangle(x, y, width, RectWindow.BORDER_SIZE);
		Rectangle r_bottom = new Rectangle(x, y+height-RectWindow.BORDER_SIZE, width, RectWindow.BORDER_SIZE);

		this.left.setBounds(r_left);
		this.right.setBounds(r_right);
		this.top.setBounds(r_top);
		this.bottom.setBounds(r_bottom);
	}

	@Override
	public void setBounds(Rectangle r) {
		this.setBounds(r.x, r.y, r.width, r.height);
	}

	@Override
	public void setVisible(boolean b) {
		this.left.setVisible(b);
		this.right.setVisible(b);
		this.top.setVisible(b);
		this.bottom.setVisible(b);
	}

	class LineWindow extends Window {

		public LineWindow(Frame f) {
			super(f);

			this.setAlwaysOnTop(true);
			this.setBackground(Color.DARK_GRAY);
		}

		@Override
		public void paint(Graphics g) {}
	}
}
