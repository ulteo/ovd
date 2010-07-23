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
import java.util.List;

import javax.swing.BorderFactory;
import javax.swing.BoxLayout;
import javax.swing.JButton;
import javax.swing.JPanel;
import javax.swing.JScrollPane;

import org.ulteo.ovd.Application;
import org.ulteo.ovd.client.I18n;


public class Menu extends JPanel {
	
	private JScrollPane scroller = null;
	private JPanel buttonPan = null;
	private CurrentApps currentApps = null;
	private ArrayList<JButton> buttons = new ArrayList<JButton>();

	public Menu(CurrentApps currentApps) {
		this.currentApps = currentApps;
		buttonPan = new JPanel();
		buttonPan.setLayout(new BoxLayout(buttonPan, BoxLayout.Y_AXIS));
		this.setBorder(BorderFactory.createTitledBorder(
				BorderFactory.createEtchedBorder(),
				I18n._("Applications"),0,0,new Font("Dialog", 1, 12),Color.BLACK));
		this.setLayout(new BorderLayout());
		revalidate();
	}

	public void initButtons(List<Application> appsList) {
		if (this.buttonPan.getComponentCount() > 0)
			return;

		if (appsList == null)
			return;

		for (Application app : appsList) {
			ApplicationButton appButton = new ApplicationButton(app);
			appButton.addActionListener(new ApplicationListener(app, currentApps));
			appButton.setEnabled(false);
			this.buttonPan.add(appButton);
			this.buttonPan.revalidate();
			this.buttonPan.repaint();
		}
	}

	public void install (Application app) {
		ApplicationButton appButton = this.findButtonByApp(app);

		if (appButton == null)
			return;

		appButton.setEnabled(true);
	}

	public void uninstall (Application app) {
		ApplicationButton appButton = this.findButtonByApp(app);

		if (appButton == null)
			return;

		appButton.setEnabled(false);
	}

	private ApplicationButton findButtonByApp(Application app) {
		int appCount = this.buttonPan.getComponentCount();

		for (int i = 0; i < appCount; i++) {
			ApplicationButton appButton = (ApplicationButton) this.buttonPan.getComponent(i);

			if (app.getName().equals(appButton.getText()) && (app.getConnection() == appButton.getConnection()))
				return appButton;
		}
		return null;
	}
	
	public void addScroller() {
		this.add(BorderLayout.CENTER, scrollerInit());
		scroller.getVerticalScrollBar().setUnitIncrement(10);
		scroller.revalidate();
		this.revalidate();
	}
	private JScrollPane scrollerInit() {
		scroller = new JScrollPane();
		scroller.setViewportView(buttonPan);
		return scroller;
	}

	public boolean isScollerInited() {
		return (this.scroller == null);
	}
	
	public ArrayList<JButton> getButtons() {
		return buttons;
	}
}
