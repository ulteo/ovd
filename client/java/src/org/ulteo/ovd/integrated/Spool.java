/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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
import java.io.File;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.util.ArrayList;
import java.util.Scanner;
import java.util.UUID;

import javax.swing.JOptionPane;
import net.propero.rdp.RdpConnection;
import org.apache.log4j.Logger;
import org.ulteo.gui.GUIActions;
import org.ulteo.gui.SwingTools;
import org.ulteo.ovd.client.OvdClient;
import org.ulteo.utils.FilesOp;
import org.ulteo.utils.I18n;

public class Spool extends Thread {
	private final static String PREFIX_ID = "id = ";
	private final static String PREFIX_ARG = "arg = ";

	private Logger logger = Logger.getLogger(Spool.class);
	private OvdClient client = null;
	private ArrayList<ApplicationInstance> appInstances = null;
	private String id = null;

	public Spool(OvdClient client_) {
		this.client = client_;
		this.appInstances = new ArrayList<ApplicationInstance>();
		this.id = UUID.randomUUID().toString();
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

				if (todo.getName().equals("quit")) {
					for (RdpConnection rc : this.client.getAvailableConnections()) {
						try {
							rc.getSeamlessChannel().send_spawn("logoff");
						} catch (Exception e) {
							this.logger.error(e);
						}
					}
					return;
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
	
	public ApplicationInstance findAppInstanceByToken(long token_) {
		for (ApplicationInstance ai : this.appInstances) {
			if (ai.getToken() == token_)
				return ai;
		}
		return null;
	}

	private void startApp(int appId_, String arg_, String token_) {
		this.logger.info("Start application "+appId_+" with arg '"+arg_+"'(token: "+token_+")");
		Application app = this.client.findAppById(appId_);
		if (app == null) {
			this.logger.error("Can not start application (id: "+appId_+")");
			return;
		}
		ApplicationInstance ai = new ApplicationInstance(app, arg_, Integer.parseInt(token_));
		ai.setLaunchedFromShortcut(true);
		this.appInstances.add(ai);
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
		File instanceFile = new File(Constants.PATH_REMOTE_APPS + File.pathSeparator + this.id +
				File.pathSeparator + Constants.DIRNAME_INSTANCES + File.pathSeparator + token);
		if (! instanceFile.delete())
			this.logger.error(String.format("Unable to remove instance file (%s)", instanceFile.getPath()));
	}

	public String getID() {
		return this.id;
	}
}
