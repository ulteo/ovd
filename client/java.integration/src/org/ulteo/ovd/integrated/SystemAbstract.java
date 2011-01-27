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

package org.ulteo.ovd.integrated;

import org.ulteo.ovd.Application;
import org.ulteo.ovd.integrated.mime.FileAssociate;
import org.ulteo.ovd.integrated.shorcut.Shortcut;

public abstract class SystemAbstract {
	protected Shortcut shortcut = null;
	protected FileAssociate fileAssociate = null;

	public abstract String create(Application app, boolean associate);

	public abstract void clean(Application app);

	public static void cleanAll() {
		if (OSTools.isWindows())
			SystemWindows.cleanAll();
		else if (OSTools.isLinux())
			SystemLinux.cleanAll();
	}

	public abstract void install(Application app, boolean showDesktopIcon);

	public abstract void uninstall(Application app);

	protected abstract void saveIcon(Application app);
	
	public final void setShortcutArgumentInstance(String token) {
		this.shortcut.setToken(token);

		if (this.fileAssociate != null)
			this.fileAssociate.setToken(token);
	}
}
