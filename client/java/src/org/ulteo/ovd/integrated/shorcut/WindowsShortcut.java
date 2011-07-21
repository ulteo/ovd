/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

package org.ulteo.ovd.integrated.shorcut;

import java.io.File;
import net.jimmc.jshortcut.JShellLink;
import org.ulteo.Logger;
import org.ulteo.utils.FilesOp;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.integrated.Constants;
import org.ulteo.ovd.integrated.OSTools;


public class WindowsShortcut extends Shortcut {
	
	private static final String[] FORBIDDEN_CHARS = {"/", "\\", ":", "*", "?", "\"", "<", ">", "|"};
	private static final String WILDCARD = "_";

	public static String replaceForbiddenChars(String str) {
		for (int i=0; i<FORBIDDEN_CHARS.length; i++) {
			if (str.contains(FORBIDDEN_CHARS[i])) {
				str = str.replaceAll(FORBIDDEN_CHARS[i], WILDCARD);
			}
		}
		return str;
	}

	private JShellLink shortcut = null;

	public WindowsShortcut() {
		this.shortcut = new JShellLink();
		this.shortcut.setFolder(Constants.PATH_SHORTCUTS);
		this.shortcut.setWorkingDirectory("");

		char fs = File.separatorChar;
		String exec;
		if (OSTools.is_applet)
			exec = System.getProperty("java.home") + fs + "bin" + fs + "javaw.exe";
		else
			exec = System.getProperty("user.dir") + fs + Constants.FILENAME_LAUNCHER;
		if (! (new File(exec).exists()))
			Logger.warn("cannot found the launcher");
		this.shortcut.setPath(exec);
	}

	@Override
	public String create(Application app) {
		if (app == null)
			return null;

		String appName = replaceForbiddenChars(app.getName());

		File shorcutDirectory = new File(Constants.PATH_SHORTCUTS);
		if (! shorcutDirectory.exists())
			shorcutDirectory.mkdirs();
		shorcutDirectory = null;

		this.shortcut.setName(appName);
		String args = "";
		if (OSTools.is_applet)
			args += String.format("-jar %s ", Constants.JAVA_LAUNCHER);
		args += String.format("%s %s", this.token, app.getId());
		this.shortcut.setArguments(args);
		this.shortcut.setIconLocation(Constants.PATH_ICONS+Constants.FILE_SEPARATOR+app.getIconName()+".ico");
		this.shortcut.setIconIndex(0);
		try {
			this.shortcut.save();
		} catch (RuntimeException re) {
			return null;
		}

		return appName+".lnk";
	}

	@Override
	public void remove(Application app) {
		File icon = new File(Constants.PATH_ICONS+Constants.FILE_SEPARATOR+app.getIconName()+".ico");
		if (icon.exists())
			icon.delete();
		icon = null;

		File shortcut = new File(Constants.PATH_SHORTCUTS+Constants.FILE_SEPARATOR+replaceForbiddenChars(app.getName())+".lnk");
		if (shortcut.exists())
			shortcut.delete();
		shortcut = null;
	}

	public static void removeAll() {
		FilesOp.deleteDirectory(new File(Constants.PATH_ICONS));

		File shortcuts_dir = new File(Constants.PATH_SHORTCUTS);
		if (! shortcuts_dir.exists())
			return;

		String[] shortcuts = shortcuts_dir.list();
		for (String filename : shortcuts) {
			File[] f = new File[2];
			f[0] = new File(Constants.PATH_DESKTOP+Constants.FILE_SEPARATOR+filename);
			f[1] = new File(Constants.PATH_STARTMENU+Constants.FILE_SEPARATOR+filename);

			for (File each: f) {
				if (each.exists())
					each.delete();
			}
		}

		FilesOp.deleteDirectory(shortcuts_dir);
	}
}