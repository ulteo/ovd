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

import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.io.BufferedReader;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileWriter;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.LineNumberReader;
import java.io.PrintWriter;

import javax.swing.JOptionPane;

import org.ini4j.Wini;
import org.ulteo.ovd.client.I18n;
import org.ulteo.ovd.client.OvdClient;
import org.ulteo.ovd.client.desktop.OvdClientDesktop;
import org.ulteo.ovd.client.remoteApps.OvdClientIntegrated;
import org.ulteo.ovd.client.remoteApps.OvdClientPortal;
import org.ulteo.ovd.integrated.Constants;

public class LoginListener implements ActionListener{

	private ButtonPanel bp = null;
	private AuthFrame frame = null;
	private OvdClient cli = null;
	private File profileInfo = null;
	private File connectionInfo = null;
	private File connectionRepInfo = null;
	private String username = null;
	private String host = null;
	private String pass = null;
	private String list = "";
	private int mode = 0;
	private int resolution = 1;
	public LoadingFrame loader = null;

	public LoginListener(ButtonPanel bp, AuthFrame frame) {
		this.bp=bp;
		this.frame=frame;
		connectionRepInfo = new File(Constants.clientConfigFilePath);
		}

	@Override
	public void actionPerformed(ActionEvent e) {
		char[] password = null;

		username = bp.getLoginPan().getUsername().getText();
		password = bp.getPasswordPan().getPwd().getPassword();
		host = bp.getHostPan().getHostname().getText();
		mode = bp.getOpt().getComboMode().getSelectedIndex();
		resolution = bp.getOpt().getScreenSizeSelecter().getValue();

		try{
			connectionRepInfo.mkdirs();
			connectionInfo = new File(Constants.clientConfigFilePath+Constants.separator+"history.conf");
			FileInputStream fis = new FileInputStream(connectionInfo);
			LineNumberReader l = new LineNumberReader(       
					new BufferedReader(new InputStreamReader(fis)));
			int count=0;
			while (l.readLine()!=null)
			{
				count = l.getLineNumber();
			}
			InputStreamReader reader = new InputStreamReader(new  FileInputStream(connectionInfo));
			LineNumberReader lineReader = new LineNumberReader(reader);
			for(int i=0;i<count;i++) {
				if(i != count-1) {
					list+=lineReader.readLine()+'\n';
				}
				else {
					list+=lineReader.readLine();
				}
			}
		}
		catch(IOException ie) {
			// If the file does not exist it will be created automatically
			System.out.println("auto complete created with success");
		}
		PrintWriter writer = null;
		try {
			writer = new PrintWriter(connectionInfo);
		}catch (FileNotFoundException fe) {}

		pass="";
		for (char each : password){
			pass = pass+each;
		}

		if (host.equals("") || username.equals("") || pass.equals("")) {
			JOptionPane.showMessageDialog(null, I18n._("You must specify all the fields !"), I18n._("Warning !"), JOptionPane.WARNING_MESSAGE);
		}
		else {
			if (bp.getIds().isChecked()) {
				saveDefault();
			}
			getInfo(writer, list, username, host);
			changeFrame(frame);
		}
	}

	public void changeFrame(AuthFrame frame) {
		if(cli != null){
			if(cli.isAlive()) {
				cli.interrupt();
				cli = null;
			}
		}
		boolean use_https = this.frame.isHttps();
		switch (this.mode) {
			case 0:
				this.cli = new OvdClientDesktop(host, use_https, username, pass, frame, resolution, this);
				break;
			case 1:
				this.cli = new OvdClientPortal(host, use_https, username, pass, frame, this);
				break;
			case 2:
				this.cli = new OvdClientIntegrated(host, use_https, username, pass, frame, this);
				break;
			default:
				throw new UnsupportedOperationException("mode "+this.mode+" is not supported");
		}
		
		cli.start();
	}

	public void getInfo(PrintWriter writer, String list, String username, String hostname) {
		writer.println(list);
		if(! list.contains(username)){
			writer.println("login="+username);
		}
		if(! list.contains(hostname)){
			writer.println("host="+hostname);
		}
		writer.flush();
	}
	
	public void saveDefault() {
		try {
			connectionRepInfo.mkdirs();
			profileInfo = new File(Constants.clientConfigFilePath+(Constants.separator+"default.conf"));
			PrintWriter out = new PrintWriter(new FileWriter(profileInfo));
			out.println();
			getProfile();
		} catch (IOException e1) {
			e1.printStackTrace();
		}
	}
	
	public void getProfile() throws IOException {
		Wini ini = new Wini(this.profileInfo);
		ini.put("user", "login", this.username);
		ini.put("server", "host", this.host);
		if (mode == 0)
			ini.put("sessionMode", "ovdSessionMode", "desktop");
		else if (mode == 1)
			ini.put("sessionMode", "ovdSessionMode", "portal");
		else
			ini.put("sessionMode", "ovdSessionMode", "integrated");
		
		switch(resolution) {
		case 0 :
			ini.put("screen", "size", "800x600");
			break;
		case 1 :
			ini.put("screen", "size", "1024x768");
			break;
		case 2 : 
			ini.put("screen", "size", "1280x678");
			break;
		case 3 : 
			ini.put("screen", "size", "maximized");
			break;
		case 4 : 
			ini.put("screen", "size", "fullscreen");
			break;
		}
		ini.store();
	}
	
	public void initLoader() {
		loader = new LoadingFrame(cli, frame);
		Thread load = new Thread(loader);
		load.start();
	}
	
	public LoadingFrame getLoader() {
		return loader;
	}
}
