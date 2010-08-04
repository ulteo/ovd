/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
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

package org.ulteo.ovd;

import java.util.ArrayList;

import javax.swing.ImageIcon;

import org.ulteo.rdp.RdpConnectionOvd;

public class Application implements Comparable<Application> {
	private int id = -1;
	private String cmd = "";
	private String name = "";
	private ArrayList<String> supportedMime = null;
	private RdpConnectionOvd connection = null;
	private ImageIcon icon = null;
	//private JMenuItem menuItem = null;

	public Application() {}

	public Application(RdpConnectionOvd connection_, int id_, String name_, String cmd_, ImageIcon icon_) {
		this.supportedMime = new ArrayList<String>();
		this.init(connection_, id_, name_, cmd_, icon_);
	}

	public Application(RdpConnectionOvd connection_, int id_, String name_, String cmd_, ArrayList<String> mimeType_, ImageIcon icon_) {
		this.supportedMime = mimeType_;
		this.init(connection_, id_, name_, cmd_, icon_);
	}

	private void init(RdpConnectionOvd connection_, int id_, String name_, String cmd_, ImageIcon icon_) {
		this.connection = connection_;
		this.id = id_;
		this.name = name_;
		this.cmd = cmd_;
		this.icon = icon_;
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

	public RdpConnectionOvd getConnection() {
		return this.connection;
	}

	public void setConnection(RdpConnectionOvd connection_) {
		this.connection = connection_;
	}

	public int compareTo(Application t) {
		return this.name.compareToIgnoreCase(t.name);
	}
}
