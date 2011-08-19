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

package org.ulteo.ovd.integrated.mime;

import java.io.BufferedReader;
import java.io.File;
import java.io.IOException;
import java.io.InputStreamReader;
import java.util.List;
import org.ulteo.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.integrated.Constants;

public class XDGMime extends FileAssociate {
	public static boolean isMimeTypeRegistered(String mimetype) {
		try {
			Process process = Runtime.getRuntime().exec("xdg-mime query default "+mimetype);
			BufferedReader in = new BufferedReader(new InputStreamReader(process.getInputStream()));

			StringBuilder buf = new StringBuilder();

			String line = null;
			while ((line = in.readLine()) != null) {
				buf.append(line);
			}

			return (buf.length() > 0);
		} catch (IOException ex) {
			Logger.error("Failed to query xdg about a mimetype existence: "+ex.getMessage());
			return false;
		}
	}

	public static void createMimeTypeFile(String mimetype) {
		File out_file = new File(Constants.PATH_CACHE_MIMETYPES_FILES+File.separator+"Ulteo-"+mimetype.replaceAll("/", "_")+".xml");
		if (out_file.exists())
			return;

		String[] extensions = FileAssociate.mimes.get(mimetype);
		if (extensions == null) {
			Logger.debug(mimetype+": No known extension");
			return;
		}

		XDGMimeTypesFile mimetypes_file = new XDGMimeTypesFile();

		mimetypes_file.setMimeType(mimetype);
		mimetypes_file.setMimeTypeComment("Comment for "+mimetype+" files");

		for (String each : extensions) {
			mimetypes_file.addPattern("*."+each);
		}

		mimetypes_file.write(out_file);
	}

	public static void registerMimeType(String mimetype) {
		File mimetype_file = new File(Constants.PATH_CACHE_MIMETYPES_FILES+File.separator+"Ulteo-"+mimetype.replaceAll("/", "_")+".xml");
		if (! mimetype_file.exists())
			return;

		Logger.debug("Registering mimetype "+mimetype);
		try {
			Process process = Runtime.getRuntime().exec("xdg-mime install --mode user "+mimetype_file.getPath());
			process.waitFor();
		} catch (Exception ex) {
			Logger.error("Failed to register mimetype '"+mimetype+"': "+ex.getMessage());
		}
	}

	public static void unregisterAllMimeTypes() {
		File mimetype_file = new File(Constants.PATH_CACHE_MIMETYPES_FILES);
		File[] files = mimetype_file.listFiles();
		if (files == null || files.length == 0)
			return;

		for (File each : files) {
			String filename = each.getName();
			if (! filename.startsWith("Ulteo-") || ! filename.endsWith(".xml")) {
				continue;
			}

			String mimetype = filename.substring("Ulteo-".length(), filename.length() - 4).replaceAll("_", "/");

			if (XDGMime.isMimeTypeRegistered(mimetype))
				XDGMime.unregisterMimeType(mimetype);
		}
	}

	public static void unregisterMimeType(String mimetype) {
		File mimetype_file = new File(Constants.PATH_CACHE_MIMETYPES_FILES+File.separator+"Ulteo-"+mimetype.replaceAll("/", "_")+".xml");
		if (! mimetype_file.exists())
			return;

		Logger.debug("Unregistering mimetype "+mimetype);
		try {
			Process process = Runtime.getRuntime().exec("xdg-mime uninstall --mode user "+mimetype_file.getPath());
			process.waitFor();
		} catch (Exception ex) {
			Logger.error("Failed to unregister mimetype '"+mimetype+"': "+ex.getMessage());
		}
	}

	public static void updateDatabase() {
		try {
			Process process = Runtime.getRuntime().exec("update-mime-database "+Constants.PATH_XDG_MIME);
			process.waitFor();
		} catch (Exception ex) {
			Logger.error("Failed to update mimetypes database: "+ex.getMessage());
		}
	}

	@Override
	public void createAppAction(Application app) {
		List<String> mimesList = app.getSupportedMimeTypes();
		for (String mimetype : mimesList) {
			if (isMimeTypeRegistered(mimetype)) {
				continue;
			}

			XDGMime.registerMimeType(mimetype);
		}
	}

	@Override
	public void removeAppAction(Application app) {
		List<String> mimesList = app.getSupportedMimeTypes();
		for (String mimetype : mimesList) {
			if (! isMimeTypeRegistered(mimetype))
				continue;

			XDGMime.unregisterMimeType(mimetype);
		}
	}
}
