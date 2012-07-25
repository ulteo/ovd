/*
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Yann Hodique <y.hodique@ulteo.com> 2012
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

package org.ulteo.pcsc;

import org.ulteo.pcsc.PCSC;

public class Status {

	public Status(String[] readerNames, byte[] atr, int state, int protocol) {
		this.readerNames = readerNames;
		this.atr = atr;
		this.state = mapState(state);
		this.protocol = protocol;
	}

	public String[] getReaderNames() {
		return readerNames;
	}

	public byte[] getAtr() {
		return atr;
	}

	public int getState() {
		return state;
	}

	public int getProtocol() {
		return protocol;
	}

	private int mapState(int state) {
		if ((state & PCSC.SCARD_SPECIFIC) != 0)
			state = 0x00000006;
		else if ((state & PCSC.SCARD_NEGOTIABLE) != 0)
			state = 0x00000006;
		else if ((state & PCSC.SCARD_POWERED) != 0)
			state = 0x00000004;
		else if ((state & PCSC.SCARD_SWALLOWED) != 0)
			state = 0x00000003;
		else if ((state & PCSC.SCARD_PRESENT) != 0)
			state = 0x00000002;
		else if ((state & PCSC.SCARD_ABSENT) != 0)
			state = 0x00000001;
		else
			state = 0x00000000;

		return state;
	}

	private String[] readerNames;
	private byte[] atr;
	private int state;
	private int protocol;

}
