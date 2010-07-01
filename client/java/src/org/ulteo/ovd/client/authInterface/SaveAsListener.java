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
import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileWriter;
import java.io.IOException;
import java.io.PrintWriter;

import javax.swing.JFileChooser;
import javax.swing.JOptionPane;
import javax.swing.filechooser.FileFilter;

import org.ini4j.Wini;
import org.ulteo.ovd.client.I18n;

public class SaveAsListener implements ActionListener{

	private ButtonPanel bp = null;
	private PrintWriter writer = null;
	private String username = null;
	private String host = null;
	private int mode = 0;
	private int resolution = 0;

	public SaveAsListener(ButtonPanel bp, AuthFrame frame, MainPanel mp) {
		this.bp = bp;
	}
	@Override
	public void actionPerformed(ActionEvent e) {
		username = bp.getLoginPan().getUsername().getText();
		host = bp.getHostPan().getHostname().getText();
		mode = bp.getOpt().getComboMode().getSelectedIndex();
		resolution = bp.getOpt().getScreenSizeSelecter().getValue();
		launchSave();
	}

	public void launchSave() {
		JFileChooser saveChooser = new JFileChooser();
		FileFilter ovd = new SimpleFilter("Ovd config", ".ovd");
		saveChooser.setAcceptAllFileFilterUsed(false);
		saveChooser.addChoosableFileFilter(ovd);
		switch(saveChooser.showSaveDialog(bp)) {
		case JFileChooser.APPROVE_OPTION :
			try {
				File profileInfo = new File(saveChooser.getSelectedFile()+".ovd");
				PrintWriter out = new PrintWriter(new FileWriter(profileInfo));
				out.println();
				getProfile(profileInfo);
			} catch (IOException e) {
				e.printStackTrace();
			}
			break;
		case JFileChooser.ERROR_OPTION:
			JOptionPane.showMessageDialog(null, I18n._("An error has occured"),I18n._("Error"),JOptionPane.ERROR_MESSAGE);
			break;
		}
	}

	public void getProfile(File filename) throws IOException {
		Wini ini = new Wini(filename);
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
}
