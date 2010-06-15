/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
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

package org.ulteo.ovd.client.portal;

import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Font;
import java.util.ArrayList;

import javax.swing.BorderFactory;
import javax.swing.BoxLayout;
import javax.swing.JButton;
import javax.swing.JPanel;
import javax.swing.JScrollPane;

import org.ulteo.ovd.Application;
import org.ulteo.ovd.client.I18n;
import org.ulteo.rdp.OvdAppChannel;


public class Menu extends JPanel {
	
	private JScrollPane scroller = null;
	private JPanel buttonPan = null;
	private CurrentApps currentApps = null;
	private ArrayList<Application> apps = null;
	private ArrayList<JButton> buttons = new ArrayList<JButton>();

	public Menu(CurrentApps currentApps, ArrayList<Application> apps) {
		this.apps = apps;
		this.currentApps = currentApps;
		buttonPan = new JPanel();
		buttonPan.setLayout(new BoxLayout(buttonPan, BoxLayout.Y_AXIS));
		this.setBorder(BorderFactory.createTitledBorder(
				BorderFactory.createEtchedBorder(),
				I18n._("Applications"),0,0,new Font("Dialog", 1, 12),Color.BLACK));
		this.setLayout(new BorderLayout());
		revalidate();
	}

	public void install (Application app, OvdAppChannel chann) {
		ApplicationButton appButton = new ApplicationButton(app.getName(), app.getIcon());
			appButton.addActionListener(new ApplicationListener(app, chann, currentApps, apps));
			buttonPan.add(appButton);
			buttonPan.revalidate();
	}
	
	public void addScroller() {
		this.add(BorderLayout.CENTER, scrollerInit());
		scroller.getVerticalScrollBar().setUnitIncrement(10);
		scroller.revalidate();
		this.revalidate();
	}
	public JScrollPane scrollerInit() {
		scroller = new JScrollPane();
		scroller.setViewportView(buttonPan);
		return scroller;
	}
	
	public ArrayList<JButton> getButtons() {
		return buttons;
	}
}
