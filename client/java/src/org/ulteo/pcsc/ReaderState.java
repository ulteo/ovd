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

public class ReaderState {

	public ReaderState(String name, int state, int event, byte[] atr) {
		this.setName(name);
		this.setState(state);
		this.setEvent(event);
		this.setAtr(atr);
	}

	public String getName() {
		return szReader;
	}

	public int getState() {
		return dwCurrentState;
	}

	public int getEvent() {
		return dwEventState;
	}

	public byte[] getAtr() {
		return rgbAtr;
	}

	public byte[] getUserData() {
		return null;
	}

	public void setName(String name) {
		szReader = name;
	}

	public void setState(int state) {
		dwCurrentState = state;
	}

	public void setEvent(int event) {
		dwEventState = event;
	}

	public void setAtr(byte[] atr) {
		rgbAtr = atr;
	}

	private String szReader;
	private int dwCurrentState;
	private int dwEventState;
	private byte[] rgbAtr;
}
