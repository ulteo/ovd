/*
 * Copyright (C) 2010-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2013
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
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

import org.ulteo.ovd.ApplicationInstance;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.WebApplication;

import java.awt.Desktop;
import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileWriter;
import java.io.IOException;
import java.net.URI;
import java.util.ArrayList;
import java.util.Scanner;
import java.util.UUID;

import javax.swing.JOptionPane;
import org.apache.log4j.Logger;
import org.ulteo.gui.GUIActions;
import org.ulteo.gui.SwingTools;
import org.ulteo.ovd.client.OvdClientRemoteApps;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.RdpConnectionOvd;
import org.ulteo.utils.FilesOp;
import org.ulteo.utils.I18n;
import org.ulteo.utils.SystemUtils;

public class Spool extends Thread {
	private final static String PREFIX_ID = "id = ";
	private final static String PREFIX_ARG = "arg = ";

	private Logger logger = Logger.getLogger(Spool.class);
	private OvdClientRemoteApps client = null;
	private String id = null;

	public Spool(OvdClientRemoteApps client_) {
		this.client = client_;
		this.id = UUID.randomUUID().toString();
	}

	private void savePID(File baseDirecotory_) {
		int jvm_pid = SystemUtils.getPID();
		File f = new File(baseDirecotory_.getPath()+File.separator+"pid");
		if (f.exists())
			f.delete();
		
		try {
			FileWriter fw = new FileWriter(f);
			fw.write(""+jvm_pid);
			fw.flush();
			fw.close();
		} catch (IOException ex) {
			org.ulteo.Logger.error("Failed to store JVM PID to '"+f.getAbsolutePath()+"': "+ex.getMessage());
		}
	}

	@Override
	public void run() {
		File baseDirectory = new File(Constants.PATH_REMOTE_APPS + File.separator + this.id);
		File instancesDir = new File(baseDirectory.getPath() + File.separator + Constants.DIRNAME_INSTANCES);
		File toLaunchDir = new File(baseDirectory.getPath() + File.separator + Constants.DIRNAME_TO_LAUNCH);
		
		// create Spooler folders
		File[] toCreateDirs = {instancesDir, toLaunchDir,
				new File(Constants.PATH_ICONS), new File(Constants.PATH_SHORTCUTS) };
		for (File f: toCreateDirs)
			f.mkdirs();
		
		if (OSTools.isWindows())
			this.savePID(baseDirectory);

		this.logger.info("Spool thread started");

		while (! Thread.interrupted()) {
			try {
				Thread.sleep(100);
			} catch (InterruptedException e) {
				break;
			}

			File[] children = toLaunchDir.listFiles();
			for (File todo : children) {
				try {
					int appId = -1;
					String arg = null;
					Scanner scanner = new Scanner(todo);
					while (scanner.hasNextLine()) {
						String line = scanner.nextLine().trim();
						if(line.isEmpty())
							continue;

						if (line.startsWith(PREFIX_ID)) {
							appId = Integer.parseInt(line.substring(PREFIX_ID.length()));
							continue;
						}

						if (line.startsWith(PREFIX_ARG)) {
							arg = line.substring(PREFIX_ARG.length());
							continue;
						}

						org.ulteo.Logger.warn("File '"+todo.getName()+"': Unknown line content: "+line);
					}
					this.startApp(appId, arg, todo.getName());
					scanner.close();
				} catch (FileNotFoundException ex) {
					this.logger.error("No read file '" + todo.getAbsolutePath() + "'");
				}

				File instanceFile = new File(instancesDir.getAbsolutePath()+Constants.FILE_SEPARATOR+todo.getName());
				try {
					instanceFile.createNewFile();
				} catch (IOException ex) {
					this.logger.error(ex);
				}

				if (!todo.delete()) {
					this.logger.error("No delete file '" + todo.getAbsolutePath() + "'");
				}
			}
		}
		
		this.logger.info("Spool thread stopped");
		
		// delete spooler folders
		File[] toDeleteDirs = {baseDirectory, new File(Constants.PATH_ICONS), new File(Constants.PATH_SHORTCUTS)};
		for (File dir : toDeleteDirs)
			FilesOp.deleteDirectory(dir);
	}
	
	public void terminate() {
		this.interrupt();
		try {
			this.join();
		} catch (InterruptedException e) {}
	}

	private void startApp(int appId_, String arg_, String token_) {
		this.logger.info("Start application "+appId_+" with arg '"+arg_+"'(token: "+token_+")");
		Application app = this.client.findAppById(appId_);
		if (app == null) {
			this.logger.error("Can not start application (id: "+appId_+")");
			return;
		}
		
		if (app.isWebApp()) {
			WebApplication wapp = (WebApplication) app;
			this.logger.info("Opening web app " + wapp.getName());
			final URI uri = ((WebApplication)app).getOpenURI();
			
			this.logger.info("URI " + uri.toString());

			try {
				Desktop.getDesktop().browse(uri);
			} catch (IOException e) {
				this.logger.error("Cannot open web app URI");
				// Ignore the error. TODO: maybe display error message?
				throw new RuntimeException(e);
			}
			return;
		}
		
		ApplicationInstance ai = new ApplicationInstance(app, arg_, Integer.parseInt(token_));
		ai.setLaunchedFromShortcut(true);
		try {
			ai.startApp();
		} catch (RestrictedAccessException ex) {
			arg_ = arg_.replaceAll("\\\\", "\\\\\\\\");
			
			String msg = I18n._("Cannot open '%PATH%'");
			msg = msg.replaceFirst("%PATH%", arg_);
			msg += "\n"+ex.getMessage();
			
			org.ulteo.Logger.error(msg);
			SwingTools.invokeLater(GUIActions.createDialog(msg, I18n._("Error"), JOptionPane.ERROR_MESSAGE, JOptionPane.DEFAULT_OPTION));
		}
	}

	public void destroyInstance(int token) {
		File instanceFile = new File(Constants.PATH_REMOTE_APPS + File.separator + this.id +
				File.separator + Constants.DIRNAME_INSTANCES + File.separator + token);
		if (! instanceFile.delete())
			this.logger.error(String.format("Unable to remove instance file (%s)", instanceFile.getPath()));
	}

	public String getID() {
		return this.id;
	}
}
