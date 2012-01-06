/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
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

package org.ulteo.ovd.disk;

import java.util.ArrayList;

import org.ulteo.Logger;

public class WNetApi {
	public static final int WNNC_NET_TERMSRV = 0x360000;
	
	protected static native String nGetProviderName(int provider);
	protected static native long nOpenEnum();
	protected static native String nGetNext(long handle, String provider);
	protected static native void nCloseEnum(long handle);
	
	
	public static ArrayList<String> getTSShare() {
		ArrayList<String> result = new ArrayList<String>();
		String shareName= null;
		
		String provider = WNetApi.nGetProviderName(WNetApi.WNNC_NET_TERMSRV);
		if (provider == null) {
			Logger.warn("No terminal services network provider");
			return result; 
		}

		long handle = WNetApi.nOpenEnum();
		if (handle == -1) {
			Logger.warn("Enable to enumerate terminal services share");
			return result;
		}
		
		while ((shareName = nGetNext(handle, provider)) != null)
			result.add(shareName);

		WNetApi.nCloseEnum(handle);
		
		return result;
	}
}
