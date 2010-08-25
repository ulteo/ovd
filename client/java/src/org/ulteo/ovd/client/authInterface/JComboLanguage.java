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
