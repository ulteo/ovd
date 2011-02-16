/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2011
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

package org.ulteo.ovd.client;

import org.ulteo.utils.I18n;
import org.ulteo.ovd.applet.LibraryLoader;
import org.ulteo.ovd.client.profile.ProfileIni;
import java.io.File;
import java.io.IOException;

import javax.swing.UIManager;
import javax.swing.UnsupportedLookAndFeelException;

import gnu.getopt.Getopt;
import gnu.getopt.LongOpt;
import java.awt.Dimension;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.io.FileNotFoundException;
import java.net.URI;
import java.util.ArrayList;
import java.util.Calendar;
import java.util.List;
import java.util.Locale;
import java.util.StringTokenizer;
import java.util.Timer;
import java.util.TimerTask;
import javax.swing.JLabel;

import javax.swing.JOptionPane;
import org.apache.log4j.BasicConfigurator;
import org.apache.log4j.Level;
import org.apache.log4j.Logger;

import org.ulteo.ovd.client.authInterface.AuthFrame;
import org.ulteo.ovd.client.authInterface.DisconnectionFrame;
import org.ulteo.ovd.client.authInterface.LoadingFrame;
import org.ulteo.ovd.client.authInterface.LoadingStatus;
import org.ulteo.ovd.client.desktop.DesktopFrame;
import org.ulteo.ovd.client.desktop.OvdClientDesktop;
import org.ulteo.utils.jni.WorkArea;
import org.ulteo.gui.GUIActions;
import org.ulteo.gui.SwingTools;
import org.ulteo.ovd.client.profile.ProfileProperties;
import org.ulteo.ovd.client.profile.ProfileRegistry;
import org.ulteo.ovd.client.remoteApps.OvdClientPortal;
import org.ulteo.ovd.integrated.Constants;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.integrated.SystemAbstract;
import org.ulteo.ovd.printer.OVDStandalonePrinterThread;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.SessionManagerException;
import org.ulteo.rdp.rdpdr.OVDPrinter;

public class NativeClient implements ActionListener, Runnable, org.ulteo.ovd.sm.Callback {
	public static class Options {
		public String profile = null;
		public String username = null;
		public String password = null;
		public String server = null;
		public int port = SessionManagerCommunication.DEFAULT_PORT;
		public String keymap = null;
		public String lang = null;
		public Dimension geometry = null;
		public int sessionMode = -1;
		public boolean nltm = false;
		public boolean showProgressBar = true;
		public boolean autopublish = false;
		public boolean autostart = false;
		public boolean debugSeamless = false;
		public boolean guiLocked = false;
	}

	public static Options main_options = null;

	public static int optionMask = 0x00000000;
	
	public static final int FLAG_NO_OPTS = 0x00000000;
	public static final int FLAG_OPTION_USERNAME = 0x00000001;
	public static final int FLAG_OPTION_PASSWORD = 0x00000002;
	public static final int FLAG_OPTION_SERVER = 0x00000004;
	public static final int FLAG_OPTION_PORT = 0x00000600;
	public static final int FLAG_OPTION_KEYMAP = 0x00000008;
	public static final int FLAG_OPTION_LANGUAGE = 0x00000010;
	public static final int FLAG_OPTION_GEOMETRY = 0x00000020;
	public static final int FLAG_OPTION_SESSION_MODE = 0x00000040;
	public static final int FLAG_OPTION_NTLM = 0x00000080;
	public static final int FLAG_OPTION_SHOW_PROGRESS_BAR = 0x00000100;
	public static final int FLAG_OPTION_AUTO_INTEGRATION = 0x00000200;
	public static final int FLAG_OPTION_AUTO_START = 0x00000400;
	public static final int FLAG_OPTION_REMEMBER_ME = 0x00004000;
	public static final int FLAG_OPTION_GUI_LOCKED = 0x00008000;

	public static final int FLAG_CMDLINE_OPTS = 0x00000800;
	public static final int FLAG_FILE_OPTS = 0x00001000;
	public static final int FLAG_REGISTRY_OPTS = 0x00002000;

	public static final String productName = "Ulteo OVD Client";

	private static void parseProperties(ProfileProperties properties) {
		if (properties == null)
			return;

		if ((NativeClient.optionMask & NativeClient.FLAG_OPTION_SESSION_MODE) == 0) {
			NativeClient.main_options.sessionMode =  Properties.MODE_ANY;
			if (properties.getSessionMode() == ProfileProperties.MODE_APPLICATIONS)
				NativeClient.main_options.sessionMode = Properties.MODE_REMOTEAPPS;
			else if (properties.getSessionMode() == ProfileProperties.MODE_DESKTOP)
				NativeClient.main_options.sessionMode = Properties.MODE_DESKTOP;
		}

		if ((NativeClient.optionMask & NativeClient.FLAG_OPTION_USERNAME) == 0) {
			String username = properties.getLogin();
			if (username != null) {
				NativeClient.main_options.username = username;
				NativeClient.optionMask |= NativeClient.FLAG_OPTION_USERNAME;
			}
		}
		if ((NativeClient.optionMask & NativeClient.FLAG_OPTION_SERVER) == 0) {
			String server = properties.getHost();
			if (server != null) {
				NativeClient.main_options.server = server;
				NativeClient.optionMask |= NativeClient.FLAG_OPTION_SERVER;
			}
		}
		if ((NativeClient.optionMask & NativeClient.FLAG_OPTION_PORT) == 0) {
			int port = properties.getPort();
			if (port == 0)
				port = SessionManagerCommunication.DEFAULT_PORT;
			NativeClient.main_options.port = port;
			NativeClient.optionMask |= NativeClient.FLAG_OPTION_PORT;
		}
		if ((NativeClient.optionMask & NativeClient.FLAG_OPTION_NTLM) == 0) {
			NativeClient.main_options.nltm = properties.getUseLocalCredentials();
			NativeClient.optionMask |= NativeClient.FLAG_OPTION_NTLM;
		}
		if ((NativeClient.optionMask & NativeClient.FLAG_OPTION_AUTO_INTEGRATION) == 0) {
			boolean auto_integration = properties.getAutoPublish();
			if (! (auto_integration && NativeClient.main_options.sessionMode == Properties.MODE_DESKTOP)) {
				NativeClient.main_options.autopublish = auto_integration;
				NativeClient.optionMask |= NativeClient.FLAG_OPTION_AUTO_INTEGRATION;
			}
		}
		if ((NativeClient.optionMask & NativeClient.FLAG_OPTION_LANGUAGE) == 0) {
			String language = properties.getLang();
			if (language != null) {
				NativeClient.main_options.lang = language;
				NativeClient.optionMask |= NativeClient.FLAG_OPTION_LANGUAGE;
			}
		}
		if ((NativeClient.optionMask & NativeClient.FLAG_OPTION_KEYMAP) == 0) {
			String keymap = properties.getKeymap();
			if (keymap != null) {
				NativeClient.main_options.keymap = keymap;
				NativeClient.optionMask |= NativeClient.FLAG_OPTION_KEYMAP;
			}
		}
		if ((NativeClient.optionMask & NativeClient.FLAG_OPTION_SHOW_PROGRESS_BAR) == 0) {
			NativeClient.main_options.showProgressBar = properties.getShowProgressbar();
			NativeClient.optionMask |= NativeClient.FLAG_OPTION_SHOW_PROGRESS_BAR;
		}
		if ((NativeClient.optionMask & NativeClient.FLAG_OPTION_GEOMETRY) == 0) {
			Dimension geometry = properties.getScreenSize();
			if (! (geometry != null && NativeClient.main_options.sessionMode == Properties.MODE_REMOTEAPPS)) {
				NativeClient.main_options.geometry = geometry;
				NativeClient.optionMask |= NativeClient.FLAG_OPTION_GEOMETRY;
			}
		}
		if ((NativeClient.optionMask & NativeClient.FLAG_OPTION_GUI_LOCKED) == 0) {
			NativeClient.main_options.guiLocked = properties.isGUILocked();
			NativeClient.optionMask |= NativeClient.FLAG_OPTION_GUI_LOCKED;
		}
	}

	public static ProfileProperties getProfileFromIni(String path) {
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

	public static boolean getFormValuesFromFile(String profile) {
		ProfileProperties properties = getProfileFromIni(profile);
		if (properties == null)
			return false;

		NativeClient.parseProperties(properties);

		NativeClient.optionMask |= NativeClient.FLAG_OPTION_REMEMBER_ME;

		return true;
	}

	private static boolean getFormValuesFromRegistry() {
		ProfileProperties properties = ProfileRegistry.loadProfile();
		if (properties == null)
			return false;

		NativeClient.parseProperties(properties);

		NativeClient.optionMask |= NativeClient.FLAG_OPTION_REMEMBER_ME;

		return true;
	}

	private static final int RETURN_CODE_SUCCESS = 0;
	@SuppressWarnings("unused")
	private static final int RETURN_CODE_ERROR = 1;
	private static final int RETURN_CODE_BAD_ARGUMENTS = 2;
	
	/**
	 * @param args
	 */
	public static void main(String[] args) {
		ClientInfos.showClientInfos();

		if (OSTools.isWindows()) {
			try {
				LibraryLoader.LoadLibrary(LibraryLoader.LIB_WINDOW_PATH_NAME);
			} catch (FileNotFoundException ex) {
				System.err.println(ex.getMessage());
				System.exit(2);
			}
		}
		
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

		// Init Ulteo Logger instance
		String log_dir = Constants.PATH_NATIVE_CLIENT_CONF + Constants.FILE_SEPARATOR + "logs";
		if (! org.ulteo.Logger.initInstance(true, log_dir+Constants.FILE_SEPARATOR +org.ulteo.Logger.getDate()+".log", true))
			System.err.println("Unable to iniatialize logger instance");

		//Cleaning up all useless OVD data
		SystemAbstract.cleanAll();

		NativeClient.main_options = new Options();
		NativeClient.optionMask = NativeClient.FLAG_CMDLINE_OPTS;

		final int nbOptions = 4;
		List<LongOpt> systemDependantOptions = new ArrayList<LongOpt>();

		if (OSTools.isWindows()) {
			systemDependantOptions.add(new LongOpt("reg", LongOpt.NO_ARGUMENT, null, nbOptions + 1));
			systemDependantOptions.add(new LongOpt("ntlm", LongOpt.NO_ARGUMENT, null, nbOptions + 2));
		}

		LongOpt[] alo = new LongOpt[nbOptions + systemDependantOptions.size()];
		alo[0] = new LongOpt("auto-start", LongOpt.NO_ARGUMENT, null, 0);
		alo[1] = new LongOpt("auto-integration", LongOpt.NO_ARGUMENT, null, 1);
		alo[2] = new LongOpt("progress-bar", LongOpt.REQUIRED_ARGUMENT, null, 2);
		alo[3] = new LongOpt("help", LongOpt.NO_ARGUMENT, null, 3);

		for (int i = 4; i < alo.length; i++)
			alo[i] = systemDependantOptions.remove(0);

		Getopt opt = new Getopt(OvdClient.productName, args, "c:p:u:m:g:k:l:s:hd:", alo);

		int c;
		while ((c = opt.getopt()) != -1) {
			switch (c) {
				case (nbOptions + 1): //--reg
					NativeClient.optionMask |= NativeClient.FLAG_REGISTRY_OPTS;
					break;
				case 0: //--auto-start
					NativeClient.main_options.autostart = true;

					NativeClient.optionMask |= NativeClient.FLAG_OPTION_AUTO_START;
					break;
				case 1: //--auto-integration
					NativeClient.main_options.autopublish = true;

					NativeClient.optionMask |= NativeClient.FLAG_OPTION_AUTO_INTEGRATION;
					break;
				case (nbOptions + 2): //--ntlm
					NativeClient.main_options.nltm = true;

					NativeClient.optionMask |= NativeClient.FLAG_OPTION_NTLM;
					NativeClient.optionMask |= NativeClient.FLAG_OPTION_USERNAME;
					break;
				case 2: //--progress-bar [show|hide]
					String arg = new String(opt.getOptarg());
					if (arg.equalsIgnoreCase("show"))
						NativeClient.main_options.showProgressBar = true;
					else if (arg.equalsIgnoreCase("hide"))
						NativeClient.main_options.showProgressBar = false;
					else
						NativeClient.usage(RETURN_CODE_BAD_ARGUMENTS);

					NativeClient.optionMask |= NativeClient.FLAG_OPTION_SHOW_PROGRESS_BAR;
					break;
				case 3: //--help
				case 'h':
					NativeClient.usage(RETURN_CODE_SUCCESS);
					break;
				case 'c':
					NativeClient.main_options.profile = new String(opt.getOptarg());
					NativeClient.optionMask |= NativeClient.FLAG_FILE_OPTS;
					break;
				case 'p':
					NativeClient.main_options.password = new String(opt.getOptarg());

					NativeClient.optionMask |= NativeClient.FLAG_OPTION_PASSWORD;
					break;
				case 'u':
					NativeClient.main_options.username = new String(opt.getOptarg());

					NativeClient.optionMask |= NativeClient.FLAG_OPTION_USERNAME;
					break;
				case 'm':
					String sessionMode = new String(opt.getOptarg());
					if (sessionMode.equalsIgnoreCase("auto"))
						NativeClient.main_options.sessionMode = Properties.MODE_ANY;
					if (sessionMode.equalsIgnoreCase("desktop"))
						NativeClient.main_options.sessionMode = Properties.MODE_DESKTOP;
					if (sessionMode.equalsIgnoreCase("applications"))
						NativeClient.main_options.sessionMode = Properties.MODE_REMOTEAPPS;

					NativeClient.optionMask |= NativeClient.FLAG_OPTION_SESSION_MODE;
					break;
				case 'g':
					String geometry = new String(opt.getOptarg());
					int pos = geometry.indexOf("x");
					
					if (geometry.lastIndexOf("x") != pos)
						NativeClient.usage(RETURN_CODE_BAD_ARGUMENTS);

					try {
						NativeClient.main_options.geometry = new Dimension();
						NativeClient.main_options.geometry.width = Integer.parseInt(geometry.substring(0, pos));
						NativeClient.main_options.geometry.height = Integer.parseInt(geometry.substring(pos + 1, geometry.length()));
					} catch (NumberFormatException ex) {
						System.err.println(ex.getMessage() + "\n" + ex.getStackTrace());
						NativeClient.usage(RETURN_CODE_BAD_ARGUMENTS);
					}

					NativeClient.optionMask |= NativeClient.FLAG_OPTION_GEOMETRY;
					break;
				case 'k':
					NativeClient.main_options.keymap = new String(opt.getOptarg());

					NativeClient.optionMask |= NativeClient.FLAG_OPTION_KEYMAP;
					break;
				case 'l':
					NativeClient.main_options.lang = new String(opt.getOptarg());

					NativeClient.optionMask |= NativeClient.FLAG_OPTION_LANGUAGE;
					break;
				case 's':
					NativeClient.main_options.server = new String(opt.getOptarg());

					NativeClient.optionMask |= NativeClient.FLAG_OPTION_SERVER;
					break;
				case 'd':
					String items = new String(opt.getOptarg());

					StringTokenizer tok = new StringTokenizer(items, ",");
					while (tok.hasMoreTokens()) {
						String item = tok.nextToken();
						if (item.equalsIgnoreCase("seamless")) {
							NativeClient.main_options.debugSeamless = true;
						}
					}
					break;
				default:
					usage(RETURN_CODE_BAD_ARGUMENTS);
					break;
			}
		}

		if ((NativeClient.optionMask & NativeClient.FLAG_FILE_OPTS) != 0 && (NativeClient.optionMask & NativeClient.FLAG_REGISTRY_OPTS) != 0) {
			org.ulteo.Logger.error("You cannot use --reg with -c");
			NativeClient.usage(RETURN_CODE_BAD_ARGUMENTS);
		}

		if ((NativeClient.optionMask & NativeClient.FLAG_FILE_OPTS) != 0) {
			if (! NativeClient.getFormValuesFromFile(NativeClient.main_options.profile))
				org.ulteo.Logger.warn("The configuration file \""+NativeClient.main_options.profile+"\" does not exist.");
		}
		else if ((NativeClient.optionMask & NativeClient.FLAG_REGISTRY_OPTS) != 0) {
			if (! NativeClient.getFormValuesFromRegistry())
				org.ulteo.Logger.warn("No available configuration from registry");
		}
		else {
			if (! NativeClient.getFormValuesFromFile(null))
				org.ulteo.Logger.warn("The default configuration file does not exist.");
		}

		if (NativeClient.main_options.nltm && (NativeClient.main_options.username != null || NativeClient.main_options.password != null)) {
			org.ulteo.Logger.error("You cannot use --ntml with -u or -p");
			NativeClient.usage(RETURN_CODE_BAD_ARGUMENTS);
		}
		if (NativeClient.main_options.sessionMode == Properties.MODE_DESKTOP && NativeClient.main_options.autopublish) {
			org.ulteo.Logger.error("You cannot use --auto-integration in desktop mode");
			NativeClient.usage(RETURN_CODE_BAD_ARGUMENTS);
		}
		if (NativeClient.main_options.sessionMode == Properties.MODE_REMOTEAPPS && NativeClient.main_options.geometry != null) {
			org.ulteo.Logger.error("You cannot use -g in applications mode");
			NativeClient.usage(RETURN_CODE_BAD_ARGUMENTS);
		}
		if (NativeClient.main_options.autostart) {
			if (((NativeClient.main_options.username == null || NativeClient.main_options.password == null) && !NativeClient.main_options.nltm) || NativeClient.main_options.server == null) {
				org.ulteo.Logger.error("You must specify the server (-s) and your credentials (-u, -p or --ntlm)");
				NativeClient.usage(RETURN_CODE_BAD_ARGUMENTS);
			}

			if (NativeClient.main_options.sessionMode == -1) {
				NativeClient.main_options.sessionMode = Properties.MODE_ANY;
			}
		}

		NativeClient s = new NativeClient(NativeClient.main_options, NativeClient.optionMask);
		if (NativeClient.main_options.autostart) {
			s.startThread();
		}
		s.waitThread();

		System.exit(RETURN_CODE_SUCCESS);
	}

	public static void usage(int status) {
		System.err.println(NativeClient.productName);
		System.err.println("Usage: java -jar OVDNativeClient.jar [options]");
		System.err.println("\t-c file				Load configuration from `file`");
		System.err.println("\t-s server			Server");
		System.err.println("\t-u username			Username");
		System.err.println("\t-p password			Password");
		System.err.println("\t-m [auto|desktop|applications]	Session mode");
		System.err.println("\t-g widthxheight			Geometry");
		System.err.println("\t-k keymap			Keymap");
		System.err.println("\t-l language			Language");
		System.err.println("\t--progress-bar [show|hide]	Set the progress bar visibility");
		System.err.println("\t--auto-integration		Enable auto integration");
		System.err.println("\t--auto-start			Enable auto start");
		System.err.println("\t-d [seamless]			Enable debug (use comma as delimiter)");
		if (OSTools.isWindows()) {
			System.err.println("\t--ntlm				Use NTLM authentication");
			System.err.println("\t--reg				Load configuration from registry");
		}
		System.err.println("Examples:");
		System.err.println("\tClassic use:");
		System.err.println("\t\tjava -jar OVDNativeClient.jar -c config.ovd -p password");
		System.err.println("\tLoad configuration from file and use NTLM authentication:");
		System.err.println("\t\tjava -jar OVDNativeClient.jar -c config.ovd");
		System.err.println("\tLoad configuration from registry");
		System.err.println("\t\tjava -jar OVDNativeClient.jar --reg");

		System.exit(status);
	}

	private static final String ERROR_AUTHENTICATION_FAILED = "auth_failed";
	private static final String ERROR_IN_MAINTENANCE = "in_maintenance";
	private static final String ERROR_INTERNAL = "internal_error";
	private static final String ERROR_INVALID_USER = "invalid_user";
	private static final String ERROR_SERVICE_NOT_AVAILABLE = "service_not_available";
	private static final String ERROR_UNAUTHORIZED_SESSION_MODE = "unauthorized_session_mode";
	private static final String ERROR_ACTIVE_SESSION = "user_with_active_session";
	private static final String ERROR_DEFAULT = "default";

	public static class ResponseHandler {
		public static String get(String key) {
			if (key.equals(ERROR_AUTHENTICATION_FAILED))
				return I18n._("Authentication failed: please double-check your password and try again");
			else if (key.equals(ERROR_IN_MAINTENANCE))
				return I18n._("The system is on maintenance mode, please contact your administrator for more information");
			else if (key.equals(ERROR_INTERNAL))
				return I18n._("An internal error occured, please contact your administrator");
			else if (key.equals(ERROR_INVALID_USER))
				return I18n._("You specified an invalid login, please double-check and try again");
			else if (key.equals(ERROR_SERVICE_NOT_AVAILABLE))
				return I18n._("The service is not available, please contact your administrator for more information");
			else if (key.equals(ERROR_UNAUTHORIZED_SESSION_MODE))
				return I18n._("You are not authorized to launch a session in this mode");
			else if (key.equals(ERROR_ACTIVE_SESSION))
				return I18n._("You already have an active session");
			
			return I18n._("An error occured, please contact your administrator");
		}
		public static boolean has(String key) {
			if (key.equals(ERROR_AUTHENTICATION_FAILED))
				return true;
			else if (key.equals(ERROR_IN_MAINTENANCE))
				return true;
			else if (key.equals(ERROR_INTERNAL))
				return true;
			else if (key.equals(ERROR_INVALID_USER))
				return true;
			else if (key.equals(ERROR_SERVICE_NOT_AVAILABLE))
				return true;
			else if (key.equals(ERROR_UNAUTHORIZED_SESSION_MODE))
				return true;
			else if (key.equals(ERROR_ACTIVE_SESSION))
				return true;
			else if (key.equals(ERROR_DEFAULT))
				return true;
			
			return false;
		}
	}
	
	
	private LoadingFrame loadingFrame = null;
	private AuthFrame authFrame = null;
	private DisconnectionFrame discFrame = null;

	private boolean isCancelled = false;
	
	private Thread thread = null;

	private OvdClient client = null;

	private Options opts = null;
	private int flags = NativeClient.FLAG_NO_OPTS;

	public NativeClient(Options opts_, int flags_) {
		this.opts = opts_;
		this.flags = flags_;

		this.init();

		if (! this.opts.autostart) {
			this.initAuthFrame();
		}
	}
	
	public void init() {
		this.loadingFrame = new LoadingFrame(this);
		this.discFrame = new DisconnectionFrame();
	}

	private void initAuthFrame() {
		this.authFrame = new AuthFrame(this, this.opts.geometry, this.opts.guiLocked);
		this.authFrame.getLanguageBox().addActionListener(this);
		this.loadOptions();
		this.authFrame.setRememberMeChecked((this.flags & NativeClient.FLAG_OPTION_REMEMBER_ME) != 0);
		this.authFrame.showWindow();
		this.loadingFrame.setLocationRelativeTo(this.authFrame.getMainFrame());
	}

	private void loadOptions() {
		if (this.opts.sessionMode > -1) {
			JLabel item = null;
			if (this.opts.sessionMode == Properties.MODE_ANY)
				item = this.authFrame.getItemModeAuto();
			if (this.opts.sessionMode == Properties.MODE_DESKTOP)
				item = this.authFrame.getItemModeDesktop();
			if (this.opts.sessionMode == Properties.MODE_REMOTEAPPS)
				item = this.authFrame.getItemModeApplication();

			this.authFrame.getSessionModeBox().setSelectedItem(item);
		}

		if (this.opts.username != null)
			this.authFrame.setLogin(this.opts.username);

		if (this.opts.password != null)
			this.authFrame.getPassword().setText(this.opts.password);

		if (this.opts.server != null) {
			String buf = this.opts.server;
			if (this.opts.port != SessionManagerCommunication.DEFAULT_PORT)
				buf+= ":"+this.opts.port;
			
			this.authFrame.setHost(buf);
		}
		
		if (this.opts.lang != null) {
			for (int i = 0; i < Language.languageList.length; i++) {
				if (this.opts.lang.equalsIgnoreCase(Language.languageList[i][2])) {
					this.authFrame.getLanguageBox().setSelectedIndex(i);
					break;
				}
			}
		}
		if (this.opts.keymap != null) {
			for (int i = 0; i < Language.keymapList.length; i++) {
				if (this.opts.keymap.equals(Language.keymapList[i][1])) {
					this.authFrame.getKeyboardBox().setSelectedIndex(i);
					break;
				}
			}
		}
		this.authFrame.setUseLocalCredentials(this.opts.nltm);
		this.authFrame.setAutoPublishChecked(this.opts.autopublish);
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

			if (job == JOB_DISCONNECT_CLI && client != null)
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
		boolean valuesChecked = false;

		if (! this.opts.autostart && this.authFrame != null) {
			try {
				this.getFormValuesFromGui();
				valuesChecked = true;

				this.getBackupEntries();

				this.authFrame.hideWindow();
				this.authFrame = null;
			} catch (IllegalArgumentException ex) {
				org.ulteo.Logger.warn(ex.getMessage());
				JOptionPane.showMessageDialog(null, I18n._(ex.getMessage()), I18n._("Warning!"), JOptionPane.WARNING_MESSAGE);
			}
		}

		boolean exit = false;
		if (this.opts.autostart || valuesChecked) {
			try {
				exit = this.launchConnection();
			} catch (UnsupportedOperationException ex) {
				org.ulteo.Logger.error(ex.getMessage());
				JOptionPane.showMessageDialog(null, ex.getMessage(), I18n._("Error!"), JOptionPane.WARNING_MESSAGE);

				this.disableLoadingMode();
			} catch (SessionManagerException ex) {
				String errormsg = I18n._("Unable to reach the Session Manager!");
				org.ulteo.Logger.error(errormsg+": "+ex.getMessage());
				JOptionPane.showMessageDialog(null, errormsg, I18n._("Error!"), JOptionPane.WARNING_MESSAGE);

				this.disableLoadingMode();
			}
			
			if (exit || this.opts.autostart) {
				this.continueMainThread = false;
			} else {
				this.initAuthFrame();
			}
		}
		else {
			this.authFrame.reset();
		}
		this.thread = null;
	}

	public void startThread() {
		if (this.thread != null) {
			System.err.println("Very weird: thread should not exist anymore!");
			this.thread.interrupt();
			this.thread = null;
		}

		this.thread = new Thread(this);
		this.thread.start();
	}
	
	@Override
	public void actionPerformed(ActionEvent e) {
		if (e.getSource() == this.loadingFrame.getCancelButton()) {
			if (this.thread == null) {
				System.err.println("Very weird: thread should exist!");
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
		else if (e.getSource() == this.authFrame.getLanguageBox()) {
			int i = this.authFrame.getLanguageBox().getSelectedIndex();
			String language[] = Language.languageList[i];
			
			Locale locale = new Locale(language[2]);
			if (Language.languageList[i].length > 3)
				locale = new Locale(language[2], language[3]);
			
			System.out.println("Switch language from "+Locale.getDefault()+" to "+locale);
			
			Locale.setDefault(locale);
			
			I18n.init();
			
			SwingTools.invokeLater(AuthFrame.changeLanguage(this.authFrame));
			SwingTools.invokeLater(LoadingFrame.changeLanguage(this.loadingFrame));
			SwingTools.invokeLater(DisconnectionFrame.changeLanguage(this.discFrame));
		}
	}

	public void disableLoadingMode() {
		if (this.opts.showProgressBar)
			this.loadingFrame.setVisible(false);
	}

	public void disableDisconnectingMode() {
		this.discFrame.setVisible(false);
	}

	public void getFormValuesFromGui() throws IllegalArgumentException {
		this.opts.username = this.authFrame.getLogin().getText();
		
		URI u = null;
		try {
			u = new URI("http://"+this.authFrame.getHost().getText());
		}
		catch( Exception err) {
			throw new IllegalArgumentException(I18n._("Invalid host field!"));
		}
		
		if (u.getHost() == null)
			throw new IllegalArgumentException(I18n._("Invalid host field!"));
		
		this.opts.server = u.getHost();
		this.opts.port = SessionManagerCommunication.DEFAULT_PORT;
		if (u.getPort() != -1)
			this.opts.port = u.getPort();
			
		this.opts.sessionMode =  Properties.MODE_ANY;
		if (this.authFrame.getSessionModeBox().getSelectedItem() == this.authFrame.getItemModeApplication())
			this.opts.sessionMode = Properties.MODE_REMOTEAPPS;
		else if (this.authFrame.getSessionModeBox().getSelectedItem() == this.authFrame.getItemModeDesktop())
			this.opts.sessionMode = Properties.MODE_DESKTOP;
				
		this.opts.geometry = this.authFrame.getResolution();
		if (this.opts.geometry == null) {
			org.ulteo.Logger.error("No resolution selected: will select the default resolution");
			this.opts.geometry = DesktopFrame.DEFAULT_RES;
		}
		
		this.opts.nltm = this.authFrame.isUseLocalCredentials();

		this.opts.autopublish = this.authFrame.isAutoPublishChecked();
		
		this.opts.lang = Language.languageList[this.authFrame.getLanguageBox().getSelectedIndex()][2];
		if (Language.languageList[this.authFrame.getLanguageBox().getSelectedIndex()].length > 3)
			this.opts.lang+= "_"+Language.languageList[this.authFrame.getLanguageBox().getSelectedIndex()][3].toUpperCase();
		this.opts.keymap = Language.keymapList[this.authFrame.getKeyboardBox().getSelectedIndex()][1];
			
		this.opts.password = new String(this.authFrame.getPassword().getPassword());
		this.authFrame.getPassword().setText("");
		
		if (this.opts.server.equals("")) {
			throw new IllegalArgumentException(I18n._("You must specify the host field!"));
		}
		
		if (this.opts.nltm == false) {
			if (this.opts.username.equals("")) {
				throw new IllegalArgumentException(I18n._("You must specify a username!"));
			}
			if (this.opts.password.equals("")) {
				throw new IllegalArgumentException(I18n._("You must specify a password!"));
			}
		}
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
	
	public boolean launchConnection() throws UnsupportedOperationException, SessionManagerException {
		if (this.opts.showProgressBar) {
			this.loadingFrame.getCancelButton().setEnabled(true);
			this.updateProgress(LoadingStatus.LOADING_START, 0);
			SwingTools.invokeLater(GUIActions.setVisible(this.loadingFrame, true));
		}

		// Start OVD session
		SessionManagerCommunication dialog = new SessionManagerCommunication(this.opts.server, this.opts.port, true);
		if (! this.opts.autostart)
			dialog.addCallbackListener(this);

		this.updateProgress(LoadingStatus.SM_CONNECTION, 0);
		Properties request = new Properties(this.opts.sessionMode);
		request.setLang(this.opts.lang);
		request.setTimeZone(Calendar.getInstance().getTimeZone().getID());

		boolean ret = false;
		if (this.opts.nltm)
			ret = dialog.askForSession(request);
		else
			ret = dialog.askForSession(this.opts.username, this.opts.password, request);
		if (ret == false) {
			this.disableLoadingMode();
			return false;
		}
		
		this.updateProgress(LoadingStatus.SM_START, 0);
		
		Properties response = dialog.getResponseProperties();
		
		if ((this.opts.sessionMode != Properties.MODE_ANY) && (response.getMode() != request.getMode())) {
			throw new UnsupportedOperationException(I18n._("Internal error: Bad session mode return"));
		}
		
		// Session timeout management
		Timer timeout = new Timer();
		if (response.getDuration() > 0) {
			long duration = (response.getDuration() - 3*60) * 1000;
			if (duration < 0)
				duration = 100;
			
			timeout.schedule(new TimerTask() {
				public void run() {
					JOptionPane.showMessageDialog(null, I18n._("Your session is going to end in 3 minutes, please save all your data now!"), I18n._("Session is going to end"), JOptionPane.INFORMATION_MESSAGE);
				}
			}, duration);
		}

		OVDPrinter.setPrinterThread(new OVDStandalonePrinterThread());
		
		this.client = null;

		boolean persistent = response.isPersistent();
		switch (response.getMode()) {
			case Properties.MODE_DESKTOP:
				this.client = new OvdClientDesktop(dialog, this.opts.geometry, this, persistent);
				break;
			case Properties.MODE_REMOTEAPPS:
				if (OSTools.isLinux()) {
					try {
						LibraryLoader.LoadLibrary(LibraryLoader.LIB_X_CLIENT_AREA);
					} catch (FileNotFoundException ex) {
						WorkArea.disableLibraryLoading();
						org.ulteo.Logger.error(ex.getMessage());
					}
				}
				
				this.client = new OvdClientPortal(dialog, response.getUsername(), this.opts.autopublish, response.isDesktopIcons(), this.opts.autostart, this);
				((OvdClientPortal) this.client).setSeamlessDebugEnabled(this.opts.debugSeamless);
				break;
			default:
				throw new UnsupportedOperationException(I18n._("Internal error: unsupported session mode"));
		}
		this.client.setKeymap(this.opts.keymap);

		boolean exit = false;
		if (! this.isCancelled) {
			Runtime.getRuntime().addShutdownHook(new ShutdownTask(this.client));
			exit = this.client.perform();
		}
		else
			this.client.disconnectAll();
		this.client = null;

		timeout.cancel();
		
		this.checkDisconnectionSource();

		return exit;
	}
	
	@Override
	public void sessionDisconnecting() {

		this.setJobMainThread(JOB_DISCONNECT_CLI);

		if (this.opts.showProgressBar)
			SwingTools.invokeLater(GUIActions.setVisible(this.loadingFrame, false));
		discFrame.setVisible(true);
	}
	
	public void checkDisconnectionSource() {
		if (! this.discFrame.isVisible()) {
			if (loadingFrame.isVisible())
				disableLoadingMode();
			if(! this.opts.autostart)
				JOptionPane.showMessageDialog(null, I18n._("You have been disconnected"), I18n._("Your session has ended"), JOptionPane.INFORMATION_MESSAGE);
			else {
				System.err.println("You have been disconnected");
				System.exit(0);
			}
		}
		else {
			this.disableDisconnectingMode();
			
			if (this.opts.autostart)
				System.exit(0);
		}
	}
	
	@Override
	public void reportBadXml(String data) {
		JOptionPane.showMessageDialog(null, I18n._("Protocol xml error: ")+data, I18n._("Error"), JOptionPane.ERROR_MESSAGE);
	}

	@Override
	public void reportErrorStartSession(String code) {

		if (ResponseHandler.has(code)) {
			JOptionPane.showMessageDialog(null, ResponseHandler.get(code), I18n._("Error"), JOptionPane.ERROR_MESSAGE);
			return;
		}

		this.reportBadXml(code);
	}

	@Override
	public void reportError(int code, String message) {
		String error = ResponseHandler.get(ERROR_DEFAULT);
		JOptionPane.showMessageDialog(null, error, I18n._("Error"), JOptionPane.ERROR_MESSAGE);
		org.ulteo.Logger.error(error+ " (code: "+code+"):\n" + message);
	}

	@Override
	public void reportUnauthorizedHTTPResponse(String moreInfos) {
		String error = ResponseHandler.get(ERROR_AUTHENTICATION_FAILED);
		JOptionPane.showMessageDialog(null, error, I18n._("Error"), JOptionPane.ERROR_MESSAGE);
		org.ulteo.Logger.error(error + "\n" + moreInfos);
	}

	@Override
	public void reportNotFoundHTTPResponse(String moreInfos) {
		String error = ResponseHandler.get(ERROR_DEFAULT);
		JOptionPane.showMessageDialog(null, error, I18n._("Error"), JOptionPane.ERROR_MESSAGE);
		org.ulteo.Logger.error(error+ "\n" + moreInfos);
	}

	public void updateProgress(LoadingStatus status, int subStatus) {
		if (this.opts.showProgressBar)
			this.loadingFrame.updateProgression(status, subStatus);
	}

	public void sessionConnected() {
		if ((this.loadingFrame != null && this.loadingFrame.isVisible()) || (this.authFrame != null && this.authFrame.getMainFrame().isVisible())) {
			this.disableLoadingMode();
		}
	}

	private void saveProfile() throws IOException {
		ProfileProperties properties = new ProfileProperties(this.opts.username, this.opts.server, this.opts.port, this.opts.sessionMode, this.opts.autopublish, this.opts.nltm, this.opts.geometry, this.opts.lang, this.opts.keymap);

		if ((this.flags & NativeClient.FLAG_REGISTRY_OPTS) != 0) {
			ProfileRegistry.saveProfile(properties);
			return;
		}

		ProfileIni ini = new ProfileIni();

		if ((this.flags & NativeClient.FLAG_FILE_OPTS) != 0) {

			String path = null;
			String profile = this.opts.profile;
			int idx = this.opts.profile.lastIndexOf(System.getProperty("file.separator"));

			if (idx != -1) {
				profile = this.opts.profile.substring(idx + 1, this.opts.profile.length());
				path = this.opts.profile.substring(0, idx + 1);
			}

			ini.setProfile(profile, path);
		}
		else {
			ini.setProfile(null, null);// Default profile
		}
		ini.saveProfile(properties);
	}
}
