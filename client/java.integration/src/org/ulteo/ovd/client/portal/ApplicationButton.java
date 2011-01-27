/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
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

package org.ulteo.ovd.client.portal;

import java.awt.Toolkit;

import javax.swing.ImageIcon;
import javax.swing.JButton;
import org.ulteo.ovd.Application;
import org.ulteo.rdp.RdpConnectionOvd;

public class ApplicationButton extends JButton {
	private Application app = null;
	private static final ImageIcon LAUNCH_ICON = new ImageIcon(Toolkit.getDefaultToolkit().getImage(ApplicationButton.class.getClassLoader().getResource("pics/launch.png"))); 
	
	public ApplicationButton (Application app_) {
		this.app = app_;
		this.setName(app_.getName());
		setIcon(LAUNCH_ICON);
		validate();
		revalidate();
	}

	public RdpConnectionOvd getConnection() {
		return this.app.getConnection();
	}
}
