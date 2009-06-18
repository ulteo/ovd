package org.ulteo;

import org.ulteo.HttpClient;
import java.net.*;


import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;

import org.w3c.dom.Document;
import org.w3c.dom.*;
import org.xml.sax.InputSource;

import java.io.StringReader;

import org.sshvnc.Viewer;



public class OvdClient implements Runnable {
    protected URL sm_url;
    protected String login;
    protected String password;
    
    protected URL aps_url;

    protected HttpClient hc = null;

    protected ArgParser ap;


    public static void usage() {
	System.err.println("Usage: XXX sessionmanager_url login password");
    }

    public OvdClient(URL sm_url, String login, String password) {
	this.sm_url = sm_url;
	this.login = login;
	this.password = password;

	this.ap = new ArgParser();
    }

    public boolean StartSession() throws Exception{
	hc = new HttpClient(this.sm_url);
	
	int r = hc.request_POST(this.sm_url.getPath() + "/startsession.php", "login=" + this.login + "&password=" + this.password);

	this.aps_url = hc.server.getURL();
	return true;
    }


    public void run() {
	while (true) {
	    try {
		this.getSessionStatus();
		Thread.currentThread().sleep(1000);

	    } catch(Exception e) {
		System.err.println("is going to end ...");
		return;

	    }
	}
    }

    protected int getSessionStatus() throws Exception {
	int e = hc.request_GET("/whatsup.php", "");

	String response = hc.getResponseText();
	DocumentBuilder parser = DocumentBuilderFactory.newInstance().newDocumentBuilder();
	Document document = parser.parse(new InputSource(new StringReader(response)));
	NodeList n = document.getElementsByTagName("session");
	if (n.getLength() < 1)
	    return -1;
	
	Node m = n.item(0);
	NamedNodeMap t = m.getAttributes();
	Node g = t.getNamedItem("status");
	String rr = g.getNodeValue();

	return (new Integer(rr)).intValue();
	//	System.out.println("Node value: "+rr);
	//	    return 0;
    }


    protected boolean StartSession_aps() throws Exception {
	int e = hc.request_GET("/start.php", "width=800&height=600");

	return e==200;
    }

 

    protected boolean getAccess() throws Exception {
	int e = hc.request_GET("/access.php", "");

	
	String response = hc.getResponseText();
	DocumentBuilder parser = DocumentBuilderFactory.newInstance().newDocumentBuilder();
	Document document = parser.parse(new InputSource(new StringReader(response)));
	NodeList nl = document.getElementsByTagName("ssh");

	Node n = nl.item(0);
	NamedNodeMap t = n.getAttributes();
	this.ap.ssh_host = t.getNamedItem("host").getNodeValue();
	this.ap.ssh_login = t.getNamedItem("user").getNodeValue();
	this.ap.ssh_password = t.getNamedItem("passwd").getNodeValue();
	this.ap.ssh_password = Utils.DecryptString(this.ap.ssh_password);

	for (Node n2=n.getFirstChild(); n2!=null; n2 = n2.getNextSibling()) {
	    if (n2.getNodeName() != "port")
		continue;

	    this.ap.ssh_port = n2.getTextContent();
	    break;
	}


	nl = document.getElementsByTagName("vnc");

	n = nl.item(0);
	t = n.getAttributes();
	this.ap.vnc_password = t.getNamedItem("passwd").getNodeValue();
	this.ap.vnc_password = Utils.DecryptEncVNCString(this.ap.vnc_password);
	this.ap.vnc_port = t.getNamedItem("port").getNodeValue();

	return e==200;
    }

    public void launchViewer() {
	/*System.out.println("Launching with following parameters:");
	System.out.println("\tssh_host: "+this.ssh_host);
	System.out.println("\tssh_port: "+this.ssh_port);
	System.out.println("\tssh_login: "+this.ssh_login);
	System.out.println("\tssh_password: "+this.ssh_password);
	System.out.println("\tvnc_password: "+this.vnc_password);
	System.out.println("\tvnc_port: "+this.vnc_port);*/

	Viewer v = new Viewer();
	v.arg_parser = this.ap;
	v.readParameters();

	v.init();
	v.start();
    }


    public static void main(String[] args) {
	URL url = null;
	String login = "";
	String password = "";

	try {
	    url = new URL(args[0]);
	    login = args[1];
	    password = args[2];
	} catch(java.lang.ArrayIndexOutOfBoundsException e) {
	    usage();
	    System.exit(1);
	} catch (MalformedURLException e) {
	    System.err.println("N'est pas une url valide !!");
	    usage();
	    System.exit(1);
	}

	OvdClient ovdclient = new OvdClient(url, login, password);

	try {
	    ovdclient.StartSession();


	    int status = -1;
	    while(status!=0 && status!= 10) {
		status = ovdclient.getSessionStatus();
		System.out.println("Status: "+status);

		try{
		    Thread.currentThread().sleep(1000);//sleep for 1000 ms
		    //		} catch(IntrerruptedException ie) {
		} catch(Exception ie) {
		    //If this thread was intrrupted by nother thread 
		}
	    }

	    ovdclient.StartSession_aps();

	    status = 0;
	    while(status!=2) {
		status = ovdclient.getSessionStatus();
		System.out.println("Status: "+status);

		try{
		    Thread.currentThread().sleep(1000);//sleep for 1000 ms
		} catch(Exception ie) {}
	    }

	    ovdclient.getAccess();

	    Thread t1 = new Thread(ovdclient);
	    t1.start();

	    ovdclient.launchViewer();


	} catch(Exception e) {
	    System.out.println("excpet !!");
	    e.printStackTrace();
	}
	System.out.println("Fini !");
    }
}