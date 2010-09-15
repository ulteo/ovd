/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

package org.ulteo.ovd.integrated.shorcut;

import java.io.File;
import java.io.FileNotFoundException;
import java.io.PrintWriter;
import java.util.logging.Level;
import java.util.logging.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.integrated.Constants;

public class LinuxShortcut extends Shortcut {

	@Override
	public String create(Application app) {
		File xfceShorcuts = new File(Constants.PATH_XFCE_MENU_ENTRIES);
		xfceShorcuts.mkdirs();

		File shortcut = new File(Constants.PATH_XFCE_MENU_ENTRIES+Constants.FILE_SEPARATOR+app.getId()+".desktop");
		try {
			boolean first = true;
			PrintWriter pw = new PrintWriter(shortcut);
			pw.println("[Desktop Entry]");
			pw.println("Type=Application");
			pw.println("Encoding=UTF-8");
			pw.println("StartupNotify=false");
			pw.println("Name="+app.getName());
			pw.println("Exec="+Constants.FILENAME_LAUNCHER+" "+this.token+" "+app.getId());
			pw.println("Icon="+Constants.PATH_ICONS+Constants.FILE_SEPARATOR+app.getIconName()+".png");
			pw.print("MimeType=");
			for (String mime : app.getSupportedMimeTypes()) {
				if (first)
					first = false;
				else
					pw.print(";");
				pw.print(mime);
			}
			pw.println();
			pw.close();
		} catch (FileNotFoundException ex) {
			Logger.getLogger(LinuxShortcut.class.getName()).log(Level.SEVERE, null, ex);
		}

		return shortcut.getName();
	}

	@Override
	public void remove(Application app) {
		File shortcut = new File(Constants.PATH_XFCE_MENU_ENTRIES+Constants.FILE_SEPARATOR+app.getId()+".desktop");
		if (shortcut.exists())
			shortcut.delete();

		File icon = new File(Constants.PATH_XFCE_MENU_ENTRIES+Constants.FILE_SEPARATOR+app.getIconName()+".png");
		if (icon.exists())
			icon.delete();
		
	}
}
