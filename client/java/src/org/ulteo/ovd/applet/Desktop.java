/*
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2009-2010
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

import net.propero.rdp.RdpConnection;
import java.applet.Applet;
import java.awt.BorderLayout;
import java.util.Observable;
import java.util.Observer;
import net.propero.rdp.Common;

import net.propero.rdp.Options;
import net.propero.rdp.RdesktopCanvas;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.rdp5.rdpdr.Printer;
import net.propero.rdp.rdp5.rdpdr.RdpdrChannel;
import netscape.javascript.JSObject;

public class Desktop extends Applet implements Observer {

	private String server = null;
	private String username = null;
	private String password = null;
	
	private Options rdp_opt = null;
	private RdpConnection rc = null;
	private Thread rdp_th = null;
	
	private boolean finished_init = false;
	private boolean finished_start = false;
	private boolean started_stop = false;
	
	public static final String JS_API_F_SERVER = "serverStatus";
	public static final String JS_API_O_SERVER_CONNECTED = "connected";
	public static final String JS_API_O_SERVER_DISCONNECTED = "disconnected";
	public static final String JS_API_O_SERVER_READY = "ready";
	
	@Override
	public void init() {
		System.out.println(this.getClass().toString() +"  init");
		
		boolean status = this.checkSecurity();
		if (! status) {
			System.err.println(this.getClass().toString() +"  init: Not enought privileges, unable to continue");
			this.stop();
			return;
		}


		if (! readParameters()) {
			System.err.println(this.getClass().toString() +"  usage error");
			this.stop();
			return;
		}

		
		this.rdp_opt = new Options();
		this.rdp_opt.hostname = this.server;
		this.rdp_opt.username = this.username;
		this.rdp_opt.password = this.password;
		this.rdp_opt.width = this.getWidth();
		this.rdp_opt.height = this.getHeight();
		this.rdp_opt.set_bpp(24);
		this.rc = null;
				
		this.finished_init = true;
		
		BorderLayout layout = new BorderLayout();
		this.setLayout(layout);
	}
	
	@Override
	public void start() {	
		if (! this.finished_init || this.started_stop)
			return;	
		System.out.println(this.getClass().toString() +" start");

		try {
			this.rc = new RdpConnection(this.rdp_opt, new Common());
		} catch (RdesktopException e) {

			System.out.println(this.getClass().toString()+" Unable to connect to "+this.server);
		}

		this.rc.addObserver(this);
		
		String[] printers = Printer.getAllAvailable();
		if (printers.length > 0) {
			RdpdrChannel rdpdrChannel = new RdpdrChannel(this.rc.opt, this.rc.common);
		 	
			for(int i=0; i<printers.length; i++) {
				Printer p = new Printer(printers[i], i);
				rdpdrChannel.register(p);
			}
			
			if (! this.rc.addChannel(rdpdrChannel))
				System.err.println("Unable to ass rdpdr channel, continue anyway");
		}
		
		this.rdp_th = new Thread(this.rc);
		this.rdp_th.start();

		this.finished_start = true;

		System.out.println(this.getClass().toString() +" started");
	}
	
	@Override
	public void stop() {
		if (this.started_stop)
			return;
		this.started_stop = true;
		System.out.println(this.getClass().toString()+" stop");
		
		if (this.rdp_th.isAlive())
			this.rdp_th.interrupt();
		
		this.removeAll();
	}
	
	@Override
	public void destroy() {
		this.rc = null;
		this.server = null;
		this.username = null;
		this.password = null;
		
		this.rdp_opt = null;
		this.rc = null;
		this.rdp_th = null;
		
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
		}
		catch(Exception e) {
			return false;
		}
		
		return true;
    }
	
	
	public void update(Observable obj, Object state) {
		if (obj != this.rc) {
			System.err.println("Observable event not for us");
			return;
		}
		
		/* Connecting */
		if (state.equals("connecting"))
			System.out.println("Connecting to "+this.rc.opt.hostname);
		
		/* Connected */
		else if (state.equals("connected")) {
			System.out.println("Connected to "+this.rc.opt.hostname);
			this.switch2session();
			this.forwardJS(JS_API_F_SERVER, 0, JS_API_O_SERVER_CONNECTED);
		}
		
		/* Disconnected */
		else if (state.equals("disconnected")) {
			System.out.println("Disconneted from "+this.rc.opt.hostname);
			this.forwardJS(JS_API_F_SERVER, 0, JS_API_O_SERVER_DISCONNECTED);
			this.stop();
		}
		
		/* Connection failed */
		else if (state.equals("failed"))
			System.out.println("Connection failed: removing rdpConnection to "+this.rc.opt.hostname);
		
		/* Undefined status */
		else
			System.out.println("Undefined state : "+state);
	}
	
	private void switch2session() {
		this.removeAll();
		RdesktopCanvas canvas = this.rc.getCanvas();
		canvas.setLocation(0, 0);
		this.add(canvas);
		this.validate();
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
}
