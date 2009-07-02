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

package org.vnc.SimpleClient;

import java.io.InputStreamReader;
import java.io.BufferedReader;

import java.awt.Container;
import java.awt.Frame;
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;

import org.vnc.Options;
import org.vnc.VncClient;

public class Viewer implements org.vnc.Dialog, WindowListener {
	protected VncClient vnc;
	protected Frame window;
	protected Container container;

	public Viewer() {
		window = new Frame("TightVncViewer");
		window.addWindowListener(this);
		container = window;
		vnc = new VncClient(this, container);
	}

	public Viewer(Container container) {
		this.window = null;
		this.container = container;
		
		vnc = new VncClient(this, container);
	}

	public void process_init() {
		container.setVisible(true);
		
		if (! vnc.connect())
			System.exit(1);

		
		if (! vnc.authenticate()) {
			vnc.disconnect();
			System.exit(1);
		}

		if (! vnc.init()) {
			vnc.disconnect();
			System.exit(1);
		}
		container.add(vnc.vc);
		if (window != null)
			window.pack();
	}

	public void loop() {
		vnc.start_background_process();

		try {
			vnc.rfbThread.join();
		} catch(java.lang.InterruptedException e) {}
		System.out.println("After all");
		System.exit(0);
	}

	// Begin Implements org.vnc.Dialog
	public String vncGetPassword() {
        String string = "";
        InputStreamReader input = new InputStreamReader(System.in);
        BufferedReader reader = new BufferedReader(input);
        try {
			System.out.print("Password: ");
            string = reader.readLine(); 
		} catch(Exception e){}
        System.out.println("You typed: " + string);

		return string;
	}
	
	public void vncSetError(String err) {
		System.err.println("Vnc error: " + err);
	}
	// End Implements org.vnc.Dialog


	// Begin Implements WindowListener
	public void windowClosing(WindowEvent evt) {
		System.out.println("Closing window");
		container.setVisible(false);
		this.vnc.stop_background_process();
	}

	public void windowActivated(WindowEvent evt) {}
	public void windowDeactivated (WindowEvent evt) {}
	public void windowOpened(WindowEvent evt) {}
	public void windowClosed(WindowEvent evt) {}
	public void windowIconified(WindowEvent evt) {}
	public void windowDeiconified(WindowEvent evt) {}
	// End Implements WindowListener


	public static void usage() {
		System.err.println("Usage: "+Viewer.class.getName()+" host:port");
    }

	public static boolean parse_commandline(String[] argv) {
		if (argv.length < 1) {
			System.err.println("Missing argumet");
			return false;
		}

		try {
			String[] buffer = argv[0].split(":", 2);
			Options.host = buffer[0];
			Options.port = Integer.parseInt(buffer[1]);
		} catch(Exception e) {
			System.err.println("Invalid argument host:port '"+argv[0]+"'");
			return false;
		}

		return true;
	}

	public static void main(String[] args) {
		if (! parse_commandline(args)) {
			usage();
			System.exit(1);
		}

		Viewer v = new Viewer();
		v.process_init();
		v.loop();
	}
}
