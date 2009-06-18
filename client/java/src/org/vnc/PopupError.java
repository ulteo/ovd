package org.vnc;

import java.awt.Component;

import javax.swing.JOptionPane;

// Util class to handle finals errors.
public class PopupError 
	{	

	public final static void showError(Component parent, String message, String title, Exception e)
		{
		JOptionPane.showMessageDialog(parent, message, title,JOptionPane.ERROR_MESSAGE);
		if (e!=null) {
			System.err.println(e);
		}
		}
	}
