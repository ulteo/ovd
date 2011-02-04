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

import java.awt.BorderLayout;
import java.awt.Dimension;
import java.awt.Image;
import java.awt.event.ActionListener;

import javax.swing.JButton;
import javax.swing.JDialog;
import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JProgressBar;

import org.ulteo.utils.I18n;

public class LoadingFrame extends JDialog {

	private Image logo = null;
	private ActionListener obj = null;
	private JButton cancel = null;
	private JProgressBar aJProgressBar = null;
	private JLabel jlabel = null;
	
	private int loadingStatus = 0;

	public LoadingFrame(ActionListener obj_) {
		this.obj = obj_;

		this.cancel = new JButton(I18n._("Cancel"));
		this.cancel.setPreferredSize(new Dimension(120, 10));
		this.cancel.setSize(new Dimension(120, 10));
		this.cancel.setEnabled(false);
		this.cancel.addActionListener(this.obj);

		this.logo = getToolkit().getImage(getClass().getClassLoader().getResource("pics/ulteo.png"));
		this.setIconImage(this.logo);
		this.setTitle(I18n._("Now loading"));
		this.setDefaultCloseOperation(JFrame.DO_NOTHING_ON_CLOSE);
		this.setSize(400, 100);
		this.setPreferredSize(new Dimension(400,100));
		this.setResizable(false);
		this.setModal(true);

		aJProgressBar = new JProgressBar(JProgressBar.HORIZONTAL, 100);
		aJProgressBar.setIndeterminate(false);
		aJProgressBar.setValue(0);
		aJProgressBar.setStringPainted(true);
		aJProgressBar.setPreferredSize(new Dimension(280, 20));
		aJProgressBar.setLocation(10,45);
		this.jlabel = new JLabel(LoadingStatus.getMsg(LoadingStatus.STATUS_SM_START));
		this.add(BorderLayout.NORTH, aJProgressBar);
		this.add(BorderLayout.EAST, this.cancel);
		this.add(BorderLayout.SOUTH, jlabel);
		this.pack();
	}

	public JButton getCancelButton() {
		return this.cancel;
	}
	
	public void updateProgression(int status, int subStatus) {
		if (! this.isVisible()) {
			return;
		}
		this.loadingStatus = status;
		int loadingValue = LoadingStatus.getIncrement(status, subStatus);
		String msg = LoadingStatus.getMsg(status);
		
		this.aJProgressBar.setValue(loadingValue);
		this.jlabel.setText(msg);
	}
	
	public static Runnable changeLanguage(LoadingFrame loadingFrame_) {
		return loadingFrame_.new ChangeLanguage(loadingFrame_);
	}
	
	private class ChangeLanguage implements Runnable {
		private LoadingFrame loadingFrame = null;
		
		public ChangeLanguage(LoadingFrame loadingFrame_) {
			this.loadingFrame = loadingFrame_;
		}
		
		public void run() {
			this.loadingFrame.jlabel.setText(LoadingStatus.getMsg(this.loadingFrame.loadingStatus));
			this.loadingFrame.cancel.setText(I18n._("Cancel"));
			this.loadingFrame.setTitle(I18n._("Now loading"));
		}
	}
}
