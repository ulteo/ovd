/*
 * Copyright (C) 2010 Ulteo SAS
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

package org.ulteo.rdp;

import gnu.getopt.Getopt;
import gnu.getopt.LongOpt;
import java.awt.GraphicsEnvironment;
import java.awt.Rectangle;
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;
import java.util.ArrayList;
import javax.swing.JFrame;
import net.propero.rdp.RdesktopCanvas;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpConnection;
import net.propero.rdp.RdpListener;
import org.apache.log4j.BasicConfigurator;
import org.apache.log4j.Level;
import org.apache.log4j.Logger;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.sm.SessionManagerCommunication;

public class RdpClient extends JFrame implements WindowListener, RdpListener {
	public static class Params {
		public boolean ovd_environnement = false;
		public String ovd_mode = SessionManagerCommunication.SESSION_MODE_DESKTOP;
		public String username = null;
		public String password = null;
		public String server = null;
		public int height = RdpConnection.DEFAULT_HEIGHT;
		public int width = RdpConnection.DEFAULT_WIDTH;
		public String shell = null;
		public int bpp = RdpConnection.DEFAULT_BPP;
		public boolean seamless = false;
		public boolean packetCompression = false;
		public boolean volatileCache = true;
		public boolean persistentCache = false;
		public int persistentCacheMaxSize = RdpConnection.DEFAULT_PERSISTENT_CACHE_SIZE;
		public String persistentCachePath = null;
	}

	public static final String productName = "Ulteo RDP Client";
	public static final String seamlessShell = "seamlessrdpshell";

	public static void usage() {
		System.err.println(RdpClient.productName);
		System.err.println("Usage: java -jar UlteoRdpClient.jar [options] server");
		System.err.println("	-u USERNAME");
		System.err.println("	-p PASSWORD");
		System.err.println("	-g WIDTHxHEIGHT				Set the screen geometry");
		System.err.println("	-s SHELL				Set the shell to launch at session start");
		System.err.println("	-A					Enable seamless");
		System.err.println("	-o BPP					Bits-per-pixel for display");
		System.err.println("	-z					Enable packet compression (MPPC 64K)");
		System.err.println("	-P					Enable persistent bitmap cache");
		System.err.println("	  --persistent-cache-location		Set the persistent cache location");
		System.err.println("	  --persistent-cache-maxsize		Set the persistent cache maximum size");
		System.err.println("	  --disable-all-cache			Disable volatile and persistent cache");
		System.err.println("	--ovd_mode=MODE				Enable the OVD environnement with mode \"desktop\"/\"portal\" (default is \"desktop\")");
		System.err.println("Example: java -jar OVDIntegratedClient.jar -u username -p password server");

		System.exit(0);
	}

	public static void main(String[] args) {
		Params params = new Params();

		BasicConfigurator.configure();
		Logger.getRootLogger().setLevel(Level.INFO);

		RdpClient.logger = Logger.getLogger(RdpClient.class);

		LongOpt[] alo = new LongOpt[4];
		alo[0] = new LongOpt("persistent-cache-location", LongOpt.REQUIRED_ARGUMENT, null, 0);
		alo[1] = new LongOpt("persistent-cache-maxsize", LongOpt.REQUIRED_ARGUMENT, null, 1);
		alo[2] = new LongOpt("disable-all-cache", LongOpt.NO_ARGUMENT, null, 2);
		alo[3] = new LongOpt("ovd_mode", LongOpt.OPTIONAL_ARGUMENT, null, 3);
		Getopt opt = new Getopt(RdpClient.productName, args, "u:p:g:As:o:zP", alo);

		int c;
		while ((c = opt.getopt()) != -1) {
			switch (c) {
				case 0: // --persistent-cache-location
					params.persistentCachePath = opt.getOptarg();
					break;
				case 1: // --persistent-cache-maxsize
					params.persistentCacheMaxSize = Integer.parseInt(opt.getOptarg());
					break;
				case 2: // --disable-all-cache
					params.volatileCache = false;
					break;
				case 3: //--ovd_mode
					params.ovd_environnement = true;
					String mode = opt.getOptarg();
					if ((mode != null) && (opt.getOptarg().equalsIgnoreCase(SessionManagerCommunication.SESSION_MODE_REMOTEAPPS)))
						params.ovd_mode = SessionManagerCommunication.SESSION_MODE_REMOTEAPPS;
					break;
				case 'u':
					params.username = new String(opt.getOptarg());
					break;
				case 'p':
					params.password = new String(opt.getOptarg());
					break;
				case 'g':
					String geometry = opt.getOptarg();
					int index = geometry.indexOf('x');
					if (index == -1) {
						RdpClient.logger.warn("Bad geometry, keep default geometry: "+RdpConnection.DEFAULT_WIDTH+"x"+RdpConnection.DEFAULT_HEIGHT);
						break;
					}
					params.width = Integer.parseInt(geometry.substring(0, index)) & ~3;
					params.height = Integer.parseInt(geometry.substring(index+1, geometry.length()));
					break;
				case 'A':
					params.seamless = true;
					break;
				case 's':
					params.shell = new String(opt.getOptarg());
					break;
				case 'o':
					params.bpp = Integer.parseInt(opt.getOptarg());
					break;
				case 'z':
					params.packetCompression = true;
					break;
				case 'P':
					params.persistentCache = true;
				default:
					break;
			}
		}

		if (params.username == null || params.password == null)
			usage();

		if (opt.getOptind() < args.length)
			params.server = new String(args[args.length - 1]);
		else
			usage();

		
		try {
			RdpClient client = new RdpClient(params);

			client.connect();
		} catch (RdesktopException ex) {
			RdpClient.logger.fatal(ex.getMessage());
		}
	}

	public static Logger logger = null;

	private ArrayList<RdpConnectionOvd> co = null;
	private RdesktopCanvas canvas = null;
	private boolean ovd_mode_application;

	public RdpClient(Params params) throws RdesktopException {
		super(RdpClient.productName);

		if (params.ovd_environnement)
			this.initOVDSession(params);
		else
			this.initRDPSession(params);
	}

	private void initRDPSession(Params params) throws RdesktopException {
		this.ovd_mode_application = false;
		this.co = new ArrayList<RdpConnectionOvd>();

		this.parseOptions(params);
		this.initWindow();
	}
	
	private void initOVDSession(Params params) throws RdesktopException {
		if (params.ovd_mode.equalsIgnoreCase(SessionManagerCommunication.SESSION_MODE_REMOTEAPPS))
			this.ovd_mode_application = true;
		
		SessionManagerCommunication sm = new SessionManagerCommunication(params.server);
		sm.askForSession(params.username, params.password, params.ovd_mode);

		this.co = sm.getConnections();
		for (RdpConnectionOvd rc : this.co) {
			if (! this.ovd_mode_application)
				rc.setGraphic(params.width, params.height);
			rc.addRdpListener(this);
		}
	}

	private void parseOptions(Params params) throws RdesktopException {
		byte flags = 0x00;

		if (params.seamless)
			flags |= RdpConnectionOvd.MODE_APPLICATION;
		else
			flags |= RdpConnectionOvd.MODE_DESKTOP;

		RdpConnectionOvd connection = new RdpConnectionOvd(flags);

		if (params.shell != null)
			connection.setShell(params.shell);
		if (params.seamless) {
			Rectangle maxWindowSize = GraphicsEnvironment.getLocalGraphicsEnvironment().getMaximumWindowBounds();
			params.width = maxWindowSize.width - maxWindowSize.x;
			params.height = maxWindowSize.height - maxWindowSize.y;

			if (params.shell.equalsIgnoreCase(seamlessShell) || params.shell.equalsIgnoreCase(seamlessShell.concat(".exe")))
				connection.setSeamForm(true);
			connection.getSeamlessChannel().setMainFrame(this);
		}

		connection.setServer(params.server, RdpConnection.RDP_PORT);
		connection.setCredentials(params.username, params.password);
		connection.setGraphic(params.width, params.height, params.bpp);

		connection.setPacketCompression(params.packetCompression);

		connection.setVolatileCaching(params.volatileCache);
		if (params.volatileCache && params.persistentCache) {
			connection.setPersistentCaching(true);
			connection.setPersistentCachingPath(params.persistentCachePath);
			connection.setPersistentCachingMaxSize(params.persistentCacheMaxSize);
		}

		connection.initSecondaryChannels();

		connection.addRdpListener(this);

		this.co.add(connection);
	}

	private void initWindow() {
		this.setVisible(false);
		this.addWindowListener(this);
	}

	public void connect() throws OvdException {
		for (RdpConnectionOvd connection : this.co)
			connection.connect();
	}

	public void disconnect() {
		for (RdpConnectionOvd connection : this.co) {
			if (connection != null && connection.isConnected())
				connection.disconnect();
		}
	}

	private void quit(int status) {
		this.setVisible(false);
		this.dispose();
		System.exit(status);
	}

	private void initPane(RdpConnection rc) {
		if (! this.ovd_mode_application) {
			this.canvas = rc.getCanvas();
			this.add(this.canvas);
			this.pack();
			this.setLocationRelativeTo(null);
			this.setVisible(true);
		}
	}

	/*RDP connection events*/
	public void connected(RdpConnection co) {
		RdpClient.logger.info("Connected to "+co.getServer());
	}

	public void connecting(RdpConnection co) {
		RdpClient.logger.info("Connecting to "+co.getServer());
		this.initPane(co);
	}

	public void failed(RdpConnection co) {
		RdpClient.logger.warn("Connection to "+co.getServer()+" failed");
	}

	public void disconnected(RdpConnection co) {
		RdpClient.logger.info("Disconnected from "+co.getServer());
		this.quit(0);
	}

	/* Window events */
	public void windowClosing(WindowEvent we) {
		this.disconnect();
	}

	public void windowOpened(WindowEvent we) {}
	public void windowClosed(WindowEvent we) {}
	public void windowIconified(WindowEvent we) {}
	public void windowDeiconified(WindowEvent we) {}
	public void windowActivated(WindowEvent we) {}
	public void windowDeactivated(WindowEvent we) {}
}
