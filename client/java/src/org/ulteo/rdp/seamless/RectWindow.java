/*
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2009-2010
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
import java.awt.Point;
import java.awt.Rectangle;
import java.awt.Window;
import java.awt.event.MouseEvent;
import org.ulteo.gui.GUIActions;
import org.ulteo.gui.SwingTools;

public class RectWindow extends Component {
	private static final int BORDER_SIZE = 5;
	
	public static int SEAMLESS_BORDER_SIZE = 4;
	public static int SEAMLESS_TOP_BORDER_SIZE = 20;
	public static int SEAMLESS_CORNER_SIZE = 20;

	public static final int NO_CORNER = -1;
	public static final int CORNER_TOP_LEFT = 0;
	public static final int CORNER_BOTTOM_LEFT = 1;
	public static final int CORNER_TOP_RIGHT = 2;
	public static final int CORNER_BOTTOM_RIGHT = 3;
	public static final int CORNER_LEFT = 4;
	public static final int CORNER_BOTTOM = 5;
	public static final int CORNER_RIGHT = 6;
	public static final int CORNER_TOP = 7;

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

	private int xOffset = 0;
	private int yOffset = 0;

	private int corner = RectWindow.NO_CORNER;

	private MouseEvent moveClick = null;
	private MouseEvent resizeClick = null;
	
	private Window refWindow = null;

	private boolean isOffscreenAuthorized = true;

	public RectWindow(Window f, Dimension dim, Rectangle maxBounds_) {
		this.left = new LineWindow(f);
		this.right = new LineWindow(f);
		this.top = new LineWindow(f);
		this.bottom = new LineWindow(f);

		this.minX = maxBounds_.x;
		this.minY = maxBounds_.y;
		this.maxX = this.minX + dim.width;
		this.maxY = this.minY + dim.height;
		this.refWindow = f;
		
		this.minWidth = 2*RectWindow.BORDER_SIZE;
		this.minHeight = 2*RectWindow.BORDER_SIZE;
	}

	public void setCorner(int corner_) {
		this.corner = corner_;
	}

	private void resetClicks() {
		if (this.resizeClick != null)
			this.resizeClick = null;
		if(this.moveClick != null)
			this.moveClick = null;
	}

	public void setResizeClick(MouseEvent me) {
		this.resizeClick = me;
	}

	public void setMoveClick(MouseEvent me) {
		this.moveClick = me;
	}

	public boolean isResizing() {
		return (this.resizeClick != null);
	}

	public boolean isMoving() {
		return (this.moveClick != null);
	}

	public Point getOffsets() {
		return new Point(this.xOffset, this.yOffset);
	}

	public void setOffsets(Point offsets) {
		this.xOffset = offsets.x;
		this.yOffset = offsets.y;
	}

	public void setOffsets(int x, int y) {
		this.xOffset = x;
		this.yOffset = y;
	}

	public void offsetsResize(MouseEvent me, Rectangle bounds) {
		if (me == null || bounds == null)
			return;

		int x, y;

		switch (this.corner) {
			case RectWindow.CORNER_TOP_LEFT:
				x = bounds.x - me.getXOnScreen();
				y = bounds.y - me.getYOnScreen();
				break;
			case RectWindow.CORNER_BOTTOM_LEFT:
				x = bounds.x - me.getXOnScreen();
				y = bounds.height + bounds.y - me.getYOnScreen();
				break;
			case RectWindow.CORNER_BOTTOM_RIGHT:
				x = bounds.width + bounds.x - me.getXOnScreen();
				y = bounds.height + bounds.y - me.getYOnScreen();
				break;
			case RectWindow.CORNER_TOP_RIGHT:
				x = bounds.width + bounds.x - me.getXOnScreen();
				y = bounds.y - me.getYOnScreen();
				break;
			default:
				x = 0;
				y = 0;
				break;
		}
		this.setOffsets(x, y);
	}

	public void resize() {
		if (this.resizeClick == null)
			return;

		Rectangle r = new Rectangle();
		Rectangle bounds = this.refWindow.getBounds();
		switch (this.corner) {
			case RectWindow.CORNER_TOP_LEFT:
				r.x = this.resizeClick.getXOnScreen() + this.xOffset;
				r.y = this.resizeClick.getYOnScreen() + this.yOffset;
				r.width = bounds.x + bounds.width - r.x;
				r.height = bounds.y + bounds.height - r.y;
				break;
			case RectWindow.CORNER_BOTTOM_LEFT:
				r.x = this.resizeClick.getXOnScreen() + this.xOffset;
				r.y = bounds.y;
				r.width = bounds.x + bounds.width - r.x;
				r.height = this.resizeClick.getYOnScreen() - bounds.y + this.yOffset;
				break;
			case RectWindow.CORNER_BOTTOM_RIGHT:
				r.x = bounds.x;
				r.y = bounds.y;
				r.width = this.resizeClick.getXOnScreen() - r.x + this.xOffset;
				r.height = this.resizeClick.getYOnScreen() - r.y + this.yOffset;
				break;
			case RectWindow.CORNER_TOP_RIGHT:
				r.x = bounds.x;
				r.y = this.resizeClick.getYOnScreen() + this.yOffset;
				r.width = this.resizeClick.getXOnScreen() - bounds.x + this.xOffset;
				r.height = bounds.y + bounds.height - r.y;
				break;
			case RectWindow.CORNER_LEFT:
				r.x = this.resizeClick.getXOnScreen();
				r.y = bounds.y;
				r.width = bounds.x + bounds.width - r.x;
				r.height = bounds.height;
				break;
			case RectWindow.CORNER_BOTTOM:
				r.x = bounds.x;
				r.y = bounds.y;
				r.width = bounds.width;
				r.height = this.resizeClick.getYOnScreen() - r.y;
				break;
			case RectWindow.CORNER_RIGHT:
				r.x = bounds.x;
				r.y = bounds.y;
				r.width = this.resizeClick.getXOnScreen() - r.x;
				r.height = bounds.height;
				break;
			case RectWindow.CORNER_TOP:
				r.x = bounds.x;
				r.y = this.resizeClick.getYOnScreen();
				r.width = bounds.width;
				r.height = bounds.y + bounds.height - r.y;
				break;
			default:
				return;
		}
		this.setBounds(r);
	}

	private Rectangle fixBounds(Rectangle r) {
		if (this.isMoving()) {
			if (r.x < this.minX)
				r.x = this.minX;

			if (r.y < this.minY)
				r.y = this.minY;

			if (r.x+r.width > this.maxX)
				r.x =  this.maxX - r.width;
			
			if (r.y+r.height > this.maxY)
				r.y = this.maxY - r.height;
		}
		else if (this.isResizing()) {
			if (r.width < this.minWidth) {
				Rectangle ref = this.refWindow.getBounds();
				
				if (r.x != ref.x)
					r.x = ref.x+ref.width - this.minWidth;

				r.width = this.minWidth;
			}
			
			if (r.height < this.minHeight) {
				Rectangle ref = this.refWindow.getBounds();
				
				if (r.y != ref.y)
					r.y = ref.y+ref.height - this.minHeight;

				r.height = this.minHeight;
			}
		}
		
		return r;
	}
	
	
	@Override
	public void setBounds(Rectangle r) {
		if (! this.isOffscreenAuthorized)
			r = this.fixBounds(r);

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
		
		if (! b) {
			this.setOffsets(0, 0);
			this.resetClicks();
		}
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
