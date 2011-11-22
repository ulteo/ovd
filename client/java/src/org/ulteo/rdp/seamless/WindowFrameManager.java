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
import java.awt.Window;
import java.awt.event.MouseEvent;
import java.awt.event.MouseListener;
import java.awt.event.MouseMotionListener;
import java.util.Date;
import net.propero.rdp.Options;
import net.propero.rdp.rdp5.seamless.SeamlessWindow;

public class WindowFrameManager implements MouseListener, MouseMotionListener {

	private static final int WINDOW_BORDER_SIZE = 4;
	private static final int WINDOW_TOP_BORDER_SIZE = 20;
	private static final int WINDOW_CORNER_SIZE = 20;

	private SeamlessChannel seamchan = null;
	private Options opt = null;

	private SeamlessWindowProperties properties = null;

	private MouseEvent lastMouseEvent = null;
	private MouseClick currentClick = null;

	public WindowFrameManager(SeamlessChannel seamchan_, Options opt_) {
		if (seamchan_ == null)
			throw new NullPointerException("Seamless channel is null");

		this.seamchan = seamchan_;
		this.opt = opt_;

		this.properties = new SeamlessWindowProperties();
		this.properties.borderSize = WINDOW_BORDER_SIZE;
		this.properties.topBorderSize = WINDOW_TOP_BORDER_SIZE;
		this.properties.cornerSize = WINDOW_CORNER_SIZE;
	}

	private void processMouseEvent(MouseEvent me, int type) {
		Window wnd = (Window) me.getComponent();

		int dx = wnd.getX() - this.opt.x_offset;
		int dy = wnd.getY() - this.opt.y_offset;
		me.translatePoint(dx, dy);

		((SeamlessMovingResizing) wnd).processMouseEvent(me, type);
	}

	public void mousePressed(MouseEvent me) {
		SeamlessMovingResizing sw = (SeamlessMovingResizing) me.getComponent();

		this.lastMouseEvent = new MouseEvent(me.getComponent(), me.getID(), new Date().getTime(), me.getModifiers(), me.getX(), me.getY(), me.getClickCount(), me.isPopupTrigger(), me.getButton());

		this.processMouseEvent(me, SeamlessMovingResizing.MOUSE_PRESSED);
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
			this.processMouseEvent(me, SeamlessMovingResizing.MOUSE_RELEASED);
		}

		this.lastMouseEvent = null;
	}

	public void mouseDragged(MouseEvent me) {
		if (this.lastMouseEvent == null) {
			return;
		}

		SeamlessMovingResizing sw = (SeamlessMovingResizing) me.getComponent();

		RectWindow rw = sw.getRectWindow();

		MouseClick click = this.currentClick;
		if (click == null)
			click = new MouseClick(this.lastMouseEvent, this.properties);
		
		if (click.getType() != MouseClick.MOVING_CLICK
			&& click.getType() != MouseClick.RESIZING_CLICK)
		{
			this.processMouseEvent(me, SeamlessMovingResizing.MOUSE_DRAGGED);

			this.currentClick = null;
			return;
		}

		if (this.currentClick == null) {
			MouseEvent releaseClick = click.getReleaseEvent();
			this.processMouseEvent(releaseClick, SeamlessMovingResizing.MOUSE_RELEASED);
		}

		click.update(me);

		rw.setBounds(click.getNewBounds());
		rw.setVisible(true);

		this.currentClick = click;
	}

	public void mouseMoved(MouseEvent me) {
		if (this.lastMouseEvent != null)
			return;

		this.processMouseEvent(me, SeamlessMovingResizing.MOUSE_MOVED);
	}
	public void mouseClicked(MouseEvent me) {}
	public void mouseEntered(MouseEvent me) {}
	public void mouseExited(MouseEvent me) {}
}
