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
import java.io.PrintWriter;


public class SaveListener implements ActionListener{
	
	private ButtonPanel bp = null;
	private File profileInfo = null;
	private PrintWriter writer = null;
	private String username = null;
	private String host = null;
	private int mode = 0;
	private int resolution = 0;

	public SaveListener(ButtonPanel bp, AuthFrame frame, MainPanel mp) {
		this.bp = bp;
		profileInfo = new File("./profileInfo.ini");
	}

	@Override
	public void actionPerformed(ActionEvent e) {
		username = bp.getLoginPan().getUsername().getText();
		host = bp.getHostPan().getHostname().getText();
		mode = bp.getOpt().getComboMode().getSelectedIndex();
		resolution = bp.getOpt().getScreenSizeSelecter().getValue();
		
		try {
			writer = new PrintWriter(profileInfo);
		}catch (FileNotFoundException fe) {
			fe.printStackTrace();
		}
		getProfile(writer, username, host, mode, resolution);
	}
	
	public void getProfile(PrintWriter writer, String username, String hostname, int mode, int resolution) {
		writer.println(" === Configuration file ===");
		writer.println();
		writer.println("[user]");
		writer.println("login="+username);
		writer.println("");
		writer.println("[server]");
		writer.println("host="+hostname);
		writer.println("");
		writer.println("[mode]");
		if (mode == 0)
			writer.println("mode=desktop");
		else if (mode == 1)
			writer.println("mode=portal");
		else
			writer.println("mode=integrated");
		
		writer.println("");
		writer.println("[screen]");
		switch(resolution) {
		case 0 :
			writer.println("resolution=800x600");
			break;
		case 1 :
			writer.println("resolution=1024x768");
			break;
		case 2 : 
			writer.println("resolution=1280x678");
			break;
		case 3 : 
			writer.println("resolution=maximized");
			break;
		case 4 : 
			writer.println("resolution=fullscreen");
			break;
		}
		writer.flush();
	}
}
