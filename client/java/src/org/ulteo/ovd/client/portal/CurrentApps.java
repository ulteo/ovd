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

import java.awt.Color;
import java.awt.Dimension;
import java.awt.Font;
import java.util.ArrayList;

import javax.swing.BorderFactory;
import javax.swing.DefaultListModel;
import javax.swing.JList;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.ListSelectionModel;
import javax.swing.event.ListSelectionEvent;
import javax.swing.event.ListSelectionListener;

import org.ulteo.ovd.Application;
import org.ulteo.ovd.client.I18n;

public class CurrentApps extends JPanel {
	
	private JList list = null;
	private ArrayList<Application> currentApps = null;
	private DefaultListModel listModel = new DefaultListModel();
	private JScrollPane listScroller = null;
	private int[] selectedApp = null;
	
	public CurrentApps() {
		this.setBorder(BorderFactory.createTitledBorder(
				BorderFactory.createEtchedBorder(),
				I18n._("Running applications"),0,0,new Font("Dialog", 1, 12),Color.BLACK));
		list = new JList(listModel);
		list.setSelectionMode(ListSelectionModel.MULTIPLE_INTERVAL_SELECTION);
		list.setLayoutOrientation(JList.VERTICAL);
		list.setVisibleRowCount(-1);
		listScroller = new JScrollPane(list);
		listScroller.setPreferredSize(new Dimension(350, getHeight()-20));
		listScroller.revalidate();
		this.add(listScroller);
		revalidate();
	}
	
	public void update(ArrayList<Application> apps) {
		if(currentApps != null)
			currentApps=null;
		if(listModel.size() != 0) {
			listModel.clear();
		}
		if(list != null) {
			listScroller.remove(list);
			list.removeAll();
			list = null;			
		}
		if(listScroller != null) {
			this.remove(listScroller);
			listScroller = null;
		}
		currentApps = apps;
		this.removeAll();
		for (Application app : currentApps){
			listModel.addElement(app.getName());
		}
		list = new JList(listModel);
		list.addListSelectionListener(new ListSelectionListener() {
			
			@Override
			public void valueChanged(ListSelectionEvent e) {
				KillListener.selectedApp = null;
				System.out.println("action !");
				int[] indices = list.getSelectedIndices();
				for(int i=0;i<indices.length;i++) {
					System.out.println("selectedIndice "+i+" : "+indices[i]);
				}
				KillListener.selectedApp = indices;
			}
		});
		listScroller = new JScrollPane(list);
		listScroller.getVerticalScrollBar().setUnitIncrement(10);
		listScroller.setPreferredSize(new Dimension(350, getHeight()-50));
		this.add(listScroller);
		list.revalidate();
		listScroller.revalidate();
		revalidate();
	}

	public int[] getSelectedApp() {
		return selectedApp;
	}

	public void setSelectedApp(int[] selectedApp) {
		this.selectedApp = selectedApp;
	}
}
