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
import java.awt.event.FocusListener;
import java.awt.event.WindowStateListener;

public interface SeamlessWindow {
	void sw_destroy();
	int sw_getId();
	int sw_getGroup();
	void sw_setMyPosition(int x, int y, int width, int height);
	int sw_getIconSize();
	boolean sw_setIconSize(int size);
	int sw_getIconOffset();
	void sw_setIconOffset(int offset);
	byte[] sw_getIconBuffer();
	void sw_setIconBuffer(byte[] buffer);

	void sw_setCursor(Cursor cursor);
	void sw_setTitle(String title);
	int sw_getExtendedState();
	void sw_setExtendedState(int state);
	void sw_setIconImage(Image image);
	void sw_addWindowStateListener(WindowStateListener l);
	void sw_addFocusListener(FocusListener l);
}
