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

package org.ulteo.ovd.client.authInterface;

import java.awt.BorderLayout;
import java.awt.Dimension;

import javax.swing.JPanel;


public class MainPanel extends JPanel {
	CenterPanel center = null;
	ButtonPanel buttonPanel = null;
	AuthFrame frame = null;
	
	
	public MainPanel(AuthFrame frame) {
		this.frame=frame;
		
		center = new CenterPanel();
		buttonPanel = new ButtonPanel(getIds(), getOptionPanel(), frame, this);
		BorderLayout layout = new BorderLayout();
		this.setLayout(layout);
		this.add(center);
		JPanel decorationPan = new JPanel();
		decorationPan.setPreferredSize(new Dimension(frame.getWidth(), 30));
		this.add(BorderLayout.NORTH, decorationPan);
		this.add(BorderLayout.SOUTH, buttonPanel);
		validate();
		revalidate();
	}
	
	public void setFocusOnLogin() {
		getLogPan().getUsername().requestFocusInWindow();
	}
	public LoginPanel getLogPan() {
		return center.getIds().getLoginPan();
	}
	
	public PasswordPanel getPassPan() {
		return center.getIds().getPasswordPan();
	}
	
	public HostPanel getHostPan() {
		return center.getIds().getHostPan();
	}
	
	public OptionPanel getOptionPanel() {
		return center.getOpt();
	}
	public IdPanel getIds() {
		return center.getIds();
	}
}
