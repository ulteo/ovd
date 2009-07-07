/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
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

package org.vnc;

import org.vnc.rfbcaching.IRfbCachingConstants;
import org.vnc.rfbcaching.RfbCacheProperties;

public class Options {
 
	public static boolean interactive = true;

	public static String host = null;
	public static int port = 5900;

	public static int width=800;
	public static int height=600;
	public static int scalingFactor=100;

	public static boolean viewOnly = false;
	public static boolean shareDesktop = false;
	public static boolean reverseMouseButtons2And3 = false;

	public static boolean offerRelogin = false;
	public static boolean showOfflineDesktop = false;

	public static int deferScreenUpdates = 20;
	public static int deferCursorUpdates = 10;
	public static int deferUpdateRequests = 50;

	public static boolean eightBitColors = false;
	public static int scaleCursor = 0;
	public static int preferredEncoding = RfbProto.EncodingTight;
	public static boolean useCopyRect = true;

	public static int compressLevel = 0;
	public static int jpegQuality = 9;
	public static boolean requestCursorUpdates = true;
	public static boolean ignoreCursorUpdates = false;

	// Rfb caching
	public static boolean cacheEnable = false;
	public static int cacheVerMajor = IRfbCachingConstants.RFB_CACHE_DEFAULT_VER_MAJOR;
	public static int cacheVerMinor = IRfbCachingConstants.RFB_CACHE_DEFAULT_VER_MINOR;
	public static int cacheSize = IRfbCachingConstants.RFB_CACHE_DEFAULT_SIZE;
	public static int cacheMaintAlgI = IRfbCachingConstants.RFB_CACHE_DEFAULT_MAINT_ALG;
	public static int cacheDataSize = IRfbCachingConstants.RFB_CACHE_DEFAULT_DATA_SIZE;


	public static RfbCacheProperties getCacheProperties() {
		if (Options.cacheMaintAlgI != IRfbCachingConstants.RFB_CACHE_DEFAULT_MAINT_ALG) {
			System.err.println("Unknown cache algorithm");
			return null;
		}

		return new RfbCacheProperties(Options.cacheMaintAlgI, 
									  Options.cacheSize, 
									  Options.cacheDataSize, 
									  Options.cacheVerMajor,
									  Options.cacheVerMinor);
	}
}
