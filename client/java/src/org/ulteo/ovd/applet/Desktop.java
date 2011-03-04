/*
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2009-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
 * Author Samuel BOVEE <samuel@ulteo.com> 2010
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

package org.ulteo.ovd.applet;

import java.applet.Applet;
import java.awt.BorderLayout;
import java.awt.event.FocusEvent;
import java.awt.event.FocusListener;
import java.io.FileNotFoundException;

import netscape.javascript.JSObject;

import org.ulteo.Logger;
import org.ulteo.ovd.client.ClientInfos;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.printer.OVDStandalonePrinterThread;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.rdp.rdpdr.OVDPrinter;
import org.ulteo.utils.AbstractFocusManager;

public class Desktop extends Applet implements JSForwarder, FocusListener {
	private boolean fullscreenMode = false;
	private int port = 0;
	private String server = null;
	private String username = null;
	private String password = null;
	private String keymap = null;
	private String token = null;
	
	private OvdClientDesktopApplet ovd = null;
	
	private boolean finished_init = false;
	private boolean finished_start = false;
	private boolean started_stop = false;
	
	public AbstractFocusManager focusManager = null;

	@Override
	public void init() {
		System.out.println(this.getClass().toString() +"  init");

		OSTools.is_applet = true;

		ClientInfos.showClientInfos();
		
		boolean status = this.checkSecurity();
		if (! status) {
			System.err.println(this.getClass().toString() +"  init: Not enought privileges, unable to continue");
			this.stop();
			return;
		}
		
		String tempdir = System.getProperty("java.io.tmpdir");
		if (! tempdir.endsWith(System.getProperty("file.separator"))) 
			tempdir+= System.getProperty("file.separator");
		
		if (OSTools.isWindows()) {
			try {
				LibraryLoader.LoadLibrary(LibraryLoader.RESOURCE_LIBRARY_DIRECTORY_WINDOWS, LibraryLoader.LIB_WINDOW_PATH_NAME);
			} catch (FileNotFoundException ex) {
				Logger.error(ex.getMessage());
				this.stop();
				return;
			}
		}
		if (! Logger.initInstance(true, tempdir+"ulteo-ovd-"+Logger.getDate()+".log", true)) {
			System.err.println(this.getClass().toString()+" Unable to iniatialize logger instance");
		}

		Properties properties = new Properties(Properties.MODE_DESKTOP);
		
		if (! readParameters(properties)) {
			System.err.println(this.getClass().toString() +" usage error");
			this.stop();
			return;
		}

		if (properties.isPrinters()){
			OVDStandalonePrinterThread appletPrinterThread = new OVDStandalonePrinterThread();
			OVDPrinter.setPrinterThread(appletPrinterThread);
			this.focusManager = new AppletFocusManager(appletPrinterThread);
		}

		ServerAccess aps = new ServerAccess(this.server, this.port, this.username, this.password);
		if (this.token != null) {
			aps.setModeGateway(true);
			aps.setToken(this.token);
		}

		try {
			this.ovd = new OvdClientDesktopApplet(aps, properties, this);
		} catch (ClassCastException ex) {
			Logger.error(ex.getMessage());
			this.stop();
			return;
		}
		this.ovd.setKeymap(this.keymap);
		this.ovd.setFullscreen(this.fullscreenMode);
		this.ovd.perform();
		
		this.finished_init = true;
		
		BorderLayout layout = new BorderLayout();
		this.setLayout(layout);
	}
	
	@Override
	public void start() {	
		if (! this.finished_init || this.started_stop)
			return;	
		System.out.println(this.getClass().toString() +" start");

		this.ovd.adjustDesktopSize();

		this.ovd.sessionReady();
		
		this.finished_start = true;

		System.out.println(this.getClass().toString() +" started");
	}
	
	@Override
	public void stop() {
		if (this.started_stop)
			return;
		this.started_stop = true;
		System.out.println(this.getClass().toString()+" stop");
		this.focusManager = null;

		if (this.ovd != null)
			this.ovd.performDisconnectAll();
		else
			this.forwardJS(JSForwarder.JS_API_F_SERVER, 0, JSForwarder.JS_API_O_SERVER_FAILED);
	}
	
	@Override
	public void destroy() {
		this.ovd = null;
		this.server = null;
		this.username = null;
		this.password = null;
		
		System.gc();
	}
	
	public boolean checkSecurity() {
		try {
			System.getProperty("user.home");
		} catch(java.security.AccessControlException e) {
			return false;
		}

		return true;
	}
	
	public String getParameterNonEmpty(String key) throws Exception {
		String buffer = super.getParameter(key);
		if (buffer != null && buffer.equals("")) {
			System.err.println("Parameter "+key+": empty value");
			throw new Exception();
		}

		return buffer;
	}

	public String getParameter(String key, boolean required) throws Exception {
		String buffer = this.getParameterNonEmpty(key);

		if (required &&  buffer == null) {
			System.err.println("Missing parameter key '"+key+"'");
			throw new Exception();
		}

		return buffer;
	}


	public boolean readParameters(Properties properties) {
		try {
			this.server = this.getParameterNonEmpty("server");
			this.username = this.getParameterNonEmpty("username");
			this.password = this.getParameterNonEmpty("password");
			this.keymap = this.getParameterNonEmpty("keymap");
			this.token = this.getParameter("token");
			String strPort = this.getParameterNonEmpty("port");
			try {
				this.port = Integer.parseInt(strPort);
			}
			catch (NumberFormatException e) {
				Logger.error("Unable to get valid port from applet parameters: "+e.getMessage());
				return false;
			}
		}
		catch(Exception e) {
			return false;
		}
		
		OptionParser.readParameters(this, properties);
		
		String buf = this.getParameter("fullscreen");
		if (buf != null)
			this.fullscreenMode = true;
		
		return true;
    }

	@SuppressWarnings("deprecation")
	public void forwardJS(String functionName, Integer instance, String status) {
		Object[] args = new Object[2];
		args[0] = instance;
		args[1] = status;
		
		try {
			try {
				JSObject win = JSObject.getWindow(this);
				win.call(functionName, args);
			} catch (ClassCastException e) {
				// a cast exception is raised when the applet is executed by the 
				// appletViewer class (used by some IDEs) and with OpenJDK JVM. This will 
				// not execute the JS, so it not possible to run an OVD session
				throw new netscape.javascript.JSException(e.getMessage());
			}
		}
		catch (netscape.javascript.JSException e) {
			String buffer = functionName+"(";
			for(Object o: args)
				buffer+= o+", ";
			if (buffer.endsWith(", "))
				buffer = buffer.substring(0, buffer.length()-2);
			buffer+=")";
			
			System.err.println(this.getClass()+" error while execute '"+buffer+"' => "+e.getMessage());
		}
	}

	@Override
	public void focusGained(FocusEvent e) {
		if (this.focusManager != null && !this.started_stop) {
			this.focusManager.performedFocusGain(e.getComponent());
		}
	}

	@Override
	public void focusLost(FocusEvent e) {
		if (this.focusManager != null && !this.started_stop) {
			this.focusManager.performedFocusLost(e.getComponent());
		}
	}
}
