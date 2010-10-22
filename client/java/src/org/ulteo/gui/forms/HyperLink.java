/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
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

package org.ulteo.gui.forms;

import java.awt.Cursor;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.MouseEvent;
import java.awt.event.MouseListener;
import java.util.ArrayList;
import java.util.List;

import javax.swing.Icon;
import javax.swing.JLabel;
import org.ulteo.gui.GUIActions;
import org.ulteo.gui.SwingTools;

public class HyperLink extends JLabel implements MouseListener {
	private Cursor prevCursor = null;
	private List<ActionListener> actionListener = null;
	
	public HyperLink() {
		super();
		this.init();
	}
	
	public HyperLink(Icon image) {
		super(image);
		this.init();
	}
	
	public HyperLink(Icon image, int horizontalAlignment) {
		super(image, horizontalAlignment);
		this.init();
	}
	
	public HyperLink(String text) {
		super(text);
		this.init();
	}
	
	public HyperLink(String text, Icon icon, int horizontalAlignment) {
		super(text, icon, horizontalAlignment);
		this.init();
	}
	
	public HyperLink(String text, int horizontalAlignment) {
		super(text, horizontalAlignment);
		this.init();
	}
    
	private void init() {
		this.addMouseListener(this);
		this.actionListener = new ArrayList<ActionListener>();
	}
    
	public void setText(String text_) {
		super.setText("<html><a>"+text_+"</a></html>");
	}
		
	public void addActionListener(ActionListener listener_) {
		this.actionListener.add(listener_);
	}
	
	public void removeActionListener(ActionListener listener_) {
		this.actionListener.remove(listener_);
	}
	
	@Override
	public void mouseClicked(MouseEvent arg0) {
		for(ActionListener listener : this.actionListener) {	
			listener.actionPerformed(new ActionEvent(this, arg0.getID(), this.getText()));
		}
	}
	
	@Override
	public void mouseEntered(MouseEvent arg0) {
		this.prevCursor = this.getCursor();
		
		SwingTools.invokeLater(GUIActions.setCursor(this, Cursor.getPredefinedCursor(Cursor.HAND_CURSOR)));
	}
	
	@Override
	public void mouseExited(MouseEvent arg0) {
		if (this.prevCursor != null)
			SwingTools.invokeLater(GUIActions.setCursor(this, this.prevCursor));
	}
	
	@Override
	public void mousePressed(MouseEvent arg0) {}
	
	@Override
	public void mouseReleased(MouseEvent arg0) {}
}
