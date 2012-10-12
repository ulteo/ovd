/*
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
 * Author Thomas MOUTON <thomas@ulteo.com> 2009-2012
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

package net.propero.rdp.rdp5.seamless;

import net.propero.rdp.Common;
import net.propero.rdp.Input;
import net.propero.rdp.WrappedImage;

import java.awt.*;
import java.awt.event.*;
import org.ulteo.gui.GUIActions;
import org.ulteo.gui.SwingTools;

public class SeamFrame extends Frame
    implements SeamlessWindow {

	protected static boolean capsLockOn = false;
	protected static boolean numLockOn = false;
	protected static boolean scrollLockOn = false;

	protected static boolean serverAltDown = false;
    protected static boolean altDown = false;
	protected static boolean ctrlDown = false;

    protected static long last_mousemove = 0;
    
	// Using this flag value (0x0001) seems to do nothing, and after running
	// through other possible values, the RIGHT flag does not appear to be
	// implemented
	protected static final int KBD_FLAG_RIGHT = 0x0001;
	protected static final int KBD_FLAG_EXT = 0x0100;

	// QUIET flag is actually as below (not 0x1000 as in rdesktop)
	protected static final int KBD_FLAG_QUIET = 0x200;
	protected static final int KBD_FLAG_DOWN = 0x4000;
	protected static final int KBD_FLAG_UP = 0x8000;

	protected static final int RDP_KEYPRESS = 0;
	protected static final int RDP_KEYRELEASE = KBD_FLAG_DOWN | KBD_FLAG_UP;

    protected static final int RDP_INPUT_SYNCHRONIZE = 0;
	protected static final int RDP_INPUT_CODEPOINT = 1;
	protected static final int RDP_INPUT_VIRTKEY = 2;
	protected static final int RDP_INPUT_SCANCODE = 4;

	protected int id;
	protected int group;
	protected int icon_size, icon_offset;
	protected byte[] icon_buffer;
	protected WrappedImage backstore;
	protected MouseAdapter mouseAdapter = null;
	protected MouseMotionAdapter mouseMotionAdapter = null;
	protected Common common = null;
	protected Rectangle maxBounds = null;

	private SeamlessWindow modalWindow = null;
	
	private boolean isFullscreenEnabled = false;

	public SeamFrame(int id_, int group_, Rectangle maxBounds_, Common common_) {
		this.common = common_;
		this.id = id_;
		this.group = group_;
		this.maxBounds = maxBounds_;
		this.icon_size = 0;
		this.icon_buffer = new byte[32 * 32 * 4];

		this.backstore = this.common.canvas.backstore;
		this.common.canvas.addComponentListener(this);

		// Set the key and mouse listeners
		Input input = this.common.canvas.getInput();

		this.mouseAdapter = input.getMouseAdapter();
		this.mouseMotionAdapter = input.getMouseMotionAdapter();

		this.addKeyListener(input.getKeyAdapter());

		this.setMaximizedBounds(this.maxBounds);
		this.setUndecorated(true);
		this.sw_setMyPosition(-1, -1, 1, 1);
		this.setVisible(false);
	}
	
	protected void finalize() throws Throwable {
		this.common.canvas.delComponentListener(this);
		super.finalize();
	}

	public int sw_getId() {
		return this.id;
	}
	
	public int sw_getGroup() {
		return this.group;
	}
	
	public int sw_getIconSize() {
		return this.icon_size;
	}
	
	public int sw_getIconOffset() {
		return this.icon_offset;
	}
	
	public byte[] sw_getIconBuffer() {
		return this.icon_buffer;
	}
	
	public void sw_setIconBuffer(byte[] icon_buffer_) {
		this.icon_buffer = icon_buffer_;
	}
	
	public boolean sw_setIconSize(int icon_size_) {
		if(icon_size_ > 32 * 32 * 4) {
			this.icon_size = 0;
			return false;
		}
		
		this.icon_size = icon_size_;
		return true;
	}

	public void sw_setIconOffset(int icon_offset_) {
		if(icon_offset_ >= 0)
			this.icon_offset = icon_offset_;
	}

	public void sw_enableMouseWheel() {
		this.addMouseWheelListener(this.mouseAdapter);
	}

	public Rectangle sw_getMaximumBounds() {
		return this.maxBounds;
	}

	public void sw_setMyPosition(int x, int y, int width, int height) {
		this.setSize(width, height);
		this.setLocation(x, y);
		this.repaint();
	}
	
	public void update(Graphics g) {
		paint(g);
	}

	public void repaint(int x, int y, int width, int height) {
		Rectangle bounds = new Rectangle(x, y, width, height);
		
		Rectangle wndBounds = this.getBounds();
		wndBounds.x -= this.maxBounds.x;
		wndBounds.y -= this.maxBounds.y;
		
		if (wndBounds.intersects(bounds))
			super.repaint();
	}
	
	public void paint(Graphics g) {
		Rectangle bounds = this.getBounds();

		int x = Math.max(bounds.x - this.maxBounds.x, 0);
		int y = Math.max(bounds.y - this.maxBounds.y, 0);
		int weight = Math.min(bounds.width, this.backstore.getWidth() - x);
		int height = Math.min(bounds.height, this.backstore.getHeight() - y);
		int dx = (bounds.x < this.maxBounds.x) ? this.maxBounds.x - bounds.x : 0;
		int dy = (bounds.y < this.maxBounds.y) ? this.maxBounds.y - bounds.y : 0;
		
		if (weight > 0 && height > 0)
			g.drawImage(this.backstore.getSubimage(x, y, weight, height), dx , dy, null);
	}


	public void sw_destroy() {
		this.setVisible(false);
		this.dispose();
	}

	public void sw_setCursor(Cursor cursor) {
		this.setCursor(cursor);
	}
	public String sw_getTitle() {
		return this.getTitle();
	}
	public void sw_setTitle(String title) {
		this.setTitle(title);
	}
	public int sw_getExtendedState() {
		if (this.isFullscreenEnabled)
			return SeamlessWindow.STATE_FULLSCREEN;

		return this.getExtendedState();
	}
	public void sw_setExtendedState(int state) {
		if (! this.isVisible()) {
			this.setVisible(true);
		}

		if (state == SeamlessWindow.STATE_FULLSCREEN) {
			this.isFullscreenEnabled = true;
			state = Frame.MAXIMIZED_BOTH;
		}
		else
			this.isFullscreenEnabled = false;

		this.setExtendedState(state);
	}
	public void sw_requestFocus() {
		SwingTools.invokeLater(GUIActions.requestFocus(this));
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
	public SeamlessWindow sw_getModalWindow() {
		return this.modalWindow;
	}
	public void sw_setModalWindow(SeamlessWindow modalWnd) {
		if (modalWnd == null || ((Window) modalWnd).getOwner() != this) {
			this.modalWindow = null;
			return;
		}
		this.modalWindow = modalWnd;
	}
	public boolean isFullscreenEnabled() {
		return this.isFullscreenEnabled;
	}
}
