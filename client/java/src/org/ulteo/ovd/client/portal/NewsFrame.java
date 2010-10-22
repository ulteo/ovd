/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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

import java.text.DateFormat;
import java.util.Date;

import java.awt.Dimension;
import java.awt.Font;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.Image;

import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JScrollPane;
import javax.swing.JTextArea;

import org.ulteo.utils.I18n;
import org.ulteo.ovd.sm.News;

public class NewsFrame extends JFrame {
	private JLabel newsTitle = null;
	private JTextArea newsContent = null;
	private JScrollPane listScroller = null;
	
	public NewsFrame(News n_) {
		Image frameLogo = this.getToolkit().getImage(getClass().getClassLoader().getResource("pics/ulteo.png"));
		this.setIconImage(frameLogo);
		
		this.setTitle(I18n._("OVD News")+ " - "+n_.getTitle());
		this.setResizable(false);
		this.setLocationRelativeTo(null);
		
		this.setLayout(new GridBagLayout());
		GridBagConstraints gbc = new GridBagConstraints();
		
		Date newsDate = new Date();
		newsDate.setTime(n_.getTimestamp()*1000);
		DateFormat df = DateFormat.getDateTimeInstance();
		this.newsTitle = new JLabel(n_.getTitle()+" ("+df.format(newsDate)+")");
		this.newsTitle.setFont(new Font("Dialog", 1, 16));
		gbc.gridx = gbc.gridy = 0;
		gbc.insets.bottom = 15;
		gbc.anchor = GridBagConstraints.NORTHWEST;
		gbc.weightx = 1.0;
		this.add(this.newsTitle, gbc);
		
		gbc.gridx = 0;
		gbc.gridy = 2;
		gbc.anchor = GridBagConstraints.NORTHWEST;
		gbc.weightx = 1.0;
		gbc.weighty = 1.0;
		this.newsContent = new JTextArea(n_.getContent());
		this.newsContent.setLineWrap(true);
		this.newsContent.setEditable(false);
		this.listScroller = new JScrollPane(this.newsContent);
		this.listScroller.setPreferredSize(new Dimension(500,300));
		this.add(this.listScroller, gbc);
		
		this.pack();
	}
}
