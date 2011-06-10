/*
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2009-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
 * Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
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

import org.ulteo.Logger;
import org.ulteo.rdp.OvdAppChannel;

import java.util.ArrayList;
import java.util.Collections;
import java.util.List;

import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.ovd.sm.SessionManagerCommunication;

import org.ulteo.rdp.seamless.SeamlessFrame;
import org.ulteo.rdp.seamless.SeamlessPopup;


abstract class Order {
	public int id;
}


class OrderServer extends Order {
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


class FileApp {
	int type;
	String path;
	String share;

	FileApp(String f_type, String f_path, String f_share) {
		if (f_type.equalsIgnoreCase("http"))
			type = OvdAppChannel.DIR_TYPE_HTTP_URL;
		else
			type = OvdAppChannel.DIR_TYPE_SHARED_FOLDER;
		
		path = f_path;
		share = f_share;
	}
}


class OrderApplication extends Order {
	public int application_id;
	public int server_id;
	public FileApp file = null;
	
	public OrderApplication(int id, int application_id, int server_id, FileApp f) {
		this.id = id;
		this.application_id = application_id;
		this.server_id = server_id;	
		file = f;
	}
	
	public String toString() {
		return "Application (id: "+this.id+", application: "+this.application_id+", server: "+this.server_id;
	}
}


public class Applications extends OvdApplet implements Runnable {
	
	private List<Order> spoolOrder = Collections.synchronizedList(new ArrayList<Order>());
	private Thread spoolThread = null;

	private OvdClientApplicationsApplet ovd = null;
	
	@Override
	protected void _init(Properties properties) {
		if (properties.isPrinters()) {
			SeamlessFrame.focusManager = focusManager;
			SeamlessPopup.focusManager = focusManager;
		}

		SessionManagerCommunication smComm = new SessionManagerCommunication(this.server, this.port, true);
		this.ovd = new OvdClientApplicationsApplet(smComm, properties, this);
		this.ovd.setKeymap(this.keymap);
		
		this.spoolThread = new Thread(this);
	}
	
	
	@Override
	protected void _start() {	
		this.spoolThread.start();
	}
	
	@Override
	protected void _stop() {
		if (this.spoolThread.isAlive())
			this.spoolThread.interrupt();
	}
	
	@Override
	protected void _destroy() {
		this.spoolOrder = null;
		this.spoolThread = null;
	}
	
	
	public Properties readParameters() {
		Properties properties = new Properties(Properties.MODE_REMOTEAPPS);

		String buf = this.getParameter("keymap");
		if (buf == null || buf.equals("")) {
			System.err.println("Parameter "+buf+": empty value");
			return null;
		}
		this.keymap = buf;
		
		OptionParser.readParameters(this, properties);
		
		return properties;
	}
	
	public void run() {
		System.out.println("Applet thread run");
		Order o;
		while(true) {
			if (this.spoolOrder.size() > 0) {
				o = this.spoolOrder.remove(0);
			} else {
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

				ServerAccess server = new ServerAccess(order.host, order.port, order.login, order.password);
				if (order.token != null) {
					server.setModeGateway(true);
					server.setToken(order.token);
				}

				if (! this.ovd.addServer(server, order.id))
					continue;
			}
			
			else if (o instanceof OrderApplication) {
				OrderApplication order = (OrderApplication)o;
				System.out.println("job "+order);
				System.out.println("Server "+order.server_id);

				if (order.file == null)
					this.ovd.startApplication(order.id, order.application_id, order.server_id);
				else
					this.ovd.startApplication(order.id, order.application_id, order.server_id, 
							order.file.type, order.file.path, order.file.share);
			} else {
				Logger.error("do not receive a good order");
			}
		}
	}
	
	// ********
	// Methods called by Javascript
	// ********
	
	public boolean serverConnect(int id, String host, int port, String login, String password) {
		System.out.println("serverConnect: ask for "+host);
		this.spoolOrder.add(new OrderServer(id, host, port, null, login, password));
		return true;
	}
	
	public boolean serverConnect(int id, String host, int port, String token, String login, String password) {
		System.out.println("serverConnect through a gateway: ask for "+host);
		this.spoolOrder.add(new OrderServer(id, host, port, token, login, password));
		return true;
	}
	
	public void startApplication(int token, int id, int server) {
		this.spoolOrder.add(new OrderApplication(token, id, new Integer(server), null));
	}
	
	public void startApplicationWithFile(int id, int apps_id, int server_id, String f_type, String f_path, String f_share) {
		FileApp f = new FileApp(f_type, f_path, f_share);
		this.spoolOrder.add(new OrderApplication(id, apps_id, new Integer(server_id), f));
	}
	
}
