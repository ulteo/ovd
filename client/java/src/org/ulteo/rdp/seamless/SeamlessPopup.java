/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

import java.awt.Cursor;
import java.awt.Dimension;
import java.awt.Frame;
import java.awt.Graphics;
import java.awt.Image;
import java.awt.MouseInfo;
import java.awt.Rectangle;
import java.awt.Window;
import java.awt.event.FocusEvent;
import java.awt.event.FocusListener;
import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;
import java.awt.event.MouseMotionAdapter;
import java.awt.event.WindowListener;
import java.awt.event.WindowStateListener;
import javax.swing.JDialog;

import org.ulteo.utils.AbstractFocusManager;

import net.propero.rdp.Common;
import net.propero.rdp.Input;
import net.propero.rdp.WrappedImage;
import net.propero.rdp.rdp5.seamless.SeamlessWindow;

public class SeamlessPopup extends JDialog implements SeamlessWindow, SeamlessMovingResizing, FocusListener {
	public static AbstractFocusManager focusManager = null;

	private Common common = null;
	private int id;
	private int group;
	private Window parent;
	private int x;
	private int y;
	private int width;
	private int height;
	protected Rectangle maxBounds = null;
	private MouseAdapter mouseAdapter = null;
	private MouseMotionAdapter mouseMotionAdapter = null;
	private WrappedImage backstore = null;

	private boolean modal = false;

	private boolean lockMouseEvents = false;
	private RectWindow rw = null;

	public SeamlessPopup(int id_, int group_, Window parent_, Rectangle maxBounds_, int flags, Common common_) {
		super(parent_);
		
		this.common = common_;
		this.id = id_;
		this.group = group_;
		this.parent = parent_;
		this.maxBounds = maxBounds_;

		this.backstore = this.common.canvas.backstore;
		this.common.canvas.addComponentListener(this);

		Dimension dim = new Dimension(this.backstore.getWidth(), this.backstore.getHeight());
		this.rw = new RectWindow(this, dim, this.maxBounds);

		// Set the key and mouse listeners
		Input input = this.common.canvas.getInput();

		this.mouseAdapter = input.getMouseAdapter();
		this.mouseMotionAdapter = input.getMouseMotionAdapter();

		this.addKeyListener(input.getKeyAdapter());
		if (MouseInfo.getNumberOfButtons() > 3)
			this.addMouseWheelListener(this.mouseAdapter);

		this.parseFlags(flags);

		this.setUndecorated(true);
		this.sw_setMyPosition(-1, -1, 1, 1);
		this.setVisible(false);
		this.addFocusListener(this);
	}

	private void parseFlags(int flags) {
		if ((flags & SeamlessChannel.WINDOW_CREATE_MODAL) != 0)
			this.modal = true;
		if ((flags & SeamlessChannel.WINDOW_CREATE_FIXEDSIZE) != 0)
			this.setResizable(false);
	}

	@Override
	public void setVisible(boolean b) {
		super.setVisible(b);
		if (this.modal)
			this.setModal(b);
	}

	@Override
	protected void finalize() throws Throwable {
		this.common.canvas.delComponentListener(this);
		super.finalize();
	}

	public void sw_destroy() {
		this.setVisible(false);
		this.dispose();
	}

	public int sw_getId() {
		return this.id;
	}

	public int sw_getGroup() {
		return this.group;
	}

	public void sw_setMyPosition(int x, int y, int width, int height) {
		this.x = x;
		this.y = y;
		this.width = width;
		this.height = height;

		this.setSize(this.width, this.height);
		this.setLocation(this.x + this.maxBounds.x, this.y + this.maxBounds.y);
		this.repaint();
	}

	@Override
	public void update(Graphics g) {
		this.paint(g);
	}

	@Override
	public void repaint(int x, int y, int width, int height) {
		Rectangle bounds = new Rectangle(x, y, width, height);

		if (this.getBounds().intersects(bounds))
			super.repaint();
	}

	@Override
	public void paint(Graphics g) {
		int x_pos = Math.max(this.x, 0);
		int y_pos = Math.max(this.y, 0);
		int w = Math.min(width, this.backstore.getWidth() - x_pos);
		int h = Math.min(height, this.backstore.getHeight() - y_pos);
		int dx = ((this.x < 0) ? -this.x : 0);
		int dy = ((this.y < 0) ? -this.y : 0);

		if ((w > 0) && (h > 0))
			g.drawImage(this.backstore.getSubimage(x_pos, y_pos, w, h), dx , dy, null);
	}

	/* Icons support for Popups */
	protected int icon_size, icon_offset;
	protected byte[] icon_buffer = new byte[32 * 32 * 4];;

	public int sw_getIconSize() {
		return this.icon_size;
	}

	public boolean sw_setIconSize(int icon_size_) {
		if(icon_size_ > 32 * 32 * 4) {
			this.icon_size = 0;
			return false;
		}

		this.icon_size = icon_size_;
		return true;
	}

	public int sw_getIconOffset() {
		return this.icon_offset;
	}

	public void sw_setIconOffset(int icon_offset_) {
		if(icon_offset_ >= 0)
			this.icon_offset = icon_offset_;
	}

	public byte[] sw_getIconBuffer() {
		return this.icon_buffer;
	}

	public void sw_setIconBuffer(byte[] icon_buffer_) {
		this.icon_buffer = icon_buffer_;
	}

	public void sw_setCursor(Cursor cursor) {
		this.setCursor(cursor);
	}
	public void sw_setTitle(String title) {
		this.setTitle(title);
	}
	public void sw_setExtendedState(int state) {
		if (! this.isVisible())
			this.setVisible(true);
	}
	public void sw_setIconImage(Image image) {
		this.setIconImage(image);
	}
	public void sw_addWindowStateListener(WindowStateListener l) {
		this.addWindowStateListener(l);
	}
	public void sw_addWindowListener(WindowListener l) {
		this.addWindowListener(l);
	}
	public void sw_addFocusListener(FocusListener l) {
		this.addFocusListener(l);
	}

	public int sw_getExtendedState() {
		return Frame.NORMAL;
	}

	public RectWindow getRectWindow() {
		return this.rw;
	}

	public boolean _isResizable() {
		return this.isResizable();
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

	public void processMouseEvent(MouseEvent e, int type) {
		e.translatePoint(this.x, this.y);
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
		if (SeamlessPopup.focusManager != null)	{
			SeamlessPopup.focusManager.performedFocusLost(this);
		}
	}

	@Override
	public void focusLost(FocusEvent e) {
		if (SeamlessPopup.focusManager != null)	{
			SeamlessPopup.focusManager.performedFocusLost(this);
		}
	}
}
