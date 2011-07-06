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

import java.util.List;
import java.util.UUID;

import javax.swing.ImageIcon;
import org.ulteo.gui.GUIActions;

import org.ulteo.rdp.RdpConnectionOvd;

public class Application implements Comparable<Application> {
	private int id = -1;
	private String name = "";
	private List<String> supportedMime = null;
	private RdpConnectionOvd connection = null;
	private ImageIcon icon = null;
	private String iconName = "";

	public Application(RdpConnectionOvd connection_, int id_, String name_, List<String> mimeType_, ImageIcon icon_) {
		this.connection = connection_;
		this.id = id_;
		this.name = name_;
		this.supportedMime = mimeType_;
		if (icon_ == null)
			this.icon =  new ImageIcon(GUIActions.DEFAULT_APP_ICON);
		else
			this.icon = icon_;
		this.iconName = UUID.randomUUID().toString()+"-"+this.id;
	}

	public List<String> getSupportedMimeTypes() {
		return this.supportedMime;
	}
	
	public ImageIcon getIcon() {
		return this.icon;
	}

	public int getId() {
		return this.id;
	}

	public String getIconName() {
		return this.iconName;
	}

	public String getName() {
		return this.name;
	}

	public RdpConnectionOvd getConnection() {
		return this.connection;
	}

	@Override
	public int compareTo(Application t) {
		return this.name.compareToIgnoreCase(t.name);
	}
}
