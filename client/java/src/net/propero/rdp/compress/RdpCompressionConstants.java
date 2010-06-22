/*
 * Copyright (C) 2010 Ulteo SAS
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

package net.propero.rdp.compress;

public abstract class RdpCompressionConstants {
	public static final int TYPE_8K = 0x0;
	public static final int TYPE_64K = 0x1;
	public static final int TYPE_RDP6 = 0x2;
	public static final int TYPE_RDP61 = 0x3;

	public static final int FLAG_TYPE_8K = 0x0;
	public static final int FLAG_TYPE_64K = 0x200;
	public static final int FLAG_TYPE_RDP6 = 0x400;
	public static final int FLAG_TYPE_RDP61 = 0x600;
}
