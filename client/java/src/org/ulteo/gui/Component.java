/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
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
package org.ulteo.gui;

import java.awt.event.FocusEvent;
import java.awt.event.FocusListener;

import javax.swing.JPasswordField;
import javax.swing.JTextField;
import javax.swing.text.JTextComponent;

/**
 * Simple GUI components 
 */
public class Component {
	
	/**
	 * add a focuslistener to a {@link JTextComponent} that can select all test when focus
	 * @param tc {@link JTextComponent} to add the focus feature
	 */
	private static void focusSelect(final JTextComponent tc) {
        tc.addFocusListener(new FocusListener() {
            @Override
            public void focusGained(FocusEvent e) {
                tc.selectAll();
            }

            @Override
            public void focusLost(FocusEvent e) {
                tc.select(0, 0);
            }
        });
	}
	
	/**
	 *  {@link JTextField} with selected content when focused
	 */
	public static class FocusTextField extends JTextField {
	    {
	    	Component.focusSelect(this);
	    }
	}
	
	/**
	 *  {@link JPasswordField} with selected content when focused
	 */
	public static class FocusPasswordField extends JPasswordField {
	    {
	    	Component.focusSelect(this);
	    }
	}
	
}
