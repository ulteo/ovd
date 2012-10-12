/*
 * Copyright (C) 2011-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2011-2012
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

import java.awt.Component;
import java.awt.Dimension;
import java.awt.Point;
import java.awt.Rectangle;
import java.awt.event.MouseEvent;
import java.util.Date;

public class MouseClick {
	public static final int UNKNOWN_CLICK = 0;
	public static final int MOVING_CLICK = 1;
	public static final int RESIZING_CLICK = 2;

	private static final int UNKNOWN_CORNER = 0;
	private static final int TOPLEFT_CORNER = 1;
	private static final int TOP = 2;
	private static final int TOPRIGHT_CORNER = 3;
	private static final int RIGHT = 4;
	private static final int BOTTOMRIGHT_CORNER = 5;
	private static final int BOTTOM = 6;
	private static final int BOTTOMLEFT_CORNER = 7;
	private static final int LEFT = 8;
	
	private static int retrieveType(MouseEvent evt, SeamlessWindowProperties props) {
		int x = evt.getX();
		int y = evt.getY();
		
		Component c = evt.getComponent();
		Rectangle bounds = c.getBounds();

		if (((SeamlessMovingResizing) c)._isResizable() && (x < props.borderSize || x > (bounds.width - props.borderSize) ||
			y < props.borderSize || y > (bounds.height - props.borderSize))
		) {
			return RESIZING_CLICK;
		}
		else if (y >= props.borderSize && y <= (props.borderSize + props.topBorderSize) &&
			x >= props.borderSize && x <= (bounds.width - props.borderSize)
		) {
			return MOVING_CLICK;
		}

		return UNKNOWN_CLICK;
	}

	private static int retrieveLocation(MouseEvent evt, SeamlessWindowProperties props) {
		int x = evt.getX();
		int y = evt.getY();

		Rectangle bounds = evt.getComponent().getBounds();

		if (x >= 0 && x < props.cornerSize) {
			if (y >= 0 && y < props.cornerSize) {
				return TOPLEFT_CORNER;
			}
			else if (y > (bounds.height - props.cornerSize) && y <= bounds.height) {
				return BOTTOMLEFT_CORNER;
			}
			else if (y >= 0 && y <= bounds.height) {
				return LEFT;
			}
		}
		else if (x > (bounds.width - props.cornerSize) && x <= bounds.width) {
			if (y >= 0 && y < props.cornerSize) {
				return TOPRIGHT_CORNER;
			}
			else if (y > (bounds.height - props.cornerSize) && y <= bounds.height) {
				return BOTTOMRIGHT_CORNER;
			}
			else if (y >= 0 && y <= bounds.height) {
				return RIGHT;
			}
		}
		else if (y > (bounds.height - props.borderSize) && y <= bounds.height) {
			return BOTTOM;
		}
		else if (y >= 0 && y < props.borderSize) {
			return TOP;
		}

		return UNKNOWN_CORNER;
	}

	private static Dimension retrieveCornerOffsets(MouseEvent evt, int corner) {
		Dimension dim = new Dimension();
		
		Rectangle bounds = evt.getComponent().getBounds();

		switch (corner) {
			case TOPLEFT_CORNER:
				dim.width = bounds.x - evt.getXOnScreen();
				dim.height = bounds.y - evt.getYOnScreen();
				break;
			case BOTTOMLEFT_CORNER:
				dim.width = bounds.x - evt.getXOnScreen();
				dim.height = bounds.height + bounds.y - evt.getYOnScreen();
				break;
			case BOTTOMRIGHT_CORNER:
				dim.width = bounds.width + bounds.x - evt.getXOnScreen();
				dim.height = bounds.height + bounds.y - evt.getYOnScreen();
				break;
			case TOPRIGHT_CORNER:
				dim.width = bounds.width + bounds.x - evt.getXOnScreen();
				dim.height = bounds.y - evt.getYOnScreen();
				break;
			default:
				dim.width = 0;
				dim.height = 0;
				break;
		}

		return dim;
	}

	private MouseEvent evt = null;
	private SeamlessWindowProperties props = null;
	
	private int type = UNKNOWN_CLICK;
	private int corner = UNKNOWN_CORNER;
	private Dimension offsets = null;
	private Rectangle bounds = null;

	public MouseClick(MouseEvent evt_, SeamlessWindowProperties props_) {
		if (evt_ == null)
			throw new NullPointerException("Mouse event is null");

		if (evt_.getID() != MouseEvent.MOUSE_PRESSED)
			throw new IllegalArgumentException("Mouse event id must be MOUSE_PRESSED");

		this.evt = evt_;
		this.props = props_;

		this.bounds = (Rectangle) this.evt.getComponent().getBounds().clone();

		this.type = retrieveType(this.evt, this.props);
		switch (this.type) {
			case RESIZING_CLICK:
				this.corner = retrieveLocation(this.evt, this.props);
				break;
			case MOVING_CLICK:
				this.corner = TOPLEFT_CORNER;
				break;
			default:
				return;
		}

		this.offsets = retrieveCornerOffsets(this.evt, this.corner);
	}

	public int getType() {
		return this.type;
	}

	public void update(MouseEvent evt_) {
		if (evt_.getComponent() != this.evt.getComponent())
			return;

		if (this.type == MOVING_CLICK) {
			this.bounds.x = evt_.getXOnScreen() + this.offsets.width;
			this.bounds.y = evt_.getYOnScreen() + this.offsets.height;
		}
		else if (this.type == RESIZING_CLICK) {
			Rectangle r = new Rectangle();
			Rectangle componentBounds = this.evt.getComponent().getBounds();
			switch (this.corner) {
				case TOPLEFT_CORNER:
					r.x = evt_.getXOnScreen() + this.offsets.width;
					r.y = evt_.getYOnScreen() + this.offsets.height;
					r.width = componentBounds.x + componentBounds.width - r.x;
					r.height = componentBounds.y + componentBounds.height - r.y;
					break;
				case BOTTOMLEFT_CORNER:
					r.x = evt_.getXOnScreen() + this.offsets.width;
					r.y = componentBounds.y;
					r.width = componentBounds.x + componentBounds.width - r.x;
					r.height = evt_.getYOnScreen() - componentBounds.y + this.offsets.height;
					break;
				case BOTTOMRIGHT_CORNER:
					r.x = componentBounds.x;
					r.y = componentBounds.y;
					r.width = evt_.getXOnScreen() - r.x + this.offsets.width;
					r.height = evt_.getYOnScreen() - r.y + this.offsets.height;
					break;
				case TOPRIGHT_CORNER:
					r.x = componentBounds.x;
					r.y = evt_.getYOnScreen() + this.offsets.height;
					r.width = evt_.getXOnScreen() - componentBounds.x + this.offsets.width;
					r.height = componentBounds.y + componentBounds.height - r.y;
					break;
				case LEFT:
					r.x = evt_.getXOnScreen();
					r.y = componentBounds.y;
					r.width = componentBounds.x + componentBounds.width - r.x;
					r.height = componentBounds.height;
					break;
				case BOTTOM:
					r.x = componentBounds.x;
					r.y = componentBounds.y;
					r.width = componentBounds.width;
					r.height = evt_.getYOnScreen() - r.y;
					break;
				case RIGHT:
					r.x = componentBounds.x;
					r.y = componentBounds.y;
					r.width = evt_.getXOnScreen() - r.x;
					r.height = componentBounds.height;
					break;
				case TOP:
					r.x = componentBounds.x;
					r.y = evt_.getYOnScreen();
					r.width = componentBounds.width;
					r.height = componentBounds.y + componentBounds.height - r.y;
					break;
				default:
					return;
			}

			if (r.width <= this.props.borderSize || r.height <= this.props.borderSize)
				return;

			this.bounds = r;
		}
	}

	public Rectangle getNewBounds() {
		return (Rectangle) this.bounds.clone();
	}

	public MouseEvent getReleaseEvent() {
		return new MouseEvent(this.evt.getComponent(), MouseEvent.MOUSE_RELEASED, new Date().getTime(), this.evt.getModifiers(), this.evt.getX(), this.evt.getY(), this.evt.getClickCount(), this.evt.isPopupTrigger(), this.evt.getButton());
	}
}
