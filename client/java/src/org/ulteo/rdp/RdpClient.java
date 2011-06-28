/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

package org.ulteo.rdp;

import gnu.getopt.Getopt;
import gnu.getopt.LongOpt;
import java.awt.Dimension;
import java.awt.GraphicsEnvironment;
import java.awt.Rectangle;
import java.awt.Toolkit;
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;
import java.io.FileNotFoundException;
import java.util.ArrayList;
import java.util.List;
import javax.swing.JFrame;
import net.propero.rdp.RdesktopCanvas;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpConnection;
import net.propero.rdp.RdpListener;
import org.apache.log4j.BasicConfigurator;
import org.apache.log4j.Level;
import org.apache.log4j.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.applet.LibraryLoader;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.SessionManagerException;
import org.ulteo.utils.LayoutDetector;

public class RdpClient extends JFrame implements WindowListener, RdpListener {
	public static class Params {
		public boolean ovd_environnement = false;
		public int ovd_mode = Properties.MODE_DESKTOP;
		public String username = null;
		public String password = null;
		public String server = null;
		public int port = RdpConnection.RDP_PORT;
		public int height = RdpConnection.DEFAULT_HEIGHT;
		public int width = RdpConnection.DEFAULT_WIDTH;
		public String shell = null;
		public int bpp = RdpConnection.DEFAULT_BPP;
		public boolean seamless = false;
		public boolean multimedia = false;
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
		System.err.println("	-m					Enable multimedia mode");
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
		if (OSTools.isWindows()) {
			try {
				LibraryLoader.LoadLibrary(LibraryLoader.LIB_WINDOW_PATH_NAME);
			} catch (FileNotFoundException ex) {
				System.err.println(ex.getMessage());
				System.exit(2);
			}
		}

		Params params = new Params();

		BasicConfigurator.configure();
		Logger.getRootLogger().setLevel(Level.INFO);

		RdpClient.logger = Logger.getLogger(RdpClient.class);

		// Init Ulteo Logger instance
		String log_dir = ".";
		if (! org.ulteo.Logger.initInstance(true, log_dir+System.getProperty("file.separator")+org.ulteo.Logger.getDate()+".log", true))
			System.err.println("Unable to iniatialize logger instance");

		LongOpt[] alo = new LongOpt[4];
		alo[0] = new LongOpt("persistent-cache-location", LongOpt.REQUIRED_ARGUMENT, null, 0);
		alo[1] = new LongOpt("persistent-cache-maxsize", LongOpt.REQUIRED_ARGUMENT, null, 1);
		alo[2] = new LongOpt("disable-all-cache", LongOpt.NO_ARGUMENT, null, 2);
		alo[3] = new LongOpt("ovd_mode", LongOpt.OPTIONAL_ARGUMENT, null, 3);
		Getopt opt = new Getopt(RdpClient.productName, args, "u:p:g:Ams:o:zP", alo);

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
						params.ovd_mode = Properties.MODE_REMOTEAPPS;
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
				case 'm':
					params.multimedia = true;
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

		if (opt.getOptind() < args.length) {
			String tmp = new String(args[args.length - 1]);
			int separatorPosition = tmp.indexOf(":");
			if (separatorPosition == -1) {
				params.server = tmp;
			}
			else {
				params.server = tmp.substring(0, separatorPosition);
				tmp = tmp.substring(separatorPosition + 1);
				try {
					int port = Integer.parseInt(tmp);
					if (port < 1 || port > 65535) {
						org.ulteo.Logger.error("Bad port range('"+port+"'): must be between 1 and 65535");
						usage();
					}
					params.port = port;
				} catch (NumberFormatException nfe) {
					org.ulteo.Logger.error("Failed to parse the port number('"+tmp+"'): "+nfe.getMessage());
					usage();
				}
			}
		}
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

		this.co = new ArrayList<RdpConnectionOvd>();

		if (params.ovd_environnement)
			this.initOVDSession(params);
		else
			this.initRDPSession(params);
	}

	private void initRDPSession(Params params) throws RdesktopException {
		this.ovd_mode_application = false;

		this.parseOptions(params);
		this.initWindow();
	}
	
	private void initOVDSession(Params params) throws RdesktopException {
		if (params.ovd_mode == Properties.MODE_REMOTEAPPS)
			this.ovd_mode_application = true;
		
		SessionManagerCommunication sm = new SessionManagerCommunication(params.server, SessionManagerCommunication.DEFAULT_PORT, true);
		try {
			sm.askForSession(params.username, params.password, new Properties(params.ovd_mode));
		} catch (SessionManagerException ex) {
			RdpClient.logger.error("Unable to ask session: "+ex.getMessage());
		}

		Properties response = sm.getResponseProperties();
		int mode = response.getMode();
		if (mode != params.ovd_mode) {
			RdpClient.logger.error("Unable to get the request session mode");
			return;
		}

		byte flags = 0x00;
		Dimension screenSize = null;

		if (mode == Properties.MODE_DESKTOP) {
			flags |= RdpConnectionOvd.MODE_DESKTOP;
			screenSize = new Dimension(params.width, params.height);
		}
		else {
			flags |= RdpConnectionOvd.MODE_APPLICATION;
			screenSize = Toolkit.getDefaultToolkit().getScreenSize();
		}

		if (response.isMultimedia())
			flags |= RdpConnectionOvd.MODE_MULTIMEDIA;

		if (response.isPrinters())
			flags |= RdpConnectionOvd.MOUNT_PRINTERS;


		for (ServerAccess server : sm.getServers()) {
			RdpConnectionOvd rc = null;

			try {
				rc = new RdpConnectionOvd(flags);
			} catch (RdesktopException ex) {
				RdpClient.logger.error("Unable to create RdpConnectionOvd object: "+ex.getMessage());
				return;
			}

			try {
				rc.initSecondaryChannels();
			} catch (RdesktopException ex) {
				RdpClient.logger.error("Unable to init channels of RdpConnectionOvd object: "+ex.getMessage());
			}

			rc.setServer(server.getHost());
			rc.setCredentials(server.getLogin(), server.getPassword());
			// Ensure that width is multiple of 4
			// Prevent artifact on screen with a with resolution
			// not divisible by 4
			rc.setGraphic((int) screenSize.width & ~3, (int) screenSize.height, RdpConnectionOvd.DEFAULT_BPP);

			for (org.ulteo.ovd.sm.Application appItem : server.getApplications()) {
				try {
					Application app = new Application(rc, appItem.getId(), appItem.getName(), appItem.getMimes(), sm.askForIcon(Integer.toString(appItem.getId())));
					rc.addApp(app);
				} catch (SessionManagerException ex) {
					RdpClient.logger.warn("Cannot get the \""+appItem.getName()+"\" icon: "+ex.getMessage());
				}
			}

			this.co.add(rc);
		}

		List<ServerAccess> servers = sm.getServers();
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

		if (params.multimedia)
			flags |= RdpConnectionOvd.MODE_MULTIMEDIA;

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

		connection.setServer(params.server, params.port);
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

		connection.setKeymap(LayoutDetector.get());
		this.co.add(connection);
	}

	private void initWindow() {
		this.setVisible(false);
		this.setResizable(false);
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

	public void failed(RdpConnection co, String msg) {
		RdpClient.logger.error("Connection to "+co.getServer()+" failed: "+msg);
	}

	public void disconnected(RdpConnection co) {
		RdpClient.logger.info("Disconnected from "+co.getServer());
		this.quit(0);
	}
	
	public void seamlessEnabled(RdpConnection co) {}

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
