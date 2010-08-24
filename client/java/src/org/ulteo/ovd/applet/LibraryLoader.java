package org.ulteo.ovd.applet;

import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;

import org.ulteo.ovd.integrated.WindowsPaths;

public class LibraryLoader {
	public static final String RESOURCE_LIBRARY_DIRECTORY = "/ressources/WindowsLibs";
	public static final String LIB_WINDOW_PATH_NAME = "libWindowsPaths.dll";
	
	//This method is called from an applet
	public static void LoadLibrary(String ressourceDirectory, String DLLName) {
		InputStream dllResource = WindowsPaths.class.getResourceAsStream(ressourceDirectory+"/"+DLLName);
		String fileSeparator= System.getProperty("file.separator");
		//test the resource in order to know if client is started in applet mode
		if (dllResource != null) {
			String destFile = System.getProperty("java.io.tmpdir") + fileSeparator + DLLName;
			try {
				int c = 0;
				File outputFile = new File(destFile);
				FileOutputStream fos = new FileOutputStream(outputFile);

				while ((c = dllResource.read()) != -1) {
					fos.write(c);
				}
				fos.close();
			} catch (FileNotFoundException e) {
				org.ulteo.Logger.error("Unable to find "+destFile+ e.getMessage());
			} catch (IOException e) {
				org.ulteo.Logger.error("Error while creating "+destFile);
			}
			try {
				System.load(destFile);
			} catch (SecurityException e) {
				org.ulteo.Logger.error("Library loading generate an security exception: "+e.getMessage());
			} catch (UnsatisfiedLinkError e) {
				org.ulteo.Logger.error("Error while loading library: "+e.getMessage());
			} catch (NullPointerException e) {
				org.ulteo.Logger.error("Unable to load an empty library: "+e.getMessage());
			}
		}
	}
	
	//This method is called from an non applet client
	public static void LoadLibrary(String DLLName) {
		String fileSeparator= System.getProperty("file.separator");
		System.load(System.getProperty("user.dir")+fileSeparator+DLLName);
	}
	
}
