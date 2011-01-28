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

package org.ulteo.ovd.client.cache;

import java.io.File;
import java.io.IOException;

public abstract class ContentManager {
	private String fileExtension = null;

	public ContentManager(String fileExtensions_) {
		this.fileExtension = fileExtensions_;
	}

	public String getFileExtension() {
		return this.fileExtension;
	}

	protected abstract Object load(File f) throws IOException;
	protected abstract void save(File f, Object content) throws IOException;
}
