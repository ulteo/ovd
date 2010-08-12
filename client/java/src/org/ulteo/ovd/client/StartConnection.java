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

import org.ulteo.ovd.client.profile.ProfileIni;
import java.io.File;
import java.io.IOException;

import javax.swing.UIManager;
import javax.swing.UnsupportedLookAndFeelException;

import gnu.getopt.Getopt;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.util.HashMap;
import java.util.List;
import javax.swing.JOptionPane;
import org.apache.log4j.BasicConfigurator;
import org.apache.log4j.Level;
import org.apache.log4j.Logger;
import org.ini4j.Wini;

import org.ulteo.ovd.client.authInterface.AuthFrame;
import org.ulteo.ovd.client.authInterface.LoadingFrame;
import org.ulteo.ovd.client.desktop.OvdClientDesktop;
import org.ulteo.ovd.client.profile.ProfileProperties;
import org.ulteo.ovd.client.remoteApps.OvdClientPortal;
import org.ulteo.ovd.printer.OVDStandalonePrinterThread;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.SessionManagerException;
import org.ulteo.rdp.rdpdr.OVDPrinter;

public class StartConnection implements ActionListener, Runnable, org.ulteo.ovd.sm.Callback {
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

				SessionManagerCommunication dialog = new SessionManagerCommunication(ovdServer, true);

				Properties request = new Properties((mode == Properties.MODE_ANY) ? Properties.MODE_DESKTOP : Properties.MODE_REMOTEAPPS);
				try {
					if (!dialog.askForSession(username, password, request)) {
						return;
					}
				} catch (SessionManagerException ex) {
					System.err.println(ex.getMessage());
					return;
				}

				Properties response = dialog.getResponseProperties();

				OVDPrinter.setPrinterThread(new OVDStandalonePrinterThread());

				OvdClient cli = null;

				switch (response.getMode()) {
					case Properties.MODE_DESKTOP:
						cli = new OvdClientDesktop(dialog, resolution);
 						break;
					case Properties.MODE_REMOTEAPPS:
						cli = new OvdClientPortal(dialog);
 						break;
 					default:
						throw new UnsupportedOperationException("mode "+response.getMode()+" is not supported");
 				}

				cli.start();
			} catch (IOException ioe) {
				ioe.printStackTrace();
			}
		}
		else {
			StartConnection s = new StartConnection();
			s.waitThread();
		}
		System.gc();
		System.exit(0);
	}

	public static void usage() {
		System.err.println(StartConnection.productName);
		System.err.println("Usage: java -jar OVDIntegratedClient.jar [options]");
		System.err.println("	-c CONFIGFILE");
		System.err.println("	-p PASSWORD");
		System.err.println("Example: java -jar OVDNativeClient.jar -c config.ovd -p password");

		System.exit(0);
	}


	private LoadingFrame loadingFrame = null;
	private AuthFrame authFrame = null;

	private Thread thread = null;

	private HashMap<String, String> responseHandler = null;
	private OvdClient client = null;

	public StartConnection() {

		this.responseHandler = new HashMap<String, String>();
		this.responseHandler.put("auth_failed", I18n._("Authentication error. Please check your login and password"));
		this.responseHandler.put("in_maintenance", I18n._("The system is in maintenance, please contact your administrator"));
		this.responseHandler.put("internal_error", I18n._("The system is broken, please contact your administratord"));
		this.responseHandler.put("invalid_user", I18n._("This user don't have privileges to start a session"));
		this.responseHandler.put("service_not_available", I18n._("This user don't have privileges to start a session"));
		this.responseHandler.put("unauthorized_session_mode", I18n._("You cannot force that session type. Please change the requested type."));
		this.responseHandler.put("user_with_active_session", I18n._("You already have an active session. Please close it before to launch another one."));

		this.loadingFrame = new LoadingFrame(this);
		this.authFrame = new AuthFrame(this);
		this.loadProfile();
		this.authFrame.showWindow();
		this.loadingFrame.setLocationRelativeTo(this.authFrame.getMainFrame());
	}


	public static int JOB_NOTHING = 0;
	public static int JOB_DISCONNECT_CLI = 1;

	private int jobMainThread = 0;
	private boolean continueMainThread = true;
	
	public synchronized void setJobMainThread(int job) {
		this.jobMainThread = job;

	}
	private synchronized int getJobMainThread() {
		int job = this.jobMainThread;
		this.jobMainThread = JOB_NOTHING;
		return job;
	}

	public void waitThread() {
		while(this.continueMainThread) {
			int job = this.getJobMainThread();

			if (job == JOB_DISCONNECT_CLI)
				this.client.disconnectAll();

			else {
				try {
					Thread.sleep(100);
				} catch (InterruptedException e) {}
			}
		}
	}

	public void run() {
		if (this.launchConnection()) {
			this.continueMainThread = false;
		}
		else {
			this.authFrame.showWindow();
		}
		this.thread = null;
	}


	@Override
	public void actionPerformed(ActionEvent e) {
		if (e.getSource() == this.loadingFrame.getCancelButton()) {
			if (this.thread == null) {
				System.err.println("Very weird: thread should exist !");
			}
			else {
				System.out.println("this.setJobMainThread(JOB_DISCONNECT_CLI);");
				this.setJobMainThread(JOB_DISCONNECT_CLI);
			}

			this.loadingFrame.getCancelButton().setEnabled(false);
		}
		else if (e.getSource() == this.authFrame.GetStartButton()) {
			if (this.thread != null) {
				System.err.println("Very weird: thread should not exist anymore !");
				this.thread.interrupt();
				this.thread = null;
			}

			this.thread = new Thread(this);
			this.thread.start();

			this.loadingFrame.setVisible(true);
		}
	}

	public void disableLoadingMode() {
		this.loadingFrame.setVisible(false);
	}


	public boolean launchConnection() {
		boolean exit = false;

		// Get form values
		String username = this.authFrame.getLogin().getText();
		String host = this.authFrame.getHost().getText();
		int mode = (this.authFrame.getDesktopButton().isSelected()) ? Properties.MODE_DESKTOP : Properties.MODE_REMOTEAPPS;
		int resolution = this.authFrame.getResBar().getValue();

		String password = new String(this.authFrame.getPassword().getPassword());
		this.authFrame.getPassword().setText("");
		
		if (host.equals("") || username.equals("") || password.equals("")) {
			JOptionPane.showMessageDialog(null, I18n._("You must specify all the fields !"), I18n._("Warning !"), JOptionPane.WARNING_MESSAGE);
			this.disableLoadingMode();
			return exit;
		}
		
		// Backup entries
		if (this.authFrame.isRememberMeChecked()) {
			try {
				this.saveProfile();
			} catch (IOException ex) {
				System.err.println("Unable to save profile: "+ex.getMessage());
			}
		}

		// Start OVD session
		SessionManagerCommunication dialog = new SessionManagerCommunication(host, true);
		dialog.addCallbackListener(this);

		Properties request = new Properties(mode);
		try {
			if (!dialog.askForSession(username, password, request)) {
				this.disableLoadingMode();
				return exit;
			}
		} catch (SessionManagerException ex) {
			System.err.println(ex.getMessage());
			this.disableLoadingMode();
			return exit;
		}
		Properties response = dialog.getResponseProperties();

		OVDPrinter.setPrinterThread(new OVDStandalonePrinterThread());
		
		this.client = null;

		switch (response.getMode()) {
			case Properties.MODE_DESKTOP:
				this.client = new OvdClientDesktop(dialog, resolution, this);
				break;
			case Properties.MODE_REMOTEAPPS:
				this.client = new OvdClientPortal(dialog, response.getUsername(), this.authFrame.isAutoPublishChecked(), this);
				break;
			default:
				JOptionPane.showMessageDialog(null, I18n._("Internal error: unsupported session mode"), I18n._("Warning !"), JOptionPane.WARNING_MESSAGE);
				this.disableLoadingMode();
				return exit;
		}

		exit = this.client.perform();
		this.client = null;

		return exit;
	}

	@Override
	public void reportBadXml(String data) {
		JOptionPane.showMessageDialog(null, I18n._("Protocol xml error: ")+data, I18n._("Error"), JOptionPane.ERROR_MESSAGE);
	}

	@Override
	public void reportErrorStartSession(String code) {

		if (this.responseHandler.containsKey(code)) {
			JOptionPane.showMessageDialog(null, this.responseHandler.get(code), I18n._("Error"), JOptionPane.ERROR_MESSAGE);
			return;
		}

		this.reportBadXml(code);
	}

	public void sessionConnected() {
		if (this.loadingFrame.isVisible() || this.authFrame.getMainFrame().isVisible()) {
			this.disableLoadingMode();
			this.authFrame.hideWindow();
		}
	}

	private void saveProfile() throws IOException {
		String login = this.authFrame.getLogin().getText();
		String host = this.authFrame.getHost().getText();
		String sessionMode = this.authFrame.getSessionMode();
		boolean autoPublish = this.authFrame.isAutoPublishChecked();
		int screensize = this.authFrame.getResBar().getValue();

		ProfileIni ini = new ProfileIni();
		ini.setProfile(null);
		ini.saveProfile(new ProfileProperties(login, host, sessionMode, autoPublish, screensize));
	}

	private void loadProfile() {
		ProfileIni ini = new ProfileIni();
		List<String> profiles = ini.listProfiles();
		String profile = ProfileIni.DEFAULT_PROFILE;
		
		if (! profiles.contains(profile))
			return;
		
		ProfileProperties properties = null;
		try {
			properties = ini.loadProfile(profile);
		} catch (IOException ex) {
			System.err.println("Unable to load \""+profile+"\" profile: "+ex.getMessage());
			return;
		}

		if (properties == null)
			return;

		this.authFrame.setLogin(properties.getLogin());
		this.authFrame.setHost(properties.getHost());
		this.authFrame.setSessionMode(properties.getSessionMode());
		this.authFrame.setAutoPublishChecked(properties.getAutoPublish());
		this.authFrame.setResolution(properties.getScreenSize());

		this.authFrame.setRememberMeChecked(true);
	}
}
