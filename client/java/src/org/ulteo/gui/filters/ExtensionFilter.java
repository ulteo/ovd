/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2011
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

package org.ulteo.gui.filters;

import java.io.File;
import javax.swing.filechooser.FileFilter;

public class ExtensionFilter extends FileFilter {
	private String extension = null;
	private String description = null;

	public ExtensionFilter(String extension_, String description_) {
		this.extension = extension_;
		this.description = description_;
	}

	@Override
	public boolean accept(File f) {
		if (f == null)
			return false;

		String filename = f.getName();
		int pos = filename.lastIndexOf(".");
		if (pos == -1)
			return false;
		
		return (filename.substring(pos + 1).equalsIgnoreCase(this.extension));
	}

	@Override
	public String getDescription() {
		return this.description;
	}
	
}
