/*
 * Copyright (C) 2010 Ulteo SAS
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

package org.ulteo.utils;

import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;

import org.ulteo.Logger;

public class FilesOp {
	
	public static void deleteDirectory(File directory) {
		if (! directory.exists())
			return;

		for (File each : directory.listFiles()) {
			if (each.isDirectory()) {
				FilesOp.deleteDirectory(each);
				continue;
			}
			each.delete();
		}

		directory.delete();
	}
	
	/**
	 * export an integrated resource into a temporary directory
	 * @param path
	 * 		name of the resource
	 * @throws FileNotFoundException
	 * 		if the resource wanted is not found
	 */
	public static File exportJarResource(String path) throws FileNotFoundException {
		InputStream jarResource = FilesOp.class.getResourceAsStream("/resources/" + path);
		if (jarResource == null)
			throw new FileNotFoundException(String.format("Unable to find required resource '%s' in the jar", path));

		String resName = path.split("/")[path.split("/").length - 1];
		File outputFile = new File(System.getProperty("java.io.tmpdir") + File.pathSeparatorChar + resName);
		try {
			FileOutputStream fos = new FileOutputStream(outputFile);
			int c = 0;
			while ((c = jarResource.read()) != -1) {
				fos.write(c);
			}
			fos.close();
		} catch (FileNotFoundException e) {
			Logger.error("Unable to find " + outputFile.getName());
		} catch (IOException e) {
			Logger.error("Error while creating " + outputFile.getName());
		}
		return outputFile;
	}
	
}
