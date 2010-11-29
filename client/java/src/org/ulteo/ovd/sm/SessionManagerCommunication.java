/*
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
 * Author David LECHEVALIER <david@ulteo.com> 2010 
 * Author Arnaud LEGRAND <arnaud@ulteo.com> 2010
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

import java.awt.Image;
import java.awt.Toolkit;
import java.io.BufferedInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStreamWriter;
import java.io.StringWriter;
import java.net.HttpURLConnection;
import java.net.InetAddress;
import java.net.URL;
import java.security.cert.CertificateException;
import java.security.cert.X509Certificate;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.concurrent.CopyOnWriteArrayList;
import java.util.logging.Level;
import java.util.logging.Logger;
import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLContext;
import javax.net.ssl.SSLSession;
import javax.net.ssl.SSLSocketFactory;
import javax.net.ssl.TrustManager;
import javax.net.ssl.X509TrustManager;
import javax.swing.ImageIcon;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerConfigurationException;
import javax.xml.transform.TransformerException;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;
import org.xml.sax.InputSource;



public class SessionManagerCommunication implements HostnameVerifier, X509TrustManager {
	public static final String SESSION_MODE_REMOTEAPPS = "applications";
	public static final String SESSION_MODE_DESKTOP = "desktop";

	private static final String WEBSERVICE_ICON = "icon.php";
	private static final String WEBSERVICE_START_SESSION = "start.php";
	private static final String WEBSERVICE_EXTERNAL_APPS = "remote_apps.php";
	private static final String WEBSERVICE_SESSION_STATUS = "session_status.php";
	private static final String WEBSERVICE_NEWS = "news.php";
	private static final String WEBSERVICE_LOGOUT = "logout.php";

	public static final String FIELD_LOGIN = "login";
	public static final String FIELD_PASSWORD = "password";
	public static final String FIELD_TOKEN = "token";
	public static final String FIELD_SESSION_MODE = "session_mode";
	public static final String FIELD_ICON_ID = "id";

	public static final String NODE_SETTINGS = "settings";
	public static final String NODE_SETTING = "setting";

	public static final String FIELD_NAME = "name";
	public static final String FIELD_VALUE = "value";

	public static final String NAME_DESKTOP_ICONS = "desktop_icons";

	private static final String CONTENT_TYPE_FORM = "application/x-www-form-urlencoded";
	private static final String CONTENT_TYPE_XML = "text/xml";
	private static final String CONTENT_TYPE_PNG = "image/png";

	private static final String REQUEST_METHOD_POST = "POST";
	private static final String REQUEST_METHOD_GET = "GET";

	public static final String SESSION_STATUS_UNKNOWN = "unknown";
	public static final String SESSION_STATUS_ERROR = "error";
	public static final String SESSION_STATUS_INIT = "init";
	public static final String SESSION_STATUS_INITED = "ready";
	public static final String SESSION_STATUS_ACTIVE = "logged";
	public static final String SESSION_STATUS_INACTIVE = "disconnected";
	public static final String SESSION_STATUS_WAIT_DESTROY = "wait_destroy";
	public static final String SESSION_STATUS_DESTROYED = "destroyed";

	private static final int TIMEOUT = 2000;

	private String host = null;
	private boolean use_https = false;

	private String base_url = null;

	private Properties requestProperties = null;
	private Properties responseProperties = null;
	private List<ServerAccess> servers = null;

	private CopyOnWriteArrayList<Callback> callbacks = null;

	private List<String> cookies = null;

	private String moreInfos_lastResponse = "no more information";

	public SessionManagerCommunication(String host_, boolean use_https_) {
		this.servers = new  ArrayList<ServerAccess>();
		this.callbacks = new CopyOnWriteArrayList<Callback>();

		this.cookies = new ArrayList<String>();
		this.host = host_;
		this.use_https = use_https_;

		this.base_url = this.makeUrl("");

	}

	private boolean isReachable() {
		try {
			InetAddress target = InetAddress.getByName(this.host);

			return target.isReachable(TIMEOUT);
		} catch (IOException ex) {
			Logger.getLogger(SessionManagerCommunication.class.getName()).log(Level.SEVERE, null, ex);
		}
		return false;
	}

	private String makeUrl(String service) {
		return (this.use_https ? "https" : "http") + "://" + this.host + "/ovd/client/" + service;
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

	private static String concatParams(HashMap<String,String> params) {
		List<String> listParameter = new ArrayList<String>();
		for (String name : params.keySet()) {
			listParameter.add(name+"="+params.get(name));
		}

		return makeStringForPost(listParameter);
	}
	
	public static Document getNewDocument() {
		DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
		DocumentBuilder builder;
		try {
			builder = factory.newDocumentBuilder();
		} catch (ParserConfigurationException e) {
			e.printStackTrace();
			return null;
		}
		
		return builder.newDocument();
	}
	
	public static String Document2String(Document document) {
		DOMSource domSource = new DOMSource(document);
		StringWriter writer = new StringWriter();
		StreamResult result = new StreamResult(writer);
		TransformerFactory tf = TransformerFactory.newInstance();
		Transformer transformer;
		try {
			transformer = tf.newTransformer();
			transformer.transform(domSource, result);
		} catch (TransformerConfigurationException e) {
			e.printStackTrace();
			return null;
		} catch (TransformerException e) {
			e.printStackTrace();
			return null;
		}
		
		return writer.toString(); 
	}
	

	public boolean askForSession(String login, String password, Properties request) throws SessionManagerException {
		if (login == null || password == null || request == null || this.requestProperties != null)
 			return false;
		
		this.requestProperties = request;
		
		Document doc = getNewDocument();
		if (doc == null)
			return false;
		
		Element session = doc.createElement("session");
		doc.appendChild(session);
		
		if (request.getMode() == Properties.MODE_DESKTOP)
			session.setAttribute("mode", SESSION_MODE_DESKTOP);
		else if (request.getMode() == Properties.MODE_REMOTEAPPS)
			session.setAttribute("mode", SESSION_MODE_REMOTEAPPS);

		Element user = doc.createElementNS(null, "user");
		user.setAttribute("login", login);
		user.setAttribute("password", password);

		session.appendChild(user);
		
		session.setAttribute("language", request.getLang());
		session.setAttribute("timezone", request.getTimeZone());
		
		String data = Document2String(doc);
		if (data == null)
			return false;
		
		Object obj = this.askWebservice(WEBSERVICE_START_SESSION, CONTENT_TYPE_XML, REQUEST_METHOD_POST, data, true);
		if (! (obj instanceof Document) || obj == null)
			return false;
 		
 		return this.parseStartSessionResponse((Document) obj);
	}

	/**
	 * If using Apache auth module (NTLM, Kerberos, ...), Java use directly Windows credentials
	 */
	public boolean askForSession(Properties request) throws SessionManagerException {
		if (request == null || this.requestProperties != null)
			return false;

		this.requestProperties = request;
		
		Document doc = getNewDocument();
		if (doc == null)
			return false;
		
		Element session = doc.createElement("session");
		doc.appendChild(session);
		
		if (request.getMode() == Properties.MODE_DESKTOP)
			session.setAttribute("mode", SESSION_MODE_DESKTOP);
		else if (request.getMode() == Properties.MODE_REMOTEAPPS)
			session.setAttribute("mode", SESSION_MODE_REMOTEAPPS);
		
		String data = Document2String(doc);
		if (data == null)
			return false;
			
		Object obj = this.askWebservice(WEBSERVICE_START_SESSION, CONTENT_TYPE_XML, REQUEST_METHOD_POST, data, true);
		if (! (obj instanceof Document) || obj == null)
			return false;

 		return this.parseStartSessionResponse((Document) obj);
	}
	
	public boolean askForExternalAppsSession(String token, Properties request) throws SessionManagerException {
		if (token == null || request == null || this.requestProperties != null)
			return false;

		this.requestProperties = request;

		HashMap<String,String> params = new HashMap<String,String>();
		if (request.getMode() == Properties.MODE_DESKTOP)
			params.put(FIELD_SESSION_MODE, SESSION_MODE_DESKTOP);
		else if (request.getMode() == Properties.MODE_REMOTEAPPS)
			params.put(FIELD_SESSION_MODE, SESSION_MODE_REMOTEAPPS);

		params.put(FIELD_TOKEN, token);

		Object obj = this.askWebservice(WEBSERVICE_EXTERNAL_APPS, CONTENT_TYPE_FORM, REQUEST_METHOD_POST, concatParams(params), true);
		if (! (obj instanceof Document) || obj == null)
			return false;

 		return this.parseStartSessionResponse((Document) obj);
	}

	public boolean askForLogout() throws SessionManagerException {
		Document doc = getNewDocument();
		if (doc == null)
			return false;
		
		Element logout = doc.createElement("logout");
		logout.setAttribute("mode", "logout");
		doc.appendChild(logout);
		
		String data = Document2String(doc);
		if (data == null)
			return false;
		
		Object obj = this.askWebservice(WEBSERVICE_LOGOUT, CONTENT_TYPE_XML, REQUEST_METHOD_POST, data, true);
		if (! (obj instanceof Document) || obj == null)
			return false;

 		return this.parseLogoutResponse((Document) obj);
	}

	public String askForSessionStatus() throws SessionManagerException {
		Document doc = getNewDocument();
		if (doc == null)
			return null;
		
		Object obj = this.askWebservice(WEBSERVICE_SESSION_STATUS, CONTENT_TYPE_FORM, REQUEST_METHOD_POST, null, false);
		if (! (obj instanceof Document) || obj == null)
			return null;
		
 		return this.parseSessionStatusResponse((Document) obj);
	}

	public List<News> askForNews() throws SessionManagerException {
		Object obj = this.askWebservice(WEBSERVICE_NEWS, CONTENT_TYPE_FORM, REQUEST_METHOD_POST, null, false);
		if (! (obj instanceof Document) || obj == null)
			return null;
		
		return this.parseNewsResponse((Document) obj);
	}

	public ImageIcon askForIcon(String appId) throws SessionManagerException {
		HashMap<String, String> params = new HashMap<String, String>();
		params.put(FIELD_ICON_ID, appId);

		Object obj = this.askWebservice(WEBSERVICE_ICON+"?"+concatParams(params), CONTENT_TYPE_FORM, REQUEST_METHOD_GET, null, true);
		if (! (obj instanceof ImageIcon) || obj == null)
			return null;

		ImageIcon icon = (ImageIcon) obj;
		if (icon.getIconHeight() <= 0 || icon.getIconWidth() <= 0)
			return null;
		
		return icon;
	}

	private Object askWebservice(String webservice, String content_type, String method, String data, boolean showLog) throws SessionManagerException {
		Object obj = null;
		HttpURLConnection connexion = null;

		if (! this.isReachable()) {
			throw new SessionManagerException("Host is unreachable");
		}
		
		try {
			URL url = new URL(this.base_url+webservice);

			if (showLog)
				System.out.println("Connecting URL ... "+url);
			connexion = (HttpURLConnection) url.openConnection();

			if (this.use_https) {		
				SSLContext sc = SSLContext.getInstance("SSL");
				sc.init(null, new TrustManager[] { this }, null);
				SSLSocketFactory factory = sc.getSocketFactory();
				((HttpsURLConnection)connexion).setSSLSocketFactory(factory);

				((HttpsURLConnection)connexion).setHostnameVerifier(this);
			}
			connexion.setDoInput(true);
			connexion.setDoOutput(true);
			connexion.setRequestProperty("Content-type", content_type);
			for (String cookie : this.cookies) {
				connexion.setRequestProperty("Cookie", cookie);
			}

			connexion.setAllowUserInteraction(true);
			connexion.setRequestMethod(method);
			if (data != null) {
				OutputStreamWriter out = new OutputStreamWriter(connexion.getOutputStream());
				out.write(data);
				out.flush();
				out.close();
				System.out.println("Send: "+data);
			}
			

			int r = connexion.getResponseCode();
			String res = connexion.getResponseMessage();
			String contentType = connexion.getContentType();

			if (showLog)
				System.out.println("Response "+r+ " ==> "+res+ " type: "+contentType);

			this.moreInfos_lastResponse = "\tResponse code: "+ r +"\n\tResponse message: "+ res +"\n\tContent type: "+ contentType;

			if (r == HttpURLConnection.HTTP_OK) {
				InputStream in = connexion.getInputStream();

				if (contentType.startsWith(CONTENT_TYPE_XML)) {
					DocumentBuilderFactory domFactory = DocumentBuilderFactory.newInstance();
					DocumentBuilder domBuilder = domFactory.newDocumentBuilder();
					
					InputSource source = new InputSource(in);
					Document doc = domBuilder.parse(source);
					
					in.close();
					
					Element rootNode = doc.getDocumentElement();
					if (rootNode.getNodeName().equalsIgnoreCase("error")) {
						for (Callback each : this.callbacks) {
							each.reportError(Integer.parseInt(rootNode.getAttribute("id")), rootNode.getAttribute("message"));
						}
					}
					else {
						obj = doc;
					}

					if (showLog)
						this.dumpXML((Document) obj, "Receiving XML:");
				}
				else if (contentType.startsWith(CONTENT_TYPE_PNG)) {
					int length = connexion.getContentLength();
					byte[] buffer = new byte[length];
					
					BufferedInputStream stream = new BufferedInputStream(in);
					int readed = stream.read(buffer);
					if (readed != length)
						System.err.println("askWebservice: Content-Length return "+length+" but read "+readed+" bytes");					
					
					obj = new ImageIcon(buffer);
				}
				else {
					BufferedInputStream d = new BufferedInputStream(in);
					String buffer = "";
					for( int c = d.read(); c !=-1; c = d.read())
						buffer+=(char)c;
					
					System.out.println("Unknown content-type: "+contentType+"buffer: \n"+buffer+"==\n");
				}

				String headerName=null;
				for (int i=1; (headerName = connexion.getHeaderFieldKey(i))!=null; i++) {
					if (headerName.equals("Set-Cookie")) {
						String cookie = connexion.getHeaderField(i);

						boolean cookieIsPresent = false;
						for (String value : this.cookies) {
							if (value.equalsIgnoreCase(cookie))
								cookieIsPresent = true;
						}
						if (! cookieIsPresent)
							this.cookies.add(cookie);
					}
				}
			}
			else if (r == HttpURLConnection.HTTP_UNAUTHORIZED) {
				for (Callback c : this.callbacks)
					c.reportUnauthorizedHTTPResponse(this.getMoreInfos());
			}
			else if (r == HttpURLConnection.HTTP_NOT_FOUND) {
				for (Callback c : this.callbacks)
					c.reportNotFoundHTTPResponse(this.getMoreInfos());
			}
			else {
				for (Callback c : this.callbacks)
					c.reportError(r, res);
			}
		}
		catch (Exception e) {
			throw new SessionManagerException(e.getMessage());
		}
		finally {
			connexion.disconnect();
		}

		return obj;
	}

	private boolean parseLogoutResponse(Document in) {
		if (in == null)
			return false;

		return true;
	}

	private String parseSessionStatusResponse(Document in) throws SessionManagerException {
		Element rootNode = in.getDocumentElement();

		if (! rootNode.getNodeName().equals("session")) {
			for (Callback c : this.callbacks)
				c.reportBadXml("");

			throw new SessionManagerException("bad xml");
 		}

		String status = null;
		try {
			status = rootNode.getAttribute("status");
 		}
		catch (Exception err) {
			for (Callback c : this.callbacks)
				c.reportBadXml("");

			throw new SessionManagerException("bad xml");
		}

		return status;
	}

	private List<News> parseNewsResponse(Document in) throws SessionManagerException {
		List<News> newsList = new ArrayList<News>();
		
		Element rootNode = in.getDocumentElement();
		
		if (! rootNode.getNodeName().equals("news")) {
			for (Callback c : this.callbacks)
				c.reportBadXml("");
			
			throw new SessionManagerException("bad xml");
		}
		
		NodeList newNodes = rootNode.getElementsByTagName("new");
		for (int j = 0; j < newNodes.getLength(); j++) {
			Element newNode = (Element) newNodes.item(j);
			
			News n = new News(Integer.parseInt(newNode.getAttribute("id")), newNode.getAttribute("title"), newNode.getFirstChild().getTextContent(), Integer.parseInt(newNode.getAttribute("timestamp")));
			
			newsList.add(n);
		}
		
		return newsList;
	}

	private boolean parseStartSessionResponse(Document document) throws SessionManagerException {
		Element rootNode = document.getDocumentElement();
		int serverPort = 3389;

		if (! rootNode.getNodeName().equals("session")) {
			if (rootNode.getNodeName().equals("response")) {
				try {
					String code = rootNode.getAttribute("code");

					for (Callback c : this.callbacks)
						c.reportErrorStartSession(code);

					return false;
				}
				catch(Exception err) {
					System.out.println("Error: bad XML #1");
				}

				for (Callback c : this.callbacks)
					c.reportBadXml("");

				return false;
 			}
		}

		try {
			int mode = Properties.MODE_ANY;
			boolean mode_gateway = false;

			if (rootNode.getAttribute("mode").equals(SESSION_MODE_DESKTOP))
				mode = Properties.MODE_DESKTOP;
			else if (rootNode.getAttribute("mode").equals(SESSION_MODE_REMOTEAPPS))
				mode = Properties.MODE_REMOTEAPPS;
			if (mode == Properties.MODE_ANY)
				throw new Exception("bad xml: no valid session mode");

			Properties response = new Properties(mode);

			if (rootNode.hasAttribute("multimedia")) {
				if (rootNode.getAttribute("multimedia").equals("1"))
					response.setMultimedia(true);
				else 
					response.setMultimedia(false);
			}
			if (rootNode.hasAttribute("redirect_client_printers")) {
				if (rootNode.getAttribute("redirect_client_printers").equals("1"))
					response.setPrinters(true);
				else
					response.setPrinters(false);
			}

			if (rootNode.hasAttribute("mode_gateway")) {
				if (rootNode.getAttribute("mode_gateway").equals("on"))
					mode_gateway = true;
					serverPort = 443;
			}

			if (rootNode.hasAttribute("duration"))
				response.setDuration(Integer.parseInt(rootNode.getAttribute("duration")));

			NodeList settingsNodeList = rootNode.getElementsByTagName(NODE_SETTINGS);
			if (settingsNodeList.getLength() == 1) {
				Element settingsNode = (Element) settingsNodeList.item(0);

				settingsNodeList = settingsNode.getElementsByTagName(NODE_SETTING);
				for (int i = 0; i < settingsNodeList.getLength(); i++) {
					Element setting = (Element) settingsNodeList.item(i);

					String name = setting.getAttribute(FIELD_NAME);
					if (name == null)
						continue;
					String value = setting.getAttribute(FIELD_VALUE);

					if (name.equalsIgnoreCase(NAME_DESKTOP_ICONS)) {
						try {
							int val = Integer.parseInt(value);
							response.setDesktopIcons(val > 0);
						} catch (NumberFormatException ex) {
							org.ulteo.Logger.error("Failed to parse value '"+value+"' (name: "+NAME_DESKTOP_ICONS+")");
						}
					}
				}
			}
			
			NodeList usernameNodeList = rootNode.getElementsByTagName("user");
			if (usernameNodeList.getLength() == 1) {
				response.setUsername(((Element) usernameNodeList.item(0)).getAttribute("displayName"));
			}

			this.responseProperties = response;

			NodeList serverNodes = rootNode.getElementsByTagName("server");
			if (serverNodes.getLength() == 0)
				throw new Exception("bad xml: no server node");

			for (int i = 0; i < serverNodes.getLength(); i++) {
				Element serverNode = (Element) serverNodes.item(i);
				String server_host = this.host;
				
				if (! mode_gateway) {
					server_host = serverNode.getAttribute("fqdn");
				}
				
				ServerAccess server = new ServerAccess(server_host, serverPort,
							serverNode.getAttribute("login"), serverNode.getAttribute("password"));
				
				if (mode_gateway) {
					server.setToken(serverNode.getAttribute("token"));
					server.setModeGateway(true);
				}

				NodeList applicationsNodes = serverNode.getElementsByTagName("application");
				for (int j = 0; j < applicationsNodes.getLength(); j++) {
					Element applicationNode = (Element) applicationsNodes.item(j);

					Application application = new Application(Integer.parseInt(applicationNode.getAttribute("id")),
							applicationNode.getAttribute("name"));
					
					NodeList mimeNodes = applicationNode.getElementsByTagName("mime");
					for (int k = 0; k < mimeNodes.getLength(); k++) {
						Element mimeNode = (Element) mimeNodes.item(k);

						application.addMime(mimeNode.getAttribute("type"));
 					}

					server.addApplication(application);
 				}
				this.servers.add(server);
 			}
 		}
		catch(Exception err) {
			for (Callback c : this.callbacks)
				c.reportBadXml(err.toString());
			return false;
		}

		return true;
	}

	private void dumpXML(Document document, String msg) {
		if (msg != null)
			System.out.println(msg);
		
		try {
			TransformerFactory tFactory = TransformerFactory.newInstance();
			Transformer transformer = tFactory.newTransformer();
			DOMSource source = new DOMSource(document);
			StreamResult result = new StreamResult(System.out);
			transformer.transform(source, result);
		} catch (TransformerException ex) {
			Logger.getLogger(this.getClass().getName()).log(Level.SEVERE, null, ex);
		}
	}

	public void addCallbackListener(Callback c) {
		this.callbacks.add(c);
	}

	public void removeCallbackListener(Callback c) {
		this.callbacks.remove(c);
	}

	public Properties getResponseProperties() {
		return this.responseProperties;
	}

	public List<ServerAccess> getServers() {
		return this.servers;
	}

	public String getMoreInfos() {
		return this.moreInfos_lastResponse;
	}

	@Override
	public boolean verify(String hostname, SSLSession session) {
		return true;
	}

	@Override
	public void checkClientTrusted(X509Certificate[] arg0, String arg1)
			throws CertificateException {
		return;
	}

	@Override
	public void checkServerTrusted(X509Certificate[] arg0, String arg1)
			throws CertificateException {
		return;		
	}

	@Override
	public X509Certificate[] getAcceptedIssuers() {
		return null;
	}
}
