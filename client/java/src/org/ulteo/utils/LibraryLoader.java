/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David  LECHEVALIER <david@ulteo.com> 2010, 2011, 2012
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
 * Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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
import java.lang.reflect.Field;
import java.util.ArrayList;
import java.util.List;
import org.ulteo.Logger;
import org.ulteo.ovd.integrated.OSTools;

public class LibraryLoader {
	public static final String RESOURCE_LIBRARY_DIRECTORY_WINDOWS = "WindowsLibs";
	public static final String RESOURCE_LIBRARY_DIRECTORY_LINUX = "LinuxLibs";
	public static final String LIB_WINDOW_PATH_NAME = "libWindowsPaths.dll";
	public static final String LIB_X_CLIENT_AREA = "libXClientArea.so";

	/**
	 * Ajoute un nouveau répertoire dans le java.library.path.
	 * @param dir Le nouveau répertoire à ajouter.
	 */
	public static void addToJavaLibraryPath(File dir) {
		final String LIBRARY_PATH = "java.library.path";
		if (!dir.isDirectory()) {
			throw new IllegalArgumentException(dir + " is not a directory.");
		}
		String javaLibraryPath = System.getProperty(LIBRARY_PATH);
		System.setProperty(LIBRARY_PATH, javaLibraryPath + File.pathSeparatorChar + dir.getAbsolutePath());
		
		resetJavaLibraryPath();
	}

	/**
	 * Supprime le cache du "java.library.path".
	 * Cela forcera le classloader à revérifier sa valeur lors du prochaine chargement de librairie.
	 * 
	 * Attention : ceci est spécifique à la JVM de Sun et pourrait ne pas fonctionner
	 * sur une autre JVM...
	 */
	public static void resetJavaLibraryPath() {
		synchronized(Runtime.getRuntime()) {
			try {
				Field field = ClassLoader.class.getDeclaredField("usr_paths");
				field.setAccessible(true);
				field.set(null, null);
				
				field = ClassLoader.class.getDeclaredField("sys_paths");
				field.setAccessible(true);
				field.set(null, null);
			} catch (NoSuchFieldException e) {
				throw new RuntimeException(e);
			} catch (IllegalAccessException e) {
				throw new RuntimeException(e);
			}
		}
	}
	
	/**
	 * load a library from an applet
	 * @param resourceDirectory
	 * 		directory where the library must be found
	 * @param DLLName
	 * 		name of the library
	 * @throws FileNotFoundException
	 * 		throw if the library wanted is not found
	 */
	public static void LoadLibrary(String resourceDirectory, String DLLName) throws FileNotFoundException {
        resourceDirectory += (OSTools.is64() ? "/64" : "/32");
        File jarLib = FilesOp.exportJarResource(resourceDirectory + '/' + DLLName);
		try {
			System.load(jarLib.getPath());
		} catch (Exception e) {
			Logger.error(String.format("Unable to load the '%s' library: %s", DLLName, e.getMessage()));
		}
		catch (UnsatisfiedLinkError e) {
			Logger.error(String.format("Unable to load the '%s' library: %s", DLLName, e.getMessage()));
			
			throw new FileNotFoundException("Unable to find a valid library for "+DLLName);
		}
	}
	
	//This method is called from an non applet client
	public static void LoadLibrary(String LibName) throws FileNotFoundException {
		String jarPath = System.getProperty("java.class.path");
		String jarDirectory = new File(jarPath).getAbsoluteFile().getParent();
		String jvmArch = System.getProperty("sun.arch.data.model");

		String standaloneLibPath = jarDirectory+File.separator+"lib"+File.separator+jvmArch;
		
		String fileSeparator= System.getProperty("file.separator");
		String libraryPaths = System.getProperty("java.library.path");

		List<String> paths = new ArrayList<String>();
		paths.add(standaloneLibPath);
		paths.add(System.getProperty("user.dir"));
		for (String each : libraryPaths.split(System.getProperty("path.separator")))
			paths.add(each);

		for (String each : paths) {
			int len = each.length();
			if (! each.substring((len - fileSeparator.length()), len).equals(fileSeparator))
				each += fileSeparator;
			each += LibName;

			if (new File(each).exists()) {
				try {
					System.load(each);
				}
				catch (UnsatisfiedLinkError e) {
					Logger.error(String.format("Unable to load the '%s' library: %s", LibName, e.getMessage()));
					
					throw new FileNotFoundException("Unable to find a valid library for "+LibName);
				}
				
				return;
			}
		}

		throw new FileNotFoundException("Unable to find required library: "+LibName);
	}
	
}
