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

import java.awt.FlowLayout;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;

import javax.swing.JCheckBox;
import javax.swing.JPanel;

import org.ulteo.ovd.client.I18n;


public class IdPanel extends JPanel {

	private LoginPanel loginPan = null;
	private PasswordPanel passwordPan = null;
	private HostPanel hostPan = null;
	private OptionPanel opt = null;
	private JCheckBox rememberMe = new JCheckBox(I18n._("Remember me"));
	private boolean checked = false;

	public IdPanel() {
		rememberMe.addActionListener(new ActionListener() {

			@Override
			public void actionPerformed(ActionEvent e) {
				checked=(checked) ? false : true;
			}
		});
		loginPan = new LoginPanel();
		passwordPan = new PasswordPanel();
		hostPan = new HostPanel();
		FlowLayout layout = new FlowLayout();
		layout.setHgap(5);
		layout.setAlignment(FlowLayout.CENTER);
		this.setLayout(layout);
		this.add(loginPan);
		this.add(passwordPan);
		this.add(hostPan);
		this.add(rememberMe);
		validate();
		revalidate();
	}

	public LoginPanel getLoginPan() {
		return loginPan;
	}

	public void setLoginPan(LoginPanel loginPan) {
		this.loginPan = loginPan;
	}

	public PasswordPanel getPasswordPan() {
		return passwordPan;
	}

	public void setPasswordPan(PasswordPanel passwordPan) {
		this.passwordPan = passwordPan;
	}

	public HostPanel getHostPan() {
		return hostPan;
	}

	public void setHostPan(HostPanel hostPan) {
		this.hostPan = hostPan;
	}

	public OptionPanel getOpt() {
		return opt;
	}

	public void setOpt(OptionPanel opt) {
		this.opt = opt;
	}
	
	public boolean isChecked() {
		return checked;
	}
	
	public void setChecked(boolean ch) {
		this.checked = ch;
	}

	public JCheckBox getRememberMe() {
		return rememberMe;
	}

	public void setRememberMe(JCheckBox rememberMe) {
		this.rememberMe = rememberMe;
	}
}
