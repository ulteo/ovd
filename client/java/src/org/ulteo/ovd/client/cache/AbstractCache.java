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
import org.ulteo.ovd.integrated.Constants;

public abstract class AbstractCache {
	private String directory = null;
	private String fileExtension = null;

	private ContentManager contentManager = null;

	public AbstractCache(String directory_, ContentManager contentManager_) {
		if (! directory_.endsWith(Constants.FILE_SEPARATOR))
			directory_ += Constants.FILE_SEPARATOR;

		File dir = new File(directory_);
		if (! dir.exists())
			dir.mkdirs();

		this.directory = directory_;
		this.contentManager = contentManager_;
		this.fileExtension = this.contentManager.getFileExtension();
	}

	protected final String constructPath(String id) {
		if (id == null)
			return null;

		return this.directory+id+"."+this.fileExtension;
	}

	protected final boolean containsObject(String id) {
		if (id == null)
			return false;

		File f = new File(this.constructPath(id));

		return f.exists();
	}

	protected final boolean putObject(String id, Object content) throws IOException {
		if (id == null || content == null)
			return false;

		File f = new File(this.constructPath(id));
		if (f.exists())
			return false;
		
		this.contentManager.save(f, content);
		
		return true;
	}
	
	protected final Object getObject(String id) throws IOException {
		if (id == null)
			return null;

		File f = new File(this.constructPath(id));
		if (! f.exists())
			return null;
		
		return this.contentManager.load(f);
	}
}
