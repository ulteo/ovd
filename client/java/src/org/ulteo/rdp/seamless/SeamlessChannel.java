/* SeamlessChannel.java
 * Component: UlteoRDP
 * 
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
 * Author Thomas MOUTON <thomas@ulteo.com> 2009
 * 
 * Revision: $Revision: 0.2 $
 * Author: $Author: arnauvp $
 * Date: $Date: 2008/06/17 18:26:30 $
 *
 * Purpose: Allow seamless RDP session
 * 
 * Inspired by: 
 * Cendio RDP seamless.c
   Copyright (C) Peter Astrand <astrand@cendio.se> 2005-2006
   
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, version 2 of the License.
   
   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.
   
   You should have received a copy of the GNU General Public License
   along with this program; if not, write to the Free Software
   Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*/

package org.ulteo.rdp.seamless;

import java.awt.Dimension;
import java.awt.Frame;
import java.awt.Point;
import java.awt.Rectangle;
import java.awt.Window;
import java.awt.event.InputEvent;
import java.awt.event.MouseEvent;
import java.awt.event.MouseListener;
import java.awt.event.MouseMotionListener;
import java.io.IOException;
import net.propero.rdp.Common;
import net.propero.rdp.Options;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.crypto.CryptoException;
import net.propero.rdp.rdp5.seamless.SeamlessWindow;


public class SeamlessChannel extends net.propero.rdp.rdp5.seamless.SeamlessChannel implements MouseListener, MouseMotionListener {
	public SeamlessChannel(Options opt_, Common common_) {
		super(opt_, common_);
	}

	@Override
	protected boolean processCreate(long id, long group, long parent, long flags) {
		String name = "w_"+id;
		if( this.windows.containsKey(name)) {
		    logger.error("ID '"+id+"' already exist");
		    return false;
		}

		SeamlessFrame sf = new SeamlessFrame((int)id, (int)group, this.common);
		sf.setName(name);
		sf.addMouseListener(this);
		sf.addMouseMotionListener(this);

		this.addFrame(sf, name);

		return true;
	}

	private int detectCorner(MouseEvent e, Rectangle wndSize) {
		if (e == null)
			return -1;

		int xClick = e.getX();
		int yClick = e.getY();

		if (xClick >= 0 && xClick < RectWindow.SEAMLESS_CORNER_SIZE) {
			if (yClick >= 0 && yClick < RectWindow.SEAMLESS_CORNER_SIZE) {
				return RectWindow.CORNER_TOP_LEFT;
			}
			else if (yClick > (wndSize.height - RectWindow.SEAMLESS_CORNER_SIZE) && yClick <= wndSize.height) {
				return RectWindow.CORNER_BOTTOM_LEFT;
			}
			else if (yClick >= 0 && yClick <= wndSize.height) {
				return RectWindow.CORNER_LEFT;
			}
		}
		else if (xClick > (wndSize.width - RectWindow.SEAMLESS_CORNER_SIZE) && xClick <= wndSize.width) {
			if (yClick >= 0 && yClick < RectWindow.SEAMLESS_CORNER_SIZE) {
				return RectWindow.CORNER_TOP_RIGHT;
			}
			else if (yClick > (wndSize.height - RectWindow.SEAMLESS_CORNER_SIZE) && yClick <= wndSize.height) {
				return RectWindow.CORNER_BOTTOM_RIGHT;
			}
			else if (yClick >= 0 && yClick <= wndSize.height) {
				return RectWindow.CORNER_RIGHT;
			}
		}
		else if (yClick > (wndSize.height - RectWindow.SEAMLESS_BORDER_SIZE) && yClick <= wndSize.height) {
			return RectWindow.CORNER_BOTTOM;
		}
		else if (yClick >= 0 && yClick < RectWindow.SEAMLESS_BORDER_SIZE) {
			return RectWindow.CORNER_TOP;
		}
		
		return -1;
	}

	public void mousePressed(MouseEvent e) {
		String name = e.getComponent().getName();
		SeamlessMovingResizing sw = null;
		if (this.windows.containsKey(name))
			sw = (SeamlessMovingResizing)this.windows.get(name);
		if (sw == null) {
			System.err.println("Bad window ("+name+")");
			return;
		}

		if (sw.isMouseEventsLocked()) {
			System.err.println("Weird behavior, should never appear (ref: 20)");
			return;
		}

		if ((((SeamlessWindow)sw).sw_getExtendedState() != Frame.MAXIMIZED_BOTH) && ((e.getModifiers() & InputEvent.BUTTON1_MASK) == InputEvent.BUTTON1_MASK)) {
			int xClick = e.getX();
			int yClick = e.getY();
			Rectangle bounds = ((Window)sw).getBounds();
			RectWindow rw = sw.getRectWindow();

			if (	xClick < RectWindow.SEAMLESS_BORDER_SIZE ||
				xClick > (bounds.width - RectWindow.SEAMLESS_BORDER_SIZE) ||
				yClick < RectWindow.SEAMLESS_BORDER_SIZE ||
				yClick > (bounds.height - RectWindow.SEAMLESS_BORDER_SIZE)
			) {
				rw.setResizeClick(e);
				int corner = this.detectCorner(e, bounds);
				rw.setCorner(corner);

				rw.offsetsResize(e, bounds);
				rw.resize();
				sw.lockMouseEvents();
			}
			else if (
				yClick >= RectWindow.SEAMLESS_BORDER_SIZE &&
				yClick <= (RectWindow.SEAMLESS_BORDER_SIZE + RectWindow.SEAMLESS_TOP_BORDER_SIZE) &&
				xClick >= RectWindow.SEAMLESS_BORDER_SIZE &&
				xClick <= (((Window)sw).getWidth() - RectWindow.SEAMLESS_BORDER_SIZE)
			) {
				rw.setOffsets((e.getXOnScreen() - bounds.x), (e.getYOnScreen() - bounds.y));
				rw.setBounds(bounds);
				rw.setMoveClick(e);

				sw.lockMouseEvents();
			}
		}

		sw.processMouseEvent(e, SeamlessMovingResizing.MOUSE_PRESSED);
	}

	public void mouseReleased(MouseEvent e) {
		String name = e.getComponent().getName();
		SeamlessMovingResizing sw = null;
		if (this.windows.containsKey(name))
			sw = (SeamlessMovingResizing)this.windows.get(name);
		if (sw == null) {
			System.err.println("Bad window ("+name+")");
			return;
		}

		if (sw.isMouseEventsLocked()) {
			sw.processMouseEvent(e, SeamlessMovingResizing.MOUSE_RELEASED);
			sw.unlockMouseEvents();

			RectWindow rw = sw.getRectWindow();
			Rectangle r = rw.getBounds();
			if (! ((Window)sw).getBounds().equals(r)) {
				try {
					this.send_position(((SeamlessWindow)sw).sw_getId(), r.x, r.y, r.width, r.height, 0);
				} catch (RdesktopException ex) {
					logger.error(ex);
				} catch (IOException ex) {
					logger.error(ex);
				} catch (CryptoException ex) {
					logger.error(ex);
				}
			}
			return;
		}

		sw.processMouseEvent(e, SeamlessMovingResizing.MOUSE_RELEASED);
	}

	public void mouseDragged(MouseEvent e) {
		String name = e.getComponent().getName();
		SeamlessMovingResizing sw = null;
		if (this.windows.containsKey(name))
			sw = (SeamlessMovingResizing)this.windows.get(name);
		if (sw == null) {
			System.err.println("Bad window ("+name+")");
			return;
		}

		RectWindow rw = sw.getRectWindow();

		if (sw.isMouseEventsLocked()) {
			if (rw.isResizing()) {
				rw.setResizeClick(e);
				rw.resize();
			}
			else if (rw.isMoving()) {
				Dimension size = ((Window)sw).getSize();
				Point offsets = rw.getOffsets();
				int x_rw = e.getXOnScreen() - offsets.x;
				int y_rw = e.getYOnScreen() - offsets.y;
				rw.setBounds(new Rectangle(x_rw, y_rw, size.width, size.height));
			}
			return;
		}

		if (rw.isResizing() || rw.isMoving()) {
			sw.lockMouseEvents();

			return;
		}

		sw.processMouseEvent(e, SeamlessMovingResizing.MOUSE_DRAGGED);
	}

	public void mouseMoved(MouseEvent e) {
		String name = e.getComponent().getName();
		SeamlessMovingResizing sw = null;
		if (this.windows.containsKey(name))
			sw = (SeamlessMovingResizing)this.windows.get(name);
		if (sw == null) {
			System.err.println("Bad window ("+name+")");
			return;
		}

		if (sw.isMouseEventsLocked())
			return;

		sw.processMouseEvent(e, SeamlessMovingResizing.MOUSE_MOVED);
	}

	public void mouseEntered(MouseEvent me) {}
	public void mouseExited(MouseEvent me) {}
	public void mouseClicked(MouseEvent me) {}
}
