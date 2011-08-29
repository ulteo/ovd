/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
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

import java.io.File;
import java.util.ArrayList;
import java.util.Enumeration;
import java.util.HashMap;
import java.util.List;
import java.util.Map.Entry;
import org.ulteo.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.utils.I18n;
import org.ulteo.ovd.integrated.Constants;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.utils.MD5;

public class WindowsRegistry extends FileAssociate {
	
	private static final String KEY_PREFIX = "ovdShell_";
	private static final String TARGET_PREFIX = "ovdTarget_";
	private static final String OPEN_PREFIX = I18n._("Open with ");

	private boolean overrideDefaultIcon = false;

	private HashMap<Application, List<String>> created_exts = null;
	private HashMap<Application, List<String>> created_shells = null;
	private HashMap<Application, List<String>> created_targets = null;
	private HashMap<Application, HashMap<String, String>> default_icons_changed = null;

	public WindowsRegistry() {
		this.created_exts = new HashMap<Application, List<String>>();
		this.created_shells = new HashMap<Application, List<String>>();
		this.created_targets = new HashMap<Application, List<String>>();
		this.default_icons_changed = new HashMap<Application, HashMap<String, String>>();
	}

	private ArrayList<String> findExtByMimeType(String mime_) {
		ArrayList<String> exts = new ArrayList<String>();

		RegistryKey key = Registry.openSubkey(Registry.HKEY_CLASSES_ROOT, "MIME\\Database\\Content Type", RegistryKey.ACCESS_READ);

		if ( key == null )
			return exts;

		try {
			Enumeration<?> enumKeys = key.keyElements();
			while (enumKeys.hasMoreElements()) {
				String keyStr = (String) enumKeys.nextElement();

				RegistryKey subKey = Registry.openSubkey(key, keyStr, RegistryKey.ACCESS_READ);

				Enumeration<?> enumValues = subKey.valueElements();
				while (enumValues.hasMoreElements()) {
					String valueStr = (String) enumValues.nextElement();
					if (!valueStr.equals("Extension"))
						continue;

					if (mime_.equals(keyStr)){
						String ext = subKey.getStringValue("Extension");
						exts.add(ext);
					}
				}
			}
		} catch ( RegistryException ex ) {
			System.err.println("ERROR getting key enumerator, "+ex.getMessage());
		}

		return exts;
	}

	private void createUserExtension(String ext, String target) throws Exception {
		RegistryKey key = Registry.openSubkey(Registry.HKEY_CURRENT_USER, "Software\\Classes", RegistryKey.ACCESS_WRITE);
		if (key == null)
			throw new Exception("Unable to access(write) to 'HKCU\\Software\\Classes'");

		RegistryKey extKey = key.createSubKey("."+ext, "");

		extKey.setValue(new RegStringValue(extKey, "", target));
	}

	private void removeUserClass(String userClass) throws Exception {
		RegistryKey key = Registry.openSubkey(Registry.HKEY_CURRENT_USER, "Software\\Classes", RegistryKey.ACCESS_ALL);
		if (key == null)
			throw new Exception("Unable to access(all) to 'HKCU\\Software\\Classes'");

		WindowsRegistry.removeKey(key, userClass);
	}

	private void associateApplicationWithExtension(Application app, String ext, String mimeType) throws Exception {
		String target = TARGET_PREFIX + app.getId();
		RegistryKey key = Registry.openSubkey(Registry.HKEY_CLASSES_ROOT, "."+ext, RegistryKey.ACCESS_READ);
		
		if (key == null) {
			this.createUserExtension(ext, target);
			this.created_exts.get(app).add(ext);
		}
		else {
			Enumeration<?> values = key.valueElements();
			String val = null;

			while (values.hasMoreElements()) {
				if (((String)values.nextElement()).equals("")) {
					val = key.getStringValue("");
					target = val;
					break;
				}
			}

			if (val == null) {
				this.createUserExtension(ext, target);
				this.created_exts.get(app).add(ext);
			}
		}

		key = Registry.openSubkey(Registry.HKEY_CURRENT_USER, "Software\\Classes", RegistryKey.ACCESS_WRITE);
		if (key == null)
			throw new Exception("Unable to access(write) to 'HKCU\\Software\\Classes'");

		try {
			key.openSubKey(target);
		} catch (Exception ex) {
			this.created_targets.get(app).add(target);
		}

		key = key.createSubKey(target, "");

		String md5sum = MD5.getMD5Sum(mimeType);
		if (md5sum != null) {
			String iconPath = Constants.PATH_CACHE_MIMETYPES_ICONS+Constants.FILE_SEPARATOR+md5sum+Constants.ICONS_EXTENSION;
			boolean defaultIconIsDefined = false;
			RegistryKey defaultIcon = null;

			try {
				defaultIcon = Registry.openSubkey(Registry.HKEY_CLASSES_ROOT, target+"\\DefaultIcon", RegistryKey.ACCESS_READ);
				defaultIcon.closeKey();

				defaultIconIsDefined = true;
			} catch (Exception ex) {}



			try {
				defaultIcon = key.openSubKey("DefaultIcon", RegistryKey.ACCESS_ALL);

				defaultIconIsDefined = true;

				if (this.overrideDefaultIcon) {
					HashMap<String, String> defaultIconsPath = this.default_icons_changed.get(app);
					if (defaultIconsPath == null) {
						defaultIconsPath = new HashMap<String, String>();
						this.default_icons_changed.put(app, defaultIconsPath);
					}
					defaultIconsPath.put(target, defaultIcon.getStringValue(""));
				}
			} catch (Exception ex) {
				if (this.overrideDefaultIcon || (! defaultIconIsDefined)) {
					defaultIcon = key.createSubKey("DefaultIcon", "");
				}
			}

			if (this.overrideDefaultIcon || (! defaultIconIsDefined)) {
				defaultIcon.setValue(new RegStringValue(defaultIcon, "", iconPath));
			}
		}

		RegistryKey ovdShell = key.createSubKey("shell", "");
		ovdShell = ovdShell.createSubKey(KEY_PREFIX + app.getId(), "");
		ovdShell.setValue(new RegStringValue(ovdShell, "", OPEN_PREFIX + app.getName()));
		ovdShell = ovdShell.createSubKey("command", "");
		
		String cmd;
		if (OSTools.is_applet)
			cmd = String.format("\"%s%sbin%sjavaw.exe\" -jar \"%s\"",
					System.getProperty("java.home"), File.separator, File.separator, Constants.JAVA_LAUNCHER);
		else
			cmd = String.format("\"%s%s%s\"",
					System.getProperty("user.dir"), File.separator, Constants.FILENAME_LAUNCHER);
		ovdShell.setValue(new RegStringValue(ovdShell, "",
				String.format("%s %s %d \"%%1\"", cmd, this.token, app.getId())));
	}

	public void createAppAction(Application app) {
		this.created_exts.put(app, new ArrayList<String>());
		this.created_shells.put(app, new ArrayList<String>());
		this.created_targets.put(app, new ArrayList<String>());

		List<String> mimeList = app.getSupportedMimeTypes();
		for (String mime : mimeList) {
			String[] extensions = WindowsRegistry.mimes.get(mime);
			if (extensions == null || extensions.length == 0) {
				Logger.warn("Unknown mime type: '"+mime+"'");
				continue;
			}

			for (String ext : extensions) {
				try {
					this.associateApplicationWithExtension(app, ext, mime);
				} catch (Exception ex) {
					Logger.error("Failed to associate application '"+app.getName()+"' with extension '"+ext+"': "+ex.getMessage());
					continue;
				}
			}
		}
	}

	public void createAppAction_alt(Application app) {
		List<String> mimeList = app.getSupportedMimeTypes();
		for (String mime : mimeList) {
			ArrayList<String> extList = this.findExtByMimeType(mime);
			for (String ext : extList) {
				try {
					this.associateApplicationWithExtension(app, ext, mime);
				} catch (Exception ex) {
					Logger.error("Failed to associate application '"+app.getName()+"' with extension '"+ext+"': "+ex.getMessage());
					continue;
				}

				Logger.debug("Associate application '"+app.getName()+"' with extension '"+ext+"' with success");
			}
		}
	}

	public void removeAppAction(Application app) {
		if (this.created_exts.containsKey(app)) {
			for (String ext : this.created_exts.get(app)) {
				try {
					this.removeUserClass("."+ext);
				} catch (Exception ex) {
					Logger.error("Failed to remove user extension '"+ext+"': "+ex.getMessage());
					continue;
				}
			}
			this.created_exts.remove(app);
		}

		if (this.created_shells.containsKey(app)) {
			for (String shell : this.created_shells.get(app)) {
				try {
					this.removeUserClass(shell);
				} catch (Exception ex) {
					Logger.error("Failed to remove user shell '"+shell+"': "+ex.getMessage());
					continue;
				}
			}
			this.created_shells.remove(app);
		}

		if (this.created_targets.containsKey(app)) {
			for (String target : this.created_targets.get(app)) {
				try {
					this.removeUserClass(target);
				} catch (Exception ex) {
					Logger.error("Failed to remove user target '"+target+"': "+ex.getMessage());
					continue;
				}
			}
			this.created_targets.remove(app);
		}

		if (this.default_icons_changed.containsKey(app)) {
			HashMap<String, String> defaultIconPath = this.default_icons_changed.get(app);
			for (Entry<String, String> each : defaultIconPath.entrySet())
				this.restoreDefaultIcon(each.getKey(), each.getValue());
			
			this.default_icons_changed.remove(app);
		}
	}

	private void restoreDefaultIcon(String target, String value) {
		if (target == null || value == null)
			return;

		String keyPath = "Software\\Classes\\"+target+"\\DefaultIcon";
		RegistryKey key = Registry.openSubkey(Registry.HKEY_CURRENT_USER, keyPath, RegistryKey.ACCESS_WRITE);
		if (key == null)
			return;
		try {
			key.setValue(new RegStringValue(key, "", value));
		} catch (RegistryException ex) {
			Logger.error("Failed to restore the default icon('"+value+"') from 'HKEY_CURRENT_USER\\"+keyPath+"'");
			return;
		}
	}
	
	public void removeAppAction_alt(Application app) {
		List<String> mimeList = app.getSupportedMimeTypes();
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
					Enumeration<?> enumKeys = key.keyElements();
					List<String> subkeysToRemove = new ArrayList<String>();
					while (enumKeys.hasMoreElements()) {
						String subKeyStr = (String) enumKeys.nextElement();
						if (! subKeyStr.startsWith("ovdShell_"))
							continue;
						
						subkeysToRemove.add(subKeyStr);
					}
					for (String subKeyStr : subkeysToRemove) {
						WindowsRegistry.removeKey(key, subKeyStr);
					}
					subkeysToRemove.clear();

				} catch (RegistryException ex) {
					Logger.error(ex.getMessage());
				}
			}
		}
	}

	private static void removeKey(RegistryKey key, String keyStr) {
		RegistryKey subKey = Registry.openSubkey(key, keyStr, RegistryKey.ACCESS_ALL);
		if (subKey == null)
			return;

		Enumeration<?> enumKeys;
		try {
			enumKeys = subKey.keyElements();
			List<String> toRemove = new ArrayList<String>();
			while (enumKeys.hasMoreElements()) {
				String subKeyStr = (String) enumKeys.nextElement();
				toRemove.add(subKeyStr);
			}
			for (String subKeyStr : toRemove) {
				WindowsRegistry.removeKey(subKey, subKeyStr);
			}
			toRemove.clear();

			enumKeys = key.valueElements();
			while (enumKeys.hasMoreElements()) {
				String valueStr = (String) enumKeys.nextElement();
				toRemove.add(valueStr);
			}
			for (String valueStr : toRemove) {
				key.deleteValue(valueStr);
			}
			toRemove.clear();

			key.deleteSubKey(keyStr);
		} catch (RegistryException ex) {
			Logger.error(ex.getMessage());
		}
	}

	public static void removeAll() {
		RegistryKey key = Registry.openSubkey(Registry.HKEY_CURRENT_USER, "Software\\Classes", RegistryKey.ACCESS_ALL);
		if (key == null) {
			Logger.error("Unable to access to HKCU");
			return;
		}

		try {
			List<String> keysToRemove = new ArrayList<String>();

			Enumeration<?> enumKeys = key.keyElements();
			while (enumKeys.hasMoreElements()) {
				boolean keep = false;
				
				String targetStr = (String) enumKeys.nextElement();

				if (targetStr.startsWith(".")) {
					RegistryKey extKey = Registry.openSubkey(Registry.HKEY_CURRENT_USER, "Software\\Classes\\"+targetStr, RegistryKey.ACCESS_READ);
					if (extKey == null)
						continue;

					String target = extKey.getStringValue("");
					if (target == null || ! target.startsWith(TARGET_PREFIX))
						keep = true;
				}
				else {
					RegistryKey targetKey = Registry.openSubkey(Registry.HKEY_CURRENT_USER, "Software\\Classes\\"+targetStr+"\\shell", RegistryKey.ACCESS_ALL);
					if (targetKey == null)
						continue;

					List<String> shellsToRemove = new ArrayList<String>();

					Enumeration<?> enumShells = targetKey.keyElements();
					while (enumShells.hasMoreElements()) {
						String shellStr = (String) enumShells.nextElement();
						
						if (! shellStr.startsWith(KEY_PREFIX)) {
							keep = true;
							continue;
						}

						shellsToRemove.add(shellStr);
					}

					for (String each : shellsToRemove)
						WindowsRegistry.removeKey(targetKey, each);

					targetKey.closeKey();
				}

				if (keep)
					continue;

				keysToRemove.add(targetStr);
			}

			for (String each : keysToRemove)
				WindowsRegistry.removeKey(key, each);
		} catch (RegistryException ex) {
			Logger.error(ex.getMessage());
		}
	}
}
