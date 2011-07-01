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
import org.ulteo.utils.MD5;

public class WindowsRegistry extends FileAssociate {
	
	private static final String KEY_DEFAULT_ICON = "DefaultIcon";

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
			Enumeration enumKeys = key.keyElements();
			while (enumKeys.hasMoreElements()) {
				String keyStr = (String) enumKeys.nextElement();

				RegistryKey subKey = Registry.openSubkey(key, keyStr, RegistryKey.ACCESS_READ);

				Enumeration enumValues = subKey.valueElements();
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
			Enumeration values = key.valueElements();
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
		ovdShell.setValue(new RegStringValue(ovdShell, "", "\""+System.getProperty("user.dir")+Constants.FILE_SEPARATOR+Constants.FILENAME_LAUNCHER+"\" "+this.token+" "+app.getId()+" \"%1\""));
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
					Enumeration enumKeys = key.keyElements();
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

		Enumeration enumKeys;
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

			Enumeration enumKeys = key.keyElements();
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

					Enumeration enumShells = targetKey.keyElements();
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

	private static final HashMap<String, String[]> mimes = new HashMap<String, String[]>() {
		{
			put("Application/xml",	 new String[]{"vcproj"});
			put("application/STEP",	 new String[]{"step", "stp"});
			put("application/acad",	 new String[]{"dwg"});
			put("application/andrew-inset",	 new String[]{"ez"});
			put("application/annodex",	 new String[]{"anx"});
			put("application/atom+xml",	 new String[]{"atom"});
			put("application/atomcat+xml",	 new String[]{"atomcat"});
			put("application/atomserv+xml",	 new String[]{"atomsrv"});
			put("application/bbolin",	 new String[]{"lin"});
			put("application/cap",	 new String[]{"cap", "pcap"});
			put("application/clariscad",	 new String[]{"ccad"});
			put("application/cu-seeme",	 new String[]{"cu"});
			put("application/davmount+xml",	 new String[]{"davmount"});
			put("application/drafting",	 new String[]{"drw"});
			put("application/dsptype",	 new String[]{"tsp"});
			put("application/dxf",	 new String[]{"dxf"});
			put("application/ecmascript",	 new String[]{"es"});
			put("application/fractals",	 new String[]{"fif"});
			put("application/futuresplash",	 new String[]{"spl"});
			put("application/hta",	 new String[]{"hta"});
			put("application/i-deas",	 new String[]{"unv"});
			put("application/java-archive",	 new String[]{"jar"});
			put("application/java-serialized-object",	 new String[]{"ser"});
			put("application/java-vm",	 new String[]{"class"});
			put("application/javascript",	 new String[]{"js"});
			put("application/m3g",	 new String[]{"m3g"});
			put("application/mac-binhex40",	 new String[]{"hqx"});
			put("application/mathematica",	 new String[]{"nb", "nbp"});
			put("application/ms-vsi",	 new String[]{"vsi"});
			put("application/msaccess",	 new String[]{"accda", "accdb", "accdc", "accde", "accdr", "accdt", "ade", "adp", "mda", "mdb", "mde"});
			put("application/msword",	 new String[]{"doc", "dot", "rtf", "wbk", "wiz"});
			put("application/nmwb",	 new String[]{"NMW"});
			put("application/octet-stream",	 new String[]{"bin", "hxd", "hxh", "hxi", "hxq", "hxr", "hxs", "hxw"});
			put("application/oda",	 new String[]{"oda"});
			put("application/ogg",	 new String[]{"ogm", "ogx"});
			put("application/pdf",	 new String[]{"pdf"});
			put("application/pgp-keys",	 new String[]{"key"});
			put("application/pgp-signature",	 new String[]{"pgp"});
			put("application/pics-rules",	 new String[]{"prf"});
			put("application/pkcs10",	 new String[]{"p10"});
			put("application/pkcs7-mime",	 new String[]{"p7c", "p7m"});
			put("application/pkcs7-signature",	 new String[]{"p7s"});
			put("application/pkix-cert",	 new String[]{"cer"});
			put("application/pkix-crl",	 new String[]{"crl"});
			put("application/postscript",	 new String[]{"ai", "eps", "eps2", "eps3", "epsf", "espi", "ps"});
			put("application/rar",	 new String[]{"rar"});
			put("application/rat-file",	 new String[]{"rat"});
			put("application/rdf+xml",	 new String[]{"rdf"});
			put("application/rss+xml",	 new String[]{"rss"});
			put("application/rtf",	 new String[]{"rtf"});
			put("application/set",	 new String[]{"set"});
			put("application/set-payment-initiation",	 new String[]{"setpay"});
			put("application/set-registration-initiation",	 new String[]{"setreg"});
			put("application/smil",	 new String[]{"smi", "smil"});
			put("application/solids",	 new String[]{"sol"});
			put("application/vda",	 new String[]{"vda"});
			put("application/vnd.cinderella",	 new String[]{"cdy"});
			put("application/vnd.google-earth.kml+xml",	 new String[]{"kml"});
			put("application/vnd.google-earth.kmz",	 new String[]{"kmz"});
			put("application/vnd.mozilla.xul+xml",	 new String[]{"xul"});
			put("application/vnd.ms-excel",	 new String[]{"csv", "slk", "xla", "xlb", "xlc", "xlk", "xll", "xlm", "xls", "xlt", "xlw"});
			put("application/vnd.ms-excel.addin.macroEnabled.12",	 new String[]{"xlam"});
			put("application/vnd.ms-excel.sheet.binary.macroEnabled.12",	 new String[]{"xlsb"});
			put("application/vnd.ms-excel.sheet.macroEnabled.12",	 new String[]{"xlsm"});
			put("application/vnd.ms-excel.template.macroEnabled.12",	 new String[]{"xltm"});
			put("application/vnd.ms-mediapackage",	 new String[]{"mpf"});
			put("application/vnd.ms-officetheme",	 new String[]{"thmx"});
			put("application/vnd.ms-package.relationships+xml",	 new String[]{"rels"});
			put("application/vnd.ms-pki.certstore",	 new String[]{"sst"});
			put("application/vnd.ms-pki.pko",	 new String[]{"pko"});
			put("application/vnd.ms-pki.seccat",	 new String[]{"cat"});
			put("application/vnd.ms-pki.stl",	 new String[]{"stl"});
			put("application/vnd.ms-powerpoint",	 new String[]{"pot", "ppa", "pps", "ppt", "ppz", "pwz"});
			put("application/vnd.ms-powerpoint.addin.macroEnabled.12",	 new String[]{"ppam"});
			put("application/vnd.ms-powerpoint.presentation.macroEnabled.12",	 new String[]{"pptm"});
			put("application/vnd.ms-powerpoint.slide.macroEnabled.12",	 new String[]{"sldm"});
			put("application/vnd.ms-powerpoint.slideshow.macroEnabled.12",	 new String[]{"ppsm"});
			put("application/vnd.ms-powerpoint.template.macroEnabled.12",	 new String[]{"potm"});
			put("application/vnd.ms-publisher",	 new String[]{"ols", "pub"});
			put("application/vnd.ms-visio.viewer",	 new String[]{"vdx", "vsd", "vss", "vst", "vsx", "vtx"});
			put("application/vnd.ms-word.document.macroEnabled.12",	 new String[]{"docm"});
			put("application/vnd.ms-word.template.macroEnabled.12",	 new String[]{"dotm"});
			put("application/vnd.ms-wpl",	 new String[]{"wpl"});
			put("application/vnd.ms-xpsdocument",	 new String[]{"xps"});
			put("application/vnd.oasis.opendocument.chart",	 new String[]{"odc"});
			put("application/vnd.oasis.opendocument.database",	 new String[]{"odb"});
			put("application/vnd.oasis.opendocument.formula",	 new String[]{"odf"});
			put("application/vnd.oasis.opendocument.graphics",	 new String[]{"odg"});
			put("application/vnd.oasis.opendocument.graphics-template",	 new String[]{"otg"});
			put("application/vnd.oasis.opendocument.image",	 new String[]{"odi"});
			put("application/vnd.oasis.opendocument.presentation",	 new String[]{"odp"});
			put("application/vnd.oasis.opendocument.presentation-template",	 new String[]{"otp"});
			put("application/vnd.oasis.opendocument.spreadsheet",	 new String[]{"ods"});
			put("application/vnd.oasis.opendocument.spreadsheet-template",	 new String[]{"ots"});
			put("application/vnd.oasis.opendocument.text",	 new String[]{"odt"});
			put("application/vnd.oasis.opendocument.text-master",	 new String[]{"odm"});
			put("application/vnd.oasis.opendocument.text-template",	 new String[]{"ott"});
			put("application/vnd.oasis.opendocument.text-web",	 new String[]{"oth"});
			put("application/vnd.openofficeorg.extension",	 new String[]{"oxt"});
			put("application/vnd.openxmlformats-officedocument.presentationml.presentation",	 new String[]{"pptx"});
			put("application/vnd.openxmlformats-officedocument.presentationml.slide",	 new String[]{"sldx"});
			put("application/vnd.openxmlformats-officedocument.presentationml.slideshow",	 new String[]{"ppsx"});
			put("application/vnd.openxmlformats-officedocument.presentationml.template",	 new String[]{"potx"});
			put("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",	 new String[]{"xlsx"});
			put("application/vnd.openxmlformats-officedocument.spreadsheetml.template",	 new String[]{"xltx"});
			put("application/vnd.openxmlformats-officedocument.wordprocessingml.document",	 new String[]{"docx"});
			put("application/vnd.openxmlformats-officedocument.wordprocessingml.template",	 new String[]{"dotx"});
			put("application/vnd.rim.cod",	 new String[]{"cod"});
			put("application/vnd.smaf",	 new String[]{"mmf"});
			put("application/vnd.stardivision.calc",	 new String[]{"sdc"});
			put("application/vnd.stardivision.chart",	 new String[]{"sds"});
			put("application/vnd.stardivision.draw",	 new String[]{"sda"});
			put("application/vnd.stardivision.impress",	 new String[]{"sdd"});
			put("application/vnd.stardivision.impress-packed",	 new String[]{"sdp"});
			put("application/vnd.stardivision.math",	 new String[]{"smf"});
			put("application/vnd.stardivision.writer",	 new String[]{"sdw", "vor"});
			put("application/vnd.stardivision.writer-global",	 new String[]{"sgl"});
			put("application/vnd.sun.xml.base",	 new String[]{"odb"});
			put("application/vnd.sun.xml.calc",	 new String[]{"sxc"});
			put("application/vnd.sun.xml.calc.template",	 new String[]{"stc"});
			put("application/vnd.sun.xml.draw",	 new String[]{"sxd"});
			put("application/vnd.sun.xml.draw.template",	 new String[]{"std"});
			put("application/vnd.sun.xml.impress",	 new String[]{"sxi"});
			put("application/vnd.sun.xml.impress.template",	 new String[]{"sti"});
			put("application/vnd.sun.xml.math",	 new String[]{"sxm"});
			put("application/vnd.sun.xml.writer",	 new String[]{"sxw"});
			put("application/vnd.sun.xml.writer.global",	 new String[]{"sxg"});
			put("application/vnd.sun.xml.writer.template",	 new String[]{"stw"});
			put("application/vnd.symbian.install",	 new String[]{"sis"});
			put("application/vnd.visio",	 new String[]{"vsd"});
			put("application/vnd.wap.wbxml",	 new String[]{"wbxml"});
			put("application/vnd.wap.wmlc",	 new String[]{"wmlc"});
			put("application/vnd.wap.wmlscriptc",	 new String[]{"wmlsc"});
			put("application/vnd.wordperfect",	 new String[]{"wpd"});
			put("application/vnd.wordperfect5.1",	 new String[]{"wp5"});
			put("application/x-123",	 new String[]{"wk"});
			put("application/x-7z-compressed",	 new String[]{"7z"});
			put("application/x-abiword",	 new String[]{"abw"});
			put("application/x-apple-diskimage",	 new String[]{"dmg"});
			put("application/x-arj-compressed",	 new String[]{"arj"});
			put("application/x-bcpio",	 new String[]{"bcpio"});
			put("application/x-bittorrent",	 new String[]{"torrent"});
			put("application/x-cab",	 new String[]{"cab"});
			put("application/x-cbr",	 new String[]{"cbr"});
			put("application/x-cbz",	 new String[]{"cbz"});
			put("application/x-cdf",	 new String[]{"cda", "cdf"});
			put("application/x-cdlink",	 new String[]{"vcd"});
			put("application/x-chess-pgn",	 new String[]{"pgn"});
			put("application/x-compress",	 new String[]{"z"});
			put("application/x-compressed",	 new String[]{"tgz"});
			put("application/x-cpio",	 new String[]{"cpio"});
			put("application/x-debian-package",	 new String[]{"deb", "udeb"});
			put("application/x-director",	 new String[]{"dcr", "dir", "dxr"});
			put("application/x-dms",	 new String[]{"dms"});
			put("application/x-doom",	 new String[]{"wad"});
			put("application/x-dvi",	 new String[]{"dvi"});
			put("application/x-font",	 new String[]{"gsf", "pcf", "pcf.Z", "pfa", "pfb"});
			put("application/x-freelance",	 new String[]{"pre"});
			put("application/x-freemind",	 new String[]{"mm"});
			put("application/x-futuresplash",	 new String[]{"spl"});
			put("application/x-gnumeric",	 new String[]{"gnumeric"});
			put("application/x-go-sgf",	 new String[]{"sgf"});
			put("application/x-graphing-calculator",	 new String[]{"gcf"});
			put("application/x-gtar",	 new String[]{"gtar", "taz", "tgz"});
			put("application/x-gzip",	 new String[]{"gz"});
			put("application/x-hdf",	 new String[]{"hdf"});
			put("application/x-httpd-eruby",	 new String[]{"rhtml"});
			put("application/x-httpd-php",	 new String[]{"php", "pht", "phtml"});
			put("application/x-httpd-php-source",	 new String[]{"phps"});
			put("application/x-httpd-php3",	 new String[]{"php3"});
			put("application/x-httpd-php3-preprocessed",	 new String[]{"php3p"});
			put("application/x-httpd-php4",	 new String[]{"php4"});
			put("application/x-ica",	 new String[]{"ica"});
			put("application/x-info",	 new String[]{"info"});
			put("application/x-internet-signup",	 new String[]{"ins", "isp"});
			put("application/x-iphone",	 new String[]{"iii"});
			put("application/x-ipix",	 new String[]{"ipx"});
			put("application/x-ipscript",	 new String[]{"ips"});
			put("application/x-iso9660-image",	 new String[]{"iso"});
			put("application/x-jam",	 new String[]{"jam"});
			put("application/x-java-jnlp-file",	 new String[]{"jnlp"});
			put("application/x-jmol",	 new String[]{"jmz"});
			put("application/x-jtx+xps",	 new String[]{"jtx"});
			put("application/x-kchart",	 new String[]{"chrt"});
			put("application/x-killustrator",	 new String[]{"kil"});
			put("application/x-koan",	 new String[]{"skd", "skm", "skp", "skt"});
			put("application/x-kpresenter",	 new String[]{"kpr", "kpt"});
			put("application/x-kspread",	 new String[]{"ksp"});
			put("application/x-kword",	 new String[]{"kwd", "kwt"});
			put("application/x-latex",	 new String[]{"latex"});
			put("application/x-lha",	 new String[]{"lha"});
			put("application/x-lisp",	 new String[]{"lsp"});
			put("application/x-lotusscreencam",	 new String[]{"scm"});
			put("application/x-lyx",	 new String[]{"lyx"});
			put("application/x-lzh",	 new String[]{"lzh"});
			put("application/x-lzx",	 new String[]{"lzx"});
			put("application/x-maker",	 new String[]{"book", "fb", "fbdoc", "fm", "frame", "frm", "maker"});
			put("application/x-mif",	 new String[]{"mif"});
			put("application/x-mix-transfer",	 new String[]{"nix"});
			put("application/x-mplayer2",	 new String[]{"asx"});
			put("application/x-ms-application",	 new String[]{"application"});
			put("application/x-ms-wmd",	 new String[]{"wmd"});
			put("application/x-ms-wmz",	 new String[]{"wmz"});
			put("application/x-ms-xbap",	 new String[]{"xbap"});
			put("application/x-msdos-program",	 new String[]{"bat", "com", "dll", "exe"});
			put("application/x-msdownload",	 new String[]{"dll", "exe"});
			put("application/x-msexcel",	 new String[]{"xls"});
			put("application/x-msi",	 new String[]{"msi"});
			put("application/x-mspowerpoint",	 new String[]{"ppt"});
			put("application/x-mspowerpoint.12",	 new String[]{"pptx"});
			put("application/x-mspowerpoint.macroEnabled.12",	 new String[]{"pptm"});
			put("application/x-netcdf",	 new String[]{"nc"});
			put("application/x-ns-proxy-autoconfig",	 new String[]{"dat", "pac"});
			put("application/x-nwc",	 new String[]{"nwc"});
			put("application/x-object",	 new String[]{"o"});
			put("application/x-oz-application",	 new String[]{"oza"});
			put("application/x-pkcs12",	 new String[]{"p12", "pfx"});
			put("application/x-pkcs7-certificates",	 new String[]{"p7b", "spc"});
			put("application/x-pkcs7-certreqresp",	 new String[]{"p7r"});
			put("application/x-pkcs7-crl",	 new String[]{"crl"});
			put("application/x-python-code",	 new String[]{"pyc", "pyo"});
			put("application/x-qgis",	 new String[]{"qgs", "shp", "shx"});
			put("application/x-quicktimeplayer",	 new String[]{"qtl"});
			put("application/x-redhat-package-manager",	 new String[]{"rpm"});
			put("application/x-ruby",	 new String[]{"rb"});
			put("application/x-shar",	 new String[]{"shar"});
			put("application/x-shockwave-flash",	 new String[]{"mfp", "swf", "swfl"});
			put("application/x-starcalc",	 new String[]{"sdc"});
			put("application/x-starchart",	 new String[]{"sds"});
			put("application/x-stardraw",	 new String[]{"sda"});
			put("application/x-starimpress",	 new String[]{"sdd"});
			put("application/x-starmath",	 new String[]{"smf"});
			put("application/x-starwriter",	 new String[]{"sdw"});
			put("application/x-stuffit",	 new String[]{"sit", "sitx"});
			put("application/x-sv4cpio",	 new String[]{"sv4cpio"});
			put("application/x-sv4crc",	 new String[]{"sv4crc"});
			put("application/x-tar",	 new String[]{"tar"});
			put("application/x-tar-gz",	 new String[]{"tar.gz"});
			put("application/x-tex-gf",	 new String[]{"gf"});
			put("application/x-tex-pk",	 new String[]{"pk"});
			put("application/x-texinfo",	 new String[]{"texi", "texinfo"});
			put("application/x-trash",	 new String[]{"bak", "old", "sik"});
			put("application/x-troff",	 new String[]{"roff", "t", "tr"});
			put("application/x-troff-man",	 new String[]{"man"});
			put("application/x-troff-me",	 new String[]{"me"});
			put("application/x-troff-ms",	 new String[]{"ms"});
			put("application/x-ustar",	 new String[]{"ustar"});
			put("application/x-wais-source",	 new String[]{"src"});
			put("application/x-wingz",	 new String[]{"wz"});
			put("application/x-x509-ca-cert",	 new String[]{"cer", "crt", "der"});
			put("application/x-xcf",	 new String[]{"xcf"});
			put("application/x-xfig",	 new String[]{"fig"});
			put("application/x-xpinstall",	 new String[]{"xpi"});
			put("application/x-zip-compressed",	 new String[]{"zip"});
			put("application/xaml+xml",	 new String[]{"xaml"});
			put("application/xhtml+xml",	 new String[]{"xht", "xhtml"});
			put("application/xml",	 new String[]{"config", "datasource", "hxa", "hxc", "hxe", "hxf", "hxk", "hxt", "hxv", "resx", "settings", "vscontent", "wsdl", "xdr", "xml", "xsd", "xsl", "xslt"});
			put("application/xml-dtd",	 new String[]{"dtd"});
			put("application/xspf+xml",	 new String[]{"xspf"});
			put("application/zip",	 new String[]{"zip"});
			put("audio/TSP-audio",	 new String[]{"tsi"});
			put("audio/aiff",	 new String[]{"aif", "aifc", "aiff"});
			put("audio/amr",	 new String[]{"amr"});
			put("audio/amr-wb",	 new String[]{"awb"});
			put("audio/annodex",	 new String[]{"axa"});
			put("audio/basic",	 new String[]{"au", "snd"});
			put("audio/flac",	 new String[]{"flac"});
			put("audio/mid",	 new String[]{"mid", "midi", "rmi"});
			put("audio/midi",	 new String[]{"kar", "mid", "midi"});
			put("audio/mp3",	 new String[]{"mp3"});
			put("audio/mpeg",	 new String[]{"m4a", "mp2", "mp3", "mpega", "mpga"});
			put("audio/mpegurl",	 new String[]{"m3u"});
			put("audio/mpg",	 new String[]{"mp3"});
			put("audio/ogg",	 new String[]{"oga", "ogg", "spx"});
			put("audio/prs.sid",	 new String[]{"sid"});
			put("audio/wav",	 new String[]{"wav"});
			put("audio/x-aiff",	 new String[]{"aif", "aifc", "aiff"});
			put("audio/x-gsm",	 new String[]{"gsm"});
			put("audio/x-mid",	 new String[]{"mid"});
			put("audio/x-midi",	 new String[]{"mid"});
			put("audio/x-mp3",	 new String[]{"mp3"});
			put("audio/x-mpeg",	 new String[]{"mp3"});
			put("audio/x-mpegurl",	 new String[]{"m3u"});
			put("audio/x-mpg",	 new String[]{"mp3"});
			put("audio/x-ms-wax",	 new String[]{"wax"});
			put("audio/x-ms-wma",	 new String[]{"wma"});
			put("audio/x-pn-realaudio",	 new String[]{"ram", "rm"});
			put("audio/x-realaudio",	 new String[]{"ra"});
			put("audio/x-scpls",	 new String[]{"pls"});
			put("audio/x-sd2",	 new String[]{"sd2"});
			put("audio/x-wav",	 new String[]{"wav"});
			put("chemical/x-alchemy",	 new String[]{"alc"});
			put("chemical/x-cache",	 new String[]{"cac", "cache"});
			put("chemical/x-cache-csf",	 new String[]{"csf"});
			put("chemical/x-cactvs-binary",	 new String[]{"cascii", "cbin", "ctab"});
			put("chemical/x-cdx",	 new String[]{"cdx"});
			put("chemical/x-cerius",	 new String[]{"cer"});
			put("chemical/x-chem3d",	 new String[]{"c3d"});
			put("chemical/x-chemdraw",	 new String[]{"chm"});
			put("chemical/x-cif",	 new String[]{"cif"});
			put("chemical/x-cmdf",	 new String[]{"cmdf"});
			put("chemical/x-cml",	 new String[]{"cml"});
			put("chemical/x-compass",	 new String[]{"cpa"});
			put("chemical/x-crossfire",	 new String[]{"bsd"});
			put("chemical/x-csml",	 new String[]{"csm", "csml"});
			put("chemical/x-ctx",	 new String[]{"ctx"});
			put("chemical/x-cxf",	 new String[]{"cef", "cxf"});
			put("chemical/x-embl-dl-nucleotide",	 new String[]{"emb", "embl"});
			put("chemical/x-galactic-spc",	 new String[]{"spc"});
			put("chemical/x-gamess-input",	 new String[]{"gam", "gamin", "inp"});
			put("chemical/x-gaussian-checkpoint",	 new String[]{"fch", "fchk"});
			put("chemical/x-gaussian-cube",	 new String[]{"cub"});
			put("chemical/x-gaussian-input",	 new String[]{"gau", "gjc", "gjf"});
			put("chemical/x-gaussian-log",	 new String[]{"gal"});
			put("chemical/x-gcg8-sequence",	 new String[]{"gcg"});
			put("chemical/x-genbank",	 new String[]{"gen"});
			put("chemical/x-hin",	 new String[]{"hin"});
			put("chemical/x-isostar",	 new String[]{"ist", "istr"});
			put("chemical/x-jcamp-dx",	 new String[]{"dx", "jdx"});
			put("chemical/x-kinemage",	 new String[]{"kin"});
			put("chemical/x-macmolecule",	 new String[]{"mcm"});
			put("chemical/x-macromodel-input",	 new String[]{"mmd", "mmod"});
			put("chemical/x-mdl-molfile",	 new String[]{"mol"});
			put("chemical/x-mdl-rdfile",	 new String[]{"rd"});
			put("chemical/x-mdl-rxnfile",	 new String[]{"rxn"});
			put("chemical/x-mdl-sdfile",	 new String[]{"sd", "sdf"});
			put("chemical/x-mdl-tgf",	 new String[]{"tgf"});
			put("chemical/x-mmcif",	 new String[]{"mcif"});
			put("chemical/x-mol2",	 new String[]{"mol2"});
			put("chemical/x-molconn-Z",	 new String[]{"b"});
			put("chemical/x-mopac-graph",	 new String[]{"gpt"});
			put("chemical/x-mopac-input",	 new String[]{"mop", "mopcrt", "mpc", "zmt"});
			put("chemical/x-mopac-out",	 new String[]{"moo"});
			put("chemical/x-mopac-vib",	 new String[]{"mvb"});
			put("chemical/x-ncbi-asn1-ascii",	 new String[]{"prt"});
			put("chemical/x-ncbi-asn1-binary",	 new String[]{"aso", "val"});
			put("chemical/x-ncbi-asn1-spec",	 new String[]{"asn"});
			put("chemical/x-pdb",	 new String[]{"ent", "pdb"});
			put("chemical/x-rosdal",	 new String[]{"ros"});
			put("chemical/x-swissprot",	 new String[]{"sw"});
			put("chemical/x-vamas-iso14976",	 new String[]{"vms"});
			put("chemical/x-vmd",	 new String[]{"vmd"});
			put("chemical/x-xtel",	 new String[]{"xtel"});
			put("chemical/x-xyz",	 new String[]{"xyz"});
			put("image/bmp",	 new String[]{"bmp", "dib"});
			put("image/gif",	 new String[]{"gif"});
			put("image/ief",	 new String[]{"ief"});
			put("image/jpeg",	 new String[]{"jfif", "jpe", "jpeg", "jpg"});
			put("image/pcx",	 new String[]{"pcx"});
			put("image/pjpeg",	 new String[]{"jpg"});
			put("image/png",	 new String[]{"png"});
			put("image/svg+xml",	 new String[]{"svg", "svgz"});
			put("image/tiff",	 new String[]{"tif", "tiff"});
			put("image/vnd.djvu",	 new String[]{"djv", "djvu"});
			put("image/vnd.ms-photo",	 new String[]{"wdp"});
			put("image/vnd.wap.wbmp",	 new String[]{"wbmp"});
			put("image/x-cmu-raster",	 new String[]{"ras"});
			put("image/x-coreldraw",	 new String[]{"cdr"});
			put("image/x-coreldrawpattern",	 new String[]{"pat"});
			put("image/x-coreldrawtemplate",	 new String[]{"cdt"});
			put("image/x-corelphotopaint",	 new String[]{"cpt"});
			put("image/x-icon",	 new String[]{"ico"});
			put("image/x-jg",	 new String[]{"art"});
			put("image/x-jng",	 new String[]{"jng"});
			put("image/x-photoshop",	 new String[]{"psd"});
			put("image/x-png",	 new String[]{"png"});
			put("image/x-portable-anymap",	 new String[]{"pnm"});
			put("image/x-portable-bitmap",	 new String[]{"pbm"});
			put("image/x-portable-graymap",	 new String[]{"pgm"});
			put("image/x-portable-pixmap",	 new String[]{"ppm"});
			put("image/x-rgb",	 new String[]{"rgb"});
			put("image/x-xbitmap",	 new String[]{"xbm"});
			put("image/x-xpixmap",	 new String[]{"xpm"});
			put("image/x-xwindowdump",	 new String[]{"xwd"});
			put("message/rfc822",	 new String[]{"eml", "mht", "mhtml", "nws"});
			put("midi/mid",	 new String[]{"mid"});
			put("model/iges",	 new String[]{"iges", "igs"});
			put("model/mesh",	 new String[]{"mesh", "msh", "silo"});
			put("model/vnd.dwfx+xps",	 new String[]{"dwfx"});
			put("text/calendar",	 new String[]{"ics", "icz"});
			put("text/css",	 new String[]{"css"});
			put("text/csv",	 new String[]{"csv"});
			put("text/dlm",	 new String[]{"dlm"});
			put("text/h323",	 new String[]{"323"});
			put("text/html",	 new String[]{"htm", "html", "shtml"});
			put("text/iuls",	 new String[]{"uls"});
			put("text/mathml",	 new String[]{"mml"});
			put("text/plain",	 new String[]{"asc", "asm", "brf", "c", "cc", "cod", "cpp", "cur", "cxx", "def", "dsp", "dsw", "f", "f90", "h", "hpp", "hxx", "i", "idl", "inc", "inl", "lst", "m", "mak", "map", "mdp", "mk", "odh", "odl", "pot", "py", "pyw", "rgs", "s", "sln", "sol", "sor", "srf", "text", "tlh", "tli", "txt"});
			put("text/richtext",	 new String[]{"rtx"});
			put("text/scriptlet",	 new String[]{"sct", "wsc"});
			put("text/sgml",	 new String[]{"sgm", "sgml"});
			put("text/tab-separated-values",	 new String[]{"tsv"});
			put("text/texmacs",	 new String[]{"tm", "ts"});
			put("text/vnd.sun.j2me.app-descriptor",	 new String[]{"jad"});
			put("text/vnd.wap.wml",	 new String[]{"wml"});
			put("text/vnd.wap.wmlscript",	 new String[]{"wmls"});
			put("text/webviewhtml",	 new String[]{"htt"});
			put("text/x-bibtex",	 new String[]{"bib"});
			put("text/x-boo",	 new String[]{"boo"});
			put("text/x-c++hdr",	 new String[]{"h++", "hh", "hpp", "hxx"});
			put("text/x-c++src",	 new String[]{"c++", "cc", "cpp", "cxx"});
			put("text/x-chdr",	 new String[]{"h"});
			put("text/x-component",	 new String[]{"htc"});
			put("text/x-csh",	 new String[]{"csh"});
			put("text/x-csrc",	 new String[]{"c"});
			put("text/x-diff",	 new String[]{"diff", "patch"});
			put("text/x-dsrc",	 new String[]{"d"});
			put("text/x-haskell",	 new String[]{"hs"});
			put("text/x-java",	 new String[]{"java"});
			put("text/x-literate-haskell",	 new String[]{"lhs"});
			put("text/x-moc",	 new String[]{"moc"});
			put("text/x-ms-iqy",	 new String[]{"iqy"});
			put("text/x-ms-odc",	 new String[]{"odc"});
			put("text/x-ms-rqy",	 new String[]{"rqy"});
			put("text/x-pascal",	 new String[]{"p", "pas"});
			put("text/x-pcs-gcd",	 new String[]{"gcd"});
			put("text/x-perl",	 new String[]{"pl", "pm"});
			put("text/x-python",	 new String[]{"py"});
			put("text/x-scala",	 new String[]{"scala"});
			put("text/x-setext",	 new String[]{"etx"});
			put("text/x-sh",	 new String[]{"sh"});
			put("text/x-tcl",	 new String[]{"tcl", "tk"});
			put("text/x-tex",	 new String[]{"cls", "ltx", "sty", "tex"});
			put("text/x-vcalendar",	 new String[]{"vcs"});
			put("text/x-vcard",	 new String[]{"vcf"});
			put("text/xml",	 new String[]{"AddIn", "vssettings", "vstemplate", "xml", "xsl"});
			put("video/3gpp",	 new String[]{"3gp"});
			put("video/annodex",	 new String[]{"axv"});
			put("video/avi",	 new String[]{"avi"});
			put("video/dl",	 new String[]{"dl"});
			put("video/dv",	 new String[]{"dif", "dv"});
			put("video/fli",	 new String[]{"fli"});
			put("video/gl",	 new String[]{"gl"});
			put("video/mp4",	 new String[]{"mp4"});
			put("video/mpeg",	 new String[]{"m1v", "mp2", "mp2v", "mpa", "mpe", "mpeg", "mpg", "mpv2"});
			put("video/mpg",	 new String[]{"mpeg"});
			put("video/msvideo",	 new String[]{"avi"});
			put("video/ogg",	 new String[]{"ogv"});
			put("video/quicktime",	 new String[]{"mov", "qt"});
			put("video/vnd.mpegurl",	 new String[]{"mxu"});
			put("video/vnd.vivo",	 new String[]{"viv", "vivo"});
			put("video/x-flv",	 new String[]{"flv"});
			put("video/x-la-asf",	 new String[]{"lsf", "lsx"});
			put("video/x-matroska",	 new String[]{"mpv"});
			put("video/x-mng",	 new String[]{"mng"});
			put("video/x-mpeg",	 new String[]{"mpeg"});
			put("video/x-mpeg2a",	 new String[]{"mpeg"});
			put("video/x-ms-asf",	 new String[]{"asf", "asx"});
			put("video/x-ms-asf-plugin",	 new String[]{"asx"});
			put("video/x-ms-wm",	 new String[]{"wm"});
			put("video/x-ms-wmv",	 new String[]{"wmv"});
			put("video/x-ms-wmx",	 new String[]{"wmx"});
			put("video/x-ms-wvx",	 new String[]{"wvx"});
			put("video/x-msvideo",	 new String[]{"avi"});
			put("video/x-sgi-movie",	 new String[]{"movie"});
			put("www/mime",	 new String[]{"mime"});
			put("x-conference/x-cooltalk",	 new String[]{"ice"});
			put("x-epoc/x-sisx-app",	 new String[]{"sisx"});
			put("x-world/x-vrml",	 new String[]{"vrm", "vrml", "wrl"});
		}
	};
}
