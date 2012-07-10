/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011, 2012
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

package org.ulteo.utils;

import java.util.Locale;
import java.util.ResourceBundle;

public class I18n {

	private static ResourceBundle catalog = null;

	public static void init() {
		try {
			catalog = ResourceBundle.getBundle("Messages");
		}
		catch(java.util.MissingResourceException e) {
			if (Locale.getDefault().getLanguage().equals("in")) {
				// Indonesian Locale does not comply with ISO 639
				// http://bugs.sun.com/bugdatabase/view_bug.do?bug_id=6457127
				try {
					catalog = ResourceBundle.getBundle("Messages_id");
				}
				catch(java.util.MissingResourceException e2) {
					System.err.println("Unable to load Indonesian translations");
					catalog = null;
				}
			}
			else if (Locale.getDefault().getLanguage().equals("iw")) {
				// Hebrew Locale does not comply with ISO 639
				// http://bugs.sun.com/bugdatabase/view_bug.do?bug_id=4778440
				try {
					catalog = ResourceBundle.getBundle("Messages_he");
				}
				catch(java.util.MissingResourceException e2) {
					System.err.println("Unable to load Hebrew translations");
					catalog = null;
				}
			}
			else {
				System.err.println("Unable to load translation");
				catalog = null;
			}
		}
	}

	public static String _(String s) {
		if (catalog == null)
			return s;
		try {
			return catalog.getString(s);
		}
		catch (Exception e) {}
		
		return s;
	}

}