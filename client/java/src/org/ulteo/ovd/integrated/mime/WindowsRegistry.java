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

package org.ulteo.ovd.integrated.mime;

import com.ice.jni.registry.RegStringValue;
import com.ice.jni.registry.Registry;
import com.ice.jni.registry.RegistryException;
import com.ice.jni.registry.RegistryKey;
import java.util.ArrayList;
import java.util.Enumeration;
import java.util.logging.Level;
import java.util.logging.Logger;
import org.ulteo.ovd.Application;

public class WindowsRegistry extends FileAssociate {
	/*public static void main(String[] args){
		String mimeType = new String("text/plain");

		WindowsRegistry reg = new WindowsRegistry(null);
		System.out.println("Searching extensions for Content Type = '"+mimeType+"' ...");
		ArrayList<String> exts = reg.findExtByMimeType(mimeType);
		System.out.println("Results:");
		for (String ext : exts) {
			System.out.println(ext);
		}
	}*/

	//private ArrayList<RdpConnection> connections = null;

	public WindowsRegistry() {
	}

	/*public WindowsRegistry(ArrayList<RdpConnection> connections_) {
		this.connections = connections_;
	}*/

	/*public void run() {
		this.createOVDAction();
		System.out.println("MimeTypes association ... ok");
	}*/

	private ArrayList<String> findExtByMimeType(String mime_) {
		ArrayList<String> exts = new ArrayList<String>();

		RegistryKey key = Registry.openSubkey(Registry.HKEY_CLASSES_ROOT, "MIME\\Database\\Content Type", RegistryKey.ACCESS_READ);

		if ( key == null )
			return exts;

		try {
			Enumeration enumKeys = key.keyElements();
			while (enumKeys.hasMoreElements()) {
				String keyStr = (String) enumKeys.nextElement();

				RegistryKey subKey = Registry.openSubkey(key, keyStr, RegistryKey.ACCESS_READ);

				Enumeration enumValues = subKey.valueElements();
				while (enumValues.hasMoreElements()) {
					String valueStr = (String) enumValues.nextElement();
					if (!valueStr.equals("Extension"))
						continue;

					if (mime_.equals(keyStr))
						exts.add(subKey.getStringValue("Content Type"));
				}
			}
		} catch ( RegistryException ex ) {
			System.err.println("ERROR getting key enumerator, "+ex.getMessage());
		}

		return exts;
	}

	/*private void createOVDAction() {
		for (RdpConnection rc : this.connections) {
			ArrayList<Application> appList = rc.getAppsList();
			for (Application app : appList) {
				this.createAppAction(app);
			}
		}
	}*/

	public void createAppAction(Application app) {
		ArrayList<String> mimeList = app.getSupportedMimeTypes();
		for (String mime : mimeList) {
			ArrayList<String> extList = this.findExtByMimeType(mime);
			for (String ext : extList) {
				RegistryKey key = Registry.openSubkey(Registry.HKEY_CLASSES_ROOT, ext, RegistryKey.ACCESS_READ);
				if (key == null) {
					System.out.println("err");
					continue;
				}

				try {
					String target = key.getStringValue("");
					key = Registry.openSubkey(Registry.HKEY_CURRENT_USER, "Software\\Classes", RegistryKey.ACCESS_WRITE);

					key = key.createSubKey(target, "");
					key = key.createSubKey("shell", "");
					RegistryKey ovdShell = key.createSubKey("ovdShell_" + app.getId(), "");
					ovdShell.setValue(new RegStringValue(ovdShell, "", "Open with "+app.getName()));
					ovdShell = ovdShell.createSubKey("command", "");
					ovdShell.setValue(new RegStringValue(ovdShell, "", "Y:\\UlteoOVDIntegratedLauncher.exe "+app.getId()+" \"%1\""));

				} catch (RegistryException ex) {
					Logger.getLogger(WindowsRegistry.class.getName()).log(Level.SEVERE, null, ex);
				}
			}
		}
	}

	/*public void removeOVDKeys() {
		for (RdpConnection rc : this.connections) {
			ArrayList<Application> appList = rc.getAppsList();
			for (Application app : appList) {
				this.removeAppAction(app);
			}
		}
	}*/

	public void removeAppAction(Application app) {
		ArrayList<String> mimeList = app.getSupportedMimeTypes();
		for (String mime : mimeList) {
			ArrayList<String> extList = this.findExtByMimeType(mime);
			for (String ext : extList) {
				RegistryKey key = Registry.openSubkey(Registry.HKEY_CLASSES_ROOT, ext, RegistryKey.ACCESS_READ);
				if (key == null) {
					System.out.println("err");
					continue;
				}

				try {
					String target = key.getStringValue("");
					key = Registry.openSubkey(Registry.HKEY_CURRENT_USER, "Software\\Classes\\"+target+"\\shell", RegistryKey.ACCESS_ALL);

					Enumeration enumKeys = key.keyElements();
					while (enumKeys.hasMoreElements()) {
						String subKeyStr = (String) enumKeys.nextElement();
						if (! subKeyStr.startsWith("ovdShell_"))
							continue;

						this.removeKey(key, subKeyStr);
					}

				} catch (RegistryException ex) {
					Logger.getLogger(WindowsRegistry.class.getName()).log(Level.SEVERE, null, ex);
				}
			}
		}
	}

	private void removeKey(RegistryKey key, String keyStr) {
		RegistryKey subKey = Registry.openSubkey(key, keyStr, RegistryKey.ACCESS_ALL);
		if (subKey == null)
			return;

		Enumeration enumKeys;
		try {
			enumKeys = subKey.keyElements();
			while (enumKeys.hasMoreElements()) {
				String subKeyStr = (String) enumKeys.nextElement();
				this.removeKey(subKey, subKeyStr);
			}
			enumKeys = key.valueElements();
			while (enumKeys.hasMoreElements()) {
				String valueStr = (String) enumKeys.nextElement();
				key.deleteValue(valueStr);
			}
			key.deleteSubKey(keyStr);
		} catch (RegistryException ex) {
			Logger.getLogger(WindowsRegistry.class.getName()).log(Level.SEVERE, null, ex);
		}
	}
}
