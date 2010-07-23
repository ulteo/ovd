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
import java.awt.Image;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;

import javax.swing.JButton;
import javax.swing.JDialog;
import javax.swing.JFrame;
import javax.swing.JProgressBar;

import org.ulteo.ovd.client.I18n;
import org.ulteo.ovd.client.OvdClient;

public class LoadingFrame extends JDialog implements Runnable{

	private OvdClient cli = null;
	private Image logo = null;
	private AuthFrame frame = null;

	public LoadingFrame(OvdClient cli, AuthFrame frame) {
		this.cli = cli;
		this.frame = frame;

		logo = getToolkit().getImage(getClass().getClassLoader().getResource("pics/ulteo.png"));
		setIconImage(logo);
	}

	public void cancelConnection() {
		cli.disconnectAll();
	}

	@Override
	public void run() {
		this.setTitle(I18n._("Now loading"));
		this.setDefaultCloseOperation(JFrame.DO_NOTHING_ON_CLOSE);
		this.setSize(300, 80);
		this.setPreferredSize(new Dimension(300,80));
		final JProgressBar aJProgressBar = new JProgressBar(JProgressBar.HORIZONTAL);
		this.setModal(true);
		aJProgressBar.setIndeterminate(true);
		aJProgressBar.setPreferredSize(new Dimension(280, 20));
		aJProgressBar.setLocation(10,45);
		JButton cancel = new JButton(I18n._("Cancel"));
		cancel.setPreferredSize(new Dimension(120, 10));
		cancel.addActionListener(new ActionListener() {

			@Override
			public void actionPerformed(ActionEvent e) {
				cancelConnection();
			}
		});
		this.add(BorderLayout.NORTH, aJProgressBar);
		this.add(BorderLayout.EAST, cancel);
		this.setLocationRelativeTo(frame.getMainFrame());
		this.setVisible(true);
		this.pack();
	}
}
