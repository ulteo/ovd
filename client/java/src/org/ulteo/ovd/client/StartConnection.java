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
import org.ulteo.ovd.client.authInterface.DisconnectionFrame;
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

	private static final String ERROR_AUTHENTICATION_FAILED = "auth_failed";
	private static final String ERROR_IN_MAINTENANCE = "in_maintenance";
	private static final String ERROR_INTERNAL = "internal_error";
	private static final String ERROR_INVALID_USER = "invalid_user";
	private static final String ERROR_SERVICE_NOT_AVAILABLE = "service_not_available";
	private static final String ERROR_UNAUTHORIZED_SESSION_MODE = "unauthorized_session_mode";
	private static final String ERROR_ACTIVE_SESSION = "user_with_active_session";
	private static final String ERROR_DEFAULT = "default";

	private LoadingFrame loadingFrame = null;
	private AuthFrame authFrame = null;
	private DisconnectionFrame discFrame = null;

	private boolean isCancelled = false;
	
	private Thread thread = null;

	private HashMap<String, String> responseHandler = null;
	private OvdClient client = null;

	public StartConnection() {

		this.responseHandler = new HashMap<String, String>();
		this.responseHandler.put(ERROR_AUTHENTICATION_FAILED, I18n._("Authentication failed, please double-check your password and try again"));
		this.responseHandler.put(ERROR_IN_MAINTENANCE, I18n._("The system is in maintenance mode, please contact your administrator for more information"));
		this.responseHandler.put(ERROR_INTERNAL, I18n._("An internal error occured, please contact your administrator"));
		this.responseHandler.put(ERROR_INVALID_USER, I18n._("You specified an invalid login, please double-check and try again"));
		this.responseHandler.put(ERROR_SERVICE_NOT_AVAILABLE, I18n._("The service is not available, please contact your administrator for more information"));
		this.responseHandler.put(ERROR_UNAUTHORIZED_SESSION_MODE, I18n._("You are not authorized to launch a session in this mode"));
		this.responseHandler.put(ERROR_ACTIVE_SESSION, I18n._("You already have an active session"));
		this.responseHandler.put(ERROR_DEFAULT, I18n._("An error occured, please contact your administrator"));

		this.loadingFrame = new LoadingFrame(this);
		this.discFrame = new DisconnectionFrame();
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
				this.client.performDisconnectAll();

			else {
				try {
					Thread.sleep(100);
				} catch (InterruptedException e) {}
			}
		}
	}

	public void run() {
		this.isCancelled = false;

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
				this.isCancelled = true;
				
				if (this.client != null)
					this.client.disconnectAll();
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

	public void disableDisconnectingMode() {
		this.discFrame.setVisible(false);
	}

	public boolean launchConnection() {
		boolean exit = false;

		// Get form values
		String username = this.authFrame.getLogin().getText();
		String host = this.authFrame.getHost().getText();
		int mode =  Properties.MODE_ANY;
		if (this.authFrame.getSessionModeBox().getSelectedItem() == this.authFrame.getItemModeApplication())
			mode = Properties.MODE_REMOTEAPPS;
		else if (this.authFrame.getSessionModeBox().getSelectedItem() == this.authFrame.getItemModeDesktop())
			mode = Properties.MODE_DESKTOP;
				
		
		int resolution = this.authFrame.getResBar().getValue();
		boolean localCredential = (this.authFrame.isUseLocalCredentials());

		String password = new String(this.authFrame.getPassword().getPassword());
		this.authFrame.getPassword().setText("");
		
		if (host.equals("")) {
			JOptionPane.showMessageDialog(null, I18n._("You must specify the host field !"), I18n._("Warning !"), JOptionPane.WARNING_MESSAGE);
			this.disableLoadingMode();
			return exit;
		}
		
		if (localCredential == false) {
			if (username.equals("")) {
				JOptionPane.showMessageDialog(null, I18n._("You must specify a username !"), I18n._("Warning !"), JOptionPane.WARNING_MESSAGE);
				this.disableLoadingMode();
				return exit;
			}
			if (password.equals("")) {
				JOptionPane.showMessageDialog(null, I18n._("You must specify a password !"), I18n._("Warning !"), JOptionPane.WARNING_MESSAGE);
				this.disableLoadingMode();
				return exit;
			}
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
			boolean ret = false;
			if (localCredential)
				ret = dialog.askForSession(request);
			else
				ret = dialog.askForSession(username, password, request);
			
			if (ret == false) {
				this.disableLoadingMode();
				return exit;
			}
		} catch (SessionManagerException ex) {
			System.err.println(ex.getMessage());
			this.disableLoadingMode();
			return exit;
		}
		this.loadingFrame.getCancelButton().setEnabled(true);
		
		Properties response = dialog.getResponseProperties();
		
		if ((mode != Properties.MODE_ANY) && (response.getMode() != request.getMode())) {
			this.disableLoadingMode();
			JOptionPane.showMessageDialog(null, I18n._("Internal error: unsupported session mode"), I18n._("Error"), JOptionPane.WARNING_MESSAGE);
			System.err.println("Error: No valid session mode received");

			return exit;
		}

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

		if (! this.isCancelled)
			exit = this.client.perform();
		else
			this.client.disconnectAll();
		this.client = null;

		this.checkDisconnectionSource();
		
		return exit;
	}
	
	@Override
	public void sessionDisconnecting() {
		this.setJobMainThread(JOB_DISCONNECT_CLI);
		this.loadingFrame.setVisible(false);
		this.discFrame.setVisible(true);
	}
	
	public void checkDisconnectionSource() {
		if (! this.discFrame.isVisible()) {
			if (loadingFrame.isVisible())
				disableLoadingMode();
			JOptionPane.showMessageDialog(null, I18n._("You have been disconnected"), I18n._("Error"), JOptionPane.WARNING_MESSAGE);
			
		}
		else
			this.disableDisconnectingMode();
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

	@Override
	public void reportUnauthorizedHTTPResponse(String moreInfos) {
		String error = this.responseHandler.get(ERROR_AUTHENTICATION_FAILED);
		JOptionPane.showMessageDialog(null, error, I18n._("Error"), JOptionPane.ERROR_MESSAGE);
		org.ulteo.Logger.error(error + "\n" + moreInfos);
	}

	@Override
	public void reportNotFoundHTTPResponse(String moreInfos) {
		String error = this.responseHandler.get(ERROR_DEFAULT);
		JOptionPane.showMessageDialog(null, error, I18n._("Error"), JOptionPane.ERROR_MESSAGE);
		org.ulteo.Logger.error(error+ "\n" + moreInfos);
	}

	public void sessionConnected() {
		if (this.loadingFrame.isVisible() || this.authFrame.getMainFrame().isVisible()) {
			this.disableLoadingMode();
			this.authFrame.hideWindow();
		}
	}

	private void saveProfile() throws IOException {
		String login = this.authFrame.getLogin().getText();
		boolean useLocalCredentials = this.authFrame.isUseLocalCredentials();
		String host = this.authFrame.getHost().getText();

		int sessionMode = ProfileProperties.MODE_AUTO;
		if (this.authFrame.getSessionModeBox().getSelectedItem() == this.authFrame.getItemModeApplication())
			sessionMode = ProfileProperties.MODE_APPLICATIONS;
		else if (this.authFrame.getSessionModeBox().getSelectedItem() == this.authFrame.getItemModeDesktop())
			sessionMode = ProfileProperties.MODE_DESKTOP;
		
		boolean autoPublish = this.authFrame.isAutoPublishChecked();
		int screensize = this.authFrame.getResBar().getValue();

		ProfileIni ini = new ProfileIni();
		ini.setProfile(null);
		ini.saveProfile(new ProfileProperties(login, host, sessionMode, autoPublish, useLocalCredentials, screensize));
	}

	private void loadProfile() {
		ProfileIni ini = new ProfileIni();
		List<String> profiles = ini.listProfiles();

		if (profiles == null)
			return;

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
		this.authFrame.setUseLocalCredentials(properties.getUseLocalCredentials());
		this.authFrame.setHost(properties.getHost());
		
		Object item = this.authFrame.getItemModeAuto();
		if (properties.getSessionMode() == ProfileProperties.MODE_APPLICATIONS)
			item = this.authFrame.getItemModeApplication();
		else if (properties.getSessionMode() == ProfileProperties.MODE_DESKTOP)
			item = this.authFrame.getItemModeDesktop();
		this.authFrame.getSessionModeBox().setSelectedItem(item);
		
		this.authFrame.setAutoPublishChecked(properties.getAutoPublish());
		this.authFrame.setResolution(properties.getScreenSize());

		this.authFrame.setRememberMeChecked(true);

		this.authFrame.getPassword().requestFocus();
	}
}
