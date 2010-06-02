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
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;
import javax.swing.JFrame;
import net.propero.rdp.Options;
import net.propero.rdp.RdesktopCanvas;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpConnection;
import net.propero.rdp.RdpListener;
import org.apache.log4j.BasicConfigurator;
import org.apache.log4j.Level;
import org.apache.log4j.Logger;
import org.ulteo.ovd.OvdException;

public class RdpClient extends JFrame implements WindowListener, RdpListener {
	public static final String productName = "Ulteo RDP Client";
	public static final String seamlessShell = "seamlessrdpshell";

	public static void usage() {
		System.err.println(RdpClient.productName);
		System.err.println("Usage: java -jar UlteoRdpClient.jar [options] server");
		System.err.println("	-u USERNAME");
		System.err.println("	-p PASSWORD");
		System.err.println("Example: java -jar OVDIntegratedClient.jar -u username -p password server");

		System.exit(0);
	}

	public static void main(String[] args) {
		Options options = new Options();

		BasicConfigurator.configure();
		Logger.getRootLogger().setLevel(Level.INFO);

		RdpClient.logger = Logger.getLogger(RdpClient.class);

		Getopt opt = new Getopt(RdpClient.productName, args, "u:p:As:");

		int c;
		while ((c = opt.getopt()) != -1) {
			switch (c) {
				case 'u':
					options.username = new String(opt.getOptarg());
					break;
				case 'p':
					options.password = new String(opt.getOptarg());
					break;
				case 'A':
					options.seamlessEnabled = true;
					break;
				case 's':
					options.command = new String(opt.getOptarg());
					if (options.command.equalsIgnoreCase(seamlessShell) || options.command.equalsIgnoreCase(seamlessShell.concat(".exe")))
						options.seamformEnabled = true;
					break;
				default:
					break;
			}
		}

		if (options.username == null || options.password == null)
			usage();

		if (opt.getOptind() < args.length)
			options.hostname = new String(args[args.length - 1]);
		else
			usage();

		
		try {
			RdpClient client = new RdpClient(options);

			client.connect();
		} catch (RdesktopException ex) {
			RdpClient.logger.fatal(ex.getMessage());
		}
	}

	public static Logger logger = null;

	private RdpConnectionOvd co = null;
	private RdesktopCanvas canvas = null;

	public RdpClient(Options opt_) throws RdesktopException {
		super(RdpClient.productName);

		this.parseOptions(opt_);
		this.initWindow();
	}

	private void parseOptions(Options opt) throws RdesktopException {
		byte flags = 0x00;

		if (opt.seamlessEnabled)
			flags |= RdpConnectionOvd.MODE_APPLICATION;
		else
			flags |= RdpConnectionOvd.MODE_DESKTOP;

		this.co = new RdpConnectionOvd(flags);

		if (! opt.command.isEmpty())
			this.co.setShell(opt.command);
		if (opt.seamlessEnabled) {
			this.co.setSeamForm(opt.seamformEnabled);
			this.co.getSeamlessChannel().setMainFrame(this);
		}

		this.co.setServer(opt.hostname, opt.port);
		this.co.setCredentials(opt.username, opt.password);
		this.co.setGraphic(opt.width, opt.height, opt.server_bpp);

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
