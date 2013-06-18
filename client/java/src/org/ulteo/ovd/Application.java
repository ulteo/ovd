/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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

import java.awt.Image;
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
	
	public Application (RdpConnectionOvd connection_, org.ulteo.ovd.sm.Application app, ImageIcon icon_) {
		this(connection_, app.getId(), app.getName(), app.getMimes(), icon_);
	}

	public List<String> getSupportedMimeTypes() {
		return this.supportedMime;
	}
	
	public ImageIcon getIcon() {
		return this.icon;
	}

	public ImageIcon getSmallIcon() {
		ImageIcon smallIcon =  new ImageIcon(this.icon.getImage().getScaledInstance(32, 32, Image.SCALE_SMOOTH));
		return smallIcon;		
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
	
	/**
	 * set the {@link ImageIcon} icon
	 * @param icon The {@link ImageIcon} to add. Do nothing if icon is null.
	 */
	public void setIcon(ImageIcon icon) {
		if (icon != null)
			this.icon = icon;
	}

	@Override
	public int compareTo(Application t) {
		return this.name.compareToIgnoreCase(t.name);
	}

	public boolean isWebApp() {
		return false;
	}
}
