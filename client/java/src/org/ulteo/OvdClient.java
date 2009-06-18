package org.ulteo;

import org.ulteo.HttpClient;
import java.net.*;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;

import javax.swing.*;

import org.w3c.dom.Document;
import org.w3c.dom.*;
import org.xml.sax.InputSource;

import java.io.StringReader;

import java.applet.*;
import java.awt.*;
import java.awt.event.*;

import org.sshvnc.Viewer;

public class OvdClient implements Runnable, ActionListener {
	protected Frame frame;
	protected Panel panel;
	protected TextField tflogin;
	protected TextField tfpassword;
	protected TextField tfsm_url;
	protected Button bsubmit;

    protected URL sm_url;
    protected String login;
    protected String password;

    protected URL aps_url;

	protected Boolean logged_in = false;

    protected HttpClient hc = null;

    protected ArgParser ap;

    public static void usage() {
		System.err.println("Usage: ...");
    }

    public OvdClient() {
		this.ap = new ArgParser();
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

    public boolean StartSession() throws Exception {
		hc = new HttpClient(this.sm_url);

		int r = hc.request_POST(this.sm_url.getPath() + "/startsession.php", "login=" + this.login + "&password=" + this.password);

		this.aps_url = hc.server.getURL();

		return true;
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
		Dimension d = this.frame.getSize();
		Insets i = this.frame.getInsets();

		int width = (int)d.getWidth()-(i.left+i.right);
		int height = (int)d.getHeight()-(i.top+i.bottom);

		int e = hc.request_GET("/start.php", "width="+width+"&height="+height);

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

		System.out.println("VNC password: "+this.ap.vnc_password);

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

		Viewer v = new Viewer(this.frame);
		v.arg_parser = this.ap;
		v.readParameters();

		v.init();
		v.start();
    }

    public static void main(String[] args) {
		System.out.println("Begin main()");

		OvdClient ovdclient = new OvdClient();

		try {
			ovdclient.AskLogin();
		} catch(Exception e) {
			System.out.println("Exception !!");
			e.printStackTrace();
			System.exit(0);
		}

		System.out.println("End main()");
	}

	public void AskLogin() {
		System.out.println("Begin AskLogin()");

		this.frame = new JFrame("Ulteo Open Virtual Desktop");
		this.panel = new Panel();

		Label llogin = new Label("Login: ");
		Label lpassword = new Label("Password: ");
		Label lsm_url = new Label("SessionManager: ");
		this.tflogin = new TextField("login");
		this.tfpassword = new TextField("password");
		this.tfsm_url = new TextField("http://url");
		tfpassword.setEchoCharacter('*');

		this.bsubmit = new Button(" Log in ");
		this.bsubmit.addActionListener(this);

		GraphicsConfiguration gc = this.frame.getGraphicsConfiguration();
		System.out.println(gc);

		this.panel.add(llogin);
		this.panel.add(this.tflogin);

		this.panel.add(lpassword);
		this.panel.add(this.tfpassword);

		this.panel.add(lsm_url);
		this.panel.add(tfsm_url);

		this.panel.add(bsubmit);

		this.frame.add(this.panel);

		this.frame.pack();
		this.frame.setSize(800, 600);

		this.frame.show();

		while(! this.logged_in) {
			try{
					Thread.currentThread().sleep(1000);//sleep for 1000 ms
			} catch(Exception ie) {}
		}

		this.frame.remove(this.panel);

		this.AfterLogin();

		System.out.println("End AskLogin()");
	}

	public void actionPerformed(ActionEvent ae) {
		System.out.println("Begin actionPerformed()");

		Object source = ae.getSource();

		if (source == this.bsubmit) {
			this.login = this.tflogin.getText();
			this.password = this.tfpassword.getText();

			try {
				this.sm_url = new URL(this.tfsm_url.getText());
			} catch(MalformedURLException e) {
				System.out.println("Exception !!");
				e.printStackTrace();
				System.exit(0);
			}

			this.logged_in = true;
		}

		System.out.println("End actionPerformed()");
	}

	public void AfterLogin() {
		System.out.println("Begin AfterLogin()");

		try {
			this.StartSession();

			int status = -1;
			while(status!=0 && status!= 10) {
				status = this.getSessionStatus();
				System.out.println("Status: "+status);

				try{
					Thread.currentThread().sleep(1000);//sleep for 1000 ms
				} catch(Exception ie) {}
			}

			this.StartSession_aps();

			status = 0;
			while(status!=2) {
				status = this.getSessionStatus();
				System.out.println("Status: "+status);

				try{
					Thread.currentThread().sleep(1000);//sleep for 1000 ms
				} catch(Exception ie) {}
			}

			this.getAccess();

			Thread t1 = new Thread(this);
			t1.start();

			this.launchViewer();
		} catch(Exception e) {
			System.out.println("Exception !!");
			e.printStackTrace();
			System.exit(0);
		}

		System.out.println("End AfterLogin()");
	}
}
