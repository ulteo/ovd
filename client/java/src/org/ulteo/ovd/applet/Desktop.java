/*
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2009-2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
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

import net.propero.rdp.RdesktopCanvas;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpConnection;
import net.propero.rdp.RdpListener;
import netscape.javascript.JSObject;

import org.ulteo.Logger;
import org.ulteo.ovd.FullscreenWindow;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.printer.OVDStandalonePrinterThread;
import org.ulteo.rdp.RdpConnectionOvd;
import org.ulteo.rdp.rdpdr.OVDPrinter;
import org.ulteo.utils.AbstractFocusManager;

public class Desktop extends Applet implements RdpListener, FocusListener {

	private boolean fullscreenMode = false;
	private FullscreenWindow externalWindow = null;
	
	private String server = null;
	private String username = null;
	private String password = null;
	private String keymap = null;
	private boolean multimedia_mode = false;
	private boolean map_local_printers = false;
	
	private RdpConnectionOvd rc = null;
	
	private boolean finished_init = false;
	private boolean finished_start = false;
	private boolean started_stop = false;
	
	public static final String JS_API_F_SERVER = "serverStatus";
	public static final String JS_API_O_SERVER_CONNECTED = "connected";
	public static final String JS_API_O_SERVER_DISCONNECTED = "disconnected";
	public static final String JS_API_O_SERVER_FAILED = "failed";
	public static final String JS_API_O_SERVER_READY = "ready";
	
	public AbstractFocusManager focusManager = null;

	@Override
	public void init() {
		System.out.println(this.getClass().toString() +"  init");

		OSTools.is_applet = true;
		
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
			Logger.initInstance(true, null, true);
		}

		if (! readParameters()) {
			System.err.println(this.getClass().toString() +"  usage error");
			this.stop();
			return;
		}

		byte flags = RdpConnectionOvd.MODE_DESKTOP;
		if(this.multimedia_mode)
			flags |= RdpConnectionOvd.MODE_MULTIMEDIA;

		if (this.map_local_printers){
			System.out.println("Printer detection active");
			flags |= RdpConnectionOvd.MOUNT_PRINTERS;
			OVDStandalonePrinterThread appletPrinterThread = new OVDStandalonePrinterThread(); 
			OVDPrinter.setPrinterThread(appletPrinterThread);
			this.focusManager = new AppletFocusManager(appletPrinterThread);
		}

		try {
			this.rc = new RdpConnectionOvd(flags);
		} catch (RdesktopException ex) {
			System.err.println("ERROR: "+ex.getMessage());
			return;
		}
		try {
			this.rc.initSecondaryChannels();
		} catch (RdesktopException ex) {
			System.err.println("WARNING: "+ex.getMessage());
		}
		this.rc.setKeymap(this.keymap);

		this.rc.setServer(this.server);
		this.rc.setCredentials(this.username, this.password);

		// Ensure that width is multiple of 4
		// Prevent artifact on screen with a with resolution
		// not divisible by 4
		int w = this.getWidth();
		int h = this.getHeight();
		if (this.fullscreenMode) {
			w = FullscreenWindow.getScreenSize().width;
			h = FullscreenWindow.getScreenSize().height;
		}
		this.rc.setGraphic(w & ~3, h, RdpConnectionOvd.DEFAULT_BPP);
		
		this.finished_init = true;
		
		BorderLayout layout = new BorderLayout();
		this.setLayout(layout);
	}
	
	@Override
	public void start() {	
		if (! this.finished_init || this.started_stop)
			return;	
		System.out.println(this.getClass().toString() +" start");

		this.rc.addRdpListener(this);
		this.rc.connect();
		
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

		if (this.rc != null) {
			try {
				this.rc.interruptConnection();
			} catch (RdesktopException ex) {
				System.out.println(ex.getMessage());
			}
		}
		
		if (this.fullscreenMode) {
			if (this.externalWindow != null) {
				this.externalWindow.unFullscreen();
				this.externalWindow.setVisible(false);
				this.externalWindow = null;
			}
		}
		else {
			this.removeAll();
		}
	}
	
	@Override
	public void destroy() {
		this.rc = null;
		this.server = null;
		this.username = null;
		this.password = null;
		
		this.rc = null;
		
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


	public boolean readParameters() {
		try {
			this.server = this.getParameterNonEmpty("server");
			this.username = this.getParameterNonEmpty("username");
			this.password = this.getParameterNonEmpty("password");
			this.keymap = this.getParameterNonEmpty("keymap");
		}
		catch(Exception e) {
			return false;
		}
		String buf = this.getParameter("multimedia");
		if (buf != null)
			this.multimedia_mode = buf.equalsIgnoreCase("true");
		
		buf = this.getParameter("redirect_client_printers");
		if (buf != null)
			this.map_local_printers = buf.equalsIgnoreCase("true");
		
		buf = this.getParameter("fullscreen");
		if (buf != null)
			this.fullscreenMode = true;
		
		return true;
    }
	
	private void switch2session() {
		RdesktopCanvas canvas = this.rc.getCanvas();
		canvas.setLocation(0, 0);
		
		if (this.fullscreenMode) {
			this.externalWindow = new FullscreenWindow();
			this.externalWindow.add(canvas);
			this.externalWindow.setFullscreen();
		}
		else {
			this.removeAll();
			this.add(canvas);
			this.validate();
		}
		canvas.addFocusListener(this);
	}
	

	public void forwardJS(String functionName, Integer instance, String status) {
		Object[] args = new Object[2];
		args[0] = instance;
		args[1] = status;
		
		try {
			JSObject win = JSObject.getWindow(this);
			win.call(functionName, args);
		}
		catch (netscape.javascript.JSException e) {
			String buffer = functionName+"(";
			for(Object o: args)
				buffer+= o+", ";
			if (buffer.endsWith(", "))
				buffer = buffer.substring(0, buffer.length()-2);
			buffer+=")";
			
			System.err.println(this.getClass()+" error while execute '"+buffer+"' =>"+e.getMessage());
		}
	}

	@Override
	public void connected(RdpConnection co) {
		System.out.println("Connected to "+this.rc.getServer());
		this.switch2session();
		this.forwardJS(JS_API_F_SERVER, 0, JS_API_O_SERVER_CONNECTED);
	}

	@Override
	public void connecting(RdpConnection co) {
		System.out.println("Connecting to "+this.rc.getServer());
	}

	@Override
	public void disconnected(RdpConnection co) {
		System.out.println("Disconnected from "+this.rc.getServer());
		this.forwardJS(JS_API_F_SERVER, 0, JS_API_O_SERVER_DISCONNECTED);
		this.stop();
	}

	@Override
	public void failed(RdpConnection co, String msg) {
		System.out.println("Connection  to "+this.rc.getServer()+" failed: "+msg);

		int tryNumber = co.getTryNumber();
		if (tryNumber < 1) {
			Logger.debug("checkRDPConnections -- Bad try number("+tryNumber+"). Will continue normal process.");
			return;
		}

		if (tryNumber > 1) {
			Logger.error("checkRDPConnections -- Several try to connect to "+co.getServer()+" failed. Will exit.");
			this.forwardJS(JS_API_F_SERVER, 0, JS_API_O_SERVER_FAILED);
			return;
		}

		co.connect();
	}

	@Override
	public void seamlessEnabled(RdpConnection co) {}

	@Override
	public void focusGained(FocusEvent e) {
		if (this.focusManager != null && this.rc != null && !this.started_stop) {
			this.focusManager.performedFocusGain(this.rc.getCanvas());
		}
	}

	@Override
	public void focusLost(FocusEvent e) {
		if (this.focusManager != null && this.rc != null && !this.started_stop) {
			this.focusManager.performedFocusLost(this.rc.getCanvas());
		}
	}
}
