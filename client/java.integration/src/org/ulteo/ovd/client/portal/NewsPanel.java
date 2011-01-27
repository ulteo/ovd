/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
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
import java.util.ArrayList;
import java.util.Date;
import java.util.List;

import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Component;
import java.awt.Dimension;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;

import javax.swing.BorderFactory;
import javax.swing.JPanel;
import javax.swing.JScrollPane;

import org.ulteo.gui.GUIActions;
import org.ulteo.gui.forms.HyperLink;
import org.ulteo.gui.SwingTools;
import org.ulteo.ovd.sm.News;


public class NewsPanel extends JPanel {
	private JPanel listPanel = new JPanel();
	private JScrollPane listScroller = null;
	
	public NewsPanel() {
		this.listPanel = new JPanel();
		this.listPanel.setBackground(Color.WHITE);
		this.setLayout(new BorderLayout());
		this.setBorder(BorderFactory.createLineBorder(Color.BLACK));
		this.listPanel.setLayout(new GridBagLayout());
		this.setPreferredSize(new Dimension(300, 50));
		
		this.listScroller = new JScrollPane(this.listPanel);
		this.add(this.listScroller, BorderLayout.CENTER);
		
		this.revalidate();	
	}
	
	public void updateNewsLinks(List<News> newsList) {
		SwingTools.invokeLater(GUIActions.removeAll(this.listPanel));
		int y = 0;
		
		List<Component> list1 = new ArrayList<Component>();
		List<GridBagConstraints> list2 = new ArrayList<GridBagConstraints>();
		
		for (final News n : newsList) {
			Date newsDate = new Date();
			newsDate.setTime(n.getTimestamp()*1000);
			DateFormat df = DateFormat.getDateTimeInstance();
			
			HyperLink link = new HyperLink(df.format(newsDate)+" - <u>"+n.getTitle()+"</u>");
			link.addActionListener(new ActionListener() {
				public void actionPerformed(ActionEvent evt) {
					new Thread(new Runnable() {
						public void run() {
							NewsFrame newFrame = new NewsFrame(n);
							SwingTools.invokeLater(GUIActions.setVisible(newFrame, true));
						}
					}).start();
				}
			});
			
			GridBagConstraints gbc = new GridBagConstraints();
			gbc.anchor = GridBagConstraints.LINE_START;
			gbc.gridx = 1;
			gbc.gridy = y;
			gbc.insets.right = 5;
			gbc.weightx = 0.33;
			
			list1.add(link);
			list2.add(gbc);
			y++;
		}
		
		SwingTools.invokeLater(GUIActions.addComponents(this.listPanel, list1, list2));
	}
}
