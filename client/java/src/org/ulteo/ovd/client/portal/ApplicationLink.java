/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

import java.awt.event.MouseEvent;
import javax.swing.SwingConstants;
import org.ulteo.gui.forms.HyperLink;
import org.ulteo.ovd.Application;
import org.ulteo.rdp.RdpConnectionOvd;

public class ApplicationLink extends HyperLink {
	private Application app = null;

	public ApplicationLink(Application app_) {
		super(app_.getName(), app_.getIcon(), SwingConstants.LEFT);

		this.app = app_;

		this.setName(this.app.getName());
	}

	public Application getApplication() {
		return this.app;
	}

	public RdpConnectionOvd getConnection() {
		if (this.app == null)
			return null;

		return this.app.getConnection();
	}

	public void mouseEntered(MouseEvent me) {
		if (! this.isEnabled())
			return;

		super.mouseEntered(me);

		this.setText("<u>"+app.getName()+"</u>");
	}

	public void mouseExited(MouseEvent me) {
		if (! this.isEnabled())
			return;

		super.mouseExited(me);

		this.setText(app.getName());
	}
}
