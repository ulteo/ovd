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

package org.ulteo.ovd.sm;

import java.awt.GraphicsEnvironment;
import java.awt.Rectangle;
//import java.io.BufferedReader;
//import java.io.ByteArrayInputStream;
import java.io.DataInputStream;
import java.io.IOException;
import java.io.InputStream;
//import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.util.ArrayList;
import java.util.List;
import java.util.logging.Level;
import java.util.logging.Logger;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;
import net.propero.rdp.Common;
import net.propero.rdp.Options;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpConnection;
import org.ulteo.ovd.Application;
import org.ulteo.rdp.Connection;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.seamless.SeamlessChannel;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;
import org.xml.sax.SAXException;


public class SessionManagerCommunication {
	public static final String SESSION_MODE_REMOTEAPPS = "portal";
	public static final String SESSION_MODE_DESKTOP = "desktop";

	private String sm = null;
	private ArrayList<Connection> connections = null;
	private String sessionMode = null;
	private String requestMode = null;
	private String sessionId = null;
	private String base_url;

	public SessionManagerCommunication(String sm_) throws Exception {
		this.connections = new ArrayList<Connection>();
		this.sm = sm_;
		this.base_url = "http://"+this.sm+"/sessionmanager/";
	}

	public String getSessionMode() {
		return this.sessionMode;
	}

	private static String makeStringForPost(List<String> listParameter) {
		String listConcat = "";
		if(listParameter.size() > 0) {
			listConcat += listParameter.get(0);
			for(int i = 1 ; i < listParameter.size() ; i++) {
				listConcat += "&";
				listConcat += listParameter.get(i);
			}
		}
		return listConcat;
	}

	public boolean askForSession(String login, String password, String mode) {
		this.requestMode = mode;
		boolean ret = false;
		HttpURLConnection connexion = null;
		
		try {
			URL url = new URL(this.base_url+"startsession.php");

			System.out.println("Connexion a l'url ... "+url);
			connexion = (HttpURLConnection) url.openConnection();
			connexion.setDoInput(true);
			connexion.setDoOutput(true);
			connexion.setRequestProperty("Content-type", "application/x-www-form-urlencoded");

			connexion.setAllowUserInteraction(true);
			connexion.setRequestMethod("POST");

			OutputStreamWriter out = new OutputStreamWriter(connexion.getOutputStream());

			List<String> listParameter = new ArrayList<String>();
			listParameter.add("login="+login);
			listParameter.add("password="+password);
			listParameter.add("session_mode="+this.requestMode);

			out.write(makeStringForPost(listParameter));
			out.flush();
			out.close();

			int r = connexion.getResponseCode();
			String res = connexion.getResponseMessage();
			String contentType = connexion.getContentType();

			System.out.println("Response "+r+ " ==> "+res+ " type: "+contentType);

			if (r == HttpURLConnection.HTTP_OK && contentType.startsWith("text/xml")) {
				DataInputStream in = new DataInputStream(connexion.getInputStream());
				ret = this.parse(in);
			}
			else {
				System.err.println("Invalid response");
			}

		}
		catch (Exception e) {
			System.err.println("Invalid session initialisation format");
			e.printStackTrace();
		}
		finally {
			connexion.disconnect();
		}

		return ret;
	}

	private boolean parse(InputStream in) {
		/* BEGIN DEBUG
		BufferedReader b = new BufferedReader(new InputStreamReader(in));

		String line;
		String content = "";
		try {
			while ((line = b.readLine()) != null) {
				content += line;
			}
		} catch (IOException ex) {
			Logger.getLogger(SessionManagerCommunication.class.getName()).log(Level.SEVERE, null, ex);
		}

		content = content.replaceFirst(".*<?xml", "<?xml");
		System.out.println("XML content: "+content);

		in = new ByteArrayInputStream(content.getBytes());
		/* END DEBUG */

		Document document = null;

		Rectangle dim = null;
		dim = GraphicsEnvironment.getLocalGraphicsEnvironment().getMaximumWindowBounds();
		Logger.getLogger(SessionManagerCommunication.class.getName()).log(Level.INFO, "ScreenSize: "+dim);

		try {
			document = DocumentBuilderFactory.newInstance().newDocumentBuilder().parse(in);
		} catch (SAXException e) {
			e.printStackTrace();
			return false;
		} catch (IOException e) {
			e.printStackTrace();
			return false;
		} catch (ParserConfigurationException e) {
			e.printStackTrace();
			return false;
		}

		NodeList ns = document.getElementsByTagName("error");
		Element ovd_node;
		if (ns.getLength() == 1) {
			ovd_node = (Element)ns.item(0);
			Logger.getLogger(SessionManagerCommunication.class.getName()).log(Level.SEVERE, "("+ovd_node.getAttribute("id")+") "+ovd_node.getAttribute("message"));
			return false;
		}

		ns = document.getElementsByTagName("session");
		if (ns.getLength() == 0) {
			Logger.getLogger(SessionManagerCommunication.class.getName()).log(Level.SEVERE, "Bad XML: err 1");
			return false;
		}
		ovd_node = (Element)ns.item(0);

		this.sessionId = ovd_node.getAttribute("id");
		this.sessionMode = ovd_node.getAttribute("mode");

		if (! this.sessionMode.equalsIgnoreCase(this.requestMode)) {
			Logger.getLogger(SessionManagerCommunication.class.getName()).log(Level.SEVERE, "The session manager do not authorize "+this.requestMode+" session mode.");
			return false;
		}

		ns = ovd_node.getElementsByTagName("server");
		if (ns.getLength() == 0) {
			Logger.getLogger(SessionManagerCommunication.class.getName()).log(Level.SEVERE, "Bad XML: err 2");
			return false;
		}
		Element server;
		for (int i = 0; i < ns.getLength(); i++) {
			RdpConnection rc = null;

			server = (Element)ns.item(i);
			NodeList appsList = server.getElementsByTagName("application");
			if (appsList.getLength() == 0) {
				Logger.getLogger(SessionManagerCommunication.class.getName()).log(Level.SEVERE, "Bad XML: err 3");
				return false;
			}
			Element appItem = null;

			Options opt = new Options();
			opt.hostname = server.getAttribute("fqdn");
			opt.username = server.getAttribute("login");
			opt.password = server.getAttribute("password");
			opt.width = (int)dim.width;
			opt.height = (int)dim.height;
			opt.set_bpp(24);

			Common common = new Common();
			OvdAppChannel appChannel = null;

			try {
				if (this.sessionMode.equalsIgnoreCase("desktop")) {
					rc = new RdpConnection(opt, common);
				}
				else if (this.sessionMode.equalsIgnoreCase("portal")) {
					rc = new RdpConnection(opt, common, new org.ulteo.rdp.seamless.SeamlessChannel(opt, common));
					appChannel = new OvdAppChannel(opt, common);
					rc.addChannel(appChannel);
				}
			} catch (RdesktopException e) {
				Logger.getLogger(SessionManagerCommunication.class.getName()).log(Level.SEVERE, "Unable to prepare an RDP connection to "+server.getAttribute("hostname"));
				return false;
			}
			
			Connection co = new Connection();
			co.common = common;
			co.options = opt;
			co.connection = rc;
			co.channel = appChannel;

			for (int j = 0; j < appsList.getLength(); j++) {
				appItem = (Element)appsList.item(j);
				NodeList mimeList = appItem.getElementsByTagName("mime");
				ArrayList<String> mimeTypes = new ArrayList<String>();

				if (mimeList.getLength() > 0) {
					Element mimeItem = null;
					for (int k = 0; k < mimeList.getLength(); k++) {
						mimeItem = (Element)mimeList.item(k);
						mimeTypes.add(mimeItem.getAttribute("type"));
					}
				}

				Application app = null;
				try {
					String iconWebservice = "http://"+this.sm+":1111/icon.php?id="+appItem.getAttribute("id");
					app = new Application(co, Integer.parseInt(appItem.getAttribute("id")), appItem.getAttribute("name"), appItem.getAttribute("command"), mimeTypes, new URL(iconWebservice));
				} catch (NumberFormatException e) {
					e.printStackTrace();
					return false;
				} catch (MalformedURLException e) {
					e.printStackTrace();
					return false;
				}
				if (app != null)
					rc.addApp(app);
			}

			this.connections.add(co);
		}
		return true;
	}

	public ArrayList<Connection> getConnections() {
		return this.connections;
	}
}
