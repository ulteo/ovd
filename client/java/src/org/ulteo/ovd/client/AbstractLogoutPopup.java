/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2011
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

package org.ulteo.ovd.client;

import javax.swing.JFrame;
import javax.swing.JOptionPane;

public abstract class AbstractLogoutPopup extends JOptionPane {
	private JFrame frame = null;
	private String title = null;
	private String text = null;
	private String[] choices = null;

	public AbstractLogoutPopup(JFrame frame) {
		this.frame = frame;
	}

	public void setTitle(String title_) {
		this.title = title_;
	}

	public void setText(String text_) {
		this.text = text_;
	}

	public void setChoices(String[] choices_) {
		this.choices = choices_;
	}

	public void showPopup() {
		if (this.title == null || this.text == null || this.choices == null || this.choices.length == 0)
			return;

		int option = showOptionDialog(this.frame,
						this.text,
						this.title,
						JOptionPane.YES_NO_CANCEL_OPTION,
						JOptionPane.WARNING_MESSAGE,
						null,
						this.choices,
						this.choices[this.choices.length - 1]);

		this.processOption(option);
	}

	protected abstract void processOption(int option_);
}
