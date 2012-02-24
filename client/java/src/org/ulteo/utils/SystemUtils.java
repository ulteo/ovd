/*
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2012
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

public class SystemUtils {
	public static int getPID() {
		String processName = java.lang.management.ManagementFactory.getRuntimeMXBean().getName();
		if (processName.indexOf("@") == -1)
			return 0;
		
		String[] chunks = processName.split("@");
		if (chunks.length <= 0 || chunks[0].length() <= 0)
			return 0;
		
		return Integer.parseInt(chunks[0]);
	}
}
