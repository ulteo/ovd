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

package org.ulteo.ovd.client.authInterface;

import java.awt.Dimension;
import java.awt.Image;

import javax.swing.JDialog;
import javax.swing.JFrame;
import javax.swing.JProgressBar;

import org.ulteo.utils.I18n;

public class DisconnectionFrame extends JDialog {
	
	private Image logo = null;
	
	public DisconnectionFrame() {

		this.logo = getToolkit().getImage(getClass().getClassLoader().getResource("pics/ulteo.png"));
		this.setIconImage(this.logo);
		this.setTitle(I18n._("Disconnecting!"));
		this.setDefaultCloseOperation(JFrame.DO_NOTHING_ON_CLOSE);
		this.setSize(300, 50);
		this.setPreferredSize(new Dimension(300,50));
		this.setResizable(false);

		final JProgressBar aJProgressBar = new JProgressBar(JProgressBar.HORIZONTAL);
		aJProgressBar.setIndeterminate(true);
		aJProgressBar.setPreferredSize(new Dimension(280, 20));
		
		this.add(aJProgressBar);
		this.setLocationRelativeTo(null);
		this.pack();
	}
	
	
	public static Runnable changeLanguage(DisconnectionFrame frame_) {
		return frame_.new ChangeLanguage(frame_);
	}
	
	private class ChangeLanguage implements Runnable {
		private DisconnectionFrame frame = null;
		
		public ChangeLanguage(DisconnectionFrame frame_) {
			this.frame = frame_;
		}
		
		public void run() {	
			this.frame.setTitle(I18n._("Disconnecting!"));
		}
	}

	@Override
	public void setVisible(boolean b) {
		if (b) {
			super.setVisible(true);
			this.setModal(true);
		}
		else {
			this.setModal(false);
			super.setVisible(false);
		}
	}

}
