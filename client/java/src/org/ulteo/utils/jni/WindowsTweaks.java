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

package org.ulteo.utils.jni;

import com.ice.jni.registry.NoSuchValueException;
import com.ice.jni.registry.RegStringValue;
import com.ice.jni.registry.Registry;
import com.ice.jni.registry.RegistryException;
import com.ice.jni.registry.RegistryKey;
import org.ulteo.Logger;

public class WindowsTweaks {
	public synchronized static void rebuildIconCache() {
		int iconSize = 32;

		RegistryKey windowMetrics = Registry.openSubkey(Registry.HKEY_CURRENT_USER, "Control Panel\\Desktop\\WindowMetrics", RegistryKey.ACCESS_ALL);
		try {
			String shellIconSize = windowMetrics.getStringValue("Shell Icon Size");
			iconSize = Integer.parseInt(shellIconSize);
		} catch (NoSuchValueException ex) {
		} catch (RegistryException ex) {
			Logger.error("Failed to rebuild icons cache: "+ex.getMessage());
			return;
		}

		try {
			windowMetrics.setValue(new RegStringValue(windowMetrics, "Shell Icon Size", ""+(iconSize - 1)));
		} catch (RegistryException ex) {
			Logger.error("Failed to change the \"Shell Icon Size\" value: "+ex.getMessage());
		}
		WindowsTweaks.nReloadWMSettings();

		try {
			windowMetrics.setValue(new RegStringValue(windowMetrics, "Shell Icon Size", ""+iconSize));
		} catch (RegistryException ex) {
			Logger.error("Failed to change the \"Shell Icon Size\" value: "+ex.getMessage());
		}
		WindowsTweaks.nReloadWMSettings();
	}

	protected static void reloadWMSettings() {
		WindowsTweaks.nReloadWMSettings();
	}

	protected static native void nReloadWMSettings();
}
