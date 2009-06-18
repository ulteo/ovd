/**
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
 **/

package org.sshvnc;

import java.awt.Container;
import java.awt.Label;
import java.awt.Color;
import java.awt.Frame;

import javax.swing.JOptionPane;

public class Applet extends java.applet.Applet implements Runnable {
	public static final String version = "0.2.4";

    public Viewer obj;

    //  @Override
	public void init() {
	 System.out.println("Starting UlteoVNC version "+version);
	 
	 //	 this.obj = new Viewer();
	 this.obj = new Viewer(this);
	 this.obj.arg_parser = new ArgParser(this);
	 this.obj.readParameters();
	 this.obj.init();

	 Thread rfbThread = new Thread(this);
	 rfbThread.start();
    }

    public void run() {
	System.out.println("Run 0");
    	this.obj.start();

	System.out.println("Run 1");
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

       //       Thread tr;
       //      Thread tr = new Thread(this);
       System.out.println("applet Start");
       //  tr.start();
       // System.out.println("applet Start 0");

       //       this.run();
       //code de d'éxécution
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
  
}