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

import java.awt.GridLayout;

import javax.swing.JPanel;

public class CenterPanel extends JPanel {
	
	private Menu menu = null;
	private CurrentApps current = null;
	
	public CenterPanel() {
		current = new CurrentApps();
		menu = new Menu(current);
		setLayout(new GridLayout(1,2));
		this.add(menu);
		this.add(current);
	}

	public Menu getMenu() {
		return menu;
	}

	public void setMenu(Menu menu) {
		this.menu = menu;
	}

	public CurrentApps getCurrent() {
		return current;
	}

	public void setCurrent(CurrentApps current) {
		this.current = current;
	}
}
