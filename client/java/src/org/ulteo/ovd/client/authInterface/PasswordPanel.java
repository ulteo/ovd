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

import java.awt.Color;
import java.awt.Font;
import java.awt.GridLayout;

import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JPasswordField;

import org.ulteo.ovd.client.I18n;

public class PasswordPanel extends JPanel {

	private Font font = new Font("Arial", Font.BOLD, 15);
	private JPasswordField pwd = new JPasswordField(15);
	private JLabel pass = new JLabel(I18n._("Password"));
	public PasswordPanel() {
		this.setLayout(new GridLayout(1,2));
		pass.setFont(font);
		pass.setForeground(new Color(106,106,106));
		this.add(pass);
		this.add(pwd);
		revalidate();
	}

	public JPasswordField getPwd() {
		return pwd;
	}

	public void setPwd(JPasswordField pwd) {
		this.pwd = pwd;
	}
}
