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


import java.awt.Color;
import java.awt.Component;

import javax.swing.ImageIcon;
import javax.swing.JLabel;
import javax.swing.JList;
import javax.swing.ListCellRenderer;

public class JComboLanguage extends JLabel implements ListCellRenderer {
	
	public JComboLanguage(ImageIcon img, String text) {
		super(text, img, JLabel.LEFT);
	}
	
	@Override
	public Component getListCellRendererComponent(JList list, Object value,	int index, boolean isSelected, boolean cellHasFocus) {
		
		this.setIcon(((JLabel)value).getIcon());
		this.setText(((JLabel)value).getText());
		
		Color background = list.getBackground();
		Color foreground = list.getForeground();

		if (isSelected) {
			background = list.getSelectionBackground();
			foreground = list.getSelectionForeground();
		}
		
		this.setBackground(background);
		this.setForeground(foreground);
		this.setEnabled(list.isEnabled());
		this.setFont(list.getFont());
		this.setOpaque(true);
		
		return this;
	}

}
