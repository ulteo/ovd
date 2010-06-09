/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2009
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

import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.printer.OVDAppletPrinterThread;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.OvdAppListener;

import java.applet.Applet;
import java.applet.AppletContext;
import java.awt.GraphicsEnvironment;
import java.awt.Rectangle;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import javax.swing.JFrame;
import net.propero.rdp.RdesktopException;

import net.propero.rdp.RdpConnection;
import net.propero.rdp.RdpListener;
import netscape.javascript.JSObject;

import org.ulteo.rdp.RdpConnectionOvd;
import org.ulteo.rdp.rdpdr.OVDPrinter;


abstract class Order {
}

class OrderServer extends Order {
	public int id;
	public String host;
	public int port;
	public String login;
	public String password;
	
	public OrderServer(int id, String host, int port, String login, String password) {
		this.id = id;
		this.host = host;
		this.port = port;
		this.login = login;
		this.password = password;		
	}
	
	public String toString() {
		return "Server (id: "+this.id+ ", host: "+this.host+")";
	}
	
}

class OrderApplication extends Order {
	public int id;
	public int application_id;
	public int server_id;
	
	public OrderApplication(int id, int application_id, int server_id) {
		this.id = id;
		this.application_id = application_id;
		this.server_id = server_id;	
	}
	
	public String toString() {
		return "Application (id: "+this.id+", application: "+this.application_id+", server: "+this.server_id;
	}
}

public class Applications extends Applet implements Runnable, RdpListener, OvdAppListener {
	public String keymap = null;
	private boolean multimedia_mode = false;
	private boolean map_local_printers = false;
	
	private HashMap<Integer, RdpConnectionOvd> connections = null;
	private List<Order> spoolOrder = null;
	private Thread spoolThread = null;

	private boolean finished_init = false;
	private boolean started_stop = false;
	
	public static final String JS_API_F_INSTANCE = "applicationStatus";
	public static final String JS_API_O_INSTANCE_STARTED = "started";
	public static final String JS_API_O_INSTANCE_STOPPED = "stopped";
	public static final String JS_API_O_INSTANCE_ERROR = "error";
	
	public static final String JS_API_F_SERVER = "serverStatus";
	public static final String JS_API_O_SERVER_CONNECTED = "connected";
	public static final String JS_API_O_SERVER_DISCONNECTED = "disconnected";
	public static final String JS_API_O_SERVER_READY = "ready";
	
	// Begin extends Applet
	@Override
	public void init() {
		System.out.println(this.getClass().toString() +"  init");
		
		boolean status = this.checkSecurity();
		if (! status) {
			System.err.println(this.getClass().toString() +"  init: Not enought privileges, unable to continue");
			this.stop();
			return;
		}
		
		if (! this.readParameters()) {
			System.err.println(this.getClass().toString() +"  usage error");
			this.stop();
			return;
		}
		
		this.spoolOrder = new ArrayList<Order>();
		this.spoolThread = new Thread(this);
		this.connections = new HashMap<Integer, RdpConnectionOvd>();
		this.finished_init = true;
	}
	
	@Override
	public void start() {	
		if (! this.finished_init || this.started_stop)
			return;	
		System.out.println(this.getClass().toString() +" start");

		this.spoolThread.start();
	}
	
	@Override
	public void stop() {
		if (this.started_stop)
			return;
		this.started_stop = true;
		System.out.println(this.getClass().toString()+" stop");
		
		if (this.spoolThread.isAlive())
			this.spoolThread.interrupt();
		
		for (RdpConnectionOvd co: this.connections.values()) {
			co.disconnect();
			try {
				co.interruptConnection();
			} catch (RdesktopException ex) {
				System.out.println(ex.getMessage());
			}
		}
	}
	
	@Override
	public void destroy() {
		this.spoolOrder = null;
		this.spoolThread = null;
		this.connections = null;
	}
	// End extends Applet
	
	public boolean readParameters() {
		String buf = this.getParameter("keymap");
		if (buf == null || buf.equals("")) {
			System.err.println("Parameter "+buf+": empty value");
			return false;
		}
		this.keymap = buf;
		
		buf = this.getParameter("multimedia");
		if (buf != null)
			this.multimedia_mode = buf.equalsIgnoreCase("true");
		
		buf = this.getParameter("redirect_client_printers");
		if (buf != null){
			AppletContext appletContext= getAppletContext();
			OVDPrinter.setPrinterThread(new OVDAppletPrinterThread(appletContext));
			this.map_local_printers = buf.equalsIgnoreCase("true");
		}
		
		return true;
	}
	
	public boolean checkSecurity() {
		try {
			System.getProperty("user.home");
		} catch(java.security.AccessControlException e) {
			return false;
		}

		return true;
	}

	
	public synchronized Order popOrder() {
		if (this.spoolOrder.size() == 0)
			return null;
		
		return this.spoolOrder.remove(0);
	}
	
	public synchronized void pushOrder(Order o) {
		this.spoolOrder.add(o);
	}
	
	// Begin implements Runnable
	public void run() {
		System.out.println("Applet thread run");
		while(true) {
			Order o = this.popOrder();
			
			if (o == null) {
				try {
					Thread.sleep(200);
				} catch (InterruptedException e) {
					System.err.println("thread interupted: stop");
					return;
				}
				continue;
			}
			System.out.println("got job "+o);
			
			if (o instanceof OrderServer) {
				OrderServer order = (OrderServer)o;
				System.out.println("job "+order.host);
				
				Rectangle dim = GraphicsEnvironment.getLocalGraphicsEnvironment().getMaximumWindowBounds();	
				System.out.println("Width: "+dim.width);
				System.out.println("Height: "+dim.height);

				byte flags = RdpConnectionOvd.MODE_APPLICATION;
				if(this.multimedia_mode)
					flags |= RdpConnectionOvd.MODE_MULTIMEDIA;
				if (this.map_local_printers)
					flags |= RdpConnectionOvd.MOUNT_PRINTERS;

				RdpConnectionOvd rc;
				try {
					rc = new RdpConnectionOvd(flags);
				} catch (RdesktopException ex) {
					System.err.println("ERROR: "+ex.getMessage());
					continue;
				}
				try {
					rc.initSecondaryChannels();
				} catch (RdesktopException ex) {
					System.err.println("WARNING: "+ex.getMessage());
				}

				rc.setServer(order.host, order.port);
				rc.setCredentials(order.login, order.password);
				// Ensure that width is multiple of 4
				// Prevent artifact on screen with a with resolution
				// not divisible by 4
				rc.setGraphic(dim.width & ~3, dim.height, RdpConnectionOvd.DEFAULT_BPP);
				rc.setKeymap(keymap);

				rc.addRdpListener(this);

				try {
					rc.addOvdAppListener(this);
				} catch (OvdException ex) {
					System.err.println("ERROR: "+ex.getMessage());
					continue;
				}
				
				this.connections.put(new Integer(order.id), rc);
				rc.connect();
				
			}
			
			else if (o instanceof OrderApplication) {
				OrderApplication order = (OrderApplication)o;
				System.out.println("job "+order);
				
				Integer server_id = null;
				
				for (Integer i: this.connections.keySet()) {
					if (i.intValue() == order.server_id) {
						server_id = i;
						break;
					}
				}
				
				if (server_id == null) {
					System.err.println("Unknown server for order "+order);
					continue;
				}
				System.out.println("Server "+server_id);
				
				RdpConnectionOvd co = this.connections.get(server_id);
				OvdAppChannel chan = co.getOvdAppChannel();
				System.out.println("channel got "+chan);
				if (! chan.isReady()) {
					System.err.println("Channel not ready for order "+order);
					continue;
				}
					
				
				chan.sendStartApp(order.id, order.application_id);
			}
		}
	}
	// End implements Runnable
	
	public boolean serverConnect(int id, String host, int port, String login, String password) {
		System.out.println("serverConnect: ask for "+host);
		this.pushOrder(new OrderServer(id, host, port, login, password));
		return true;
	}
	
	/*public boolean serverDisconnect(int id) {
		this.pushOrder(new OrderServer(id, host, port, login, password));
		return true;
	}*/
	
	public void startApplication(int token, int id, int server) {
		this.pushOrder(new OrderApplication(token, id, new Integer(server)));
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
	
	// Begin implements OvdAppListener
	@Override
	public void ovdInited(OvdAppChannel channel) {
		for (Integer i: this.connections.keySet()) {
			if (this.connections.get(i).getOvdAppChannel() == channel) {
				this.forwardJS(JS_API_F_SERVER, i, JS_API_O_SERVER_READY);
				return;
			}
		}
	}
	
	@Override
	public void ovdInstanceError(int instance) {
		this.forwardJS(JS_API_F_INSTANCE, new Integer(instance), JS_API_O_INSTANCE_ERROR);	
	}

	@Override
	public void ovdInstanceStarted(int instance) {
		this.forwardJS(JS_API_F_INSTANCE, new Integer(instance), JS_API_O_INSTANCE_STARTED);	
	}

	@Override
	public void ovdInstanceStopped(int instance) {
		this.forwardJS(JS_API_F_INSTANCE, new Integer(instance), JS_API_O_INSTANCE_STOPPED);	
	}
	// End implements OvdAppListener

	@Override
	public void connected(RdpConnection co) {
		System.out.println("Connected to "+co.getServer());
		
		Integer server_id = null;
		for (Integer i: this.connections.keySet()) {
			if (this.connections.get(i) == co) {
				server_id = i;
				break;
			}
		}
		this.forwardJS(JS_API_F_SERVER, server_id, JS_API_O_SERVER_CONNECTED);
	}

	@Override
	public void connecting(RdpConnection co) {
		System.out.println("Connecting to "+co.getServer());

		JFrame f = new JFrame();
		f.setVisible(false);
		f.add(co.getCanvas());
		f.pack();
	}

	@Override
	public void disconnected(RdpConnection co) {
		System.out.println("Disconneted from "+co.getServer());
		
		Integer server_id = null;
		for (Integer i: this.connections.keySet()) {
			if (this.connections.get(i) == co) {
				server_id = i;
				break;
			}
		}
		this.forwardJS(JS_API_F_SERVER, server_id, JS_API_O_SERVER_DISCONNECTED);
	}

	@Override
	public void failed(RdpConnection co) {
		System.out.println("Connection failed: removing rdpConnection to "+co.getServer());
	}
}
