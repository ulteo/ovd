/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
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

package org.ulteo.ovd.client;

import java.io.File;
import java.io.IOException;

import javax.swing.UIManager;
import javax.swing.UnsupportedLookAndFeelException;

import gnu.getopt.Getopt;
import org.apache.log4j.BasicConfigurator;
import org.apache.log4j.Level;
import org.apache.log4j.Logger;
import org.ini4j.Wini;

import org.ulteo.ovd.client.authInterface.AuthFrame;
import org.ulteo.ovd.client.desktop.OvdClientDesktop;
import org.ulteo.ovd.client.remoteApps.OvdClientIntegrated;
import org.ulteo.ovd.client.remoteApps.OvdClientPortal;
import org.ulteo.ovd.printer.OVDStandalonePrinterThread;
import org.ulteo.rdp.rdpdr.OVDPrinter;

public class StartConnection {
	public static final String productName = "Ulteo OVD Client";
	/**
	 * @param args
	 */
	public static void main(String[] args) {
		
		try {
			UIManager.put("Slider.paintValue", Boolean.FALSE);
			UIManager.setLookAndFeel(UIManager.getSystemLookAndFeelClassName());
		} catch (ClassNotFoundException e) {
			e.printStackTrace();
		} catch (InstantiationException e) {
			e.printStackTrace();
		} catch (IllegalAccessException e) {
			e.printStackTrace();
		} catch (UnsupportedLookAndFeelException e) {
			e.printStackTrace();
		}
		
		I18n.init();
		BasicConfigurator.configure();
		Logger.getRootLogger().setLevel(Level.INFO);

		boolean use_https = true;
		String profile = null;
		String password = null;
		Getopt opt = new Getopt(OvdClient.productName, args, "c:p:s:");

		int c;
		while ((c = opt.getopt()) != -1) {
			if(c == 'c') {
				profile = new String(opt.getOptarg());
			}
			else if(c == 'p') {
				password = new String(opt.getOptarg());
			}
			else if (c == 's') {
				use_https = (opt.getOptarg().equalsIgnoreCase("off")) ? false : true;
			}
		}

		if (profile == null && password != null)
			usage();

		if (profile != null) {
			try {
				String username = null;
				String ovdServer = null;
				String initMode = null;
				int mode = 0;
				String initRes = null;
				int resolution = 0;
				String token = null;

				Wini ini = new Wini(new File(profile));
				username = ini.get("user", "login");
				ovdServer = ini.get("server", "host");
				initMode = ini.get("sessionMode", "ovdSessionMode");
				if (initMode.equals("desktop"))
					mode = 0;
				else if (initMode.equals("portal"))
					mode = 1;
				else
					mode = 2;

				initRes = ini.get("screen", "size");
				if(initRes.equals("800x600"))
					resolution=0;
				else if(initRes.equals("1024x768"))
					resolution=1;
				else if(initRes.equals("1280x678"))
					resolution=2;
				else if(initRes.equals("maximized"))
					resolution=3;
				else
					resolution=4;				

				token = ini.get("token", "token");
				OvdClient cli = null;
				if (token != null) {
					System.out.println("Token Auth");
					cli = new OvdClientIntegrated(ovdServer, use_https, token);
				}
				else {
					OVDPrinter.setPrinterThread(new OVDStandalonePrinterThread());
					switch (mode) {
					case 0:
						cli = new OvdClientDesktop(ovdServer, use_https, username, password, resolution);
						break;
					case 1:
						cli = new OvdClientPortal(ovdServer, use_https, username, password);
						break;
					case 2:
						cli = new OvdClientIntegrated(ovdServer, use_https, username, password);
						break;
					default:
						throw new UnsupportedOperationException("mode "+mode+" is not supported");
					}
				}
				cli.start();
			} catch (IOException ioe) {
				ioe.printStackTrace();
			}
		}
		else
			new AuthFrame(use_https);
	}

	public static void usage() {
		System.err.println(StartConnection.productName);
		System.err.println("Usage: java -jar OVDIntegratedClient.jar [options]");
		System.err.println("	-c CONFIGFILE");
		System.err.println("	-p PASSWORD");
		System.err.println("Example: java -jar OVDNativeClient.jar -c config.ovd -p password");

		System.exit(0);
	}
}
