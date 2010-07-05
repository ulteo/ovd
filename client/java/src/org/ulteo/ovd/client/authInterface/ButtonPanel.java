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

import java.awt.Font;
import java.awt.event.KeyEvent;

import javax.swing.JButton;
import javax.swing.JComponent;
import javax.swing.JPanel;
import javax.swing.KeyStroke;

import org.ulteo.ovd.client.I18n;

public class ButtonPanel extends JPanel {

	private JButton loginButton = new JButton(I18n._("Connect"));
	private LoginPanel loginPan = null;
	private PasswordPanel passwordPan = null;
	private Font font = new Font("Arial", Font.BOLD, 11);
	private HostPanel hostPan = null;
	private OptionPanel opt = null;
	private JButton saveAsButton = new JButton(I18n._("Save as ..."));
	private IdPanel ids = null;
	
	public ButtonPanel(IdPanel ids, OptionPanel opt, AuthFrame frame, MainPanel mp) {
		this.ids = ids;
		this.loginPan = ids.getLoginPan();
		this.passwordPan = ids.getPasswordPan();
		this.hostPan = ids.getHostPan();
		this.opt = opt;
		loginButton.setFont(font);
		saveAsButton.setFont(font);
		loginButton.addActionListener(new LoginListener(this, frame));
		saveAsButton.addActionListener(new SaveAsListener(this, frame, mp));
		enterPressesWhenFocused(loginButton);
		enterPressesWhenFocused(saveAsButton);
		this.add(loginButton);
		this.add(saveAsButton);
		revalidate();
	}

	public void enterPressesWhenFocused(JButton button) {
	    button.registerKeyboardAction(
	        button.getActionForKeyStroke(
	            KeyStroke.getKeyStroke(KeyEvent.VK_SPACE, 0, false)), 
	            KeyStroke.getKeyStroke(KeyEvent.VK_ENTER, 0, false), 
	            JComponent.WHEN_FOCUSED);

	    button.registerKeyboardAction(
	        button.getActionForKeyStroke(
	            KeyStroke.getKeyStroke(KeyEvent.VK_SPACE, 0, true)), 
	            KeyStroke.getKeyStroke(KeyEvent.VK_ENTER, 0, true), 
	            JComponent.WHEN_FOCUSED);
	}

	public IdPanel getIds() {
		return ids;
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

	public JButton getSaveAsButton() {
		return saveAsButton;
	}

	public void setSaveAsButton(JButton saveAsButton) {
		this.saveAsButton = saveAsButton;
	}
}
