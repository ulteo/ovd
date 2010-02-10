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

import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.OvdAppListener;
import java.applet.Applet;
import java.awt.GraphicsEnvironment;
import java.awt.Rectangle;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Observable;
import java.util.Observer;
import net.propero.rdp.Common;

import netscape.javascript.JSObject;

import net.propero.rdp.Options;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpConnection;
import net.propero.rdp.rdp5.rdpdr.Printer;
import net.propero.rdp.rdp5.rdpdr.RdpdrChannel;


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

class Connection {
	public Options options = null;
	public Common common = null;
	public Thread thread = null;
	public RdpConnection connection = null;
	public OvdAppChannel channel = null;
}


public class Portal extends Applet implements Runnable, Observer, OvdAppListener {
	private HashMap<Integer, Connection> connections = null;
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
		
		this.spoolOrder = new ArrayList<Order>();
		this.spoolThread = new Thread(this);
		this.connections = new HashMap<Integer, Connection>();
		
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
		
		for (Connection co: this.connections.values()) {
			co.connection.disconnect();
		
			if (co.thread.isAlive())
				co.thread.interrupt();
		}
	}
	
	@Override
	public void destroy() {
		this.spoolOrder = null;
		this.spoolThread = null;
		this.connections = null;
	}
	// End extends Applet
	
	
	
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
	
	// Begin implements Observer
	public void update(Observable obj, Object state) {
		Integer server_id = null;
		
		for (Integer i: this.connections.keySet()) {
			if (this.connections.get(i).connection == obj) {
				server_id = i;
				break;
			}
		}
		
		if (server_id == null) {
			System.err.println("Observable event not for us");
			return;
		}
		Connection co = this.connections.get(server_id);
		
		/* Connecting */
		if (state.equals("connecting"))
			System.out.println("Connecting to "+co.options.hostname);
		
		/* Connected */
		else if (state.equals("connected")) {
			System.out.println("Connected to "+co.options.hostname);
			
			this.forwardJS(JS_API_F_SERVER, server_id, JS_API_O_SERVER_CONNECTED);
		}
		
		/* Disconnected */
		else if (state.equals("disconnected")) {
			System.out.println("Disconneted from "+co.options.hostname);
			
			this.forwardJS(JS_API_F_SERVER, server_id, JS_API_O_SERVER_DISCONNECTED);
		}
		
		/* Connection failed */
		else if (state.equals("failed"))
			System.out.println("Connection failed: removing rdpConnection to "+co.options.hostname);
		
		/* Undefined status */
		else
			System.out.println("Undefined state : "+state);
	}
	// End implements Observer

	
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
				
				Connection co = new Connection();
				
				co.options = new Options();
				co.options.width = dim.width;
				co.options.height = dim.height;
				co.options.set_bpp(24);
							
				co.options.hostname = order.host;
				co.options.port = order.port;
				co.options.username = order.login;
				co.options.password = order.password;

				co.common = new Common();

				try {
					co.connection = new RdpConnection(co.options, co.common, new org.ulteo.rdp.seamless.SeamlessChannel(co.options, co.common));
				} catch (RdesktopException e) {
					System.out.println(this.getClass().toString()+" Unable to inti connection to "+co.options.hostname);
					continue;
				}
				co.connection.addObserver(this);
				co.thread = new Thread(co.connection);
				
				co.channel = new OvdAppChannel(co.connection.opt, co.connection.common);
				co.channel.addOvdAppListener(this);
				if (! co.connection.addChannel(co.channel)) {
					System.err.println("Can't add channel ovdapp ...");
					continue;
				}
				
				
				String[] printers = Printer.getAllAvailable();
				if (printers.length > 0) {
					RdpdrChannel rdpdrChannel = new RdpdrChannel(co.connection.opt, co.connection.common);
				 	
					for(int i=0; i<printers.length; i++) {
						Printer p = new Printer(printers[i], i);
						rdpdrChannel.register(p);
					}
					
					if (! co.connection.addChannel(rdpdrChannel))
						System.err.println("Unable to ass rdpdr channel, continue anyway");
				}
				
				this.connections.put(new Integer(order.id), co);
				
				co.thread.start();
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
				
				Connection co = this.connections.get(server_id);
				System.out.println("channel got "+co.channel);
				if (! co.channel.isReady()) {
					System.err.println("Channel not ready for order "+order);
					continue;
				}
					
				
				co.channel.sendStartApp(order.id, order.application_id);	
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
			if (this.connections.get(i).channel == channel) {
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
}
