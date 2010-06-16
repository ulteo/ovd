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

import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.io.IOException;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.crypto.CryptoException;
import org.apache.log4j.Logger;
import org.ulteo.ovd.ApplicationInstance;

import org.ulteo.rdp.RdpActions;

public class KillListener implements ActionListener {

	private Logger logger = Logger.getLogger(KillListener.class);
	private RdpActions rdpActions = null;
	private CurrentApps currentAppsPanel = null;
	
	public KillListener(RdpActions rdpActions_, CurrentApps currentAppsPanel_) {
		this.rdpActions = rdpActions_;
		this.currentAppsPanel = currentAppsPanel_;
	}
	
	@Override
	public void actionPerformed(ActionEvent e) {
		ApplicationInstance[] appsInstList = this.currentAppsPanel.getSelectedApps();
		for (ApplicationInstance ai : appsInstList) {
			try {
				ai.stopApp();
			} catch (RdesktopException ex) {
				this.logger.warn(ex);
			} catch (IOException ex) {
				this.logger.warn(ex);
			} catch (CryptoException ex) {
				this.logger.warn(ex);
			}
		}
	}

}
