package org.ulteo;

import java.net.*;
import java.io.StringReader;
import java.applet.*;
import java.awt.*;
import java.awt.event.*;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.swing.*;
import org.w3c.dom.Document;
import org.w3c.dom.*;
import org.xml.sax.InputSource;
import org.ulteo.HttpClient;
import org.sshvnc.Viewer;

public class OvdClient implements Runnable, ActionListener {
    protected OvdClientJFrame frame;
    protected URL sm_url;
    protected String login;
    protected String password;
    protected URL aps_url;
    protected Boolean logged_in = false;
    protected HttpClient hc = null;
    protected ArgParser ap;

    public OvdClient()
    {
        this.ap = new ArgParser();
    }

    public static void usage()
    {
        System.err.println("Usage: ...");
    }

    public void run()
    {
        while(true) {
            try {
                this.getSessionStatus();
                Thread.currentThread().sleep(1000);
            } catch(Exception e) {
                System.err.println("is going to end ...");
                return;
            }
        }
    }

    public boolean StartSession() throws Exception
    {
        hc = new HttpClient(this.sm_url);

        int r = hc.request_POST(this.sm_url.getPath() + "/startsession.php", "login=" + this.login + "&password=" + this.password);
        try {
            this.aps_url = hc.server.getURL();
        } catch (Exception e) {
            debug("exception");
            return false;
        }

        return true;
    }

    protected int getSessionStatus() throws Exception
    {
        int e = hc.request_GET("/whatsup.php", "");

        String response = hc.getResponseText();
        DocumentBuilder parser = DocumentBuilderFactory.newInstance().newDocumentBuilder();
        Document document = parser.parse(new InputSource(new StringReader(response)));
        NodeList n = document.getElementsByTagName("session");

        if(n.getLength() < 1)
            return -1;

        Node m = n.item(0);
        NamedNodeMap t = m.getAttributes();
        Node g = t.getNamedItem("status");
        String rr = g.getNodeValue();

        return (new Integer(rr)).intValue();
        //    System.out.println("Node value: "+rr);
        //        return 0;
    }

    protected boolean StartSession_aps() throws Exception
    {
        Dimension d = this.frame.getSize();
        Insets i = this.frame.getInsets();

        int width = (int)d.getWidth() - (i.left + i.right);
        int height = (int)d.getHeight() - (i.top + i.bottom);

        int e = hc.request_GET("/start.php", "width=" + width + "&height=" + height);

        return e == 200;
    }

    protected boolean getAccess() throws Exception
    {
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

        for(Node n2 = n.getFirstChild(); n2 != null; n2 = n2.getNextSibling()) {
            if(n2.getNodeName() != "port")
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

        debug("VNC password: " + this.ap.vnc_password);

        return e == 200;
    }

    public void launchViewer()
    {
        /*System.out.println("Launching with following parameters:");
           System.out.println("\tssh_host: "+this.ssh_host);
           System.out.println("\tssh_port: "+this.ssh_port);
           System.out.println("\tssh_login: "+this.ssh_login);
           System.out.println("\tssh_password: "+this.ssh_password);
           System.out.println("\tvnc_password: "+this.vnc_password);
           System.out.println("\tvnc_port: "+this.vnc_port);*/

        debug("launch viewer");

        Viewer v = new Viewer(this.frame);

        v.arg_parser = this.ap;
        v.readParameters();

        v.init();
        v.start();
    }

    public static void main(String[] args)
    {
        System.out.println("Begin main()");

        OvdClient ovdclient = new OvdClient();

        try {
            ovdclient.AskLogin();
        } catch(Exception e) {
            System.out.println("An exception occured in AskLogin()");
            e.printStackTrace();
            System.exit(0);
        }

        ovdclient.debug("End main()");
    }

    public void AskLogin()
    {
        System.out.println("Begin AskLogin()");

        javax.swing.UIManager.LookAndFeelInfo[] info = javax.swing.UIManager.getInstalledLookAndFeels();
        String systemName = System.getProperty("os.name");
        for(int i = 0; i < info.length; i++) {
            String className = info[i].getClassName();
            if(className == "com.sun.java.swing.plaf.gtk.GTKLookAndFeel" && (systemName.contains("Linux") || systemName.contains("BSD") || systemName.contains("Solaris")) ||
            className == "com.sun.java.swing.plaf.mac.MacLookAndFeel" && systemName.contains("Mac OS") ||
            className == "com.sun.java.swing.plaf.windows.WindowsLookAndFeel" && systemName.contains("Windows")) {
                try {
                    javax.swing.UIManager.setLookAndFeel(className);
                    break;
                } catch (Exception e) {
                    System.err.println("Could not set look and feel");
                }
            }
        }

        this.frame = new OvdClientJFrame();
        this.frame.setSize(410, 265);
        this.frame.setTitle("Ulteo Open Virtual Desktop");
        this.frame.debugPane.setVisible(false);
        this.frame.show();

        this.frame.loginButton.addActionListener(this);
        this.frame.debugCheckBox.addActionListener(this);
        this.frame.exitButton.addActionListener(this);

        while(!this.logged_in) {
            try {
                Thread.currentThread().sleep(1000);
            } catch(Exception ie) {}
        }

        this.frame.remove(this.frame.loginPanel);
        this.frame.remove(this.frame.exitButton);
        if(this.frame.debugPane.isVisible() == false) {
            this.frame.remove(this.frame.jScrollPane1);
            this.frame.setSize(800, 600);
        }
        else if(this.frame.debugPane.isVisible() == true) {
            this.frame.setSize(800, 800);
            //TODO: move at bottom
        }
        this.AfterLogin();

        debug("End AskLogin()");
    }

    public void actionPerformed(ActionEvent ae)
    {
        debug("Begin actionPerformed()");

        Object source = ae.getSource();

        if(source == this.frame.loginButton) {
            this.login = this.frame.userNameTextField.getText();
            this.password = this.frame.passwordField.getText();

            try {
                this.sm_url = new URL(this.frame.sessionManagerURLTextField.getText());
            } catch(MalformedURLException e) {
                System.err.println("Exception !!");
                e.printStackTrace();
                System.exit(1);
            }

            this.logged_in = true;
        }

        if(source == this.frame.debugCheckBox) {
            if(this.frame.debugPane.isVisible() == true) {
                this.frame.debugPane.setVisible(false);
                this.frame.setSize(410, 265);
            }
            else if(this.frame.debugPane.isVisible() == false) {
                this.frame.debugPane.setVisible(true);
                this.frame.setSize(410, 450);
            }
        }

        if(source == this.frame.exitButton)
            System.exit(0);

        debug("End actionPerformed()");
    }

    public void AfterLogin()
    {
        debug("Begin AfterLogin()");

        try {
            this.StartSession();

            int status = -1;
            while(status != 0 && status != 10) {
                try {
                    status = this.getSessionStatus();
                    debug("Status: " + status);
                } catch(Exception e) {
                    debug("exception");
                    return;
                }
                try {
                    Thread.currentThread().sleep(1000);    //sleep for 1000 ms
                } catch(Exception ie) {
                    debug("exception");
                }
            }

            this.StartSession_aps();

            status = 0;
            while(status != 2) {
                try {
                    status = this.getSessionStatus();
                    debug("Status: " + status);
                } catch(Exception e) {
                    debug("exception");
                    return;
                }
                try {
                    Thread.currentThread().sleep(1000);    //sleep for 1000 ms
                } catch(Exception ie) {
                    debug("exception");
                }
            }

            this.getAccess();

            Thread t1 = new Thread(this);
            t1.start();

            this.launchViewer();
        } catch(Exception e) {
            debug("Exception !!");
            e.printStackTrace();
            System.exit(0);
        }

        debug("End AfterLogin()");
    }

    public void debug(String message)
    {
        System.out.println(message);
        this.frame.debugPane.setText(this.frame.debugPane.getText() + "\n" + message);
    }

}
