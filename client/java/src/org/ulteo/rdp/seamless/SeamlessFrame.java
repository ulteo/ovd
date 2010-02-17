/*
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
 * Author Thomas MOUTON <thomas@ulteo.com> 2009-2010
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

import java.awt.Dimension;
import java.awt.Rectangle;
import java.awt.event.InputEvent;
import java.awt.event.MouseEvent;
import java.io.IOException;
import java.util.logging.Level;
import java.util.logging.Logger;
import net.propero.rdp.Common;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.crypto.CryptoException;
import net.propero.rdp.rdp5.seamless.SeamFrame;


public class SeamlessFrame extends SeamFrame {
	protected static int SEAMLESS_BORDER_SIZE = 4;
	protected static int SEAMLESS_TOP_BORDER_SIZE = 20;
	protected static int SEAMLESS_CORNER_SIZE = 20;

	protected static final int NO_CORNER = -1;
	protected static final int CORNER_TOP_LEFT = 0;
	protected static final int CORNER_BOTTOM_LEFT = 1;
	protected static final int CORNER_TOP_RIGHT = 2;
	protected static final int CORNER_BOTTOM_RIGHT = 3;
	protected static final int CORNER_LEFT = 4;
	protected static final int CORNER_BOTTOM = 5;
	protected static final int CORNER_RIGHT = 6;
	protected static final int CORNER_TOP = 7;

	protected boolean lockMouseEvents = false;
	protected RectWindow rw = null;

	protected MouseEvent moveClick = null;
	protected int xOffset = 0;
	protected int yOffset = 0;
	
	protected MouseEvent resizeClick = null;
	protected int corner = SeamlessFrame.NO_CORNER;

	public SeamlessFrame(int id_, int group_, Common common_) {
		super(id_, group_, common_);

		Dimension dim = new Dimension(this.backstore.getHeight(), this.backstore.getWidth());
		this.rw = new RectWindow(this, dim);
		this.toFront();
	}

	protected void lockMouseEvents() {
		lockMouseEvents = true;
		this.rw.setVisible(true);
	}

	protected void unlockMouseEvents() {
		this.rw.setVisible(false);
		this.lockMouseEvents = false;
		this.xOffset = 0;
		this.yOffset = 0;

		if (this.resizeClick != null)
			this.resizeClick = null;
		if(this.moveClick != null)
			this.moveClick = null;
	}
	
	protected int detectCorner(MouseEvent e) {
		if (e == null)
			return -1;

		int xClick = e.getX();
		int yClick = e.getY();

		if (xClick >= 0 && xClick < SeamlessFrame.SEAMLESS_CORNER_SIZE) {
			if (yClick >= 0 && yClick < SeamlessFrame.SEAMLESS_CORNER_SIZE) {
				return SeamlessFrame.CORNER_TOP_LEFT;
			}
			else if (yClick > (this.getHeight() - SeamlessFrame.SEAMLESS_CORNER_SIZE) && yClick <= this.getHeight()) {
				return SeamlessFrame.CORNER_BOTTOM_LEFT;
			}
			else if (yClick >= 0 && yClick <= this.getHeight()) {
				return SeamlessFrame.CORNER_LEFT;
			}
		}
		else if (xClick > (this.getWidth() - SeamlessFrame.SEAMLESS_CORNER_SIZE) && xClick <= this.getWidth()) {
			if (yClick >= 0 && yClick < SeamlessFrame.SEAMLESS_CORNER_SIZE) {
				return SeamlessFrame.CORNER_TOP_RIGHT;
			}
			else if (yClick > (this.getHeight() - SeamlessFrame.SEAMLESS_CORNER_SIZE) && yClick <= this.getHeight()) {
				return SeamlessFrame.CORNER_BOTTOM_RIGHT;
			}
			else if (yClick >= 0 && yClick <= this.getHeight()) {
				return SeamlessFrame.CORNER_RIGHT;
			}
		}
		else if (yClick > (this.getHeight() - SeamlessFrame.SEAMLESS_BORDER_SIZE) && yClick <= this.getHeight()) {
			return SeamlessFrame.CORNER_BOTTOM;
		}
		else if (yClick >= 0 && yClick < SeamlessFrame.SEAMLESS_BORDER_SIZE) {
			return SeamlessFrame.CORNER_TOP;
		}
		return -1;
	}

	protected void offsetResize(MouseEvent me) {
		if (me == null)
			return;

		switch (this.corner) {
			case SeamlessFrame.CORNER_TOP_LEFT:
				this.xOffset = this.getX() - me.getXOnScreen();
				this.yOffset = this.getY() - me.getYOnScreen();
				break;
			case SeamlessFrame.CORNER_BOTTOM_LEFT:
				this.xOffset = this.getX() - me.getXOnScreen();
				this.yOffset = this.getHeight() - me.getY();
				break;
			case SeamlessFrame.CORNER_BOTTOM_RIGHT:
				this.xOffset = this.getWidth() - me.getX();
				this.yOffset = this.getHeight() - me.getY();
				break;
			case SeamlessFrame.CORNER_TOP_RIGHT:
				this.xOffset = this.getWidth() - me.getX();
				this.yOffset = this.getY() - me.getYOnScreen();
				break;
			default:
				this.xOffset = 0;
				this.yOffset = 0;
				break;
		}
	}

	protected Rectangle getRWSize(MouseEvent me) {
		if (me == null)
			return null;

		Rectangle r = new Rectangle();
		switch (this.corner) {
			case SeamlessFrame.CORNER_TOP_LEFT:
				r.x = me.getXOnScreen() + this.xOffset;
				r.y = me.getYOnScreen() + this.yOffset;
				r.width = this.getX() + this.getWidth() - r.x;
				r.height = this.getY() + this.getHeight() - r.y;
				break;
			case SeamlessFrame.CORNER_BOTTOM_LEFT:
				r.x = me.getXOnScreen() + this.xOffset;
				r.y = this.getY();
				r.width = this.getX() + this.getWidth() - r.x;
				r.height = me.getYOnScreen() - this.getY() + this.yOffset;
				break;
			case SeamlessFrame.CORNER_BOTTOM_RIGHT:
				r.x = this.getX();
				r.y = this.getY();
				r.width = me.getXOnScreen() - r.x + this.xOffset;
				r.height = me.getYOnScreen() - r.y + this.yOffset;
				break;
			case SeamlessFrame.CORNER_TOP_RIGHT:
				r.x = this.getX();
				r.y = me.getYOnScreen() + this.yOffset;
				r.width = me.getXOnScreen() - this.getX() + this.xOffset;
				r.height = this.getY() + this.getHeight() - r.y;
				break;
			case SeamlessFrame.CORNER_LEFT:
				r.x = me.getXOnScreen();
				r.y = this.getY();
				r.width = this.getX() + this.getWidth() - r.x;
				r.height = this.getHeight();
				break;
			case SeamlessFrame.CORNER_BOTTOM:
				r.x = this.getX();
				r.y = this.getY();
				r.width = this.getWidth();
				r.height = me.getYOnScreen() - r.y;
				break;
			case SeamlessFrame.CORNER_RIGHT:
				r.x = this.getX();
				r.y = this.getY();
				r.width = me.getXOnScreen() - r.x;
				r.height = this.getHeight();
				break;
			case SeamlessFrame.CORNER_TOP:
				r.x = this.getX();
				r.y = me.getYOnScreen();
				r.width = this.getWidth();
				r.height = this.getY() + this.getHeight() - r.y;
				break;
			default:
				return null;
		}
		return r;
	}

	protected void resizeRW(MouseEvent me) {
		Rectangle r = this.getRWSize(me);
		
		if (r == null)
			return;
				
		this.rw.setBounds(r.x, r.y, r.width, r.height);
	}

	@Override
	public void mousePressed(MouseEvent e) {
		if (this.lockMouseEvents) {
			System.err.println("Weird behavior, should never appear (ref: 20)");
			return;
		}

		if ((e.getModifiers() & InputEvent.BUTTON1_MASK) == InputEvent.BUTTON1_MASK) {
			int xClick = e.getX();
			int yClick = e.getY();

			if (	xClick < SeamlessFrame.SEAMLESS_BORDER_SIZE ||
				xClick > (this.width - SeamlessFrame.SEAMLESS_BORDER_SIZE) ||
				yClick < SeamlessFrame.SEAMLESS_BORDER_SIZE ||
				yClick > (this.height - SeamlessFrame.SEAMLESS_BORDER_SIZE)
			) {
				this.resizeClick = e;
				this.corner = this.detectCorner(this.resizeClick);
				
				this.offsetResize(e);
				this.resizeRW(e);
				this.lockMouseEvents();
			}
			else if (
				yClick >= SeamlessFrame.SEAMLESS_BORDER_SIZE &&
				yClick <= (SeamlessFrame.SEAMLESS_BORDER_SIZE + SeamlessFrame.SEAMLESS_TOP_BORDER_SIZE) &&
				xClick >= SeamlessFrame.SEAMLESS_BORDER_SIZE &&
				xClick <= (this.width - SeamlessFrame.SEAMLESS_BORDER_SIZE)
			) {
				this.xOffset = e.getXOnScreen() - this.getX();
				this.yOffset = e.getYOnScreen() - this.getY();

				this.rw.setBounds(this.getX(), this.getY(), this.getWidth(), this.getHeight());
				
				this.moveClick = e;
				this.lockMouseEvents();
			}
		}
		
		super.mousePressed(e);
	}

	@Override
	public void mouseReleased(MouseEvent e) {
		if (this.lockMouseEvents) {
			Rectangle r = null;
			if (this.resizeClick != null) {
				r = this.getRWSize(e);
			}
			else if (this.moveClick != null) {
				r = new Rectangle();
				
				r.x = e.getXOnScreen() - this.xOffset;
				r.y = e.getYOnScreen() - this.yOffset;
				r.width = this.getWidth();
				r.height = this.getHeight();
			}

			super.mouseReleased(e);
			this.unlockMouseEvents();
			
			if (r != null && ! this.getBounds().equals(r)) {
				try {
					this.common.seamlessChannelInstance.send_position(this.id, r.x, r.y, r.width, r.height, 0);
				} catch (RdesktopException ex) {
					Logger.getLogger(SeamlessFrame.class.getName()).log(Level.SEVERE, null, ex);
				} catch (IOException ex) {
					Logger.getLogger(SeamlessFrame.class.getName()).log(Level.SEVERE, null, ex);
				} catch (CryptoException ex) {
					Logger.getLogger(SeamlessFrame.class.getName()).log(Level.SEVERE, null, ex);
				}
			}
			return;
		}
		
		super.mouseReleased(e);
	}

	@Override
	public void mouseMoved(MouseEvent e) {
		if (this.lockMouseEvents)
			return;
		
		super.mouseMoved(e);
	}

	@Override
	public void mouseDragged(MouseEvent e) {
		if (this.lockMouseEvents) {
			if (this.resizeClick != null) {
				this.resizeRW(e);
			}
			else if (this.moveClick != null) {
				int x_rw = e.getXOnScreen() - this.xOffset;
				int y_rw = e.getYOnScreen() - this.yOffset;
				this.rw.setBounds(x_rw, y_rw, this.width, this.height);
			}
			return;
		}

		if (this.resizeClick != null || this.moveClick != null) {
			this.lockMouseEvents();
			
			return;
		}

		super.mouseDragged(e);
	}
}
