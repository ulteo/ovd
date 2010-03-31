/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

package org.ulteo.ovd.integrated;

import gnu.getopt.Getopt;
import java.util.Observable;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.rdp.Connection;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.OvdAppListener;

import java.util.ArrayList;
import java.util.Observer;
import java.util.logging.Level;
import java.util.logging.Logger;
import net.propero.rdp.RdpConnection;

public class Client implements Observer, OvdAppListener{
	public static final String productName = "Ulteo OVD Integrated Client";

	public static void usage() {
		System.err.println(Client.productName);
		System.err.println("Usage: java -jar OVDIntegratedClient.jar [options] server");
		System.err.println("	-u USERNAME");
		System.err.println("	-p PASSWORD");
		System.err.println("Example: java -jar OVDIntegratedClient.jar -u username -p password sessionManagerHost");

		System.exit(0);
	}

	public static void main(String[] args) throws Exception {
		String username = null;
		String password = null;
		String server = null;
		
		Getopt opt = new Getopt(Client.productName, args, "u:p:");

		int c;
		while ((c = opt.getopt()) != -1) {
			switch (c) {
				case 'u':
					username = new String(opt.getOptarg());
					break;
				case 'p':
					password = new String(opt.getOptarg());
					break;
				default:
					break;
			}
		}

		if (username == null || password == null)
			usage();

		if (opt.getOptind() < args.length)
			server = new String(args[args.length - 1]);
		else
			usage();

		new Client(server, username, password);
	}

	private SessionManagerCommunication smComm = null;
	private ArrayList<Connection> connections = null;
	private Spool spool = null;
	private SystemAbstract sys = null;

	public Client(String fqdn_, String login_, String password_) {
		try {
			/*BasicConfigurator.configure();
			(Logger.getLogger("net.propero.rdp")).setLevel(org.apache.log4j.Level.INFO);*/
			this.spool = new Spool();
			this.spool.createIconsDir();

			this.smComm = new SessionManagerCommunication(fqdn_);

			if (! this.smComm.askForSession(login_, password_, SessionManagerCommunication.SESSION_MODE_REMOTEAPPS))
				this.quit(1);

			this.connections = smComm.getConnections();
			
			if (OSTools.isWindows()) {
				this.sys = new SystemWindows();
			}
			else if (OSTools.isLinux()) {
				this.sys = new SystemLinux();
			}
			else {
				Logger.getLogger(Client.class.getName()).log(Level.SEVERE, "This Operating System is not supported");
			}

			for (Connection co : this.connections) {
				co.connection.addObserver(this);
				co.channel.addOvdAppListener(this);
				co.thread = new Thread(co.connection);
				co.thread.start();
			}

			Thread fileListener = new Thread(this.spool);
			fileListener.start();
			while (fileListener.isAlive()) {
				try {
					Thread.sleep(100);
				} catch (InterruptedException ex) {
					Logger.getLogger(Client.class.getName()).log(Level.SEVERE, null, ex);
				}
			}
			this.quit(0);
		} catch (Exception ex) {
			Logger.getLogger(Client.class.getName()).log(Level.SEVERE, null, ex);
		}
	}

	private void quit(int i) {
		this.spool.deleteTree();
		System.exit(i);
	}

	private void addAvailableConnection(RdpConnection rc) {

		this.spool.addConnection(rc);
	}

	private void removeAvailableConnection(RdpConnection rc) {
		this.spool.removeConnection(rc);
		for (Application app : rc.getAppsList()) {
			this.sys.uninstall(app);
		}
	}

	public void update(Observable o, Object o1) {
		RdpConnection rc = (RdpConnection) o;
		String state = (String)o1;

		if (state.equals("connecting")) {
			Logger.getLogger(Client.class.getName()).log(Level.INFO, "Connecting to "+rc.opt.hostname);
		}
		else if (state.equals("failed")) {
			Logger.getLogger(Client.class.getName()).log(Level.WARNING, "Connection to "+rc.opt.hostname+" failed");
		}
		else if (state.equals("connected")) {
			Logger.getLogger(Client.class.getName()).log(Level.INFO, "Connected to "+rc.opt.hostname);
			this.addAvailableConnection(rc);
		}
		else if (state.equals("disconnected")) {
			Logger.getLogger(Client.class.getName()).log(Level.INFO, "Disconnected from "+rc.opt.hostname);
			this.removeAvailableConnection(rc);
		}
	}

	@Override
	public void ovdInited(OvdAppChannel o) {
		for (Connection co : this.connections) {
			if (co.channel == o) {
				if (! co.inited) {
					for (Application app : co.connection.getAppsList()) {
						this.sys.install(app);
					}
					co.inited = true;
				}
			}
		}
	}

	@Override
	public void ovdInstanceError(int instance) {
		
	}

	@Override
	public void ovdInstanceStarted(int instance) {
		
	}

	@Override
	public void ovdInstanceStopped(int instance) {
		
	}
}
