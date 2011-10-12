/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David Lechevalier <david@ulteo.com> 2011
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

package net.propero.rdp.rdp5;

import net.propero.rdp.RdpPacket_Localised;

public class SpooledPacket {
	RdpPacket_Localised packet;
	int channel;
	
	public SpooledPacket(RdpPacket_Localised packet, int channel) {
		this.packet = packet;
		this.channel = channel;
	}
	
	public int getChannel() {
		return this.channel;
	}
	
	public RdpPacket_Localised getPacket() {
		return this.packet;
	}
}
