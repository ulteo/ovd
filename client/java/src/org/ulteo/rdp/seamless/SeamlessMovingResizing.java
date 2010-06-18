/*
 * Copyright (C) 2009 Ulteo SAS
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

import java.awt.event.MouseEvent;

public interface SeamlessMovingResizing {
	public static final int MOUSE_PRESSED	= 0;
	public static final int MOUSE_RELEASED	= 1;
	public static final int MOUSE_MOVED	= 2;
	public static final int MOUSE_DRAGGED	= 3;

	RectWindow getRectWindow();

	boolean _isResizable();

	boolean isMouseEventsLocked();
	void lockMouseEvents();
	void unlockMouseEvents();
	void processMouseEvent(MouseEvent e, int type);
}
