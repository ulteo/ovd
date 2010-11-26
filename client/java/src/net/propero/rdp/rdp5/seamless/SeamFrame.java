/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
 * Author Thomas MOUTON <thomas@ulteo.com> 2009
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

	protected int id,x,y,width,height;
	protected int group;
	protected int icon_size, icon_offset;
	protected byte[] icon_buffer;
	protected WrappedImage backstore;
	protected MouseAdapter mouseAdapter = null;
	protected MouseMotionAdapter mouseMotionAdapter = null;
	protected Common common = null;
	protected Rectangle maxBounds = null;

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
		if (MouseInfo.getNumberOfButtons() > 3)
			this.addMouseWheelListener(this.mouseAdapter);

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

	public void sw_setMyPosition(int x, int y, int width, int height) {
		this.x = x;
		this.y = y;
		this.width = width;
		this.height = height;

		this.setSize(width, height);
		this.setLocation(x + this.maxBounds.x, y + this.maxBounds.y);
		this.repaint();
	}


	@Override
	public void setExtendedState(int state) {
		if (state == Frame.MAXIMIZED_BOTH) {
			this.sw_setMyPosition(0, 0, this.maxBounds.width, this.maxBounds.height);
			return;
		}

		super.setExtendedState(state);
	}

	public void setParams(int id,int x,int y,int width,int height) {
		this.id = id;
		this.x = x;
		this.y = y;
		this.width = width;
		this.height = height;
	}
	
	public void update(Graphics g) {
		paint(g);
	}

	public void repaint(int x, int y, int width, int height) {
		Rectangle bounds = new Rectangle(x, y, width, height);
		
		if (this.getBounds().intersects(bounds))
			super.repaint();
	}
	
	public void paint(Graphics g) {
		int x = Math.max(this.x,0);
		int y = Math.max(this.y,0);
		int w = Math.min(width,this.backstore.getWidth()-x);
		int h = Math.min(height,this.backstore.getHeight()-y);
		int dx = ((this.x + this.maxBounds.x) < 0) ? -(this.x + this.maxBounds.x) : 0;
		int dy = ((this.y + this.maxBounds.y) < 0) ? -(this.y + this.maxBounds.y) : 0;

		if (w>0 && h>0)
			g.drawImage(this.backstore.getSubimage(x,y,w,h), dx , dy,null);
	}


	public void sw_destroy() {
		this.setVisible(false);
		this.dispose();
	}

	public void sw_setCursor(Cursor cursor) {
		this.setCursor(cursor);
	}
	public void sw_setTitle(String title) {
		this.setTitle(title);
	}
	public int sw_getExtendedState() {
		return this.getExtendedState();
	}
	public void sw_setExtendedState(int state) {
		if (! this.isVisible()) {
			this.setVisible(true);
		}
		this.setExtendedState(state);
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
}
