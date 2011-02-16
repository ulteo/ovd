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
import java.awt.Dimension;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.event.ActionListener;
import java.util.List;

import javax.swing.BorderFactory;
import javax.swing.JPanel;
import javax.swing.JScrollPane;

import org.ulteo.ovd.Application;

public class MyApplicationPanel extends JPanel {

	private JPanel buttonPan = null;
	private JScrollPane scroller = null;
	private RunningApplicationPanel runningApps = null;
	private GridBagConstraints gbc = null;
	private int y = 0;
	
	public MyApplicationPanel(RunningApplicationPanel runningApps) {
		this.buttonPan = new JPanel();
		this.buttonPan.setBackground(Color.WHITE);
		this.runningApps = runningApps;
		this.setLayout(new BorderLayout());
		this.setBorder(BorderFactory.createLineBorder(Color.BLACK));
		this.buttonPan.setLayout(new GridBagLayout());
		this.setPreferredSize(new Dimension(300, 194));
		this.gbc = new GridBagConstraints();
		this.revalidate();	
	}
	
	public void initButtons(List<Application> appsList) {
		if (this.buttonPan.getComponentCount() > 0)
			return;

		if (appsList == null)
			return;

		for (Application app : appsList) {
			ApplicationLink link = new ApplicationLink(app);
			
			gbc.gridx = 0;
			gbc.gridy = y;
			gbc.anchor = GridBagConstraints.LINE_START;
			gbc.insets.right = 5;
			this.buttonPan.add(link, gbc);
			
			this.buttonPan.revalidate();
			this.buttonPan.repaint();
			y++;
			
			this.repaint();
			this.revalidate();
			
			link.setEnabled(false);
		}
	}
	
	public void toggleAppButton (Application app, boolean enable) {
		ApplicationLink appLink = this.findLinkByApp(app);
		
		if (appLink == null)
			return;
		
		for (ActionListener each : appLink.getListeners(ApplicationListener.class))
			appLink.removeActionListener(each);

		appLink.setEnabled(enable);

		if (enable)
			appLink.addActionListener(new ApplicationListener(app, this.runningApps));
	}

	private ApplicationLink findLinkByApp(Application app) {
		int appCount = this.buttonPan.getComponentCount();

		for (int i = 0; i < appCount; i++) {
			
			if (this.buttonPan.getComponent(i) instanceof ApplicationLink) {
				ApplicationLink link = (ApplicationLink) this.buttonPan.getComponent(i);

				if (app.getId() == link.getApplication().getId())
					return link;
			}
		}
		return null;
	}
	
	public void addScroller() {
		this.add(scrollerInit());
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
}
