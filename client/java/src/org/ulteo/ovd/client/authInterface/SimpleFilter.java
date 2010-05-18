/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
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

package org.ulteo.ovd.client.authInterface;

import java.io.File;

import javax.swing.filechooser.FileFilter;

class SimpleFilter extends FileFilter {

	private String desc;
	private String extension;

	public SimpleFilter(String desc, String extension) {

		if(desc == null || extension ==null){
			throw new NullPointerException("La description (ou extension) ne peut être null.");
		}
		this.desc = desc;
		this.extension = extension;
	}
	@Override
	public boolean accept(File f) {
		if(f.isDirectory()) {
			return true;
		}
		String fileName = f.getName().toLowerCase(); 

		return fileName.endsWith(extension);
	}

	@Override
	public String getDescription() {
		return desc;
	}

}