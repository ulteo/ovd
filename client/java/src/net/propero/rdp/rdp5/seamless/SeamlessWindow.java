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

package net.propero.rdp.rdp5.seamless;

import java.awt.Cursor;
import java.awt.Image;
import java.awt.Rectangle;
import java.awt.event.FocusListener;
import java.awt.event.WindowListener;
import java.awt.event.WindowStateListener;

public interface SeamlessWindow {
	public static final int RDP_INPUT_MOUSE = 0x8001;

	public static final int MOUSE_FLAG_MOVE = 0x0800;
	public static final int MOUSE_FLAG_DOWN = 0x8000;

	public static final int MOUSE_FLAG_BUTTON1 = 0x1000;
	public static final int MOUSE_FLAG_BUTTON2 = 0x2000;
	public static final int MOUSE_FLAG_BUTTON3 = 0x4000;
	public static final int MOUSE_FLAG_BUTTON4 = 0x0280;
	public static final int MOUSE_FLAG_BUTTON5 = 0x0380;

	public static final int STATE_FULLSCREEN = 8;

	void sw_destroy();
	int sw_getId();
	int sw_getGroup();
	void sw_setMyPosition(int x, int y, int width, int height);
	Rectangle sw_getMaximumBounds();
	int sw_getIconSize();
	boolean sw_setIconSize(int size);
	int sw_getIconOffset();
	void sw_setIconOffset(int offset);
	byte[] sw_getIconBuffer();
	void sw_setIconBuffer(byte[] buffer);
	void sw_enableMouseWheel();

	void sw_setCursor(Cursor cursor);
	String sw_getTitle();
	void sw_setTitle(String title);
	int sw_getExtendedState();
	void sw_setExtendedState(int state);
	void sw_requestFocus();
	void sw_setIconImage(Image image);
	void sw_addWindowStateListener(WindowStateListener l);
	void sw_addWindowListener(WindowListener l);
	void sw_addFocusListener(FocusListener l);

	SeamlessWindow sw_getModalWindow();
	void sw_setModalWindow(SeamlessWindow modalWnd);

	boolean isFullscreenEnabled();
}
