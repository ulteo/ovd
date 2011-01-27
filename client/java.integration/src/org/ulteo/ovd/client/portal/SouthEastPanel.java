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

import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;

import javax.swing.JButton;
import javax.swing.JPanel;

import org.ulteo.utils.I18n;
import org.ulteo.ovd.client.remoteApps.OvdClientPortal;
import org.ulteo.rdp.RdpActions;


public class SouthEastPanel extends JPanel {
	
	private JButton disconnect = new JButton(I18n._("Disconnect"));
	private JButton publishingButton = null;;
	private RdpActions actions = null;
	private GridBagConstraints gbc = null;
	private static final String DISPLAY = I18n._("Display icons");
	private static final String HIDE = I18n._("Hide icons");
	
	public SouthEastPanel(RdpActions rdpActions) {
		this.setLayout(new GridBagLayout());
		this.gbc = new GridBagConstraints();
		this.actions = rdpActions;
		publishingButton = (((OvdClientPortal)actions).isAutoPublish() ?  new JButton(HIDE) : new JButton(DISPLAY));
		disconnect.addActionListener(new LogoutListener(rdpActions));
		publishingButton.addActionListener(new ActionListener() {
			
			@Override
			public void actionPerformed(ActionEvent arg0) {
				publishingButton.setText((((OvdClientPortal)actions).togglePublications()) ? HIDE : DISPLAY);
			}
		});
		this.toggleIconsButton(false);

		this.gbc.anchor = GridBagConstraints.LINE_END;
		this.gbc.gridx = this.gbc.gridy = 0;
		this.add(publishingButton, gbc);
		
		this.gbc.gridx = 1;
		this.add(disconnect, gbc);
	}

	public void toggleIconsButton(boolean enable) {
		this.publishingButton.setEnabled(enable);
	}
}
