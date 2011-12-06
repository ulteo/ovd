/*
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2009-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
 * Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
 * Author David LECHEVALIER <david@ulteo.com> 2011
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

import java.io.ByteArrayInputStream;
import java.io.File;
import java.io.FileNotFoundException;
import java.util.ArrayList;
import java.util.Collections;
import java.util.HashMap;
import java.util.Map;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;

import org.ulteo.Logger;
import org.ulteo.ovd.client.OvdClient;
import org.ulteo.ovd.integrated.Constants;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.sm.Application;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.ovd.sm.SessionManagerCommunication;

import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.seamless.SeamlessFrame;
import org.ulteo.rdp.seamless.SeamlessPopup;
import org.ulteo.utils.FilesOp;
import org.ulteo.utils.LibraryLoader;
import org.w3c.dom.Document;


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


public class Applications extends OvdApplet {
	
	/** SM address */
	private String sm_host;
	/** SM port */
	private int sm_port;
	
	private SpoolOrder spooler;
	
	private Map<Integer, ArrayList<Application>> serverApps;
	
	private File jshortcut_dll;
	private File registry_dll;
	
	private boolean local_integration = false;
	
	@Override
	protected void _init() throws FileNotFoundException {
		if (OSTools.isWindows()) {
			jshortcut_dll = FilesOp.exportJarResource("WindowsLibs/32/jshortcut.dll");
			registry_dll = FilesOp.exportJarResource("WindowsLibs/32/ICE_JNIRegistry.dll");
			LibraryLoader.addToJavaLibraryPath(registry_dll.getParentFile());
		}

		((OvdClientApplicationsApplet)this.ovd).setPerformDesktopIntegration(this.local_integration);
		
		this.spooler = new SpoolOrder((OvdClientApplicationsApplet) this.ovd);
		this.serverApps = Collections.synchronizedMap(new HashMap<Integer, ArrayList<Application>>());
		
		Constants.JAVA_LAUNCHER = FilesOp.exportJarResource("ovdIntegratedLauncher.jar").getPath();
	}
	
	@Override
	protected void _start() {	
		this.spooler.start();
	}
	
	@Override
	protected void _stop() {
		if (this.spooler != null && this.spooler.isAlive())
			this.spooler.interrupt();
		if (Constants.JAVA_LAUNCHER != null)
			new File(Constants.JAVA_LAUNCHER).delete();
		if (jshortcut_dll != null && jshortcut_dll.exists())
			jshortcut_dll.delete();
		if (registry_dll != null && registry_dll.exists())
			registry_dll.delete();
	}
	
	@Override
	protected void _destroy() {
		this.sm_host = null;
		this.spooler = null;
		this.serverApps = null;
	}

	@Override
	protected int getMode() {
		return Properties.MODE_REMOTEAPPS;
	}
	
	@Override
	protected void readParameters() throws Exception {
		String[] address = getParameterNonEmpty("sessionmanager").split(":");
		this.sm_host = address[0];
		this.sm_port = Integer.parseInt(address[1]);
		
		String param = this.getParameter("local_integration");
		if (param != null && param.equalsIgnoreCase("true"))
			this.local_integration = true;
	}
	
	@Override
	protected OvdClient createOvdClient(Properties properties) {
		if (properties.isPrinters()) {
			SeamlessFrame.focusManager = focusManager;
			SeamlessPopup.focusManager = focusManager;
		}
		
		properties.setDesktopIcons(this.local_integration);
		SessionManagerCommunication smComm = new SessionManagerCommunication(this.sm_host, this.sm_port, true);
		return new OvdClientApplicationsApplet(smComm, properties, this);
	}
	
	// ********
	// Methods called by Javascript
	// ********

	/**
	 * retrieve all informations about a server. This function is optional and must be called before connecting.
	 * @param JSId
	 * 		Javascript ID of the server
	 * @param xml
	 * 		XML information about the server
	 */
	public void serverPrepare(int JSId, String xml) {
		try {
			DocumentBuilder domBuilder = DocumentBuilderFactory.newInstance().newDocumentBuilder();
			Document doc = domBuilder.parse(new ByteArrayInputStream(xml.getBytes()));
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
		server.applications = serverApps.get(JSId);;
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
	
}
