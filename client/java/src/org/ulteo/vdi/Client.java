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

package org.ulteo.vdi;

import net.propero.rdp.RdpConnection;
import net.propero.rdp.RdpListener;

import org.apache.log4j.*;
import gnu.getopt.Getopt;

import java.awt.GraphicsEnvironment;
import java.awt.Rectangle;
import java.io.BufferedReader;
import java.io.FileReader;
import java.io.IOException;

import javax.swing.JFrame;

public class Client implements RdpListener {

	private static Logger logger;
	
	private RdpConnectionVDI rc = null;
	
	public Client(String fqdn_, String login_, String password_) {
		
		BasicConfigurator.configure();
		(Logger.getLogger("net.propero.rdp")).setLevel(Level.INFO);
		logger = Logger.getLogger(Client.class.getName());
		
		try {
			rc = new RdpConnectionVDI();
		} catch (VdiException e) {
			logger.error("Can't create an RDP connexion");
		}
		
		Rectangle dim = GraphicsEnvironment.getLocalGraphicsEnvironment().getMaximumWindowBounds();

		rc.setGraphic((int)(dim.width & ~3), (int)dim.height);
		rc.setServer(fqdn_);
		rc.setCredentials(login_, password_);
		rc.setVolatileCaching(true);
		rc.setPersistentCaching(false);
		rc.setShell("seamlessrdpshell");
		
		rc.addRdpListener(this);
		rc.connect();
	}

	private void sendCmd(String cmd) {
		if(rc.isConnected())
			try {
				rc.getSeamlessChannel().send_spawn(cmd);
				logger.info("Commande seamless exécutée: " + cmd); 
			} catch (Exception e) {
				logger.warn("Impossibilité de lancer cette commande : " + cmd);
				e.printStackTrace();
			}
	}

	public void connected(RdpConnection co) {
		logger.info("Connected to " + rc.getUsername() + "@" + rc.getServer());
        Runtime.getRuntime().addShutdownHook(new Thread(new Runnable() {
            public void run() {
        		try {
        			logger.info("Logoff from " + rc.getServer());
        			rc.getSeamlessChannel().send_spawn("logoff");
        		} catch (Exception e1) {
        			logger.error("logoff from" + rc.getServer() + "failed");
        		}
            }
        }));
	}

	public void connecting(RdpConnection co) {
		logger.info("Connecting to " + rc.getUsername() + "@" + rc.getServer());
		JFrame f = new JFrame();
		f.setVisible(false);
		f.add(co.getCanvas());
		f.pack();
	}

	public void disconnected(RdpConnection co) {
		logger.info("Disconnected from " + rc.getUsername() + "@" + rc.getServer());
		System.exit(0);
	}

	public void failed(RdpConnection co) {
		logger.info("Connection to "+rc.getUsername() + "@" + rc.getServer() + "failed");
		System.exit(0);
	}
	
	/************************* STATIC *******************************/
	
	private static final String productName = "Ulteo VDI Client";

	private static String fifodir = "/var/cache/vdiserver/fifo/";

	private static void usage() {
		System.err.println(Client.productName);
		System.err.println("Usage: java -jar OVDIntegratedClient.jar [options] server");
		System.err.println("	-u USERNAME");
		System.err.println("	-p PASSWORD");
		System.err.println("Example: java -jar OVDIntegratedClient.jar -u username -p password sessionManagerHost");
		System.exit(0);
	} // usage()

	public static void main(String[] args) throws Exception {
		String username = null;
		String password = null;
		String server = null;
		String namedpipe = null;
		
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
		if (username == null || password == null) usage();
		
		if ( (opt.getOptind()+2) <= args.length) {
			server = new String(args[args.length - 2]);
			namedpipe = new String(args[args.length - 1]);
		} else {
			usage();
		}
		
		Client client = new Client(server, username, password);
		String pipe = fifodir + namedpipe;
		logger.info("Lecture dans le tube nommé: " + pipe);
		FileReader f = new FileReader(pipe);
		BufferedReader in = new BufferedReader(f);

		while (true) {
			if (!in.ready()) {
				Thread.sleep(100);
			} else { 
				try {
					String cmd = in.readLine();
					if (cmd != null) client.sendCmd(cmd);
				} catch (IOException e) {
					logger.error("Problème de lecture du tube nommé");
					throw new Exception("Problème de lecture du tube nommé");
				}
			}
		}
	} // main 
	
}
