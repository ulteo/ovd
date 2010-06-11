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
import java.awt.event.MouseEvent;
import net.propero.rdp.Common;
import net.propero.rdp.rdp5.seamless.SeamFrame;


public class SeamlessFrame extends SeamFrame implements SeamlessMovingResizing {
	protected boolean lockMouseEvents = false;
	protected RectWindow rw = null;

	public SeamlessFrame(int id_, int group_, Common common_) {
		super(id_, group_, common_);
		
		Dimension dim = new Dimension(this.backstore.getWidth(), this.backstore.getHeight());
		this.rw = new RectWindow(this, dim);
		this.toFront();
	}

	public boolean isMouseEventsLocked() {
		return this.lockMouseEvents;
	}

	public void lockMouseEvents() {
		this.lockMouseEvents = true;
		this.rw.setVisible(true);
	}

	public void unlockMouseEvents() {
		this.lockMouseEvents = false;
		this.rw.setVisible(false);
		this.rw.setOffsets(0, 0);
		this.rw.resetClicks();
	}

	public RectWindow getRectWindow() {
		return this.rw;
	}

	public void processMouseEvent(MouseEvent e, int type) {
		switch (type) {
			case MOUSE_PRESSED:
				this.mousePressed(e);
				break;
			case MOUSE_RELEASED:
				this.mouseReleased(e);
				break;
			case MOUSE_MOVED:
				this.mouseMoved(e);
				break;
			case MOUSE_DRAGGED:
				this.mouseDragged(e);
				break;
			default:
				break;
		}
	}
}
