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

import org.ulteo.ovd.applet.LibraryLoader;
import org.ulteo.ovd.client.profile.ProfileIni;
import java.io.File;
import java.io.IOException;

import javax.swing.UIManager;
import javax.swing.UnsupportedLookAndFeelException;

import gnu.getopt.Getopt;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.util.Calendar;
import java.util.HashMap;
import java.util.List;
import javax.swing.JOptionPane;
import org.apache.log4j.BasicConfigurator;
import org.apache.log4j.Level;
import org.apache.log4j.Logger;

import org.ulteo.ovd.client.authInterface.AuthFrame;
import org.ulteo.ovd.client.authInterface.DisconnectionFrame;
import org.ulteo.ovd.client.authInterface.LoadingFrame;
import org.ulteo.ovd.client.authInterface.LoadingStatus;
import org.ulteo.ovd.client.desktop.OvdClientDesktop;
import org.ulteo.ovd.client.profile.ProfileProperties;
import org.ulteo.ovd.client.remoteApps.OvdClientPortal;
import org.ulteo.ovd.integrated.Constants;
import org.ulteo.ovd.integrated.OSTools;
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

		if (OSTools.isWindows()) {
			LibraryLoader.LoadLibrary(LibraryLoader.LIB_WINDOW_PATH_NAME);
		}
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
			else {
				usage();
				System.exit(0);
			}
		}
		
		// Init Ulteo Logger instance
		String log_dir = Constants.PATH_NATIVE_CLIENT_CONF + Constants.FILE_SEPARATOR + "logs";
		(new File(log_dir)).mkdirs();
		if (! org.ulteo.Logger.initInstance(true, log_dir+Constants.FILE_SEPARATOR +org.ulteo.Logger.getDate()+".log", true))
			System.err.println("Unable to iniatialize logger instance");
		
		StartConnection s = null;
		
		if (profile != null) {
			s = new StartConnection(profile, password);
			s.startThread();
			s.waitThread();
		}
		else {
			s = new StartConnection();
			s.waitThread();
		}
		System.gc();
		System.exit(0);
	}

	public static void usage() {
		System.err.println(StartConnection.productName);
		System.err.println("Usage: java -jar OVDNativeClient.jar [options]");
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
	private String profile = null;
	private boolean command = false;
	private String password = null;
	private int mode = 0;
	private int resolution = 0;
	private boolean localCredential = false;
	private String host = null;
	private String username = null;
	private boolean autoPublicated = false;
	private String language = null;
	private String keymap = null;

	private boolean isCancelled = false;
	
	private Thread thread = null;

	private HashMap<String, String> responseHandler = null;
	private OvdClient client = null;

	public StartConnection() {
		this.init();
		this.authFrame = new AuthFrame(this);
		this.loadProfile(null);
		this.authFrame.showWindow();
		this.loadingFrame.setLocationRelativeTo(this.authFrame.getMainFrame());
	}

	public StartConnection(String profile, String password) {
		this.init();
		this.profile = profile;
		this.password = password;
		this.command = true;
		this.loadingFrame.setLocationRelativeTo(null);
	}
	
	public void init() {
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
			if(! command)
				this.authFrame.showWindow();
		}
		this.thread = null;
	}

	public void startThread() {
		if (this.thread != null) {
			System.err.println("Very weird: thread should not exist anymore !");
			this.thread.interrupt();
			this.thread = null;
		}

		this.thread = new Thread(this);
		this.thread.start();
		
		this.loadingFrame.setVisible(true);
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
			this.startThread();
		}
	}

	public void disableLoadingMode() {
		this.loadingFrame.setVisible(false);
	}

	public void disableDisconnectingMode() {
		this.discFrame.setVisible(false);
	}

	public boolean getFormValuesFromGui() {
		this.username = this.authFrame.getLogin().getText();
		this.host = this.authFrame.getHost().getText();
		this.mode =  Properties.MODE_ANY;
		if (this.authFrame.getSessionModeBox().getSelectedItem() == this.authFrame.getItemModeApplication())
			this.mode = Properties.MODE_REMOTEAPPS;
		else if (this.authFrame.getSessionModeBox().getSelectedItem() == this.authFrame.getItemModeDesktop())
			this.mode = Properties.MODE_DESKTOP;
				
		
		this.resolution = this.authFrame.getResBar().getValue();
		this.localCredential = (this.authFrame.isUseLocalCredentials());

		this.autoPublicated = this.authFrame.isAutoPublishChecked();
		
		this.language = Language.languageList[this.authFrame.getLanguageBox().getSelectedIndex()][2];
		this.keymap = Language.keymapList[this.authFrame.getKeyboardBox().getSelectedIndex()][1];
			
		this.password = new String(this.authFrame.getPassword().getPassword());
		this.authFrame.getPassword().setText("");
		
		if (this.host.equals("")) {
			JOptionPane.showMessageDialog(null, I18n._("You must specify the host field !"), I18n._("Warning !"), JOptionPane.WARNING_MESSAGE);
			this.disableLoadingMode();
			return false;
		}
		
		if (this.localCredential == false) {
			if (this.username.equals("")) {
				JOptionPane.showMessageDialog(null, I18n._("You must specify a username !"), I18n._("Warning !"), JOptionPane.WARNING_MESSAGE);
				this.disableLoadingMode();
				return false;
			}
			if (this.password.equals("")) {
				JOptionPane.showMessageDialog(null, I18n._("You must specify a password !"), I18n._("Warning !"), JOptionPane.WARNING_MESSAGE);
				this.disableLoadingMode();
				return false;
			}
		}
		return true;
	}
	
	public boolean getFormValuesFromFile() {
		ProfileProperties properties = getProfile(profile);
		if (properties == null) {
			System.out.println("The configuration file \""+profile+"\" doesn't exist.");
			return false;
		}
		
		this.mode =  Properties.MODE_ANY;
		if (properties.getSessionMode() == ProfileProperties.MODE_APPLICATIONS)
			this.mode = Properties.MODE_REMOTEAPPS;
		else if (properties.getSessionMode() == ProfileProperties.MODE_DESKTOP)
			this.mode = Properties.MODE_DESKTOP;

		this.username = properties.getLogin();
		this.host = properties.getHost();
		this.localCredential = properties.getUseLocalCredentials();
		this.autoPublicated = properties.getAutoPublish();
		
		if (this.host.equals("")) {
			System.err.println("You must specifiy the host field !");
			this.disableLoadingMode();
			return false;
		}
		
		if (properties.getUseLocalCredentials() == false) {
			if (this.username.equals("")) {
				System.err.println("You must specify a username !");
				this.disableLoadingMode();
				return false;
			}
			
			if (this.password == null) {
				usage();
				return false;
			}
		}
		return true;
	}
	
	public void getBackupEntries() {
		if (this.authFrame.isRememberMeChecked()) {
			try {
				this.saveProfile();
			} catch (IOException ex) {
				System.err.println("Unable to save profile: "+ex.getMessage());
			}
		}
	}
	
	public boolean launchConnection() {
		boolean exit = false;

		if (! command) {
			if (! this.getFormValuesFromGui())
				return exit;
			this.getBackupEntries();
		}
		else {
			if (! this.getFormValuesFromFile())
				return exit;
		}

		// Start OVD session
		SessionManagerCommunication dialog = new SessionManagerCommunication(host, true);
		if (! command)
			dialog.addCallbackListener(this);

		this.updateProgress(LoadingStatus.STATUS_SM_CONNECTION, 0);
		Properties request = new Properties(mode);
		request.setLang(language);
		request.setTimeZone(Calendar.getInstance().getTimeZone().getID());
		
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
		this.updateProgress(LoadingStatus.STATUS_SM_START, 0);
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
				this.client = new OvdClientPortal(dialog, response.getUsername(), this.autoPublicated, this);
				break;
			default:
				JOptionPane.showMessageDialog(null, I18n._("Internal error: unsupported session mode"), I18n._("Warning !"), JOptionPane.WARNING_MESSAGE);
				this.disableLoadingMode();
				return exit;
		}
		this.client.setKeymap(this.keymap);

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
			if(! command)
				JOptionPane.showMessageDialog(null, I18n._("You have been disconnected"), I18n._("Your session has ended"), JOptionPane.INFORMATION_MESSAGE);
			else {
				System.err.println("You have been disconnected");
				System.exit(0);
			}
		}
		else {
			this.disableDisconnectingMode();
			
			if (command)
				System.exit(0);
		}
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
	public void reportError(int code, String message) {
		String error = this.responseHandler.get(ERROR_DEFAULT);
		JOptionPane.showMessageDialog(null, error, I18n._("Error"), JOptionPane.ERROR_MESSAGE);
		org.ulteo.Logger.error(error+ " (code: "+code+"):\n" + message);
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

	public void updateProgress(int status, int subStatus) {
		this.loadingFrame.updateProgression(status, subStatus);
	}

	public void sessionConnected() {
		if (this.loadingFrame.isVisible() || (this.authFrame != null && this.authFrame.getMainFrame().isVisible())) {
			this.disableLoadingMode();
			if (! command)
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
		String lang = this.language;
		String keymap = this.keymap;

		ProfileIni ini = new ProfileIni();
		ini.setProfile(null, null);
		ini.saveProfile(new ProfileProperties(login, host, sessionMode, autoPublish, useLocalCredentials, screensize, lang, keymap));
	}

	public static ProfileProperties getProfile(String path) {
		ProfileIni ini = new ProfileIni();
		String profile = "";

		if (path == null) {
			List<String> profiles = ini.listProfiles();

			if (profiles == null)
				return null;

			profile = ProfileIni.DEFAULT_PROFILE;
			
			if (! profiles.contains(profile))
				return null;
		}
		else {
			File file = new File(path);
			profile = file.getName();
			path = file.getParent();
		}
		
		ProfileProperties properties = null;
		try {
			properties = ini.loadProfile(profile, path);
		} catch (IOException ex) {
			System.err.println("Unable to load \""+profile+"\" profile: "+ex.getMessage());
			return null;
		}
		
		return properties;
	}
	
	private void loadProfile(String path) {
		ProfileProperties properties = getProfile(path);

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
		
		if (properties.getLang() != null) {
			for (int i = 0; i < Language.languageList.length; i++) {
				if (properties.getLang().equalsIgnoreCase(Language.languageList[i][2])) {
					this.authFrame.getLanguageBox().setSelectedIndex(i);
					break;
				}
			}
		}
		if (properties.getKeymap() != null) {
			for (int i = 0; i < Language.keymapList.length; i++) {
				if (properties.getKeymap().equals(Language.keymapList[i][1])) {
					this.authFrame.getKeyboardBox().setSelectedIndex(i);
					break;
				}
			}
		}
		
		this.authFrame.getPassword().requestFocus();
	}
}
