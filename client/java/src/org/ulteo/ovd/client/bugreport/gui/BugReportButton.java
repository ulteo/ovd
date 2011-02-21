/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2011
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

package org.ulteo.ovd.client.bugreport.gui;

import java.awt.Dimension;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import javax.swing.ImageIcon;
import javax.swing.JButton;
import org.ulteo.ovd.client.bugreport.BugReporter;
import org.ulteo.utils.I18n;

public class BugReportButton extends JButton implements ActionListener {

	public BugReportButton() {
		super();

		this.initButton();
		this.addActionListener(this);
	}

	private void initButton() {
		ImageIcon icon = new ImageIcon(this.getToolkit().getImage(getClass().getClassLoader().getResource("pics/bug.png")));
		this.setIcon(icon);
		this.setText("");
		this.setToolTipText(I18n._("Report a bug"));

		int size = this.getPreferredSize().height;
		this.setPreferredSize(new Dimension(size, size));
	}

	public void actionPerformed(ActionEvent e) {
		if (e.getSource() != this)
			return;

		BugReporter.showWindow();
	}
}
