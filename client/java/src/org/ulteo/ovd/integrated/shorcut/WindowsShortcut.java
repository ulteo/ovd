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
import net.jimmc.jshortcut.JShellLink;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.integrated.Constants;

public class WindowsShortcut extends Shortcut {
	
	private String appName = null;
	private String[] forbiddenChars = {"/", "\\", ":", "*", "?", "\"", "<", ">", "|"};

	@Override
	public void create(Application app) {
		appName = app.getName();
		for (int i=0; i<forbiddenChars.length; i++) {
			if (appName.contains(forbiddenChars[i])) {
				appName = appName.replaceAll(forbiddenChars[i], "_");				
			}
		}
		JShellLink shortcut = new JShellLink(Constants.clientShortcutsPath, appName);
		shortcut.setWorkingDirectory("");
		shortcut.setPath(System.getProperty("user.dir")+Constants.separator+Constants.launcher);
		shortcut.setArguments(""+this.token+" "+app.getId());
		shortcut.setIconLocation(Constants.iconsPath+Constants.separator+app.getId()+".ico");
		shortcut.setIconIndex(0);
		shortcut.save();
	}

	@Override
	public void remove(Application app) {
		File shortcut = new File(Constants.desktopPath+Constants.separator+app.getName()+".lnk");
		if (shortcut.exists())
			shortcut.delete();

		shortcut = new File(Constants.startmenuPath+Constants.separator+app.getName()+".lnk");
		if (shortcut.exists())
			shortcut.delete();
		
		shortcut = new File(Constants.clientShortcutsPath+Constants.separator+app.getName()+".lnk");
		if (shortcut.exists())
			shortcut.delete();
	}
}
