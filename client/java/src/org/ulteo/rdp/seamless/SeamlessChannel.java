/* SeamlessChannel.java
 * Component: UlteoRDP
 * 
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
 * Author Thomas MOUTON <thomas@ulteo.com> 2009-2010
 * 
 * Revision: $Revision: 0.2 $
 * Author: $Author: arnauvp $
 * Date: $Date: 2008/06/17 18:26:30 $
 *
 * Purpose: Allow seamless RDP session
 * 
 * Inspired by: 
 * Cendio RDP seamless.c
   Copyright (C) Peter Astrand <astrand@cendio.se> 2005-2006
   
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, version 2 of the License.
   
   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.
   
   You should have received a copy of the GNU General Public License
   along with this program; if not, write to the Free Software
   Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*/

package org.ulteo.rdp.seamless;

import java.awt.Component;
import java.awt.Dimension;
import java.awt.Frame;
import java.awt.KeyboardFocusManager;
import java.awt.Point;
import java.awt.Rectangle;
import java.awt.Window;
import java.awt.event.InputEvent;
import java.awt.event.MouseEvent;
import java.awt.event.MouseListener;
import java.awt.event.MouseMotionListener;
import java.awt.event.WindowEvent;
import java.util.Date;
import java.util.Timer;
import java.util.TimerTask;
import java.util.concurrent.ConcurrentHashMap;
import javax.swing.JDialog;
import javax.swing.JOptionPane;
import net.propero.rdp.Common;
import net.propero.rdp.Options;
import net.propero.rdp.rdp5.seamless.SeamlessWindow;
import org.ulteo.Logger;
import org.ulteo.gui.GUIActions;
import org.ulteo.gui.SwingTools;
import org.ulteo.utils.I18n;


public class SeamlessChannel extends net.propero.rdp.rdp5.seamless.SeamlessChannel implements MouseListener, MouseMotionListener {
	public static final int WINDOW_CREATE_MODAL	= 0x0001;
	public static final int WINDOW_CREATE_TOPMOST	= 0x0002;
	public static final int WINDOW_CREATE_POPUP	= 0x0004;
	public static final int WINDOW_CREATE_FIXEDSIZE	= 0x0008;
	public static final int WINDOW_CREATE_TOOLTIP	= 0x0010;

	private static final long CLICK_DELAY = 100;

	private Timer clickTimer = null;

	protected ConcurrentHashMap<String, DestroyWindowTimer> closeHistory = null;

	public SeamlessChannel(Options opt_, Common common_) {
		super(opt_, common_);

		this.closeHistory = new ConcurrentHashMap<String, DestroyWindowTimer>();
	}

	@Override
	protected boolean processCreate(long id, long group, long parent, long flags) {
		String name = "w_"+id;
		if( this.windows.containsKey(name)) {
		    logger.error("[processCreate] ID '"+String.format("0x%08x", id)+"' already exist");
		    return false;
		}

		Window sf;

		if (parent != 0 && (flags & WINDOW_CREATE_POPUP) != 0) {
			String parentName = "w_"+parent;
			Window sf_parent;

			// Special case for transient windows
			if (parent == 0xffffffffL) {
				logger.debug("[processCreate] Transient window: "+String.format("0x%08x", id));
				sf_parent = KeyboardFocusManager.getCurrentKeyboardFocusManager().getActiveWindow();
			}
			else if(! this.windows.containsKey(parentName)) {
			    logger.error("[processCreate] Parent window ID '"+String.format("0x%08x", parent)+"' does not exist");
			    return false;
			}
			else
				sf_parent = (Window)this.windows.get(parentName);
			
			sf = new SeamlessPopup((int)id, (int)group, sf_parent, this.getMaximumWindowBounds(), (int)flags, this.common);
		}
		else
			sf = new SeamlessFrame((int)id, (int)group, this.getMaximumWindowBounds(), (int)flags, this.common);
		
		sf.setName(name);
		sf.addMouseListener(this);
		sf.addMouseMotionListener(this);
		if ((flags & SeamlessChannel.WINDOW_CREATE_TOPMOST) != 0)
			sf.setAlwaysOnTop(true);

		this.addFrame((SeamlessWindow)sf, name);

		return true;
	}
	
	protected boolean processDestroy(long id, long flags) {
		String name = "w_"+id;
		if (this.closeHistory.containsKey(name)) {
			DestroyWindowTimer closeTimer = this.closeHistory.remove(name);
			if (closeTimer == null) {
				Logger.error("[processDestroy] Weird. No close timer for window '"+name+"'");
				return false;
			}

			if (closeTimer.isStarted() || closeTimer.isDone()) {
				Logger.debug("closeTimer is started or done");
				return true;
			}

			closeTimer.cancel();
			closeTimer.purge();
		}

		return super.processDestroy(id, flags);
	}

	@Override
	public void windowClosing(WindowEvent we) {
		Component c = we.getComponent();
		if (! (c instanceof SeamlessWindow))
			return;

		SeamlessWindow wnd = (SeamlessWindow) c;
		String name = "w_"+wnd.sw_getId();

		if (this.closeHistory.containsKey(name))
			return;

		DestroyWindowTimer closeTimer = new DestroyWindowTimer(wnd);
		closeTimer.schedule();

		this.closeHistory.put(name, closeTimer);

		super.windowClosing(we);
	}

	private class DestroyWindowTimer extends Timer {
		private DestroyWindowTask task = null;

		public DestroyWindowTimer(SeamlessWindow wnd) {
			this.task = new DestroyWindowTask(wnd);
		}

		public void schedule() {
			this.schedule(this.task, 2000);
		}

		public boolean isStarted() {
			return (this.task.getState() == 1);
		}

		public boolean isDone() {
			return (this.task.getState() == 2);
		}
	}

	private class DestroyWindowTask extends TimerTask {
		private SeamlessWindow wnd = null;
		private int state = 0;

		DestroyWindowTask(SeamlessWindow wnd_) {
			super();

			this.wnd = wnd_;
		}

		public int getState() {
			return this.state;
		}

		@Override
		public void run() {
			this.state = 1;

			String key = "w_"+this.wnd.sw_getId();
			if (! windows.containsKey(key)) {
				Logger.error("[DestroyWindowTask] Failed to find the window "+key);
				return;
			}

			String[] choices = {I18n._("Yes"), I18n._("No")};

			JOptionPane pane = new JOptionPane(I18n._("Would you force quit the application '"+this.wnd.sw_getTitle()+"'?"),
					JOptionPane.WARNING_MESSAGE,
					JOptionPane.YES_NO_OPTION,
					null,
					choices,
					choices[1]);
			final JDialog dialog = pane.createDialog(I18n._("Warning!"));
			GUIActions.setIconImage(dialog, null).run();
			try {
				SwingTools.invokeAndWait(new Thread(new Runnable() {

					public void run() {
						GUIActions.setVisible(dialog, true).run();
						GUIActions.requestFocus(dialog).run();
					}
				}));
			} catch (Exception ex) {
				Logger.error("[DestroyWindowTask] Failed to show the popup: "+ex.getMessage());
				return;
			}
			Object r = pane.getValue();
			if (! (r instanceof String)) {
				Logger.error("[DestroyWindowTask] Popup result is not a string: "+r);
				return;
			}

			if (! ((String) r).equalsIgnoreCase(I18n._("Yes"))) {
				closeHistory.remove(key);
				return;
			}

			windows.remove(key);
			this.wnd.sw_destroy();
			
			this.state = 2;
		}
	}

	private int detectCorner(MouseEvent e, Rectangle wndSize) {
		if (e == null)
			return -1;

		int xClick = e.getX();
		int yClick = e.getY();

		if (xClick >= 0 && xClick < RectWindow.SEAMLESS_CORNER_SIZE) {
			if (yClick >= 0 && yClick < RectWindow.SEAMLESS_CORNER_SIZE) {
				return RectWindow.CORNER_TOP_LEFT;
			}
			else if (yClick > (wndSize.height - RectWindow.SEAMLESS_CORNER_SIZE) && yClick <= wndSize.height) {
				return RectWindow.CORNER_BOTTOM_LEFT;
			}
			else if (yClick >= 0 && yClick <= wndSize.height) {
				return RectWindow.CORNER_LEFT;
			}
		}
		else if (xClick > (wndSize.width - RectWindow.SEAMLESS_CORNER_SIZE) && xClick <= wndSize.width) {
			if (yClick >= 0 && yClick < RectWindow.SEAMLESS_CORNER_SIZE) {
				return RectWindow.CORNER_TOP_RIGHT;
			}
			else if (yClick > (wndSize.height - RectWindow.SEAMLESS_CORNER_SIZE) && yClick <= wndSize.height) {
				return RectWindow.CORNER_BOTTOM_RIGHT;
			}
			else if (yClick >= 0 && yClick <= wndSize.height) {
				return RectWindow.CORNER_RIGHT;
			}
		}
		else if (yClick > (wndSize.height - RectWindow.SEAMLESS_BORDER_SIZE) && yClick <= wndSize.height) {
			return RectWindow.CORNER_BOTTOM;
		}
		else if (yClick >= 0 && yClick < RectWindow.SEAMLESS_BORDER_SIZE) {
			return RectWindow.CORNER_TOP;
		}
		
		return -1;
	}

	public void mousePressed(MouseEvent e) {
		String name = e.getComponent().getName();
		SeamlessMovingResizing sw = null;
		if (this.windows.containsKey(name))
			sw = (SeamlessMovingResizing)this.windows.get(name);
		if (sw == null) {
			System.err.println("Bad window ("+name+")");
			return;
		}

		if (sw.isMouseEventsLocked()) {
			System.err.println("Weird behavior, should never appear (ref: 20)");
			return;
		}

		if (e.getClickCount() == 1) {
			if ((((SeamlessWindow)sw).sw_getExtendedState() != Frame.MAXIMIZED_BOTH) && ((e.getModifiers() & InputEvent.BUTTON1_MASK) == InputEvent.BUTTON1_MASK)) {
				this.cancelClickTimer();

				MouseEvent me = new MouseEvent(e.getComponent(), e.getID(), new Date().getTime(), e.getModifiers(), e.getX(), e.getY(), e.getClickCount(), e.isPopupTrigger(), e.getButton());
				this.clickTimer = new Timer();
				this.clickTimer.schedule(new CheckClickTask(sw, me), CLICK_DELAY);
			}
		}
		sw.processMouseEvent(e, SeamlessMovingResizing.MOUSE_PRESSED);
	}

	public void mouseReleased(MouseEvent e) {
		this.cancelClickTimer();
		
		String name = e.getComponent().getName();
		Window sw = null;
		if (this.windows.containsKey(name))
			sw = (Window) this.windows.get(name);
		if (sw == null) {
			System.err.println("Bad window ("+name+")");
			return;
		}
		if (((SeamlessMovingResizing) sw).isMouseEventsLocked()) {
			Rectangle bounds = ((SeamlessMovingResizing) sw).getRectWindow().getBounds();
			try {
				this.send_position(((SeamlessWindow) sw).sw_getId(), bounds.x, bounds.y, bounds.width, bounds.height, 0);
			} catch (Exception ex) {
				org.ulteo.Logger.error("Position message not sent: "+ex.getMessage());
			}
			((SeamlessMovingResizing) sw).unlockMouseEvents();
			return;
		}
		((SeamlessMovingResizing) sw).processMouseEvent(e, SeamlessMovingResizing.MOUSE_RELEASED);
	}

	public void mouseDragged(MouseEvent e) {
		String name = e.getComponent().getName();
		SeamlessMovingResizing sw = null;
		if (this.windows.containsKey(name))
			sw = (SeamlessMovingResizing)this.windows.get(name);
		if (sw == null) {
			System.err.println("Bad window ("+name+")");
			return;
		}

		RectWindow rw = sw.getRectWindow();

		if (sw.isMouseEventsLocked()) {
			if (rw.isResizing()) {
				rw.setResizeClick(e);
				rw.resize();
			}
			else if (rw.isMoving()) {
				Dimension size = ((Window)sw).getSize();
				Point offsets = rw.getOffsets();
				int x_rw = e.getXOnScreen() - offsets.x;
				int y_rw = e.getYOnScreen() - offsets.y;
				rw.setBounds(new Rectangle(x_rw, y_rw, size.width, size.height));
			}
			return;
		}

		if (rw.isResizing() || rw.isMoving()) {
			sw.lockMouseEvents();

			return;
		}

		sw.processMouseEvent(e, SeamlessMovingResizing.MOUSE_DRAGGED);
	}

	public void mouseMoved(MouseEvent e) {
		String name = e.getComponent().getName();
		SeamlessMovingResizing sw = null;
		if (this.windows.containsKey(name))
			sw = (SeamlessMovingResizing)this.windows.get(name);
		if (sw == null) {
			System.err.println("Bad window ("+name+")");
			return;
		}

		if (sw.isMouseEventsLocked())
			return;

		sw.processMouseEvent(e, SeamlessMovingResizing.MOUSE_MOVED);
	}

	public void mouseEntered(MouseEvent me) {}
	public void mouseExited(MouseEvent me) {}
	public void mouseClicked(MouseEvent me) {
		Component c = me.getComponent();
		if (! (c instanceof SeamlessWindow))
			return;
		
		SeamlessWindow wnd = (SeamlessWindow) me.getComponent();
		this.setFocusOnWindow(wnd);
	}

	private void cancelClickTimer() {
		if (this.clickTimer != null) {
			this.clickTimer.cancel();
			this.clickTimer.purge();
			this.clickTimer = null;
		}
	}


	private class CheckClickTask extends TimerTask {
		private MouseEvent evt = null;
		private SeamlessMovingResizing sw = null;
		private Rectangle wndBounds = null;

		CheckClickTask(SeamlessMovingResizing sw_, MouseEvent evt_) {
			super();

			this.sw = sw_;
			this.evt = evt_;

			this.wndBounds = ((Window) this.sw).getBounds();
		}

		@Override
		public void run() {
			int xClick = this.evt.getX();
			int yClick = this.evt.getY();
			RectWindow rw = this.sw.getRectWindow();

			if (	(xClick < RectWindow.SEAMLESS_BORDER_SIZE ||
				xClick > (this.wndBounds.width - RectWindow.SEAMLESS_BORDER_SIZE) ||
				yClick < RectWindow.SEAMLESS_BORDER_SIZE ||
				yClick > (this.wndBounds.height - RectWindow.SEAMLESS_BORDER_SIZE))
				&& this.sw._isResizable()
			) {
				rw.setResizeClick(this.evt);
				int corner = detectCorner(this.evt, this.wndBounds);
				rw.setCorner(corner);

				rw.offsetsResize(this.evt, this.wndBounds);
				rw.resize();
			}
			else if (
				yClick >= RectWindow.SEAMLESS_BORDER_SIZE &&
				yClick <= (RectWindow.SEAMLESS_BORDER_SIZE + RectWindow.SEAMLESS_TOP_BORDER_SIZE) &&
				xClick >= RectWindow.SEAMLESS_BORDER_SIZE &&
				xClick <= (((Window) this.sw).getWidth() - RectWindow.SEAMLESS_BORDER_SIZE)
			) {
				rw.setOffsets((this.evt.getXOnScreen() - this.wndBounds.x), (this.evt.getYOnScreen() - this.wndBounds.y));
				rw.setBounds(this.wndBounds);
				rw.setMoveClick(this.evt);
			}
			else
				return;

			MouseEvent me = new MouseEvent(this.evt.getComponent(), this.evt.getID(), new Date().getTime(), this.evt.getModifiers(), this.evt.getX(), this.evt.getY(), this.evt.getClickCount(), this.evt.isPopupTrigger(), this.evt.getButton());
			this.sw.processMouseEvent(me, SeamlessMovingResizing.MOUSE_RELEASED);
			this.sw.lockMouseEvents();
		}
	}
}
