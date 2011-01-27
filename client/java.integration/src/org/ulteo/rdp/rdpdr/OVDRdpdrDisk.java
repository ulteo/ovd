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

package org.ulteo.rdp.rdpdr;

import net.propero.rdp.rdp5.rdpdr.Disk;
import net.propero.rdp.rdp5.rdpdr.RdpdrChannel;

public class OVDRdpdrDisk extends Disk {
	private boolean internalUsage = false;

	public OVDRdpdrDisk(RdpdrChannel rdpdr_, String path, String name_) {
		super(rdpdr_, path, name_);
	}

	public OVDRdpdrDisk(RdpdrChannel rdpdr_, String path, String name_, boolean internalUsage_) {
		super(rdpdr_, path, name_);

		this.internalUsage = internalUsage_;
	}

	public void setInternalUsage(boolean internalUsage_) {
		this.internalUsage = internalUsage_;
	}

	public boolean getInternalUsage() {
		return this.internalUsage;
	}
}
