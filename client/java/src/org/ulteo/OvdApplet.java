/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
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


package org.ulteo;

import org.sshvnc.Viewer;

import javax.swing.JOptionPane;

public class OvdApplet extends org.sshvnc.Applet {
    public static final String version = "0.2.4";


	public void init() {
		Runtime rt = Runtime.getRuntime();
		if(rt.totalMemory() == rt.maxMemory() && rt.freeMemory() < 11000000){
			System.err.println("Not enough memory to start the applet");
			JOptionPane.showMessageDialog(null, "Your Java Machine is low on virtual memory.\nPlease restart the browser before launching Ulteo Online Desktop", "Warning", JOptionPane.ERROR_MESSAGE);
			stop();
			return;
		}

//		this.add("Center", this);
		super.init();
    }

	public String vncGetPassword() {
		return Utils.DecryptEncVNCString(this.vncPassword);
	}


	public void readParameters() {
		this.ssh.host = getParameter("ssh.host");

		String[] buffer = getParameter("ssh.port").split(",");
		if (buffer.length == 0) {
			System.err.println("no port given");
			stop();
		}
		try {
			this.ssh.port = Integer.parseInt(buffer[0]);
		} catch(NumberFormatException e) {}

		this.ssh.user = getParameter("ssh.user");
		this.ssh.password = Utils.DecryptString(getParameter("ssh.password"));

		// Read proxy parameters, if any -- by ArnauVP
		proxyType = getParameter("proxyType");
		proxyHost = getParameter("proxyHost");
		try {
			proxyPort = Integer.parseInt(getParameter("proxyPort"));
		} catch(NumberFormatException e) {}

		proxyUsername = getParameter("proxyUsername");
		proxyPassword = getParameter("proxyPassword");

		try {
			this.ssh.vncPort = Integer.parseInt(getParameter("PORT"));
		} catch(NumberFormatException e) {}

		org.vnc.Options.host = getParameter("HOST");
		this.vncPassword = getParameter("ENCPASSWORD");
    }

    void showMessage(String msg) {
		//vncContainer.removeAll();
		JOptionPane.showMessageDialog(this, "The Online Desktop has closed.\n" +
				      "Thanks for using our service!\n", "Online Desktop session finished",JOptionPane.INFORMATION_MESSAGE);
		System.err.println("ERROR: "+msg+"\n");
    }
  
    public String getAppletInfo() {
		return "UlteoVNC";
    }
}
