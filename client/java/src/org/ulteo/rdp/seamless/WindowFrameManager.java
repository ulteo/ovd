/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2011
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

import java.awt.Rectangle;
import java.awt.event.MouseEvent;
import java.awt.event.MouseListener;
import java.awt.event.MouseMotionListener;
import java.util.Date;
import net.propero.rdp.rdp5.seamless.SeamlessWindow;

public class WindowFrameManager implements MouseListener, MouseMotionListener {

	private static final int WINDOW_BORDER_SIZE = 4;
	private static final int WINDOW_TOP_BORDER_SIZE = 20;
	private static final int WINDOW_CORNER_SIZE = 20;

	private SeamlessChannel seamchan = null;

	private SeamlessWindowProperties properties = null;

	private MouseEvent lastMouseEvent = null;
	private MouseClick currentClick = null;

	public WindowFrameManager(SeamlessChannel seamchan_) {
		if (seamchan_ == null)
			throw new NullPointerException("Seamless channel is null");

		this.seamchan = seamchan_;

		this.properties = new SeamlessWindowProperties();
		this.properties.borderSize = WINDOW_BORDER_SIZE;
		this.properties.topBorderSize = WINDOW_TOP_BORDER_SIZE;
		this.properties.cornerSize = WINDOW_CORNER_SIZE;
	}

	public void mousePressed(MouseEvent me) {
		SeamlessMovingResizing sw = (SeamlessMovingResizing) me.getComponent();

		this.lastMouseEvent = new MouseEvent(me.getComponent(), me.getID(), new Date().getTime(), me.getModifiers(), me.getX(), me.getY(), me.getClickCount(), me.isPopupTrigger(), me.getButton());

		sw.processMouseEvent(me, SeamlessMovingResizing.MOUSE_PRESSED);
	}

	public void mouseReleased(MouseEvent me) {
		SeamlessWindow sw = (SeamlessWindow) me.getComponent();

		if (this.currentClick != null) {
			((SeamlessMovingResizing) sw).getRectWindow().setVisible(false);

			Rectangle bounds = this.currentClick.getNewBounds();
			this.seamchan.updatePosition(sw, bounds);

			this.currentClick = null;
		}
		else {
			((SeamlessMovingResizing) sw).processMouseEvent(me, SeamlessMovingResizing.MOUSE_RELEASED);
		}

		this.lastMouseEvent = null;
	}

	public void mouseDragged(MouseEvent me) {
		if (this.lastMouseEvent == null) {
			return;
		}

		SeamlessMovingResizing sw = (SeamlessMovingResizing) me.getComponent();

		RectWindow rw = sw.getRectWindow();

		MouseClick click = null;
		if (this.currentClick == null) {
			click = new MouseClick(this.lastMouseEvent, this.properties);

			MouseEvent releaseClick = click.getReleaseEvent();
			sw.processMouseEvent(releaseClick, SeamlessMovingResizing.MOUSE_RELEASED);
		}
		else
			click = this.currentClick;

		if (click.getType() != MouseClick.MOVING_CLICK
			&& click.getType() != MouseClick.RESIZING_CLICK)
		{
			sw.processMouseEvent(me, SeamlessMovingResizing.MOUSE_DRAGGED);

			this.currentClick = null;
			this.lastMouseEvent = null;
			return;
		}

		click.update(me);

		rw.setBounds(click.getNewBounds());
		rw.setVisible(true);

		this.currentClick = click;
	}

	public void mouseMoved(MouseEvent me) {
		if (this.lastMouseEvent != null)
			return;

		SeamlessMovingResizing sw = (SeamlessMovingResizing) me.getComponent();
		sw.processMouseEvent(me, SeamlessMovingResizing.MOUSE_MOVED);
	}
	public void mouseClicked(MouseEvent me) {}
	public void mouseEntered(MouseEvent me) {}
	public void mouseExited(MouseEvent me) {}
}
