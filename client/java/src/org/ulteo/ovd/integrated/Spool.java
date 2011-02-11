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

package org.ulteo.ovd.integrated;

import org.ulteo.ovd.ApplicationInstance;
import org.ulteo.ovd.Application;
import java.io.File;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.util.ArrayList;
import java.util.Random;
import java.util.Scanner;
import javax.swing.JOptionPane;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.crypto.CryptoException;
import net.propero.rdp.RdpConnection;
import org.apache.log4j.Logger;
import org.ulteo.gui.GUIActions;
import org.ulteo.gui.SwingTools;
import org.ulteo.ovd.client.OvdClient;
import org.ulteo.rdp.RdpConnectionOvd;
import org.ulteo.utils.I18n;

public class Spool implements Runnable {
	private final static String PREFIX_ID = "id = ";
	private final static String PREFIX_ARG = "arg = ";

	private Logger logger = Logger.getLogger(Spool.class);
	private OvdClient client = null;
	private String os = null;
	private File instancesDir = null;
	private File toLaunchDir = null;
	private ArrayList<ApplicationInstance> appInstances = null;
	private String instance = null;
	private File baseDirectory = null;

	public Spool(OvdClient client_) {
		this.client = client_;
		this.appInstances = new ArrayList<ApplicationInstance>();
		this.instance = this.randomString();
		this.baseDirectory = new File(Constants.PATH_REMOTE_APPS+Constants.FILE_SEPARATOR+this.instance);
	}

	public void createIconsDir() {
		new File(Constants.PATH_ICONS).mkdirs();
	}

	public void createShortcutDir() {
		new File(Constants.PATH_SHORTCUTS).mkdirs();
	}

	public void createTree() {
		this.instancesDir = new File(this.baseDirectory.getAbsolutePath()+Constants.FILE_SEPARATOR+Constants.DIRNAME_INSTANCES);
		this.instancesDir.mkdirs();

		this.toLaunchDir = new File(this.baseDirectory.getAbsolutePath()+Constants.FILE_SEPARATOR+Constants.DIRNAME_TO_LAUNCH);
		this.toLaunchDir.mkdirs();
	}

	public void delete(File path) throws IOException {
		if (!path.exists()) throw new IOException("File not found '" + path.getAbsolutePath() + "'");

		if (path.isDirectory()) {
			File[] children = path.listFiles();
			for (int i=0; children != null && i<children.length; i++)
				this.delete(children[i]);
			if (!path.delete())
				throw new IOException("Could not delete folder '" + path.getAbsolutePath() + "'");
		}
		else if (!path.delete())
			throw new IOException("Could not delete file '" + path.getAbsolutePath() + "'");
	}

	public void deleteTree() {
		File[] toDelete = {this.baseDirectory, new File(Constants.PATH_ICONS), new File(Constants.PATH_SHORTCUTS)};
		for (File each : toDelete) {
			if (each.exists()) {
				try {
					this.delete(each);
				} catch (IOException ioe) {
					this.logger.error(ioe.getMessage());
				}
			}
		}
	}

	private boolean existTree() {
		if (this.instancesDir == null || this.toLaunchDir == null) {
			return false;
		}
		return true;
	}

	public void run() {
		if (! this.existTree()) {
			this.createTree();
		}

		this.logger.info("Spool thread started");

		try {
			while (true) {
				Thread.sleep(100);

				if (this.toLaunchDir == null)
					continue;

				File[] children = this.toLaunchDir.listFiles();
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

					File instanceFile = new File(this.instancesDir.getAbsolutePath()+Constants.FILE_SEPARATOR+todo.getName());
					try {
						instanceFile.createNewFile();
					} catch (IOException ex) {
						this.logger.error(ex);
					}

					if (!todo.delete()) {
						this.logger.error("No delete file '" + todo.getAbsolutePath() + "'");
					}

					if (todo.getName().equals("quit")) {
						this.doLogoff();
						return;
					}
				}
			} 
		} catch (InterruptedException ex) {
			this.logger.info("Spool thread stopped");
			return;
		}
	}
	
	private Thread spoolThread = null;

	public void start() {
		this.spoolThread = new Thread(this);
		this.spoolThread.start();
	}

	public void waitThreadEnd() {
		while (this.spoolThread != null && this.spoolThread.isAlive()) {
			try {
				Thread.sleep(100);
			} catch (InterruptedException ex) {
				this.logger.error(ex);
			}
		}
	}

	public void stop() {
		spoolThread.interrupt();

		while (spoolThread.isAlive()) {
			try {
				Thread.sleep(10);
			} catch (InterruptedException ex) {}
		}

		spoolThread = null;
		org.ulteo.Logger.info("Spool thread stopped");
	}

	private Application findAppById(long id_) {
		for (RdpConnectionOvd rc : this.client.getAvailableConnections()) {
			for (Application app : rc.getAppsList()) {
				if (app.getId() == id_) {
					return app;
				}
			}
		}
		return null;
	}

	private ApplicationInstance findAppInstanceByToken(long token_) {
		for (ApplicationInstance ai : this.appInstances) {
			if (ai.getToken() == token_)
				return ai;
		}
		return null;
	}

	private void startApp(int appId_, String arg_, String token_) {
		this.logger.info("Start application "+appId_+" with arg '"+arg_+"'(token: "+token_+")");
		Application app = this.findAppById(appId_);
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
		File instanceFile = new File(this.instancesDir.getAbsolutePath()+Constants.FILE_SEPARATOR+token);
		if (! instanceFile.exists()) {
			this.logger.error("Unable to remove instance file ("+this.instancesDir.getAbsolutePath()+Constants.FILE_SEPARATOR+token+") : does not exist");
			return;
		}
		if (! instanceFile.isFile()) {
			this.logger.error("Unable to remove instance file ("+this.instancesDir.getAbsolutePath()+Constants.FILE_SEPARATOR+token+") : is not a file");
			return;
		}

		instanceFile.delete();
	}

	private void doLogoff() {
		for (RdpConnection rc : this.client.getAvailableConnections()) {
			try {
				rc.getSeamlessChannel().send_spawn("logoff");
			} catch (RdesktopException ex) {
				this.logger.error(ex);
			} catch (IOException ex) {
				this.logger.error(ex);
			} catch (CryptoException ex) {
				this.logger.error(ex);
			}
		}
	}

	public ArrayList<ApplicationInstance> getAppInstance() {
		return this.appInstances;
	}

	public String getInstanceName() {
		return this.instance;
	}

	private String randomString() {
		String base = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		Random randomGenerator = new Random();

		String ret = new String();
		for (int i = 0; i < 5; i++){
			int r = randomGenerator.nextInt(base.length());
			ret+= (new Integer(r)).toString();
		}

		return ret;
	}
}
