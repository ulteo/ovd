/* SeamlessChannel.java
 * Component: UlteoRDP
 * 
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
 * Author Thomas MOUTON <thomas@ulteo.com> 2009-2011
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
import java.awt.KeyboardFocusManager;
import java.awt.Window;
import java.awt.event.MouseEvent;
import java.awt.event.MouseListener;
import java.awt.event.WindowEvent;
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


public class SeamlessChannel extends net.propero.rdp.rdp5.seamless.SeamlessChannel implements MouseListener {
	public static final int WINDOW_CREATE_MODAL	= 0x0001;
	public static final int WINDOW_CREATE_TOPMOST	= 0x0002;
	public static final int WINDOW_CREATE_POPUP	= 0x0004;
	public static final int WINDOW_CREATE_FIXEDSIZE	= 0x0008;
	public static final int WINDOW_CREATE_TOOLTIP	= 0x0010;

	protected WindowFrameManager windowFrameManager = null;

	protected ConcurrentHashMap<String, DestroyWindowTimer> closeHistory = null;

	public SeamlessChannel(Options opt_, Common common_) {
		super(opt_, common_);

		this.windowFrameManager = new WindowFrameManager(this, this.opt);

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

		String timerToCancelName = null;
		for (String each : this.closeHistory.keySet()) {
			if (! this.windows.containsKey(each))
				continue;

			SeamlessWindow wnd = this.windows.get(each);
			if (wnd == null)
				continue;

			if (wnd.sw_getGroup() != group)
				continue;

			timerToCancelName = each;
		}
		if (timerToCancelName != null) {
			DestroyWindowTimer timerToCancel = this.closeHistory.remove(timerToCancelName);
			if (timerToCancel != null) {
				timerToCancel.cancel();
				timerToCancel.purge();
				timerToCancel = null;
			}
		}

		if (parent != 0 && (flags & WINDOW_CREATE_POPUP) != 0) {
			String parentName = "w_"+parent;
			Window sf_parent;

			// Special case for transient windows
			if (parent == 0xffffffffL) {
				logger.debug("[processCreate] Transient window: "+String.format("0x%08x", id));

				sf_parent = null;
				Window active = KeyboardFocusManager.getCurrentKeyboardFocusManager().getActiveWindow();
				if ((active instanceof SeamlessFrame) && ((SeamlessWindow) active).sw_getGroup() == group) {
					sf_parent = active;
				}
				else {
					for (SeamlessWindow sw : this.windows.values()) {
						if (sw.sw_getGroup() != group)
							continue;

						if (! (sw instanceof SeamlessFrame))
							continue;

						sf_parent = (Window) sw;
						break;
					}
				}
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
		sf.addMouseListener(this.windowFrameManager);
		sf.addMouseMotionListener(this.windowFrameManager);
		if ((flags & SeamlessChannel.WINDOW_CREATE_TOPMOST) != 0 && (sf instanceof SeamlessFrame))
			SwingTools.invokeLater(GUIActions.setAlwaysOnTop(sf, true));

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

	public void mousePressed(MouseEvent e) {}
	public void mouseReleased(MouseEvent e) {}
	public void mouseEntered(MouseEvent me) {}
	public void mouseExited(MouseEvent me) {}
	public void mouseClicked(MouseEvent me) {
		Component c = me.getComponent();
		if (! (c instanceof SeamlessWindow))
			return;
		
		SeamlessWindow wnd = (SeamlessWindow) me.getComponent();
		this.setFocusOnWindow(wnd);
	}
}
