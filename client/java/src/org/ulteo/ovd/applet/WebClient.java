/*
 * Copyright (C) 2010-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010, 2012
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
 * Author David LECHEVALIER <david@ulteo.com> 2011, 2012, 2013
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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

package org.ulteo.ovd.applet;


import java.applet.Applet;
import java.awt.BorderLayout;
import java.awt.Frame;
import java.awt.event.FocusEvent;
import java.awt.event.FocusListener;
import java.io.ByteArrayInputStream;
import java.io.File;
import java.io.FileNotFoundException;
import java.nio.charset.Charset;
import java.util.ArrayList;
import java.util.Collections;
import java.util.Enumeration;
import java.util.HashMap;
import java.util.Map;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import netscape.javascript.JSObject;
import org.w3c.dom.Document;

import org.ulteo.Logger;
import org.ulteo.ovd.client.ClientInfos;
import org.ulteo.ovd.client.OvdClient;
import org.ulteo.ovd.client.OvdClientDesktop;
import org.ulteo.ovd.client.WebClientCommunication;
import org.ulteo.ovd.client.profile.ProfileProperties;
import org.ulteo.ovd.client.profile.ProfileWeb;
import org.ulteo.ovd.integrated.Constants;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.printer.OVDStandalonePrinterThread;
import org.ulteo.ovd.sm.Application;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.Protocol;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.pcsc.PCSC;
import org.ulteo.rdp.rdpdr.OVDPrinter;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.seamless.SeamlessFrame;
import org.ulteo.rdp.seamless.SeamlessPopup;
import org.ulteo.utils.FilesOp;
import org.ulteo.utils.AbstractFocusManager;
import org.ulteo.utils.LayoutDetector;
import org.ulteo.utils.LibraryLoader;
import org.ulteo.utils.jni.WorkArea;


public class WebClient extends Applet implements FocusListener {
	protected boolean finished_init = false;
	protected boolean started_stop = false;

	protected AbstractFocusManager focusManager;
	protected OvdClient ovd = null;
	
	public static final int JS_API_ERROR_CODE_CONTAINER = 1;
	public static final String JS_API_F_CONFIRM_REGISTER = "applet_registered";
	public static final String JS_API_F_SESSION_READY = "applet_sessionReady";
	public static final String JS_API_F_SESSION_ERROR = "applet_sessionError";
	
	public static final String JS_API_F_SERVER = "applet_serverStatus";
	public static final String JS_API_O_SERVER_CONNECTED = "connected";
	public static final String JS_API_O_SERVER_DISCONNECTED = "disconnected";
	public static final String JS_API_O_SERVER_FAILED = "failed";
	public static final String JS_API_O_SERVER_READY = "ready";

	public static final String JS_API_F_INSTANCE = "applet_applicationStatus";
	public static final String JS_API_O_INSTANCE_STARTED = "started";
	public static final String JS_API_O_INSTANCE_STOPPED = "stopped";
	public static final String JS_API_O_INSTANCE_ERROR = "error";

	public static final String JS_API_F_AJAXRESPONSE = "applet_ajaxResponse";
	
	private JSObject js_instance = null;
	
	private RequestForwarder ajax = null;
	private Thread ajaxThread = null;
	
	private String userLogin = "";
	private String userKeyboardLayout = null;

	private int session_mode = Properties.MODE_DESKTOP;
	private ServerAccess aps = null;


	/** SM address */
	private String sm_host;
	/** SM port */
	private int sm_port;
	
	private SpoolOrder spooler;
	
	private Map<Integer, ArrayList<Application>> serverApps;
	
	private File jshortcut_dll;
	private File registry_dll;
	private File libpcsc_dll;
	
	private boolean local_integration = false;

	private boolean already_load_others_jni = false;

	public static String tempdir;
	
	static {
		OSTools.is_applet = true;
		
		tempdir = System.getProperty("java.io.tmpdir");
		if (! tempdir.endsWith(System.getProperty("file.separator")))
			tempdir+= System.getProperty("file.separator");
		
		if (! Logger.initInstance(true, tempdir+"ulteo-ovd-"+Logger.getDate()+".log", true)) {
			System.err.println("WebClient: unable to iniatialize logger instance");
		}
		
		if (OSTools.isWindows()) {
			try {
				LibraryLoader.LoadLibrary(LibraryLoader.RESOURCE_LIBRARY_DIRECTORY_WINDOWS, LibraryLoader.LIB_WINDOW_PATH_NAME);
			} catch (FileNotFoundException ex) {
				Logger.error(ex.getMessage());
			}
			try {
				LibraryLoader.LoadLibrary(LibraryLoader.RESOURCE_LIBRARY_DIRECTORY_WINDOWS, LibraryLoader.LIB_PCSC_WINDOWS);
				PCSC.libraryLoaded();
			} catch (FileNotFoundException ex) {
				org.ulteo.Logger.warn(ex.getMessage());
				PCSC.disableLibraryLoading();
			}
		}
		else if (OSTools.isLinux()) {
			try {
				LibraryLoader.LoadLibrary(LibraryLoader.RESOURCE_LIBRARY_DIRECTORY_LINUX, LibraryLoader.LIB_X_CLIENT_AREA);
			} catch (FileNotFoundException ex) {
				Logger.error(ex.getMessage());
				WorkArea.disableLibraryLoading();
			}
			try {
				LibraryLoader.LoadLibrary(LibraryLoader.RESOURCE_LIBRARY_DIRECTORY_LINUX, LibraryLoader.LIB_PCSC_UNIX);
				PCSC.LoadPCSCLite();
				PCSC.libraryLoaded();
			} catch (FileNotFoundException ex) {
				org.ulteo.Logger.warn(ex.getMessage());
				PCSC.disableLibraryLoading();
			}
		}
	}
	
	private boolean load_others_jni() {
		if (this.already_load_others_jni) {
			return true;
		}
		
		this.already_load_others_jni = true;
		String arch = System.getProperty("sun.arch.data.model");
		
		// DLL must be exported before the client creation object
		if (OSTools.isWindows()) {
			try {
				jshortcut_dll = FilesOp.exportJarResource("WindowsLibs/32/jshortcut.dll");
				registry_dll = FilesOp.exportJarResource("WindowsLibs/32/ICE_JNIRegistry.dll");
			} catch (FileNotFoundException e) {
				this.local_integration = false;
			}
			LibraryLoader.addToJavaLibraryPath(registry_dll.getParentFile());
			try {
				libpcsc_dll = FilesOp.exportJarResource("WindowsLibs/"+arch+"/"+LibraryLoader.LIB_PCSC_WINDOWS);
			} catch (FileNotFoundException e) {
				libpcsc_dll = null;
			}
			
		}
		return true;
	}
	
	@Override
	public final void init() {
		Logger.info(this.getClass().toString() + " init");
		System.out.println("//--");
		ClientInfos.showClientInfos();
		System.out.println("--//");
		
		try {
			System.getProperty("user.home");
		} catch(java.security.AccessControlException e) {
			System.err.println("AccessControl issue");
			return;
		}
		
		if (! this.load_others_jni()) {
			System.err.println("Failed to load additionnal jni");
			return;
		}
		
		try {
			this.spooler = new SpoolOrder(this);
			this.serverApps = Collections.synchronizedMap(new HashMap<Integer, ArrayList<Application>>());
			
			Constants.JAVA_LAUNCHER = FilesOp.exportJarResource("ovdIntegratedLauncher.jar").getPath();
			
			this.finished_init = true;
		}
		catch (Exception e) {
			Logger.error(this.getClass().toString() + ": " + e.getMessage());
			this.stop();
		}
		finally {
			Logger.info(this.getClass().toString() + " inited");
		}
		
		this.ajax = new RequestForwarder(this);
		this.ajaxThread = new Thread(this.ajax);
	}
	
	
	@Override
	public final void start() {
		if (! this.finished_init || this.started_stop)
			return;
		
		Logger.info(this.getClass().toString() +" start");
		
		this.spooler.start();
		
		try {
			System.getProperty("user.home");
			this.userLogin = System.getProperty("user.name");
		} catch(java.security.AccessControlException e) {
			System.err.println("AccessControl issue");
		}
		this.userKeyboardLayout = LayoutDetector.get();
		
		this.ajaxThread.start();
		
		Logger.info(this.getClass().toString() +" started");
	}
	
	
	@Override
	public final void stop() {
		if (this.started_stop)
			return;
		
		Logger.info(this.getClass().toString() +" stopping");
		this.started_stop = true;
		
		if (this.ovd != null)
			this.ovd.performDisconnectAll();
		Logger.info(this.getClass().toString() +" stopped");
		
		if (this.ajax != null) {
			this.ajax.setDisable();
			this.ajax = null;
			this.ajaxThread = null;
		}
		
		if (this.spooler != null && this.spooler.isAlive())
			this.spooler.interrupt();
		if (Constants.JAVA_LAUNCHER != null)
			new File(Constants.JAVA_LAUNCHER).delete();
		if (jshortcut_dll != null && jshortcut_dll.exists())
			jshortcut_dll.delete();
		if (registry_dll != null && registry_dll.exists())
			registry_dll.delete();
		if (libpcsc_dll != null && libpcsc_dll.exists())
			libpcsc_dll.delete();
	}
	
	
	@Override
	public final void destroy() {
		this.ovd = null;
		this.focusManager = null;
		this.sm_host = null;
		this.spooler = null;
		this.serverApps = null;
		this.aps = null;
		System.gc();
	}
	
	protected void createOvdSession(int mode_, Map<String, String> settings_) {
		this.session_mode = mode_;
		
		String[] address = settings_.get("sessionmanager").split(":");
		this.sm_host = address[0];
		this.sm_port = Integer.parseInt(address[1]);
		
		if (settings_.containsKey("local_integration")) {
			this.local_integration = settings_.get("local_integration").equalsIgnoreCase("true");
		}
		
		boolean fullscreenMode = (settings_.containsKey("fullscreen") && settings_.get("fullscreen").equalsIgnoreCase("true"));
		String keymap = settings_.get("keymap");
		String rdp_input_method = settings_.get("rdp_input_method");
		String wc = settings_.get("wc_url");
		
		Properties properties = new Properties(this.session_mode);
		for (Map.Entry<String, String> setting : settings_.entrySet()) {
			String key = setting.getKey();
			String value = setting.getValue();
			Protocol.parseSessionSettings(properties, key, value);
		}
		
		if (properties.isPrinters()){
			OVDStandalonePrinterThread appletPrinterThread = new OVDStandalonePrinterThread();
			OVDPrinter.setPrinterThread(appletPrinterThread);
			this.focusManager = new AppletFocusManager(appletPrinterThread);
		}
			
		// configure client
		if (this.session_mode == Properties.MODE_DESKTOP) {
			OvdClientDesktopApplet client = new OvdClientDesktopApplet(properties, this);
			client.setFullscreen(fullscreenMode);
			
			if (fullscreenMode == false) {
				String container = settings_.get("container");
				if (container == null) {
					System.err.println("No container given for desktop (no fullscreen) session");
					this.forwardSessionError(this.JS_API_ERROR_CODE_CONTAINER, "No container given for desktop (no fullscreen) session");
					return;
				}
				
				System.out.println("Looking for applet '"+container+"'");
				Applet desktop_container = null;
				Enumeration<Applet> applets = this.getAppletContext().getApplets();
				while (applets.hasMoreElements()) {
					Applet a = applets.nextElement();
					System.out.println("  * found applet: "+a+" id: "+a.getParameter("id"));
					String applet_id = a.getParameter("id");
					if (applet_id == null || ! applet_id.equals(container)) {
						continue;
					}
					
					desktop_container = a;
					System.out.println("    * this is the applet I'm looking for!");
					break;
				}
				
				if (desktop_container == null) {
					System.err.println("Unable to find another applet to host desktop session");
					this.forwardSessionError(this.JS_API_ERROR_CODE_CONTAINER, "Unable to find the canvas applet");
					return;
				}
				
				client.setApplet(desktop_container);
			}
			
			this.ovd = client;
		}
		else {
			if (properties.isPrinters()) {
				SeamlessFrame.focusManager = focusManager;
				SeamlessPopup.focusManager = focusManager;
			}
			
			properties.setDesktopIcons(this.local_integration);
			SessionManagerCommunication smComm = new SessionManagerCommunication(this.sm_host, this.sm_port, true);
			this.ovd = new OvdClientApplicationsApplet(smComm, properties, this);
		}
		
		this.ovd.setKeymap(keymap);
		if (rdp_input_method != null)
			this.ovd.setInputMethod(rdp_input_method);
		
		// load web profile
		WebClientCommunication webComm = new WebClientCommunication(wc);
		ProfileProperties pproperties = new ProfileWeb(webComm).loadProfile();
		if (pproperties != null) {
			this.ovd.setPacketCompression(pproperties.isUsePacketCompression());
			this.ovd.setOffscreenCache(pproperties.isUseOffscreenCache());
			this.ovd.setUseFrameMarker(pproperties.isUseFrameMarker());
			this.ovd.setUseTLS(pproperties.isUseTLS());
			if (pproperties.isUsePersistantCache())
				this.ovd.setPersistentCaching(pproperties.getPersistentCacheMaxCells(), pproperties.getPersistentCachePath());
			if (pproperties.isUseBandwithLimitation()) {
				int diskBandwidthLimit = 0;
				if (pproperties.isUseDiskBandwithLimitation())
					diskBandwidthLimit = pproperties.getDiskBandwidthLimit();
				this.ovd.setBandWidthLimitation(pproperties.getSocketTimeout(), diskBandwidthLimit);
			}
			
			if (pproperties.isUseKeepAlive()) {
				int keepAliveInterval = 0;
				this.ovd.setUseKeepAlive(true);
				keepAliveInterval = pproperties.getKeepAliveInterval();
				this.ovd.setKeepAliveInterval(keepAliveInterval);
			}
		}
		
		// call javascript function
		this.forwardSessionReady();
	}

	protected void destroyOvdSession() {
		if (this.ovd == null) {
			System.err.println("Unable to destroy empty session");
			return;
		}
		
		this.ovd.sessionTerminated();
		this.ovd = null;
	}

	
	// ********
	// Methods called by Javascript
	// ********
	
	public String getUserLogin() {
		return this.userLogin;
	}
	
	public String getDetectedKeyboardLayout() {
		return this.userKeyboardLayout;
	}

	public boolean register(JSObject js_instance_) {
		if (this.js_instance != null) {
			System.err.println("Applet already registered");
			return false;
		}
		
		this.js_instance = js_instance_;
		System.out.println("WebClient::register: "+this.js_instance);
		this.spooler.add(new OrderRegister());
		return true;
	}
	
	public boolean startSession(String mode_, String[] settings_) {
		System.out.println("WebClient::startSession mode:"+mode_);
		int session_mode;
		if (mode_.equalsIgnoreCase("desktop")) {
			session_mode = Properties.MODE_DESKTOP;
		}
		else if (mode_.equalsIgnoreCase("applications")) {
			session_mode = Properties.MODE_REMOTEAPPS;
		}
		else {
			System.err.println("Invalid session mode '"+mode_+"' != desktop|applications ...");
			return false;
		}
		
		System.out.println("call start_session mode: "+mode_);

		Map<String, String> settings = new HashMap<String, String>();
		for (int i=1; i<settings_.length; i+=2) {
			settings.put(settings_[i-1], settings_[i]);
			System.out.println("  * setting: "+settings_[i-1]+" => "+settings_[i]);
		}
		
		this.spooler.add(new OrderSessionStart(session_mode, settings));
		return true;
	}
	
	public boolean endSession() {
		if (this.ovd == null) {
			System.err.println("Unable to destroy empty session");
			return false;
		}

		this.spooler.add(new OrderSessionStop());
		return true;
	}

	public void serverPrepare(int JSId, String xml) {
		try {
			DocumentBuilder domBuilder = DocumentBuilderFactory.newInstance().newDocumentBuilder();
			Document doc = domBuilder.parse(new ByteArrayInputStream(xml.getBytes(Charset.forName("UTF-8"))));
			serverApps.put(JSId, SessionManagerCommunication.parseApplications(doc.getDocumentElement()));
		} catch (Exception e) {
			Logger.warn("Error during 'serverPrepare' parsing: " + e.getMessage());
		}
	}
	
	public boolean serverConnect(int JSId, String host, int port, String login, String password) {
		return this.serverConnect(JSId, host, port, null, login, password);
	}
	
	public boolean serverConnect(int JSId, String host, int port, String gw_token, String login, String password) {
		ServerAccess server = new ServerAccess(host, port, login, password);
		if (this.session_mode == Properties.MODE_REMOTEAPPS){
			server.applications = serverApps.get(JSId);
		}
		
		server.setGatewayToken(gw_token);
		this.spooler.add(new OrderServer(JSId, server));
		return true;
	}
	
	public void startApplication(int token, int app_id, int server_id) {
		this.spooler.add(new OrderApplication(token, app_id, new Integer(server_id), null));
	}
	
	public void startApplicationWithFile(int token, int app_id, int server_id, String f_type, String f_path, String f_share) {
		FileApp f = new FileApp(f_type, f_path, f_share);
		this.spooler.add(new OrderApplication(token, app_id, new Integer(server_id), f));
	}
	
	public void ajaxRequest(String url, String method, String content_type, String data, String request_id) {
		this.ajax.pushOrder(new AjaxOrder(url, method, content_type, data, request_id));
	}
	
	// ********
	// Methods to call Javascript
	// ********
	
	public void forwardToJS(String functionName, Object[] args) {
		if (this.js_instance == null) {
			System.err.println("Cannot communicate with javascript cause js_instance is null");
			return;
		}
		
		if (args == null)
			args = new Object[0];
		
		try {
			try {
				this.js_instance.call(functionName, args);
			} catch (ClassCastException e) {
				// a cast exception is raised when the applet is executed by the 
				// appletViewer class (used by some IDEs) and with OpenJDK JVM. This will 
				// not execute the JS, so it not possible to run an OVD session
				throw new netscape.javascript.JSException(e.getMessage());
			}
		} catch (netscape.javascript.JSException e) {
			String argsStr = "";
			if (args.length > 0) {
				for (int i = 0; i < args.length; i++) {
					argsStr += args[i];
					
					if (i < args.length - 1)
						argsStr += ", ";
				}
			}
			
			Logger.error(String.format("%s: error while execute %s(%s) => %s",
					this.getClass(), functionName, argsStr, e.getMessage()));
		}
	}
	
	public void forwardSessionReady() {
		Object[] args = {};
		this.forwardToJS(JS_API_F_SESSION_READY, args);
	}
	
	public void forwardSessionError(int code, String msg) {
		Object[] args = {code, msg};
		this.forwardToJS(JS_API_F_SESSION_ERROR, args);
	}
	
	public void forwardServerStatusToJS(Integer serverId_, String status_) {
		Object[] args = {serverId_, status_};
		this.forwardToJS(JS_API_F_SERVER, args);
	}
	
	public void forwardApplicationStatusToJS(Integer app_id_, Integer token_, String status_) {
		Object[] args = {app_id_, token_, status_};
		this.forwardToJS(JS_API_F_INSTANCE, args);
	}
	
	protected void confirm_register() {
		System.out.println("WebClient::confirm_register: "+this.js_instance);
		Object[] args = {};
		this.forwardToJS(JS_API_F_CONFIRM_REGISTER, args);
	}

	protected void forwardAjaxResponse(String request_id_, int http_code_, String contentType_, String data_) {
		Object[] args = new Object[4];
		args[0] = request_id_;
		args[1] = new Integer(http_code_);
		args[2] = contentType_;
		args[3] = data_;
		
		this.forwardToJS(JS_API_F_AJAXRESPONSE, args);
	}
	
	/**
	 * get a parameter give to the applet
	 * @param key
	 * 		key of the parameter
	 * @return
	 * 		parameter expected
	 * @throws Exception 
	 */
	public String getParameterNonEmpty(String key) throws Exception {
		String param = super.getParameter(key);
		if (param == null || param.isEmpty())
			throw new Exception(String.format("Invalid parameter '%s'", key));
		return param;
	}
	
	// FocusListener's method interface
	
	@Override
	public void focusGained(FocusEvent e) {
		if (this.focusManager != null && !this.started_stop) {
			this.focusManager.performedFocusGain(e.getComponent());
		}
	}

	@Override
	public void focusLost(FocusEvent e) {
		if (this.focusManager != null && !this.started_stop) {
			this.focusManager.performedFocusLost(e.getComponent());
		}
	}
	
	// Methods called by Javascript
	
	/**
	 * switch back fullscreen window requested by javascript
	 */
	public void switchBackFullscreenWindow() {
		System.out.println("switch back fullscreen window requested by javascript");
		Frame w = (Frame) ((OvdClientDesktopApplet) this.ovd).getFullscreenWindow();
		w.setExtendedState(Frame.NORMAL);
	}
	
	protected OvdClient getOvdClient() {
		return this.ovd;
	}
}


class FileApp {
	int type;
	String path;
	String share;

	FileApp(String f_type, String f_path, String f_share) {
		if (f_type.equalsIgnoreCase("http"))
			type = OvdAppChannel.DIR_TYPE_HTTP_URL;
		else
			type = OvdAppChannel.DIR_TYPE_SHARED_FOLDER;
		
		path = f_path;
		share = f_share;
	}
	
	public String toString() {
		return String.format("file(type: %d, path: %s, share: %s)", this.type, this.path, this.share);
	}
}
