/*
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2009-2011
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
import java.awt.Graphics;
import java.awt.Rectangle;
import java.awt.Window;
import org.ulteo.gui.GUIActions;
import org.ulteo.gui.SwingTools;

public class RectWindow extends Component {
	private static final int BORDER_SIZE = 5;

	private LineWindow left = null;
	private LineWindow right = null;
	private LineWindow top = null;
	private LineWindow bottom = null;

	public RectWindow(Window f, Dimension dim, Rectangle maxBounds_) {
		this.left = new LineWindow(f);
		this.right = new LineWindow(f);
		this.top = new LineWindow(f);
		this.bottom = new LineWindow(f);
	}
	
	@Override
	public void setBounds(Rectangle r) {
		Rectangle r_left = new Rectangle(r.x, r.y, RectWindow.BORDER_SIZE, r.height);
		Rectangle r_right = new Rectangle(r.x+r.width-RectWindow.BORDER_SIZE, r.y, RectWindow.BORDER_SIZE, r.height);
		Rectangle r_top = new Rectangle(r.x, r.y, r.width, RectWindow.BORDER_SIZE);
		Rectangle r_bottom = new Rectangle(r.x, r.y+r.height-RectWindow.BORDER_SIZE, r.width, RectWindow.BORDER_SIZE);

		SwingTools.invokeLater(GUIActions.setBounds(this.left, r_left));
		SwingTools.invokeLater(GUIActions.setBounds(this.right, r_right));
		SwingTools.invokeLater(GUIActions.setBounds(this.top, r_top));
		SwingTools.invokeLater(GUIActions.setBounds(this.bottom, r_bottom));
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

	@Override
	public boolean isVisible() {
		boolean isVisible1 = this.left.isVisible();
		boolean isVisible2 = this.right.isVisible();
		boolean isVisible3 = this.top.isVisible();
		boolean isVisible4 = this.bottom.isVisible();

		if (! (isVisible1 || isVisible2 || isVisible3 || isVisible4))
			return false;

		if (! (isVisible1 && isVisible2 && isVisible3 && isVisible4))
			org.ulteo.Logger.error("Weird. Some parts of rect window are visible (left: "+isVisible1+", right: "+isVisible2+", top: "+isVisible3+", bottom: "+isVisible4+")");

		return true;
	}
	
	@Override
	public void setVisible(boolean b) {
		SwingTools.invokeLater(GUIActions.setVisible(this.left, b));
		SwingTools.invokeLater(GUIActions.setVisible(this.right, b));
		SwingTools.invokeLater(GUIActions.setVisible(this.top, b));
		SwingTools.invokeLater(GUIActions.setVisible(this.bottom, b));
	}

	class LineWindow extends Window {

		public LineWindow(Window f) {
			super(f);

			SwingTools.invokeLater(GUIActions.setAlwaysOnTop(this, true));
			this.setBackground(Color.DARK_GRAY);
		}

		@Override
		public void paint(Graphics g) {}
	}
}
