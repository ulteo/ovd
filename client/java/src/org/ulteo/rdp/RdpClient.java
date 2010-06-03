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
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;
import javax.swing.JFrame;
import net.propero.rdp.RdesktopCanvas;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpConnection;
import net.propero.rdp.RdpListener;
import org.apache.log4j.BasicConfigurator;
import org.apache.log4j.Level;
import org.apache.log4j.Logger;
import org.ulteo.ovd.OvdException;

public class RdpClient extends JFrame implements WindowListener, RdpListener {
	public static class Params {
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
		System.err.println("Example: java -jar OVDIntegratedClient.jar -u username -p password server");

		System.exit(0);
	}

	public static void main(String[] args) {
		Params params = new Params();

		BasicConfigurator.configure();
		Logger.getRootLogger().setLevel(Level.INFO);

		RdpClient.logger = Logger.getLogger(RdpClient.class);

		LongOpt[] alo = new LongOpt[3];
		alo[0] = new LongOpt("persistent-cache-location", LongOpt.REQUIRED_ARGUMENT, null, 0);
		alo[1] = new LongOpt("persistent-cache-maxsize", LongOpt.REQUIRED_ARGUMENT, null, 0);
		alo[2] = new LongOpt("disable-all-cache", LongOpt.NO_ARGUMENT, null, 0);
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

	private RdpConnectionOvd co = null;
	private RdesktopCanvas canvas = null;

	public RdpClient(Params params) throws RdesktopException {
		super(RdpClient.productName);

		this.parseOptions(params);
		this.initWindow();
	}

	private void parseOptions(Params params) throws RdesktopException {
		byte flags = 0x00;

		if (params.seamless)
			flags |= RdpConnectionOvd.MODE_APPLICATION;
		else
			flags |= RdpConnectionOvd.MODE_DESKTOP;

		this.co = new RdpConnectionOvd(flags);

		if (params.shell != null)
			this.co.setShell(params.shell);
		if (params.seamless) {
			if (params.shell.equalsIgnoreCase(seamlessShell) || params.shell.equalsIgnoreCase(seamlessShell.concat(".exe")))
				this.co.setSeamForm(true);
			this.co.getSeamlessChannel().setMainFrame(this);
		}

		this.co.setServer(params.server, RdpConnection.RDP_PORT);
		this.co.setCredentials(params.username, params.password);
		this.co.setGraphic(params.width, params.height, params.bpp);

		this.co.setPacketCompression(params.packetCompression);

		this.co.setVolatileCaching(params.volatileCache);
		if (params.volatileCache && params.persistentCache) {
			this.co.setPersistentCaching(true);
			this.co.setPersistentCachingPath(params.persistentCachePath);
			this.co.setPersistentCachingMaxSize(params.persistentCacheMaxSize);
		}

		this.co.addRdpListener(this);
	}

	private void initWindow() {
		this.setVisible(false);
		this.addWindowListener(this);
	}

	public void connect() throws OvdException {
		this.co.connect();
	}

	public void disconnect() {
		if (this.co != null && this.co.isConnected())
			this.co.disconnect();
	}

	private void quit(int status) {
		this.setVisible(false);
		this.dispose();
		System.exit(status);
	}

	private void initPane() {
		this.canvas = co.getCanvas();
		this.add(this.canvas);
		this.pack();
		this.setLocationRelativeTo(null);
		this.setVisible(true);
	}

	/*RDP connection events*/
	public void connected(RdpConnection co) {
		RdpClient.logger.info("Connected to "+co.getServer());
	}

	public void connecting(RdpConnection co) {
		RdpClient.logger.info("Connecting to "+co.getServer());
		this.initPane();
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
