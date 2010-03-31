package org.ulteo.ovd;

import java.net.URL;
import java.util.ArrayList;
import java.util.Comparator;

import javax.swing.ImageIcon;

import org.ulteo.rdp.Connection;

public class Application implements Comparator<Object>{
	private int id = -1;
	private String cmd = "";
	private String name = "";
	private ArrayList<String> supportedMime = null;
	private Connection connection = null;
	private ImageIcon icon = null;
	//private JMenuItem menuItem = null;
	
	public Application() {}

	public Application(Connection connection_, int id_, String name_, String cmd_, URL icon_) {
		this.supportedMime = new ArrayList<String>();
		this.init(connection_, id_, name_, cmd_, icon_);
	}

	public Application(Connection connection_, int id_, String name_, String cmd_, ArrayList<String> mimeType_, URL icon_) {
		this.supportedMime = mimeType_;
		this.init(connection_, id_, name_, cmd_, icon_);
	}

	private void init(Connection connection_, int id_, String name_, String cmd_, URL icon_) {
		this.connection = connection_;
		this.id = id_;
		this.name = name_;
		this.cmd = cmd_;
		this.icon = new ImageIcon(icon_);
		//this.menuItem = new JMenuItem(this.name, this.icon);
	}

	public ArrayList<String> getSupportedMimeTypes() {
		return this.supportedMime;
	}
	
	/*public JMenuItem getMenuItem() {
		return this.menuItem;
	}*/
	
	public ImageIcon getIcon() {
		return this.icon;
	}

	public void setIcon(ImageIcon icon) {
		this.icon = icon;
	}

	public int getId() {
		return this.id;
	}

	public void setId(int id_) {
		this.id = id_;
	}

	public String getCmd() {
		return this.cmd;
	}

	public void setCmd(String cmd_) {
		this.cmd = cmd_;
	}

	public String getName() {
		return this.name;
	}

	public void setName(String name_) {
		this.name = name_;
	}

	public Connection getConnection() {
		return this.connection;
	}

	public void setConnection(Connection connection_) {
		this.connection = connection_;
	}

	public int compare(Object o1, Object o2) {
		return ((Application)o1).name.compareToIgnoreCase(((Application)o2).name);
	}
}
