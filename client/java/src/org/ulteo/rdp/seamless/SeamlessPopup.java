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
import java.awt.Rectangle;
import java.awt.Window;
import java.awt.event.FocusListener;
import java.awt.event.InputEvent;
import java.awt.event.KeyEvent;
import java.awt.event.KeyListener;
import java.awt.event.MouseEvent;
import java.awt.event.MouseListener;
import java.awt.event.MouseMotionListener;
import java.awt.event.MouseWheelEvent;
import java.awt.event.MouseWheelListener;
import java.awt.event.WindowListener;
import java.awt.event.WindowStateListener;
import javax.swing.JDialog;
import net.propero.rdp.Common;
import net.propero.rdp.Input;
import net.propero.rdp.WrappedImage;
import net.propero.rdp.rdp5.seamless.SeamlessWindow;

public class SeamlessPopup extends JDialog implements SeamlessWindow, SeamlessMovingResizing, KeyListener, MouseListener, MouseMotionListener, MouseWheelListener {
	private Common common = null;
	private int id;
	private int group;
	private Window parent;
	private int x;
	private int y;
	private int width;
	private int height;
	protected Rectangle maxBounds = null;
	private Input input = null;
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

		this.input = this.common.canvas.getInput();
		this.backstore = this.common.canvas.backstore;

		addKeyListener(this);
		addMouseWheelListener(this);

		this.common.canvas.addComponentListener(this);

		Dimension dim = new Dimension(this.backstore.getWidth(), this.backstore.getHeight());
		this.rw = new RectWindow(this, dim, this.maxBounds);

		this.parseFlags(flags);

		this.setUndecorated(true);
		this.sw_setMyPosition(-1, -1, 1, 1);
		this.setVisible(false);
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

	/* Ignore Icons methods for Popups */
	public int sw_getIconSize() {
		return 0;
	}
	public boolean sw_setIconSize(int size) {
		return true;
	}
	public int sw_getIconOffset() {
		return 0;
	}
	public void sw_setIconOffset(int offset) {}
	public byte[] sw_getIconBuffer() {
		return null;
	}
	public void sw_setIconBuffer(byte[] buffer) {}



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

	public void keyTyped(KeyEvent ke) {
		this.input.lastKeyEvent = ke;
		this.input.modifiersValid = true;
		long time = Input.getTime();

		if (this.common.rdp != null) {
			if (! this.input.handleSpecialKeys(time, ke, false))
				this.input.sendKeyPresses(this.input.newKeyMapper.getKeyStrokes(ke));
		}
	}

	public void keyPressed(KeyEvent ke) {
		this.input.lastKeyEvent = ke;
		this.input.modifiersValid = true;
		long time = Input.getTime();

		this.input.pressedKeys.addElement(new Integer(ke.getKeyCode()));

		if (this.common.rdp != null) {
			if (! this.input.handleSpecialKeys(time, ke, true)) {
				this.input.sendKeyPresses(this.input.newKeyMapper.getKeyStrokes(ke));
			}
		}
	}

	public void keyReleased(KeyEvent ke) {
		Integer keycode = new Integer(ke.getKeyCode());
		if (! this.input.pressedKeys.contains(keycode)) {
			this.keyPressed(ke);
		}

		this.input.pressedKeys.removeElement(keycode);

		this.input.lastKeyEvent = ke;
		this.input.modifiersValid = true;
		long time = Input.getTime();

		this.input.pressedKeys.addElement(new Integer(ke.getKeyCode()));

		if (this.common.rdp != null) {
			if (! this.input.handleSpecialKeys(time, ke, true))
				this.input.sendKeyPresses(this.input.newKeyMapper.getKeyStrokes(ke));
		}
	}

	public void mouseWheelMoved(MouseWheelEvent mwe) {
		int flag;
		int time = Input.getTime();

		if (mwe.getWheelRotation() < 0)
			flag = MOUSE_FLAG_BUTTON4;
		else
			flag = MOUSE_FLAG_BUTTON5;
		if (this.common.rdp != null) {
			this.common.rdp.sendInput(time, RDP_INPUT_MOUSE, flag, 1, 1);
		}
	}

	public void mousePressed(MouseEvent me) {
		me.translatePoint(this.x, this.y);
		int time = Input.getTime();

		if (this.common.rdp != null) {
			if ((me.getModifiers() & InputEvent.BUTTON1_MASK) == InputEvent.BUTTON1_MASK) {
				this.common.rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_BUTTON1
						| MOUSE_FLAG_DOWN, me.getX(), me.getY());
			} else if ((me.getModifiers() & InputEvent.BUTTON3_MASK) == InputEvent.BUTTON3_MASK) {
				this.common.rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_BUTTON2
						| MOUSE_FLAG_DOWN, me.getX(), me.getY());
			} else if ((me.getModifiers() & InputEvent.BUTTON2_MASK) == InputEvent.BUTTON2_MASK) {
				// middleButtonPressed(e);
			}
		}
	}

	public void mouseReleased(MouseEvent me) {
		me.translatePoint(this.x, this.y);
		int time = Input.getTime();

		if (this.common.rdp != null) {
			if ((me.getModifiers() & InputEvent.BUTTON1_MASK) == InputEvent.BUTTON1_MASK) {
				this.common.rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_BUTTON1, me.getX(), me.getY());
			} else if ((me.getModifiers() & InputEvent.BUTTON3_MASK) == InputEvent.BUTTON3_MASK) {
				this.common.rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_BUTTON2, me.getX(), me.getY());
			} else if ((me.getModifiers() & InputEvent.BUTTON2_MASK) == InputEvent.BUTTON2_MASK) {
				// middleButtonReleased(e);
			}
		}
	}

	public void mouseDragged(MouseEvent me) {
		me.translatePoint(this.x, this.y);
		int time = Input.getTime();

		if (this.common.rdp != null) {
			this.common.rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_MOVE, me.getX(), me.getY());
		}
	}

	public void mouseMoved(MouseEvent me) {
		me.translatePoint(this.x, this.y);
		int time = Input.getTime();

		if (this.common.rdp != null) {
			this.common.rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_MOVE, me.getX(), me.getY());
		}
	}

	public void mouseClicked(MouseEvent me) {}
	public void mouseEntered(MouseEvent me) {}
	public void mouseExited(MouseEvent me) {}

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
