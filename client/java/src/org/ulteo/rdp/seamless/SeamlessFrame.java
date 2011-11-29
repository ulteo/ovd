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
import java.awt.Frame;
import java.awt.Rectangle;
import java.awt.event.FocusEvent;
import java.awt.event.FocusListener;
import java.awt.event.MouseEvent;

import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.utils.AbstractFocusManager;

import net.propero.rdp.Common;
import net.propero.rdp.Input;
import net.propero.rdp.rdp5.seamless.SeamFrame;
import org.ulteo.gui.GUIActions;


public class SeamlessFrame extends SeamFrame implements SeamlessMovingResizing, FocusListener {
	public static AbstractFocusManager focusManager = null;
	
	protected boolean lockMouseEvents = false;
	protected RectWindow rw = null;
	
	private Input input = null;

	public SeamlessFrame(int id_, int group_, Rectangle maxBounds_, int flags, Common common_) {
		super(id_, group_, maxBounds_, common_);
		
		this.parseFlags(flags);
		
		Dimension dim = new Dimension(this.backstore.getWidth(), this.backstore.getHeight());
		this.rw = new RectWindow(this, dim, this.maxBounds);
		this.addFocusListener(this);
		input = this.common.canvas.getInput();

		GUIActions.setIconImage(this, GUIActions.DEFAULT_APP_ICON).run();
	}

	private void parseFlags(int flags) {
		if ((flags & SeamlessChannel.WINDOW_CREATE_FIXEDSIZE) != 0)
			this.setResizable(false);
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
	}

	public RectWindow getRectWindow() {
		return this.rw;
	}

	public boolean _isResizable() {
		return this.isResizable();
	}

	@Override
	public void sw_setMyPosition(int x, int y, int width, int height) {
		super.sw_setMyPosition(x, y, width, height);

		if (this.isMouseEventsLocked())
			this.unlockMouseEvents();
	}

	@Override
	public void sw_setExtendedState(int state) {
		super.sw_setExtendedState(state);

		if (state == Frame.ICONIFIED && this.rw.isVisible())
			this.rw.setVisible(false);
	}

	@Override
	public void sw_destroy() {
		super.sw_destroy();

		if (this.rw.isVisible())
			this.rw.setVisible(false);
	}

	public void processMouseEvent(MouseEvent e, int type) {
		switch (type) {
			case MOUSE_PRESSED:
				this.mouseAdapter.mousePressed(e);
				break;
			case MOUSE_RELEASED:
				this.mouseAdapter.mouseReleased(e);
				break;
			case MOUSE_MOVED:
				this.mouseMotionAdapter.mouseMoved(e);
				break;
			case MOUSE_DRAGGED:
				this.mouseMotionAdapter.mouseDragged(e);
				break;
			default:
				break;
		}
	}
	
	@Override
	public void focusGained(FocusEvent e) {
		if (OSTools.isWindows())
			((sun.awt.im.InputContext)this.getInputContext()).disableNativeIM();

		if (SeamlessFrame.focusManager != null)
		{
			SeamlessFrame.focusManager.performedFocusLost(this);
		}
	}

	@Override
	public void focusLost(FocusEvent e) {
		input.lostFocus();
		if (SeamlessFrame.focusManager != null)
		{
			SeamlessFrame.focusManager.performedFocusLost(this);
		}
	}
}
