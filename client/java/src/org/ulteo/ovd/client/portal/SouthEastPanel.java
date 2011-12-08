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
import java.awt.Image;
import java.awt.Toolkit;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.net.URL;
import javax.swing.Icon;
import javax.swing.ImageIcon;

import javax.swing.JButton;
import javax.swing.JPanel;
import org.ulteo.Logger;

import org.ulteo.utils.I18n;
import org.ulteo.ovd.client.remoteApps.OvdClientPortal;
import org.ulteo.rdp.RdpActions;


public class SouthEastPanel extends JPanel {
	
	private JButton disconnect = null;
	private JButton publishingButton = null;;
	private Icon rotateIcon = null;
	private static final String DISPLAY = I18n._("Display icons");
	private static final String HIDE = I18n._("Hide icons");

	private RdpActions rdpActions = null;
	
	public SouthEastPanel(final RdpActions rdpActions) {
		this.setLayout(new GridBagLayout());

		this.rdpActions = rdpActions;
		disconnect = new JButton(I18n._("Disconnect"));
		
		this.initRotate();
		publishingButton = new JButton(this.rotateIcon);
		
		disconnect.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent e) {
				rdpActions.disconnect(false);
			}
		});
		publishingButton.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent arg0) {
				new Thread(new Runnable() {
					public void run() {
						publishingButton.setEnabled(false);
						publishingButton.setIcon(rotateIcon);
						publishingButton.setText(null);

						boolean isPublished = ((OvdClientPortal)rdpActions).togglePublications();

						publishingButton.setIcon(null);
						publishingButton.setText(isPublished ? HIDE : DISPLAY);
						publishingButton.setEnabled(true);
					}
				}).start();
			}
		});
		this.publishingButton.setEnabled(false);

		GridBagConstraints gbc = new GridBagConstraints();
		
		gbc.anchor = GridBagConstraints.LINE_END;
		gbc.gridx = gbc.gridy = 0;
		this.add(publishingButton, gbc);
		
		gbc.gridx = 1;
		this.add(disconnect, gbc);
	}

	private void initRotate() {
		URL url = SouthEastPanel.class.getClassLoader().getResource("pics/rotate.gif");
		if (url == null) {
			Logger.error("Weird. The icon pics/rotate.gif was not found in the jar");
			return;
		}

		Image rotateImg = Toolkit.getDefaultToolkit().getImage(url);
		if (rotateImg == null) {
			Logger.error("Weird. Failed to create Image object from icon pics/rotate.gif");
			return;
		}

		this.rotateIcon = new ImageIcon(rotateImg);
		if (this.rotateIcon == null) {
			Logger.error("Weird. Failed to create Icon object from icon pics/rotate.gif");
			return;
		}
	}

	public void initPublishingButton() {
		if (this.publishingButton == null)
			return;

		this.publishingButton.setIcon(null);
		this.publishingButton.setText(((OvdClientPortal) this.rdpActions).isAutoPublish() ?  HIDE : DISPLAY);
		this.publishingButton.setEnabled(true);
	}
}
