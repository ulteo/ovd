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
	
	public static final int MODE_NONE=0;
	public static final int MODE_MOVE=1;
	public static final int MODE_RESIZE=2;

	private LineWindow left = null;
	private LineWindow right = null;
	private LineWindow top = null;
	private LineWindow bottom = null;

	private int minX = 0;
	private int minY = 0;
	private int maxX = 0;
	private int maxY = 0;
	private int minWidth = 0;
	private int minHeight = 0;
	
	private Frame refFrame = null;
	
	private int mode = 0;

	public RectWindow(Frame f, Dimension dim) {
		this.left = new LineWindow(f);
		this.right = new LineWindow(f);
		this.top = new LineWindow(f);
		this.bottom = new LineWindow(f);

		this.maxX = dim.width;
		this.maxY = dim.height;
		this.refFrame = f;
		
		this.minWidth = 2*RectWindow.BORDER_SIZE;
		this.minHeight = 2*RectWindow.BORDER_SIZE;
	}

	protected Rectangle fixBounds(Rectangle r) {
		if (this.mode == MODE_MOVE) {
			if (r.x < this.minX)
				r.x = this.minX;

			if (r.y < this.minY)
				r.y = this.minY;

			if (r.x+r.width > this.maxX)
				r.x =  this.maxX - r.width;
			
			if (r.y+r.height > this.maxY)
				r.y = this.maxY - r.height;
		}
		else if (this.mode == MODE_RESIZE) {
			if (r.width < this.minWidth) {
				Rectangle ref = this.refFrame.getBounds();
				
				if (r.x != ref.x)
					r.x = ref.x+ref.width - this.minWidth;

				r.width = this.minWidth;
			}
			
			if (r.height < this.minHeight) {
				Rectangle ref = this.refFrame.getBounds();
				
				if (r.y != ref.y)
					r.y = ref.y+ref.height - this.minHeight;

				r.height = this.minHeight;
			}
		}
		
		return r;
	}
	
	
	@Override
	public void setBounds(Rectangle r) {
		r = this.fixBounds(r);

		Rectangle r_left = new Rectangle(r.x, r.y, RectWindow.BORDER_SIZE, r.height);
		Rectangle r_right = new Rectangle(r.x+r.width-RectWindow.BORDER_SIZE, r.y, RectWindow.BORDER_SIZE, r.height);
		Rectangle r_top = new Rectangle(r.x, r.y, r.width, RectWindow.BORDER_SIZE);
		Rectangle r_bottom = new Rectangle(r.x, r.y+r.height-RectWindow.BORDER_SIZE, r.width, RectWindow.BORDER_SIZE);

		this.left.setBounds(r_left);
		this.right.setBounds(r_right);
		this.top.setBounds(r_top);
		this.bottom.setBounds(r_bottom);
	}

	@Override
	public Rectangle getBounds() {
		Rectangle r = new Rectangle();
		r.x = this.left.getX();
		r.width = this.right.getX() + this.right.getWidth() - r.x;
		r.y = this.top.getY();
		r.height = this.bottom.getY() + this.bottom.getHeight() - r.y;
		return r;
	}
	
	public int getMode() {
		return this.mode;
	}
	
	public void setMode(int mode) {
		this.mode = mode;
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
