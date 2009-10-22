/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
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

package org.vnc;

import java.awt.Component;
import java.awt.Toolkit;
import java.awt.datatransfer.Clipboard;
import java.awt.datatransfer.ClipboardOwner;
import java.awt.datatransfer.DataFlavor;
import java.awt.datatransfer.FlavorEvent;
import java.awt.datatransfer.FlavorListener;
import java.awt.datatransfer.StringSelection;
import java.awt.datatransfer.Transferable;
import java.awt.datatransfer.UnsupportedFlavorException;
import java.awt.event.FocusEvent;
import java.awt.event.FocusListener;
import java.io.IOException;


public class ClipboardManagement implements ClipboardOwner, FlavorListener, FocusListener {
	private RfbProto rfb = null;
	private String selection = "";
	private Clipboard clipboard = null;
	private int tryInit = 0;


	public ClipboardManagement() {
		this.clipboard = Toolkit.getDefaultToolkit().getSystemClipboard();
	}


	public void registerEvents(Component c) {
		try {
			this.clipboard.addFlavorListener(this);
		}
		catch (java.lang.NullPointerException e) {
			System.err.println("Unable to addFlavorListener, using old method");
		}

		/*
		 * We add the FocusListener even if the flavorListener registering 
		 * worked because it seems we don't receive a flavorEvent each 
		 * time the clipboard is changed
		 */
		c.addFocusListener(this);
	}


	public void setRfbProto(RfbProto rfb) {
		this.rfb = rfb;
	}
	

	public void recv(String text) {
		StringSelection ss = new StringSelection(text);

		this.clipboard.setContents(ss, this);
	}


	public void send(String text) {
		if (this.rfb == null)
			return;
		if (! this.rfb.inNormalProtocol)
			return;
	
		try {
			this.rfb.writeClientCutText(text);
		} catch (Exception e) {
			System.err.println("Clipboard: unable to send");
			e.printStackTrace();
		}
	}


	private boolean process() {
		Transferable t = this.clipboard.getContents(this);
		
		if (t == null)
			return false;
		
		if (! t.isDataFlavorSupported(DataFlavor.stringFlavor))
			return false;
		
		String content = null;
		try {
			content = (String) t.getTransferData(DataFlavor.stringFlavor);
		} catch (IOException exc) {
			System.err.println("Exception getting clipboard data: " + exc.getMessage());
		} catch (UnsupportedFlavorException exc) {
			System.err.println("Exception getting clipboard data: " + exc.getMessage());
		}

		if (content == null)
			return false;

		if (content.equals(this.selection))
			return false;

//		System.out.println("process: "+content);
		this.selection = content;
		this.send(this.selection);
		return true;
	}


	// Implement interface FlavorListener
	public void flavorsChanged(FlavorEvent e) {
//		System.out.println("flavorsChanged event");
		this.process();
	}


	// Implement interface ClipboardOwner
	public void lostOwnership(Clipboard clipboard, Transferable contents) {}


	// Implement inteface FocusListener
	public void focusGained(FocusEvent evt) {
//		System.out.println("focusGained event");
		this.process();
	}


	public void focusLost(FocusEvent evt){}
}
