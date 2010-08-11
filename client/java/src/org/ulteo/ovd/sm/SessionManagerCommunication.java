/*
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
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

import com.sun.org.apache.xerces.internal.dom.DOMImplementationImpl;
import com.sun.org.apache.xerces.internal.parsers.DOMParser;
import com.sun.org.apache.xml.internal.serialize.OutputFormat;
import com.sun.org.apache.xml.internal.serialize.XMLSerializer;
import java.awt.Image;
import java.awt.Toolkit;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStreamWriter;
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

import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerException;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;
import org.w3c.dom.DOMImplementation;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;
import org.xml.sax.InputSource;
import sun.awt.image.URLImageSource;


public class SessionManagerCommunication {
	public static final String SESSION_MODE_REMOTEAPPS = "applications";
	public static final String SESSION_MODE_DESKTOP = "desktop";

	private static final String WEBSERVICE_ICON = "icon.php";
	private static final String WEBSERVICE_START_SESSION = "start.php";
	private static final String WEBSERVICE_EXTERNAL_APPS = "remote_apps.php";
	private static final String WEBSERVICE_SESSION_STATUS = "session_status.php";
	private static final String WEBSERVICE_LOGOUT = "logout.php";

	public static final String FIELD_LOGIN = "login";
	public static final String FIELD_PASSWORD = "password";
	public static final String FIELD_TOKEN = "token";
	public static final String FIELD_SESSION_MODE = "session_mode";
	public static final String FIELD_ICON_ID = "id";

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

	public boolean askForSession(String login, String password, Properties request) throws SessionManagerException {
		if (login == null || password == null || request == null || this.requestProperties != null)
 			return false;
		
		this.requestProperties = request;

		HashMap<String,String> params = new HashMap<String,String>();
		if (request.getMode() == Properties.MODE_DESKTOP)
			params.put(FIELD_SESSION_MODE, SESSION_MODE_DESKTOP);
		else if (request.getMode() == Properties.MODE_REMOTEAPPS)
			params.put(FIELD_SESSION_MODE, SESSION_MODE_REMOTEAPPS);

		params.put(FIELD_LOGIN, login);
		params.put(FIELD_PASSWORD, password);
		// todo : other options

 		Document response = (Document) this.askWebservice(WEBSERVICE_START_SESSION, CONTENT_TYPE_FORM, REQUEST_METHOD_POST, concatParams(params), true);
 		if (response == null)
 			return false;

 		return this.parseStartSessionResponse(response);
	}

	public boolean askForSession(String token, Properties request) throws SessionManagerException {
		if (token == null || request == null || this.requestProperties != null)
			return false;

		this.requestProperties = request;

		HashMap<String,String> params = new HashMap<String,String>();
		if (request.getMode() == Properties.MODE_DESKTOP)
			params.put(FIELD_SESSION_MODE, SESSION_MODE_DESKTOP);
		else if (request.getMode() == Properties.MODE_REMOTEAPPS)
			params.put(FIELD_SESSION_MODE, SESSION_MODE_REMOTEAPPS);

		params.put(FIELD_TOKEN, token);

		Document response = (Document) this.askWebservice(WEBSERVICE_EXTERNAL_APPS, CONTENT_TYPE_FORM, REQUEST_METHOD_POST, concatParams(params), true);

		if (response == null)
			return false;

 		return this.parseStartSessionResponse(response);
	}

	public boolean askForLogout() throws SessionManagerException {
		DOMImplementation domImpl = DOMImplementationImpl.getDOMImplementation();
		Document request = domImpl.createDocument(null, "logout", null);

		Element logout = request.getDocumentElement();
		logout.setAttribute("mode", "logout");

		Document response = (Document) this.askWebservice(WEBSERVICE_LOGOUT, CONTENT_TYPE_XML, REQUEST_METHOD_POST, request, true);

		if (response == null)
			return false;

		return this.parseLogoutResponse(response);
	}

	public String askForSessionStatus() throws SessionManagerException {
		DOMImplementation domImpl = DOMImplementationImpl.getDOMImplementation();
		Document request = domImpl.createDocument(null, "session", null);

		Element session = request.getDocumentElement();
		session.setAttribute("id", "");
		session.setAttribute("status", "");

		Document response = (Document) this.askWebservice(WEBSERVICE_SESSION_STATUS, CONTENT_TYPE_XML, REQUEST_METHOD_POST, request, false);

		if (response == null)
			return null;

		return this.parseSessionStatusResponse(response);
	}

	public ImageIcon askForIcon(String appId) throws SessionManagerException {
		HashMap<String, String> params = new HashMap<String, String>();
		params.put(FIELD_ICON_ID, appId);

		return (ImageIcon) this.askWebservice(WEBSERVICE_ICON+"?"+concatParams(params), CONTENT_TYPE_FORM, REQUEST_METHOD_GET, null, false);
	}

	private Object askWebservice(String webservice, String content_type, String method, Object data, boolean showLog) throws SessionManagerException {
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
				// An all-trusting TrustManager for SSL URL validation
				TrustManager[] trustAllCerts = new TrustManager[] {
					new X509TrustManager() {
						public X509Certificate[] getAcceptedIssuers() {
							return null;
						}
						public void checkServerTrusted(X509Certificate[] certs, String authType) throws CertificateException {
							return;
						}
						public void checkClientTrusted(X509Certificate[] certs, String authType) throws CertificateException {
							return;
						}
					}
				};

				// An all-trusting HostnameVerifier for SSL URL validation
				HostnameVerifier trustAllHosts = new HostnameVerifier() {
					public boolean verify(String hostname, SSLSession session) {
						return true;
					}
				};
				
				SSLContext sc = SSLContext.getInstance("SSL");
				sc.init(null, trustAllCerts, null);
				SSLSocketFactory factory = sc.getSocketFactory();
				((HttpsURLConnection)connexion).setSSLSocketFactory(factory);

				((HttpsURLConnection)connexion).setHostnameVerifier(trustAllHosts);
			}
			connexion.setDoInput(true);
			connexion.setDoOutput(true);
			connexion.setRequestProperty("Content-type", content_type);
			for (String cookie : this.cookies) {
				connexion.setRequestProperty("Cookie", cookie);
			}

			connexion.setAllowUserInteraction(true);
			connexion.setRequestMethod(method);
			OutputStreamWriter out = new OutputStreamWriter(connexion.getOutputStream());

			if (data instanceof String) {
				out.write((String) data);
			}
			else if (data instanceof Document) {
				Document request = (Document) data;

				OutputFormat outFormat = new OutputFormat(request);
				XMLSerializer serializer = new XMLSerializer(out, outFormat);
				serializer.serialize(request);

				if (showLog)
					this.dumpXML(request, "Receiving XML:");
			}
			else if (data != null) {
				System.err.println("Cannot send "+ data.getClass().getName() +" data to session manager webservices");
				return obj;
			}

			out.flush();
			out.close();

			int r = connexion.getResponseCode();
			String res = connexion.getResponseMessage();
			String contentType = connexion.getContentType();

			if (showLog)
				System.out.println("Response "+r+ " ==> "+res+ " type: "+contentType);

			if (r == HttpURLConnection.HTTP_OK) {
				InputStream in = connexion.getInputStream();

				if (contentType.startsWith(CONTENT_TYPE_XML)) {
					DOMParser parser = new DOMParser();
					InputSource source = new InputSource(in);

					parser.parse(source);
					in.close();

					obj = parser.getDocument();

					if (showLog)
						this.dumpXML((Document) obj, "Receiving XML:");
				}
				else if (contentType.startsWith(CONTENT_TYPE_PNG)) {
					URLImageSource imgSrc = (URLImageSource) connexion.getContent();
					Image img = Toolkit.getDefaultToolkit().createImage(imgSrc);
					obj = new ImageIcon(img);
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
			else {
				System.err.println("Invalid response:\n\tResponse code: "+ r +"\n\tResponse message: "+ res +"\n\tContent type: "+ contentType);
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

	private boolean parseStartSessionResponse(Document document) throws SessionManagerException {
		Element rootNode = document.getDocumentElement();

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
			if (rootNode.getAttribute("mode").equals(SESSION_MODE_DESKTOP))
				mode = Properties.MODE_DESKTOP;
			else if (rootNode.getAttribute("mode").equals(SESSION_MODE_REMOTEAPPS))
				mode = Properties.MODE_REMOTEAPPS;
			if (mode == Properties.MODE_ANY)
				throw new Exception("bad xml: no valid session mode");

			Properties response = new Properties(mode);

			if (rootNode.hasAttribute("multimedia"))
				response.setMultimedia(true);
			if (rootNode.hasAttribute("redirect_client_printers"))
				response.setMultimedia(true);

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

				ServerAccess server = new ServerAccess(serverNode.getAttribute("fqdn"), 3389,
							serverNode.getAttribute("login"), serverNode.getAttribute("password"));

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
}
