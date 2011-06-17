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

import java.util.ArrayList;
import java.util.Collections;
import java.util.List;

import org.ulteo.ovd.sm.ServerAccess;


abstract class Order {}

class OrderServer extends Order {
	public int id;
	public ServerAccess server;
	
	public OrderServer(int JSId, ServerAccess server) {
		this.id = JSId;
		this.server = server;
	}
	
	public String toString() {
		return String.format("Server (id: %s, server: [%s])", this.id, this.server);
	}
}

class OrderApplication extends Order {
	public int token;
	public int app_id;
	public int server_id;
	public FileApp file = null;
	
	public OrderApplication(int token, int app_id, int server_id, FileApp f) {
		this.token = token;
		this.app_id = app_id;
		this.server_id = server_id;
		this.file = f;
	}
	
	public String toString() {
		return String.format("Application (id: %s, application: %s, server: %s, %s)",
				this.token, this.app_id, this.server_id, this.file);
	}
}


class SpoolOrder extends Thread {
	
	private List<Order> spoolOrder = Collections.synchronizedList(new ArrayList<Order>());
	
	private OvdClientApplicationsApplet client;
	
	
	public SpoolOrder(OvdClientApplicationsApplet client) {
		this.client = client;
	}
	
	@Override
	public void run() {
		Order o;
		while(true) {
			if (this.spoolOrder.size() > 0) {
				o = this.spoolOrder.remove(0);
			} else {
				try {
					Thread.sleep(200);
				} catch (InterruptedException e) {
					return;
				}
				continue;
			}
			Logger.info("Got job: "+o);
			
			if (o instanceof OrderServer) {
				OrderServer order = (OrderServer)o;
				if (! client.addServer(order.id, order.server))
					continue;
			}
			
			else if (o instanceof OrderApplication) {
				OrderApplication order = (OrderApplication)o;

				if (order.file == null)
					client.startApplication(order.token, order.app_id, order.server_id);
				else
					client.startApplication(order.token, order.app_id, order.server_id, 
							order.file.type, order.file.path, order.file.share);
			} else {
				Logger.error("do not receive a good order");
			}
		}
	}
	
	public void add (Order o) {
		this.spoolOrder.add(o);
	}
	
}
