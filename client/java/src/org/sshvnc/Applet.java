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

package org.sshvnc;

import java.awt.Container;
import java.awt.Label;
import java.awt.Color;
import java.awt.Frame;

import javax.swing.JOptionPane;

public class Applet extends java.applet.Applet {
	public static final String version = "0.2.4";

    //Proxy parameters
    public String proxyType,proxyHost,proxyUsername,proxyPassword;
    public int proxyPort;
    public Viewer obj;

    //  @Override
	public void init() {
	 System.out.println("Starting UlteoVNC version "+version);

	 readParameters();

	 this.obj = new Viewer();

	 if(proxyHost != null && !proxyHost.equals("")){
		this.obj.ssh_properties.setTransportProviderString(proxyType);
		this.obj.ssh_properties.setPort(443); //Always use this when using proxy
		this.obj.ssh_properties.setProxyHost(proxyHost);
		this.obj.ssh_properties.setProxyPort(proxyPort);
		this.obj.ssh_properties.setProxyUsername(proxyUsername);
		this.obj.ssh_properties.setProxyPassword(proxyPassword);
	 }
    }

  //
  // Show message text and optionally "Relogin" and "Close" buttons.
  //

  void showMessage(String msg) {
      this.removeAll();
    JOptionPane.showMessageDialog(this, "The Online Desktop has closed.\n" +
			"Thanks for using our service!\n", "Online Desktop session finished",JOptionPane.INFORMATION_MESSAGE);
    	//System.err.println("ERROR: "+msg+"\n");
  }

  //
  // Stop the applet.
  // Main applet thread will terminate on first exception
  // after seeing that rfbThread has been set to null
  //

  
  public String getAppletInfo() {
	  return "UlteoVNC";
  }

  public String getHostToPing(){
	  return getParameter("hostToPing");
  }


   public void start()
   {
       System.out.println("applet Start");
	   this.obj.process_init();
	   this.obj.loop();
   }
   
   public void stop()
   {
       System.out.println("applet Stop");
       //code de suspension de l'execution
   }
   
   public void destroy()
   {
       System.out.println("applet destroy");
       //code de terminaison
   }

    public void readParameters() {
		String buffer;

	obj.sshHost = getParameter("ssh.host");
	try {
		obj.sshPort = Integer.parseInt(getParameter("ssh.port"));
	} catch(NumberFormatException e) {}

	obj.sshUser = getParameter("ssh.user");
	obj.sshPassword = getParameter("ssh.password");

	// Read proxy parameters, if any -- by ArnauVP
	proxyType = getParameter("proxyType");
	proxyHost = getParameter("proxyHost");
	buffer = getParameter("proxyPort");
	if (buffer != null) {
		try {
			proxyPort = Integer.parseInt(buffer);
		} catch(NumberFormatException e) {}
	}
	proxyUsername = getParameter("proxyUsername");
	proxyPassword = getParameter("proxyPassword");

	buffer = getParameter("vncPort");
	obj.vncPort = Integer.parseInt(buffer);

	obj.vncPassword = getParameter("vncPassword");
    }
}