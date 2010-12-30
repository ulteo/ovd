/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2009
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
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

import java.io.FileNotFoundException;
import java.net.UnknownHostException;

import org.ulteo.Logger;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.printer.OVDStandalonePrinterThread;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.OvdAppListener;

import java.applet.Applet;
import java.awt.Rectangle;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.concurrent.ConcurrentHashMap;
import net.propero.rdp.RdesktopException;

import net.propero.rdp.RdpConnection;
import net.propero.rdp.RdpListener;
import netscape.javascript.JSObject;
import org.ulteo.ovd.sm.Properties;

import org.ulteo.utils.AbstractFocusManager;
import org.ulteo.utils.jni.WorkArea;

import org.ulteo.rdp.RdpConnectionOvd;
import org.ulteo.rdp.rdpdr.OVDPrinter;
import org.ulteo.rdp.seamless.SeamlessFrame;
import org.ulteo.rdp.seamless.SeamlessPopup;


abstract class Order {
}

class OrderServer extends Order {
	public int id;
	public String host;
	public int port;
	public String login;
	public String password;
	public String token;
	
	public OrderServer(int id, String host, int port, String token, String login, String password) {
		this.id = id;
		this.host = host;
		this.port = port;
		this.token = token;
		this.login = login;
		this.password = password;
	}
	
	public String toString() {
		return "Server (id: "+this.id+ ", host: "+this.host+", token: "+this.token+")";
	}
}

class OrderApplication extends Order {
	public int id;
	public int application_id;
	public int server_id;
	public int repository;
	public String path = null;
	
	public OrderApplication(int id, int application_id, int server_id) {
		this.id = id;
		this.application_id = application_id;
		this.server_id = server_id;	
	}
	
	public void setPath(int repository, String path) {
		this.repository = repository;
		this.path = path;
	}
	
	public String toString() {
		return "Application (id: "+this.id+", application: "+this.application_id+", server: "+this.server_id;
	}
}

public class Applications extends Applet implements Runnable, RdpListener, OvdAppListener {
	public String keymap = null;
	private boolean multimedia_mode = false;
	private boolean map_local_printers = false;
	private int mount_local_drives = Properties.REDIRECT_DRIVES_NO;
	
	private ConcurrentHashMap<Integer, RdpConnectionOvd> connections = null;
	private List<Order> spoolOrder = null;
	private Thread spoolThread = null;
	private AbstractFocusManager focusManager;

	private boolean finished_init = false;
	private boolean started_stop = false;
	
	public static final String JS_API_F_INSTANCE = "applicationStatus";
	public static final String JS_API_O_INSTANCE_STARTED = "started";
	public static final String JS_API_O_INSTANCE_STOPPED = "stopped";
	public static final String JS_API_O_INSTANCE_ERROR = "error";
	
	public static final String JS_API_F_SERVER = "serverStatus";
	public static final String JS_API_O_SERVER_CONNECTED = "connected";
	public static final String JS_API_O_SERVER_DISCONNECTED = "disconnected";
	public static final String JS_API_O_SERVER_FAILED = "failed";
	public static final String JS_API_O_SERVER_READY = "ready";
	
	// Begin extends Applet
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
		else if (OSTools.isLinux()) {
			try {
				LibraryLoader.LoadLibrary(LibraryLoader.RESOURCE_LIBRARY_DIRECTORY_LINUX, LibraryLoader.LIB_X_CLIENT_AREA);
			} catch (FileNotFoundException ex) {
				Logger.error(ex.getMessage());
				WorkArea.disableLibraryLoading();
			}
		}
		
		if (! Logger.initInstance(true, tempdir+"ulteo-ovd-"+Logger.getDate()+".log", true)) {
			System.err.println(this.getClass().toString()+" Unable to iniatialize logger instance");
			Logger.initInstance(true, null, true);
		}

		if (! this.readParameters()) {
			System.err.println(this.getClass().toString() +"  usage error");
			this.stop();
			return;
		}
		
		this.spoolOrder = new ArrayList<Order>();
		this.spoolThread = new Thread(this);
		this.connections = new ConcurrentHashMap<Integer, RdpConnectionOvd>();
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

		while (! this.connections.isEmpty()) {
			try {
				Thread.sleep(100);
			} catch (InterruptedException ex) {}
		}
	}
	
	@Override
	public void destroy() {
		this.spoolOrder = null;
		this.spoolThread = null;
		this.connections.clear();
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
			OVDStandalonePrinterThread appletPrinterThread = new OVDStandalonePrinterThread(); 
			OVDPrinter.setPrinterThread(appletPrinterThread);
			focusManager = new AppletFocusManager(appletPrinterThread);
			SeamlessFrame.focusManager = focusManager;
			SeamlessPopup.focusManager = focusManager;
			this.map_local_printers = buf.equalsIgnoreCase("true");
		}

		buf = this.getParameter("redirect_client_drives");
		if (buf != null) {
			if (buf.equalsIgnoreCase("full"))
				this.mount_local_drives = Properties.REDIRECT_DRIVES_FULL;
			else if (buf.equalsIgnoreCase("partial"))
				this.mount_local_drives = Properties.REDIRECT_DRIVES_PARTIAL;
			else
				this.mount_local_drives = Properties.REDIRECT_DRIVES_NO;
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
				
				Rectangle dim = WorkArea.getWorkAreaSize();
				System.out.println("Width: "+dim.width);
				System.out.println("Height: "+dim.height);
				
				byte flags = RdpConnectionOvd.MODE_APPLICATION;
				if(this.multimedia_mode)
					flags |= RdpConnectionOvd.MODE_MULTIMEDIA;
				if (this.map_local_printers)
					flags |= RdpConnectionOvd.MOUNT_PRINTERS;
				if (this.mount_local_drives == Properties.REDIRECT_DRIVES_FULL)
					flags |= RdpConnectionOvd.MOUNTING_MODE_FULL;
				else if (this.mount_local_drives == Properties.REDIRECT_DRIVES_PARTIAL)
					flags |= RdpConnectionOvd.MOUNTING_MODE_PARTIAL;
				
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
				if (order.token != null) {
					rc.setCookieElement("token", order.token);

					try {
						rc.useSSLWrapper(order.host, order.port);
					} catch(OvdException ex) {
						Logger.error("Unable to create SSLWrapper: " + ex.getMessage());
					} catch(UnknownHostException ex) {
						Logger.error("Undefined error during creation of SSLWrapper: " + ex.getMessage());
					}
				}

				rc.setCredentials(order.login, order.password);
				// Ensure that width is a multiple of 4
				// to prevent artifacts
				rc.setGraphic(dim.width & ~3, dim.height, RdpConnectionOvd.DEFAULT_BPP);
				rc.setGraphicOffset(dim.x, dim.y);
				rc.setKeymap(keymap);
				rc.setShell("OvdRemoteApps");
				
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
					
				if (order.path == null)
					chan.sendStartApp(order.id, order.application_id);
				else {
					System.out.println("App: "+order.path);
					chan.sendStartApp(order.id, order.application_id, chan.DIR_TYPE_SHARED_FOLDER, ""+order.repository, order.path);
				}
			}
		}
	}
	// End implements Runnable
	
	public boolean serverConnect(int id, String host, int port, String login, String password) {
		System.out.println("serverConnect: ask for "+host);
		this.pushOrder(new OrderServer(id, host, port, null, login, password));
		return true;
	}
	
	public boolean serverConnect(int id, String host, int port, String token, String login, String password) {
		System.out.println("serverConnect through a gateway: ask for "+host);
		this.pushOrder(new OrderServer(id, host, port, token, login, password));
		return true;
	}
	
	/*public boolean serverDisconnect(int id) {
		this.pushOrder(new OrderServer(id, host, port, login, password));
		return true;
	}*/
	
	public void startApplication(int token, int id, int server) {
		this.pushOrder(new OrderApplication(token, id, new Integer(server)));
	}
	
	public void startApplicationWithFile(int token, int id, int server, int repository, String path) {
		OrderApplication o = new OrderApplication(token, id, new Integer(server));
		o.setPath(repository, path);
		
		this.pushOrder(o);
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
		if (co == null || this.connections == null)
			return;
		
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
		if (co == null || this.connections == null)
			return;
		
		System.out.println("Connecting to "+co.getServer());
	}
	
	@Override
	public void disconnected(RdpConnection co) {
		if (co == null || this.connections == null)
			return;

		System.out.println("Disconnected from "+co.getServer());
		
		Integer server_id = null;
		for (Integer i: this.connections.keySet()) {
			if (this.connections.get(i) == co) {
				server_id = i;
				break;
			}
		}
		if (server_id == null)
			return;

		this.connections.remove(server_id);
		this.forwardJS(JS_API_F_SERVER, server_id, JS_API_O_SERVER_DISCONNECTED);
	}
	
	@Override
	public void failed(RdpConnection co, String msg) {
		if (co == null || this.connections == null)
			return;
		
		System.out.println("Connection to "+co.getServer()+" failed: "+msg);
		
		boolean retry = false;
		
		int state = co.getState();
		
		if (state == RdpConnectionOvd.STATE_CONNECTED) {
			return;
		}
		
		if (state != RdpConnectionOvd.STATE_FAILED) {
			Logger.debug("checkRDPConnections "+co.getServer()+" -- Bad connection state("+state+"). Will continue normal process.");
			return;
		}
		
		int tryNumber = co.getTryNumber();
		if (tryNumber < 1) {
			Logger.debug("checkRDPConnections "+co.getServer()+" -- Bad try number("+tryNumber+"). Will continue normal process.");
			return;
		}
		
		if (tryNumber > 1) {
			Logger.error("checkRDPConnections "+co.getServer()+" -- Several try to connect failed.");
			Integer server_id = null;
			for (Integer o : this.connections.keySet()) {
				if (this.connections.get(o) == co) {
					server_id = o;
					break;
				}
			}
			if (server_id == null) {
				Logger.error("checkRDPConnections "+co.getServer()+" -- Failed to retrieve connection.");
				return;
			}

			this.connections.remove(server_id);
			this.forwardJS(JS_API_F_SERVER, server_id, JS_API_O_SERVER_FAILED);
			return;
		}
		
		Logger.warn("checkRDPConnections "+co.getServer()+" -- Connection failed. Will try to reconnect.");
		co.connect();
	}
	
	@Override
	public void seamlessEnabled(RdpConnection co) {}
}
