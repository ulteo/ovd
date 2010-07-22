/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
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
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.PrintStream;

import org.ini4j.Wini;
import org.ulteo.ovd.client.remoteApps.OvdClientIntegrated;

import gnu.getopt.Getopt;
import gnu.getopt.LongOpt;

public class StartTokenAuth {
	public static String name = "OvdExternalAppsClient";
	
	public static void usage() {
		System.err.println("Usage: java -jar "+name+" [-c configfile] [-s host] [-t token]");
		System.err.println("	-c|--config=FILE			Configuration file");
		System.err.println("	-o|--output=FILE			redirect output to file");
		System.err.println("	-s|--session-manager=HOST	Session Manager adress");
		System.err.println("	-t|--token=TOKEN			authentication token");
	}
	
	
	public static void main(String[] args) {
		LongOpt[] options = new LongOpt[4];
		StringBuffer optionsBuffer = new StringBuffer();
		options[0] = new LongOpt("config", LongOpt.REQUIRED_ARGUMENT, optionsBuffer, 0);
		options[1] = new LongOpt("output", LongOpt.REQUIRED_ARGUMENT, optionsBuffer, 0);
		options[2] = new LongOpt("session-manager", LongOpt.REQUIRED_ARGUMENT, optionsBuffer, 0);
		options[3] = new LongOpt("token", LongOpt.REQUIRED_ARGUMENT, optionsBuffer, 0);
		
		Getopt opt = new Getopt(name, args, "c:o:s:t:", options);
		
		String ovdServer = null;
		String token = null;
		int c;
		while ((c = opt.getopt()) != -1) {
			if (c == 'c' || c == 0 && opt.getLongind() == 0) {
				try {
					Wini ini = new Wini(new File(opt.getOptarg()));
					ovdServer = ini.get("server", "host");
					ovdServer.isEmpty();
					token = ini.get("token", "token");
					token.isEmpty();
				}
				catch(Exception err) {
					System.err.println("Error: invalid configurtion file '"+opt.getOptarg()+"'");
					usage();
					System.exit(1);
				}
			}
			else if (c == 'o' || c == 0 && opt.getLongind() == 1) {
				
				try {
					FileOutputStream fos = new FileOutputStream(opt.getOptarg());
					PrintStream stream = new PrintStream(fos);
					System.setOut(stream);
					System.setErr(stream);
				}
				catch(Exception err) {
					System.err.println("Error: unable to open file '"+opt.getOptarg()+"'");
					usage();
					System.exit(1);
				}
			}
			else if (c == 's' || c == 0 && opt.getLongind() == 2) {
				ovdServer = opt.getOptarg();
			}
			else if (c == 't' || c == 0 && opt.getLongind() == 3) {
				token = opt.getOptarg();
			}
			else {
				usage();
				System.exit(1);
			}
		}
		
		if (ovdServer == null) {
			System.err.println("Error: No SM host specified");
			usage();
			System.exit(1);
		}
		
		if (token == null) {
			System.err.println("Error: No token specified");
			usage();
			System.exit(1);
		}
		
		OvdClient cli = new OvdClientIntegrated(ovdServer, true, token);
		cli.start();
	}
}
