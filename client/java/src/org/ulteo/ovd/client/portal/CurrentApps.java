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
import javax.swing.ListModel;
import javax.swing.ListSelectionModel;
import javax.swing.event.ListSelectionEvent;
import javax.swing.event.ListSelectionListener;

import org.apache.log4j.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.ApplicationInstance;
import org.ulteo.ovd.client.I18n;
import org.ulteo.ovd.integrated.Spool;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.OvdAppListener;

public class CurrentApps extends JPanel implements OvdAppListener {
	
	private Logger logger = Logger.getLogger(CurrentApps.class);
	private JList list = null;
	private ArrayList<ApplicationInstance> currentApps = null;
	private JScrollPane listScroller = null;
	private int[] selectedApp = null;
	private Spool spool = null;
	
	public CurrentApps() {
		this.currentApps = new ArrayList<ApplicationInstance>();

		this.setBorder(BorderFactory.createTitledBorder(
				BorderFactory.createEtchedBorder(),
				I18n._("Running applications"),0,0,new Font("Dialog", 1, 12),Color.BLACK));

		this.initList(null);
	}

	private void initList(ListModel listModel) {
		this.removeAll();

		if (listModel == null)
			this.list = new JList();
		else
			this.list = new JList(listModel);
		this.list.setSelectionMode(ListSelectionModel.MULTIPLE_INTERVAL_SELECTION);
		this.list.setLayoutOrientation(JList.VERTICAL);
		this.list.setVisibleRowCount(-1);

		this.listScroller = new JScrollPane(this.list);
		this.listScroller.getVerticalScrollBar().setUnitIncrement(10);
		this.listScroller.setPreferredSize(new Dimension(350, getHeight()-50));

		this.list.revalidate();
		this.listScroller.revalidate();
		this.add(this.listScroller);
		this.revalidate();
	}

	public void addInstance(ApplicationInstance ai) {
		this.currentApps.add(ai);
	}

	private void add(ApplicationInstance new_ai) {
		DefaultListModel listModel = new DefaultListModel();
		ListModel previousListModel = list.getModel();
		for (int i = 0; i < previousListModel.getSize(); i++) {
			ApplicationInstance ai = (ApplicationInstance) previousListModel.getElementAt(i);
			System.out.println(ai);
			listModel.addElement(ai);
		}
		listModel.addElement(new_ai);
		this.initList(listModel);
	}

	private void remove(ApplicationInstance old_ai) {
		this.currentApps.remove(old_ai);
		DefaultListModel listModel = new DefaultListModel();
		ListModel previousListModel = list.getModel();
		for (int i = 0; i < previousListModel.getSize(); i++) {
			ApplicationInstance ai = (ApplicationInstance) previousListModel.getElementAt(i);
			if (ai == old_ai)
				continue;
			listModel.addElement(ai);
		}
		this.initList(listModel);
	}

	private ApplicationInstance findApplicationInstanceByToken(int token) {
		for (ApplicationInstance ai : this.currentApps) {
			if (ai.getToken() == token)
				return ai;
		}
		for (ApplicationInstance ai : this.spool.getAppInstance()) {
			if (ai.getToken() == token)
				return ai;
		}
		return null;
	}

	public ApplicationInstance[] getSelectedApps() {
		Object[] objs = this.list.getSelectedValues();
		ApplicationInstance[] appsInst = new ApplicationInstance[objs.length];
		for (int i = 0; i < objs.length; i++) {
			appsInst[i] = (ApplicationInstance) objs[i];
		}
		return appsInst;
	}

	public void setSelectedApp(int[] selectedApp) {
		this.selectedApp = selectedApp;
	}

	@Override
	public void ovdInstanceError(int instance) {}

	@Override
	public void ovdInstanceStarted(int instance) {
		ApplicationInstance ai = this.findApplicationInstanceByToken(instance);
		if (ai == null) {
			this.logger.error("Can't find ApplicationInstance "+instance);
			return;
		}
		ai.setState(ApplicationInstance.STARTED);

		this.add(ai);
	}

	@Override
	public void ovdInstanceStopped(int instance) {
		ApplicationInstance ai = this.findApplicationInstanceByToken(instance);
		if (ai == null) {
			this.logger.error("Can't find ApplicationInstance "+instance);
			return;
		}
		ai.setState(ApplicationInstance.STOPPED);

		this.remove(ai);
	}

	public void ovdInited(OvdAppChannel o) {}
	
	public void setSpool(Spool spool) {
		this.spool = spool;
	}
}
