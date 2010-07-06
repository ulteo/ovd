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
import java.io.BufferedReader;
import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.LineNumberReader;
import java.util.ArrayList;

import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JTextField;

import org.jdesktop.swingx.autocomplete.AutoCompleteDecorator;
import org.ulteo.ovd.client.I18n;
import org.ulteo.ovd.integrated.Constants;

public class LoginPanel extends JPanel {

	private Font font = new Font("Arial", Font.BOLD, 15);
	private ArrayList<String> logins = null;
	private JTextField username = new JTextField(15);
	private JLabel login = new JLabel(I18n._("Login"));
	private InputStreamReader reader = null;
	private LineNumberReader lineReader = null;
	private File logInfo = null;
	private String previousLogs = null;

	public LoginPanel() {
		logins = new ArrayList<String>();
		this.setLayout(new GridLayout(1,2));
		login.setFont(font);
		login.setForeground(new Color(106,106,106));
		this.add(login);
		this.add(username);
		logInfo = new File(Constants.clientConfigFilePath+Constants.separator+"history.conf");
		try{
			FileInputStream fis = new FileInputStream(logInfo);
			LineNumberReader l = new LineNumberReader(       
					new BufferedReader(new InputStreamReader(fis)));
			int count=0;
			while (l.readLine() != null)
			{
				count = l.getLineNumber();
			}

			username.requestFocus();

			reader = new InputStreamReader(new  FileInputStream(logInfo));
			lineReader = new LineNumberReader(reader);
			
			for(int i=0;i<count;i++) {
				previousLogs = lineReader.readLine();
				if(previousLogs.startsWith("login=")) {
					logins.add(previousLogs.substring("login=".length()));
				}
			}
		}catch(IOException ioe){
			System.out.println("No auto complete file app used for the first time");
		}
		AutoCompleteDecorator.decorate(username, logins, false);
		revalidate();
	}

	public JTextField getUsername() {
		return username;
	}

	public void setUsername(JTextField username) {
		this.username = username;
	}
}
