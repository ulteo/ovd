/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
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

package org.ulteo.ovd.client;

import java.io.BufferedInputStream;
import java.io.ByteArrayInputStream;
import java.io.InputStream;
import java.io.OutputStream;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.security.cert.CertificateException;
import java.security.cert.X509Certificate;
import java.util.ArrayList;
import java.util.List;

import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLContext;
import javax.net.ssl.SSLSession;
import javax.net.ssl.SSLSocketFactory;
import javax.net.ssl.TrustManager;
import javax.net.ssl.X509TrustManager;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;

import org.ulteo.Logger;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.xml.sax.InputSource;


public class WebClientCommunication  implements HostnameVerifier, X509TrustManager {
	private static final String WEBSERVICE_CONFIG = "config.php";

	private static final String CONTENT_TYPE_XML = "text/xml";

	@SuppressWarnings("unused")
	private static final String REQUEST_METHOD_POST = "POST";
	private static final String REQUEST_METHOD_GET = "GET";

	private static final int TIMEOUT = 2000;
	private static final int MAX_REDIRECTION_TRY = 5;

	private String base_url = null;

	private List<String> cookies = null;

	public WebClientCommunication(String url) {
		this.cookies = new ArrayList<String>();
		this.base_url = url;
	}
	
	/**
	 * get other config paramter 
	 * @return
	 * 		a Ini File object
	 */
	public Document askForConfig() {
		Object obj = null;
		try {
			obj = this.askWebservice(WEBSERVICE_CONFIG, CONTENT_TYPE_XML, REQUEST_METHOD_GET, null, true);
		} catch (WebClientCommunicationException e) {
			Logger.warn("Cannot get the configuration from the webClient: " + e.getMessage());
		}
		
		if (! (obj instanceof Document))
			return null;

		return (Document) obj;
	}
	
	/**
	 * send a customized request to the Session Manager 
	 * @param webservice
	 * 		the service path to join on the SM
	 * @param content_type
	 * 		the content type expected to receive
	 * @param method
	 * 		specify GET or POST for the HTTP request
	 * @param data
	 * 		some optional data to send during the request
	 * @param showLog
	 * 		verbosity of this function
	 * @return
	 * 		generic {@link Object} result sent by the Session Manager
	 * @throws WebClientCommunicationException
	 * 		generic exception for all failure during the Session manager communication
	 */
	private Object askWebservice(String webservice, String content_type, String method, String data, boolean showLog) throws WebClientCommunicationException {
		try {
			URL url = new URL(this.base_url + webservice);
			return this.askWebservice(url, content_type, method, data, showLog, MAX_REDIRECTION_TRY);
		} catch (MalformedURLException e) {
			throw new WebClientCommunicationException(e.getMessage());
		}
	}
	
	
	/**
	 * send a customized request to the Session Manager 
	 * @param url
	 * 		the complete {@link URL} to join on the SM
	 * @param content_type
	 * 		the content type expected to receive
	 * @param method
	 * 		specify GET or POST for the HTTP request
	 * @param data
	 * 		some optional data to send during the request
	 * @param showLog
	 * 		verbosity of this function
	 * @param retry
	 * 		indicate the maximum of redirected request to make 
	 * @return
	 * 		generic {@link Object} result sent by the Session Manager
	 * @throws WebClientCommunicationException
	 * 		generic exception for all failure during the Session manager communication
	 */
	private Object askWebservice(URL url, String content_type, String method, String data, boolean showLog, int retry) throws WebClientCommunicationException {
		if (showLog)
			Logger.debug("Connecting URL: " + url);
		
		if (retry == 0)
			throw new WebClientCommunicationException(MAX_REDIRECTION_TRY + " redirections has been done without success");
		
		Object obj = null;
		HttpURLConnection connexion = null;

		try {
			connexion = (HttpURLConnection) url.openConnection();
			connexion.setAllowUserInteraction(true);
			connexion.setConnectTimeout(TIMEOUT);
			connexion.setDoInput(true);
			connexion.setDoOutput(true);
			connexion.setInstanceFollowRedirects(false);
			connexion.setRequestMethod(method);
			connexion.setRequestProperty("Content-type", content_type);
			for (String cookie : this.cookies) {
				connexion.setRequestProperty("Cookie", cookie);
			}
			
			if (url.getProtocol().equalsIgnoreCase("https")) {
				SSLContext sc = SSLContext.getInstance("SSL");
				sc.init(null, new TrustManager[] { this }, null);
				SSLSocketFactory factory = sc.getSocketFactory();
				((HttpsURLConnection)connexion).setSSLSocketFactory(factory);
				((HttpsURLConnection)connexion).setHostnameVerifier(this);
			}
			connexion.connect();

			if (data != null) {
				OutputStreamWriter out = new OutputStreamWriter(connexion.getOutputStream());
				out.write(data);
				out.flush();
				out.close();

				try {
					DocumentBuilder domBuilder = DocumentBuilderFactory.newInstance().newDocumentBuilder();
					Document xmlOut = domBuilder.parse(new ByteArrayInputStream(data.getBytes()));
					Logger.debug("Sending XML data: ");
					dumpXML(xmlOut);
				} catch (Exception ex) {
					Logger.debug("Send: "+data);
				}
			}
			
			int r = connexion.getResponseCode();
			String res = connexion.getResponseMessage();
			String contentType = connexion.getContentType();

			if (showLog)
				Logger.debug("Response "+r+ " ==> "+res+ " type: "+contentType);

			if (r == HttpURLConnection.HTTP_OK) {
				InputStream in = connexion.getInputStream();

				if (contentType.startsWith(CONTENT_TYPE_XML)) {
					DocumentBuilder domBuilder = DocumentBuilderFactory.newInstance().newDocumentBuilder();
					Document doc = domBuilder.parse(new InputSource(in));
					
					Element rootNode = doc.getDocumentElement();
					if (rootNode.getNodeName().equalsIgnoreCase("error"))
						Logger.warn("Unable to get user additionnal config : "+rootNode.getAttribute("message"));
					else
						obj = doc;

					if (showLog) {
						Logger.debug("Receiving XML:");
						this.dumpXML((Document) doc);
					}
				}
				else {
					BufferedInputStream d = new BufferedInputStream(in);
					String buffer = "";
					for( int c = d.read(); c !=-1; c = d.read())
						buffer+=(char)c;
					
					Logger.warn("Unknown content-type: "+contentType+"buffer: \n"+buffer+"==\n");
				}
				in.close();

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
			else if (r == HttpURLConnection.HTTP_MOVED_TEMP) {
				URL location = new URL(connexion.getHeaderField("Location"));
				Logger.debug("Redirection: " + location);
				return askWebservice(location, content_type, method, data, showLog, retry-1);
			}
			else if (r == HttpURLConnection.HTTP_UNAUTHORIZED) {
				throw new WebClientCommunicationException("Unauthorized to get "+url+": permission denied");
			}
			else if (r == HttpURLConnection.HTTP_NOT_FOUND) {
				throw new WebClientCommunicationException("Unauthorized to get "+url+": page not found");
			}
			else {
				throw new WebClientCommunicationException("Unauthorized to get "+url+": "+res);
			}
		}
		catch (Exception e) {
			throw new WebClientCommunicationException(e.getMessage());
		}
		finally {
			connexion.disconnect();
		}

		return obj;
	}

	/**
	 * display all XML data in the standard logger output
	 * @param doc
	 * 		XML {@link Document} to display
	 */
	private void dumpXML(Document doc) {
		if (doc == null)
			throw new NullPointerException("Document parameter must not be null");
		
		OutputStream out = Logger.getOutputStream();
		try {
			if (out == null)
				throw new NullPointerException("no output stream is available from Logger");
			
			Transformer transformer = TransformerFactory.newInstance().newTransformer();
			transformer.transform(new DOMSource(doc), new StreamResult(out));

			out.flush();
		} catch (Exception ex) {
			Logger.error("Failed to dump XML data: "+ex.getMessage());
		}
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
