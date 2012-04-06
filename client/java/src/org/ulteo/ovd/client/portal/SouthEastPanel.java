/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
 * Author Omar AKHAM <oakham@ulteo.com> 2011
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

import java.awt.ComponentOrientation;
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

import java.util.Locale;

import org.ulteo.utils.I18n;
import org.ulteo.ovd.client.NativeClientActions;
import org.ulteo.ovd.client.remoteApps.OvdClientPortal;


public class SouthEastPanel extends JPanel {
	
	private JButton disconnect = null;
	private JButton publishingButton = null;;
	private Icon rotateIcon = null;

	private NativeClientActions rdpActions = null;
	
	public SouthEastPanel(final NativeClientActions rdpActions) {
		this.setLayout(new GridBagLayout());

		this.rdpActions = rdpActions;
		disconnect = new JButton(I18n._("Disconnect"));
		
		this.initRotate();
		publishingButton = new JButton(this.rotateIcon);
		
		disconnect.addActionListener(new ActionListener() {
			@Override
			public void actionPerformed(ActionEvent e) {
				rdpActions.disconnect();
			}
		});
		publishingButton.addActionListener(new ActionListener() {
			@Override
			public void actionPerformed(ActionEvent arg0) {
				new Thread(new Runnable() {
					@Override
					public void run() {
						publishingButton.setEnabled(false);
						publishingButton.setIcon(rotateIcon);
						publishingButton.setText(null);

						boolean isPublished = ((OvdClientPortal)rdpActions).togglePublications();

						publishingButton.setIcon(null);
						publishingButton.setText(isPublished ? I18n._("Hide icons") : I18n._("Display icons"));
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
		
		this.applyComponentOrientation(ComponentOrientation.getOrientation(Locale.getDefault()));
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

	public void initPublishingButton(boolean enabled) {
		this.publishingButton.setIcon(null);
		this.publishingButton.setText(((OvdClientPortal) this.rdpActions).isAutoPublish() ?  I18n._("Hide icons") : I18n._("Display icons"));
		this.publishingButton.setEnabled(enabled);
	}
}
