/*
 * Copyright (C) 2011-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2011
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
 * Author Thomas MOUTON <thomas@ulteo.com> 2013
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

import java.io.File;
import java.io.FileNotFoundException;

public class Launcher {

	/**
	 * main class
	 * 
	 * @param args
	 */
	public static void main(String[] args) {
		if (args.length < 2) {
			System.err.println("Missing arguments.");
			usage();
			System.exit(-3);
		}

		String spool_dir = getRemoteAppsPath(args[0]);
		if (spool_dir == null) {
			System.err.println("System not supported");
			System.exit(-7);
		}

		SpoolLauncher spool = null;
		try {
			spool = new SpoolLauncher(spool_dir);
		} catch (FileNotFoundException e) {
			System.err.println("No spool directory: " + e.getMessage());
			System.exit(-5);
		}

		String args_application = new String();
		if (args.length > 2) {
			args_application = args[2];
		}

		int instance = 0;
		try {
			instance = spool.createInstance(args[1], args_application);
		} catch (Exception e) {
			System.err.println("Internal error while creating instance");
			System.exit(-5);
		}

		while (! spool.instanceIsAlive(instance)) {
			try {
				Thread.sleep(100);
			} catch (InterruptedException e) {
				System.exit(-7);
			}
		}

		while (spool.instanceIsAlive(instance)) {
			try {
				Thread.sleep(1000);
			} catch (InterruptedException e) {
				System.exit(-6);
			}
		}
	}

	private static String getRemoteAppsPath(String arg) {
		String ulteo_dir;
		String path;

		if (OSTools.isWindows()) {
			path = System.getenv("APPDATA");
			ulteo_dir = "ulteo";
		} else if (OSTools.isLinux()) {
			path = System.getProperty("user.home");
			ulteo_dir = ".ulteo";
		}
		/* else if (OSTools.isMac()) {
			// not implemented yet
		} */
		else
			return null;

		char fs = File.separatorChar;
		return path += String.format("%c%s%covd%cremoteApps%c%s", fs, ulteo_dir, fs, fs, fs, arg);
	}

	private static void usage() {
		System.err.println("Usage: OVDIntegratedClient INSTANCE APP_ID [args...]");
		// System.err.println("\t-d INSTANCE: the spool directory name to use"); // not yet supported
		System.err.println("\tAPP_ID: the Ulteo OVD Application id to start");
	}
}
