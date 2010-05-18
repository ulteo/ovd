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

import org.ulteo.ovd.client.I18n;

import org.jdesktop.swingx.autocomplete.AutoCompleteDecorator;

public class HostPanel extends JPanel {
	private Font font = new Font("Arial", Font.BOLD, 15);
	private JTextField hostname = new JTextField(15);
	private JLabel host = new JLabel(I18n._("Host address"));
	private InputStreamReader reader = null;
	private LineNumberReader lineReader = null;
	private File logInfo = null;
	private String previousHost = null;
	private ArrayList<String> hosts = null;
	
	public HostPanel() {
		hosts = new ArrayList<String>();
		this.setLayout(new GridLayout(1,2));
		host.setFont(font);
		host.setForeground(new Color(106,106,106));
		this.add(host);
		this.add(hostname);
		logInfo = new File("./usersInfo.ovd");
		try{
			FileInputStream fis = new FileInputStream(logInfo);
			LineNumberReader l = new LineNumberReader(       
					new BufferedReader(new InputStreamReader(fis)));
			int count=0;
			while (l.readLine() != null)
			{
				count = l.getLineNumber();
			}
			reader = new InputStreamReader(new  FileInputStream(logInfo));
			lineReader = new LineNumberReader(reader);
			
			for(int i=0;i<count;i++) {
				previousHost = lineReader.readLine();
				if(previousHost.startsWith("host=")) {
					hosts.add(previousHost.substring("host=".length()));
				}
			}
		}catch(IOException ioe){}
		
		AutoCompleteDecorator.decorate(hostname, hosts, false);
		this.revalidate();
	}

	public JTextField getHostname() {
		return hostname;
	}

	public void setHostname(JTextField hostname) {
		this.hostname = hostname;
	}
}
