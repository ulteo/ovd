/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010, 2012
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

public enum MPPCType {
	mppc4(0x0),	// PACKET_COMPR_TYPE_8K
	mppc5(0x1),	// PACKET_COMPR_TYPE_64K
	mppc6(0x2),	// PACKET_COMPR_TYPE_RDP6
	mppc61(0x3);	// PACKET_COMPR_TYPE_RDP61
	
	private int value;

	private MPPCType(int value) {
		this.value = value;
	}
	
	public int value() {
		return this.value;
	}
}
