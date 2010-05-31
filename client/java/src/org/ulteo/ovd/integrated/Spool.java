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
import java.util.Scanner;
import java.util.logging.Level;
import java.util.logging.Logger;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.crypto.CryptoException;
import net.propero.rdp.RdpConnection;
import org.ulteo.rdp.RdpConnectionOvd;

public class Spool implements Runnable {
	private ArrayList<RdpConnectionOvd> connections = null;
	private String os = null;
	private File instancesDir = null;
	private File toLaunchDir = null;
	private ArrayList<ApplicationInstance> appInstances = null;

	public Spool() {
		this.connections = new ArrayList<RdpConnectionOvd>();
		this.appInstances = new ArrayList<ApplicationInstance>();
	}

	public void createIconsDir() {
		new File(Constants.iconsPath).mkdirs();
	}

	public void addConnection(RdpConnectionOvd rc) {
		this.connections.add(rc);
	}

	public void removeConnection(RdpConnectionOvd rc) {
		this.connections.remove(rc);
	}

	public void createTree() {
		this.instancesDir = new File(Constants.instancesPath);
		this.instancesDir.mkdirs();

		this.toLaunchDir = new File(Constants.toLaunchPath);
		this.toLaunchDir.mkdirs();
	}

	public void delete(File path) throws IOException {
		if (!path.exists()) throw new IOException("File not found '" + path.getAbsolutePath() + "'");

		if (path.isDirectory()) {
			File[] children = path.listFiles();
			for (int i=0; children != null && i<children.length; i++)
				this.delete(children[i]);
			if (!path.delete())
				throw new IOException("No delete path '" + path.getAbsolutePath() + "'");
		}
		else if (!path.delete())
			throw new IOException("No delete file '" + path.getAbsolutePath() + "'");
	}

	public void deleteTree() {
		if (this.instancesDir != null) {
			try {
				this.delete(this.instancesDir);
			} catch (IOException ioe) {
				ioe.printStackTrace();
			}
		}

		if (this.toLaunchDir != null) {
			try {
				this.delete(this.toLaunchDir);
			} catch (IOException ioe) {
				ioe.printStackTrace();
			}
		}

		File iconsDir = new File(Constants.iconsPath);
		if (iconsDir.exists()) {
			try {
				this.delete(iconsDir);
			} catch (IOException ioe) {
				ioe.printStackTrace();
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
		
		while (true) {
			File[] children = this.toLaunchDir.listFiles();
			for (File todo : children) {
				try {
					Scanner scanner = new Scanner(todo);
					while (scanner.hasNextLine()) {
						String line = scanner.nextLine().trim();
						if(line.isEmpty())
							continue;

						this.startApp(line, todo.getName());
					}
					scanner.close();
				} catch (FileNotFoundException ex) {
					System.err.println("No read file '" + todo.getAbsolutePath() + "'");
				}

				File instance = new File(Constants.instancesPath+Constants.separator+todo.getName());
				try {
					instance.createNewFile();
				} catch (IOException ex) {
					Logger.getLogger(Spool.class.getName()).log(Level.SEVERE, null, ex);
				}

				if (!todo.delete()) {
					System.err.println("No delete file '" + todo.getAbsolutePath() + "'");
				}

				if (todo.getName().equals("quit")) {
					this.doLogoff();
					return;
				}
			}
			try {
				Thread.sleep(100);
			} catch (InterruptedException ex) {
				Logger.getLogger(Spool.class.getName()).log(Level.SEVERE, null, ex);
			}
		}
	}

	private Application findAppById(long id_) {
		for (RdpConnectionOvd rc : this.connections) {
			for (Application app : rc.getAppsList()) {
				if (app.getId() == id_) {
					return app;
				}
			}
		}
		return null;
	}

	private ApplicationInstance findAppInstanceByPid(long pid_) {
		for (ApplicationInstance ai : this.appInstances) {
			if (ai.getPid() == pid_)
				return ai;
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

	private void startApp(String appId_, String token_) {
		int appId = Integer.parseInt(appId_);
		Application app = this.findAppById(appId);
		if (app == null) {
			System.err.println("Can not start application (id: "+appId_+")");
			return;
		}
		ApplicationInstance ai = new ApplicationInstance(app);
		this.appInstances.add(ai);
		try {
			ai.startApp(Long.parseLong(token_));
		} catch (RdesktopException ex) {
			Logger.getLogger(Spool.class.getName()).log(Level.SEVERE, null, ex);
		} catch (IOException ex) {
			Logger.getLogger(Spool.class.getName()).log(Level.SEVERE, null, ex);
		} catch (CryptoException ex) {
			Logger.getLogger(Spool.class.getName()).log(Level.SEVERE, null, ex);
		}
	}

	private void destroyInstance(long token) {
		File instanceFile = new File(Constants.instancesPath+Constants.separator+token);
		if (! instanceFile.exists()) {
			System.err.println("Unable to remove instance file ("+Constants.instancesPath+Constants.separator+token+") : does not exist");
			return;
		}
		if (! instanceFile.isFile()) {
			System.err.println("Unable to remove instance file ("+Constants.instancesPath+Constants.separator+token+") : is not a file");
			return;
		}

		instanceFile.delete();
	}

	private void doLogoff() {
		for (RdpConnection rc : this.connections) {
			try {
				rc.getSeamlessChannel().send_spawn("logoff");
			} catch (RdesktopException ex) {
				Logger.getLogger(Spool.class.getName()).log(Level.SEVERE, null, ex);
			} catch (IOException ex) {
				Logger.getLogger(Spool.class.getName()).log(Level.SEVERE, null, ex);
			} catch (CryptoException ex) {
				Logger.getLogger(Spool.class.getName()).log(Level.SEVERE, null, ex);
			}
		}
	}
}
