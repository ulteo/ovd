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
import java.awt.Component;
import java.awt.Dimension;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.util.ArrayList;

import javax.swing.BorderFactory;
import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JScrollPane;

import org.apache.log4j.Logger;
import org.ulteo.ovd.ApplicationInstance;
import org.ulteo.ovd.integrated.Spool;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.OvdAppListener;

public class RunningApplicationPanel extends JPanel implements OvdAppListener {

	private Logger logger = Logger.getLogger(RunningApplicationPanel.class);
	
	//private static final ImageIcon KILL_ICON = new ImageIcon(Toolkit.getDefaultToolkit().getImage(RunningApplicationPanel.class.getClassLoader().getResource("pics/button_cancel.png")));
	private ArrayList<ApplicationInstance> runningApps = null;
	private ArrayList<Component> components = null;
	private JScrollPane listScroller = null;
	private Spool spool = null;
	private JPanel listPanel = new JPanel();
	private int y = 0;
	private GridBagConstraints gbc = null;
	
	public RunningApplicationPanel() {
		this.listPanel.setBackground(Color.white);
		this.runningApps = new ArrayList<ApplicationInstance>();
		this.components = new ArrayList<Component>();
		
		this.setLayout(new BorderLayout());
		this.setPreferredSize(new Dimension(300, 194));
		this.setBorder(BorderFactory.createLineBorder(Color.BLACK));
		
		this.listPanel.setLayout(new GridBagLayout());
		this.gbc = new GridBagConstraints();
		
		this.listScroller = new JScrollPane(listPanel);
		this.add(listScroller, BorderLayout.CENTER);
		
		this.revalidate();
	}
	
	public void addInstance(ApplicationInstance ai) {
		this.runningApps.add(ai);
	}
	
	private void add(ApplicationInstance new_ai) {
		String appId = new_ai.getApplication().getName()+new_ai.getToken();
		JLabel appIcon = new JLabel();
		JLabel appName = new JLabel(new_ai.getApplication().getName());
		appIcon.setIcon(new_ai.getApplication().getIcon());
		appIcon.setName(appId);
		appName.setName(appId);
		
		/*JButton kill = new JButton();
		kill.setIcon(KILL_ICON);
		kill.setName(appId);*/
		
		components.add(appIcon);
		components.add(appName);
		//components.add(kill);
		
			this.gbc.gridx = 0;
			this.gbc.gridy = this.y;
			this.gbc.anchor = GridBagConstraints.LINE_START;
			this.gbc.insets.right = 5;
			
			this.listPanel.add(appIcon, gbc);
			
			this.gbc.gridx = 1;
			this.gbc.fill = GridBagConstraints.HORIZONTAL;
			this.listPanel.add(appName, gbc);
			
			/*this.gbc.gridx = 2;
			this.gbc.fill = GridBagConstraints.NONE;
			this.gbc.anchor = GridBagConstraints.LINE_END;
			this.listPanel.add(kill, gbc);*/
			this.y++;
			
			this.gbc.anchor = GridBagConstraints.CENTER;
			this.listPanel.revalidate();
	}
	
	private void remove(ApplicationInstance old_ai) {
		for (Component cmp : components) {
			if(cmp.getName().equals(old_ai.getApplication().getName()+old_ai.getToken()))
				this.listPanel.remove(cmp);
		}
		this.listPanel.revalidate();
		this.listScroller.revalidate();
		this.revalidate();
		this.repaint();
	}

	public ApplicationInstance findApplicationInstanceByToken(int token) {
		for (ApplicationInstance ai : this.runningApps) {
			if (ai.getToken() == token)
				return ai;
		}
		for (ApplicationInstance ai : this.spool.getAppInstance()) {
			if (ai.getToken() == token)
				return ai;
		}
		return null;
	}
	
	public void ovdInited(OvdAppChannel o) {}

	public void ovdInstanceError(int instance) {}

	public void ovdInstanceStarted(int instance) {
		ApplicationInstance ai = this.findApplicationInstanceByToken(instance);
		if (ai == null) {
			this.logger.error("Can't find ApplicationInstance "+instance);
			return;
		}
		ai.setState(ApplicationInstance.STARTED);

		this.add(ai);
	}

	public void ovdInstanceStopped(int instance) {
		ApplicationInstance ai = this.findApplicationInstanceByToken(instance);
		if (ai == null) {
			this.logger.error("Can't find ApplicationInstance "+instance);
			return;
		}
		ai.setState(ApplicationInstance.STOPPED);

		this.remove(ai);
	}
	
	public void setSpool(Spool spool) {
		this.spool = spool;
	}
}
